<?php

namespace App\Services;

use App\Models\Post;
use App\Models\TgMsg;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Telegram;
use Telegram\Bot\Exceptions\TelegramResponseException;

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
        $USERS = User::whereNotNull('tg_name')->orderByRaw('approvals + rejects')->get();
        foreach ($USERS as $user) {
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
                            'url' => env('APP_URL') . "/login-tg?r=%2Freview%2F{$post->uid}",
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
                    'photo' => env('APP_URL') . "/img/{$post->uid}.jpg",
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
                            'url' => env('APP_URL') . "/login-tg?r=%2Freview%2F{$post->uid}",
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
                    'photo' => env('APP_URL') . "/img/{$post->uid}.jpg",
                    'caption' => $msg,
                    'reply_markup' => $keyboard,
                ]);
        } catch (TelegramResponseException $e) {
            if ($e->getMessage() == 'Forbidden: bot was blocked by the user')
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
     * @param Post $post
     * @throws Exception
     */
    public
    function delete(Post $post)
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

