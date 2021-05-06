<?php

namespace App\Http\Controllers;

use App\Jobs\ReviewDelete;
use App\Jobs\ReviewSend;
use App\Models\Post;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\JsonResponse;
use Log;
use Response;
use Schema;
use Telegram;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Objects\Update;
use Throwable;

class TelegramController extends Controller
{
    /**
     * @param string $token
     * @return JsonResponse
     * @throws Throwable
     */
    public function webhook(string $token)
    {
        if ($token != sha1(env('TELEGRAM_BOT_TOKEN')))
            return Response::json(['msg' => 'You are not from Telegram'], 401);

        /** @var Update $update */
        $update = Telegram::getWebhookUpdates();

        response()->json(['ok' => true])->send();

        if ($update->message)
            $this->message($update->message);

        if ($update->callbackQuery)
            $this->callback($update->callbackQuery);

        return Response::json(['ok' => true]);
    }

    /**
     * @param Message $message
     * @throws Throwable
     */
    protected function message(Message $message)
    {
        $text = $message->text ?? '';

        if ($message->chat->id < 0) {
            if ($message->chat->id != env('TELEGRAM_LOG_GROUP'))
                Telegram::sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => '目前尚未支援群組功能',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => '📢 ' . env('APP_CHINESE_NAME') . ' 頻道',
                                    'url' => 'https://t.me/' . env('TELEGRAM_USERNAME'),
                                ]
                            ]
                        ]
                    ])
                ]);

            if (substr($text, 0, 1) != '/')
                return;
        }

        $user = User::where('tg_id', '=', $message->from->id)->first();
        if (!$user) {
            $msg = "您尚未綁定任何交大身份\n\n";
            $msg .= "請先至網站登入後，再點擊下方按鈕綁定帳號";
            Telegram::sendMessage([
                'chat_id' => $message->chat->id,
                'text' => $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => "綁定" . env('APP_CHINESE_NAME') . ' 帳號',
                                'login_url' => [
                                    'url' => url('/login/tg'),
                                ]
                            ]
                        ]
                    ]
                ])
            ]);
            return;
        }

        if (substr($text, 0, 1) == '/') {
            $text = substr($text, 1);
            if (strpos($text, ' '))
                [$cmd, $arg] = explode(' ', $text, 2);
            else
                [$cmd, $arg] = [$text, ''];

            if (strpos($cmd, '@'))
                $cmd = explode('@', $cmd, 2)[0];

            switch ($cmd) {
                case 'start':
                    $msg = "歡迎使用" . env('APP_CHINESE_NAME') . " 機器人\n\n";
                    $msg .= "使用 /help 顯示指令清單";

                    Telegram::sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => $msg,
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => "登入" . env('APP_CHINESE_NAME'),
                                        'login_url' => [
                                            'url' => url('/login/tg'),
                                        ]
                                    ]
                                ]
                            ]
                        ])
                    ]);
                    break;

                case 'help':
                    $msg = "目前支援的指令：\n\n";
                    $msg .= "/name 更改網站上的暱稱\n";
                    $msg .= "/unlink 解除 Telegram 綁定\n";
                    $msg .= "/delete 刪除貼文\n";
                    $msg .= "/help 顯示此訊息\n";
                    $msg .= "\nℹ️ 由 @SeanChannel 提供";

                    Telegram::sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => $msg
                    ]);
                    break;

                case 'name':
                    $arg = enHTML(trim($arg));
                    if (mb_strlen($arg) < 1 || mb_strlen($arg) > 10) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "使用方式：`/name 新暱稱`\n\n字數上限：10 個字",
                            'parse_mode' => 'Markdown'
                        ]);
                        break;
                    }

                    $user->update(['name' => $arg]);

                    Telegram::sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => '修改成功！',
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => '開啟網站',
                                        'login_url' => [
                                            'url' => url('/login/tg'),
                                        ]
                                    ]
                                ]
                            ]
                        ])
                    ]);
                    break;

                case 'unlink':
                    $user->update([
                        'tg_id' => null,
                        'tg_name' => null,
                        'tg_username' => null,
                        'tg_photo' => null,
                    ]);

                    Telegram::sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => "已取消連結，請點擊下方按鈕連結新的 NCTU OAuth 帳號",
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => "綁定" . env('APP_CHINESE_NAME') . ' 網站',
                                        'login_url' => [
                                            'url' => url('/login/tg'),
                                        ]
                                    ]
                                ]
                            ]
                        ])
                    ]);
                    break;

                case 'update':
                    if ($message->chat->id != env('TELEGRAM_LOG_GROUP')) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "此功能僅限核心維護群組使用",
                        ]);
                        return;
                    }

                    [$column, $new] = explode(' ', $arg, 2);

                    if ($column == 'name') {
                        [$stuid, $name] = explode(' ', $new, 2);
                        User::find($stuid)->update(['name' => $name]);

                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "Done."
                        ]);
                        break;
                    }

                    if (!preg_match('/^#投稿(\w{4})/um', $message->replyToMessage->text ?? $message->replyToMessage->caption ?? '', $matches)) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => 'Please reply to submission message.'
                        ]);
                        return;
                    }
                    $uid = $matches[1];
                    $post = Post::find($uid);

                    switch ($column) {
                        case 'body':
                            if (!$post->orig) $post->update(['orig' => $post->body]);
                            $post->update(['body' => $new]);
                            break;

                        case 'status':
                            $post->update(['status' => $new]);
                            break;

                        default:
                            Telegram::sendMessage([
                                'chat_id' => $message->chat->id,
                                'text' => "Column '$column' unsupported."
                            ]);
                            return;
                    }

                    Telegram::sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => "Done."
                    ]);

                    break;

                case 'delete':
                    if ($message->chat->id != env('TELEGRAM_LOG_GROUP')) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "此功能僅限核心維護群組使用\n\n" .
                                "如果您有興趣為" . env('APP_CHINESE_NAME') . ' 盡一份心力的話，歡迎聯絡開發團隊 🙃',
                        ]);
                        return;
                    }

                    [$uid, $status, $reason] = explode(' ', $arg, 3);

                    if ($status >= 0 || mb_strlen($reason) == 0) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "Usage: /delete <uid> <status> <reason>\n\n" .
                                "-2 rejected\n" .
                                "-3 deleted by author (hidden)\n" .
                                "-4 deleted by admin\n" .
                                "-11 deleted and hidden by admin"
                        ]);
                        return;
                    }

                    $post = Post::find($uid);
                    ReviewDelete::dispatch($post);
                    $post->update([
                        'status' => $status,
                        'delete_note' => $reason,
                        'deleted_at' => Carbon::now(),
                    ]);

                    Telegram::sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => "Done."
                    ]);
                    break;

                case 'adduser':
                    if ($message->chat->id != env('TELEGRAM_LOG_GROUP')) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "此功能僅限核心維護群組使用"
                        ]);
                        return;
                    }

                    $args = explode(' ', $arg);
                    if (count($args) != 2) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "使用方式：/adduser <Student ID> <TG ID>",
                        ]);
                        return;
                    }

                    $stuid = $args[0];
                    $tg_id = $args[1];

                    $count = User::where('stuid', '=', $stuid)->orWhere('tg_id', '=', $tg_id)->count();
                    if ($count) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "Failed.\n\nUser exists.",
                        ]);
                        return;
                    }

                    User::create([
                        'name' => $stuid,
                        'stuid' => $stuid,
                        'tg_id' => $tg_id,
                    ]);

                    $result = Telegram::sendMessage([
                        'chat_id' => $tg_id,
                        'text' => "🎉 驗證成功！\n\n請點擊以下按鈕登入" . env('APP_CHINESE_NAME') . ' 網站',
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => '登入' . env('APP_CHINESE_NAME'),
                                        'login_url' => [
                                            'url' => url('/login/tg?r=%2Freview'),
                                        ]
                                    ]
                                ]
                            ]
                        ])
                    ]);

                    if ($result->ok)
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "Done.\n"
                        ]);
                    else
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "Failed.\n\n" . json_encode($result, JSON_PRETTY_PRINT)
                        ]);
                    break;

                case 'migrate':
                    if ($message->chat->id != env('TELEGRAM_LOG_GROUP')) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "此功能僅限核心維護群組使用"
                        ]);
                        return;
                    }

                    if ($arg == '') {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "使用方式：/migrate <old stuid> [new stuid]",
                        ]);
                        return;
                    }
                    $args = explode(' ', $arg);

                    $stuid_old = $args[0];
                    $stuid_new = $args[1] ?? '';

                    $user_old = User::find($stuid_old);
                    $user_new = User::find($stuid_new);

                    if ($stuid_new == '') {
                        $posts_count = Post::where('author_id', '=', $stuid_old)->count();
                        $votes_count = Vote::where('stuid', '=', $stuid_old)->count();

                        $text = "舊使用者資訊：\n";
                        $text .= "暱稱：{$user_old['name']}\n";
                        if ($posts_count) $text .= "投稿數：" . $posts_count . " 篇\n";
                        if ($votes_count) $text .= "投票數：" . $votes_count . " 篇\n";

                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => $text
                        ]);
                        break;
                    }

                    if (isset($user_new)) {
                        Telegram::sendMessage([
                            'chat_id' => $message->chat->id,
                            'text' => "新帳號 {$user_new['name']} 已註冊"
                        ]);
                        break;
                    }

                    Schema::disableForeignKeyConstraints();
                    Post::where('author_id', '=', $stuid_old)->update(['author_id' => $stuid_new]);
                    Vote::where('stuid', '=', $stuid_old)->update(['stuid' => $stuid_new]);
                    User::where('stuid', '=', $stuid_old)->update(['stuid' => $stuid_new]);
                    Schema::enableForeignKeyConstraints();

                    Telegram::sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => 'Done.'
                    ]);

                    break;

                default:
                    Telegram::sendMessage([
                        'chat_id' => $message->chat->id,
                        'text' => "未知的指令\n\n如需查看使用說明請使用 /help 功能"
                    ]);
                    break;
            }

            return;
        }

        if (preg_match('#^\[(approve|reject)/([a-zA-Z0-9]+)]#', $message->replyToMessage->text ?? '', $matches)) {
            $vote = $matches[1] == 'approve' ? 1 : -1;
            $uid = $matches[2];
            $reason = $text;

            $type = $vote == 1 ? '✅ 通過' : '❌ 駁回';

            if (mb_strlen($reason) < 1 || mb_strlen($reason) > 100) {
                Telegram::sendMessage([
                    'chat_id' => $message->chat->id,
                    'text' => '請輸入 1 - 100 字投票附註'
                ]);

                return;
            }

            try {
                $result = voteSubmission($uid, $user->stuid, $vote, $reason);
                if (!$result['ok'])
                    $msg = $result['msg'];
                else {
                    $msg = "您成功為 #投稿$uid 投下了 $type\n\n";
                    $msg .= "目前通過 {$result['approvals']} 票、駁回 {$result['rejects']} 票";

                    system("php " . __DIR__ . "/../jobs.php vote $uid {$user->stuid} > /dev/null &");
                }
            } catch (Exception $e) {
                $msg = 'Error ' . $e->getCode() . ': ' . $e->getMessage() . "\n";
            }

            Telegram::sendMessage([
                'chat_id' => $message->chat->id,
                'text' => $msg,
            ]);

            try {
                Telegram::deleteMessage([
                    'chat_id' => $message->chat->id,
                    'message_id' => $message->replyToMessage->messageId,
                ]);
            } catch (Exception $e) {
                Log::error('Error ' . $e->getCode() . ': ' . $e->getMessage() . "\n" . "chat_id={$message->chat->id}, message_id={$message->replyToMessage->messageId}");
            }

            return;
        }
    }

    /**
     * @param CallbackQuery $callback
     * @throws TelegramResponseException
     */
    protected function callback(CallbackQuery $callback)
    {
        if (!$callback->data) {
            Telegram::sendMessage([
                'chat_id' => $callback->from->id,
                'text' => 'Error: No callback data.'
            ]);
            return;
        }

        $user = User::where('tg_id', '=', $callback->from->id)->first();
        if (!$user) {
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callback->id,
                'show_alert' => true,
                'text' => "您尚未綁定 NCTU 帳號，請至" . env('APP_CHINESE_NAME') . ' 網站登入',
            ]);
            return;
        }

        $arg = $callback->data;
        $args = explode('_', $arg);
        switch ($args[0]) {
            case 'approve':
            case 'reject':
                $type = $args[0];
                $uid = $args[1];

                $check = canVote($uid, $user->stuid);
                if (!$check['ok']) {
                    Telegram::answerCallbackQuery([
                        'callback_query_id' => $callback->id,
                        'text' => $check['msg'],
                        'show_alert' => true
                    ]);

                    Telegram::editMessageReplyMarkup([
                        'chat_id' => $callback->message->chat->id,
                        'message_id' => $callback->message->messageId,
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    [
                                        'text' => '開啟審核頁面',
                                        'login_url' => [
                                            'url' => url("/login/tg?r=%2Freview%2F$uid")
                                        ]
                                    ]
                                ]
                            ]
                        ])
                    ]);

                    break;
                }

                Telegram::sendMessage([
                    'chat_id' => $callback->message->chat->id,
                    'reply_to_message_id' => $callback->message->messageId,
                    'text' => "[$type/$uid] 請輸入 1 - 100 字理由\n\n" .
                        "將會顯示於貼文頁面中，所有已登入的交大人都能看到您的具名投票",
                    'reply_markup' => json_encode([
                        'force_reply' => true,
                    ])
                ]);

                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callback->id
                ]);

                break;

            case 'confirm':
            case 'delete':
                /* Only sent to admin group */
                Telegram::editMessageReplyMarkup([
                    'chat_id' => $callback->message->chat->id,
                    'message_id' => $callback->message->messageId,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [],
                    ]),
                ]);

                $uid = $args[1];
                $post = Post::find($uid);

                if ($post->status != 0) {
                    Telegram::answerCallbackQuery([
                        'callback_query_id' => $callback->id,
                        'text' => "Status {$post->status} invalid.",
                        'show_alert' => true,
                    ]);
                    return;
                }

                if ($args[0] == 'confirm')
                    $post->update([
                        'status' => 1,
                        'submitted_at' => Carbon::now(),
                    ]);
                else
                    $post->update([
                        'status' => -13,
                        'delete_note' => '逾期未確認',
                        'deleted_at' => Carbon::now(),
                    ]);

                Telegram::answerCallbackQuery([
                    'callback_query_id' => $callback->id,
                    'text' => 'Done.',
                    'show_alert' => true,
                ]);

                if ($args[0] == 'confirm')
                    ReviewSend::dispatch($post);

                break;

            default:
                Telegram::sendMessage([
                    'chat_id' => $callback->from->id,
                    'text' => "Unknown callback data: {$arg}"
                ]);
                break;
        }
    }
}
