<?php

namespace App\Services;

use App\Models\Post;
use App\Models\TgMsg;
use App\Models\User;
use App\Models\Vote;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\FileUpload\InputFile;

class ReviewService extends BaseService
{
    public function __construct()
    {
        //
    }

    /**
     * @param Post $post
     * @throws Exception
     */
    public function send(Post $post)
    {
        assert($post->status == 1);
        $post->update(['status' => 2]);

        /* Post UID */
        $hashtag = "#投稿{$post->uid}";

        /* Author Name */
        $hashtag_author = '#匿名_' . preg_replace('/[ ,]+/u', '_', $post->ip_from);

        if ($post->author) {
            $hashtag_author = "#{$post->author->name}";
            if (preg_match('/^\d+$/', $post->author->name))
                $hashtag_author = "#N{$post->author->name}";
        }
        $hashtag_author = preg_replace('/[ ,]+/u', '_', $hashtag_author);

        $hashtag = "$hashtag | $hashtag_author";

        /* IP Address */
        $ip_masked = ip_mask($post->ip_addr);
        if (strpos($post->ip_from, '境外') !== false)
            $ip_masked = $post->ip_addr;

        $hashtag_ip = preg_replace('/[.:*]/u', '_', $ip_masked);
        $hashtag_ip = preg_replace('/___+/', '___', $hashtag_ip);

        if (strpos($post->ip_addr, ':') !== false)
            $hashtag_ip = "#IPv6_$hashtag_ip";
        else
            $hashtag_ip = "#IPv4_$hashtag_ip";

        if (!$post->author)
            $hashtag = "$hashtag | $hashtag_ip";

        /* Post Body */
        $msg = "{$post->body}\n\n$hashtag";

        /* Send to Votes Log */
        $this->send_group($post, $msg);

        /* Send to Users */
        $USERS = User::whereNotNull('tg_name')->orderByRaw('approvals + rejects DESC')->get();
        foreach ($USERS as $user) {
            if (!canVote($post->uid, $user->stuid)['ok'])
                continue;

            try {
                $this->send_each($post, $user, $msg);
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }

        $post->update(['status' => 3]);
    }

    protected
    function send_group(Post $post, string $msg)
    {
        $keyboard = json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => '開啟審核頁面',
                        'login_url' => [
                            'url' => url("/login/tg?r=%2Freview%2F{$post->uid}"),
                        ],
                    ],
                ],
            ],
        ]);

        try {
            if ($post->media == 0)
                Telegram::sendMessage([
                    'chat_id' => env('TELEGRAM_LOG_GROUP'),
                    'text' => $msg,
                    'reply_markup' => $keyboard,
                ]);
            else
                Telegram::sendPhoto([
                    'chat_id' => env('TELEGRAM_LOG_GROUP'),
                    'photo' => new InputFile(public_path("img/{$post->uid}.jpg")),
                    'caption' => $msg,
                    'reply_markup' => $keyboard,
                ]);
        } catch (TelegramResponseException $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * @param Post $post
     * @param User $user
     * @param string $msg
     * @throws Exception
     */
    protected function send_each(Post $post, User $user, string $msg)
    {
        $keyboard = json_encode([
            'inline_keyboard' => [
                [
                    [
                        'text' => '✅ 通過',
                        'callback_data' => "approve_{$post->uid}",
                    ],
                    [
                        'text' => '❌ 駁回',
                        'callback_data' => "reject_{$post->uid}",
                    ],
                ],
                [
                    [
                        'text' => '開啟審核頁面',
                        'login_url' => [
                            'url' => url("/login/tg?r=%2Freview%2F{$post->uid}"),
                        ],
                    ],
                ],
            ],
        ]);

        try {
            if ($post->media == 0)
                $result = Telegram::sendMessage([
                    'chat_id' => $user->tg_id,
                    'text' => $msg,
                    'reply_markup' => $keyboard,
                ]);
            else
                $result = Telegram::sendPhoto([
                    'chat_id' => $user->tg_id,
                    'photo' => new InputFile(public_path("img/{$post->uid}.jpg")),
                    'caption' => $msg,
                    'reply_markup' => $keyboard,
                ]);
        } catch (TelegramResponseException $e) {
            if ($e->getMessage() == 'Forbidden: bot was blocked by the user')
                $user->update(['tg_name' => null]);
            if ($e->getMessage() == 'Forbidden: user is deactivated')
                $user->update(['tg_name' => null]);
            if ($e->getMessage() == 'Bad Request: chat not found')
                $user->update(['tg_name' => null]);
            return;
        }

        TgMsg::create([
            'uid' => $post->uid,
            'chat_id' => $user->tg_id,
            'msg_id' => $result->message_id
        ]);
    }

    /**
     * After inserted vote to database, call this function to
     * remove inline keyboard and send notification to log group.
     *
     * @param Post $post
     * @param User $user
     */
    public function voted(Post $post, User $user)
    {
        $vote = Vote::where([
            ['uid', '=', $post->uid],
            ['stuid', '=', $user->stuid],
        ])->firstOrFail();

        /* Remove vote keyboard in Telegram */
        $msg = TgMsg::where([
            ['uid', '=', $post->uid],
            ['chat_id', '=', $user->tg_id ?? 42],
        ])->first();

        if ($msg) {
            try {
                Telegram::editMessageReplyMarkup([
                    'chat_id' => $user->tg_id,
                    'message_id' => $msg->msg_id,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '開啟審核頁面',
                                    'login_url' => [
                                        'url' => url("/login/tg?r=%2Freview%2F{$post->uid}"),
                                    ]
                                ]
                            ]
                        ]
                    ])
                ]);
                TgMsg::where([
                    ['uid', '=', $post->uid],
                    ['chat_id', '=', $user->tg_id],
                ])->delete();
            } catch (Exception $e) {
                Log::error('Review service, ' . $e->getMessage());
            }
        }

        /* Send vote log to group */
        $hashtag = "#投稿{$post->uid}";
        $body = preg_replace('/\s+/', '', $post->body);
        $body = mb_substr($body, 0, 10) . '..';

        $dep = $user->dep();
        $name = $user->name;
        if (is_numeric($name))
            $name = "N$name";
        $name = preg_replace('/[ -\/:-@[-`{-~]/iu', '_', $name);

        $type = ($vote->vote == 1 ? '✅' : '❌');
        $reason = $vote->reason;

        $msg = "$hashtag $body\n" .
            "$dep #$name\n\n" .
            "$type $reason";

        try {
            Telegram::sendMessage([
                'chat_id' => env('TELEGRAM_LOG_GROUP'),
                'text' => $msg,
                'disable_web_page_preview' => true,
            ]);
        } catch (TelegramResponseException $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * @param Post $post
     * @throws Exception
     */
    public function delete(Post $post)
    {
        $msgs = TgMsg::where('uid', '=', $post->uid)->get();

        foreach ($msgs as $msg) {
            try {
                Telegram::deleteMessage([
                    'chat_id' => $msg->chat_id,
                    'message_id' => $msg->msg_id,
                ]);
            } catch (TelegramResponseException $e) {
                Log::error($e->getMessage());
            }
        }

        TgMsg::where('uid', '=', $post->uid)->delete();
    }
}

