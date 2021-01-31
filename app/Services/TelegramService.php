<?php

namespace App\Services;

use App\Models\Post;
use Exception;
use Telegram;

class TelegramService extends BaseService implements PostContract
{
    public function __construct()
    {
        //
    }

    /**
     * @param Post $post
     * @throws Exception
     */
    public function publish(Post $post)
    {
        $link = env('APP_URL') . "/post/{$post->id}";
        /* Check latest line */
        $lines = explode("\n", $post['body']);
        $end = end($lines);
        $is_url = filter_var($end, FILTER_VALIDATE_URL);
        if (!$post['has_img'] && $is_url)
            $msg = "<a href='$end'>#</a><a href='{$link}'>靠交{$post['id']}</a>";
        else
            $msg = "<a href='{$link}'>#靠交{$post['id']}</a>";

        $msg .= "\n\n" . enHTML($post['body']);


        /* Send to @xNCTU */
        if (!$post['has_img'])
            $result = Telegram::sendMessage([
                'chat_id' => '@' . env('TELEGRAM_USERNAME'),
                'text' => $msg,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => !$is_url,
            ]);
        else
            $result = Telegram::sendPhoto([
                'chat_id' => '@' . env('TELEGRAM_USERNAME'),
                'photo' => env('APP_URL') . "/img/{$post['uid']}.jpg",
                'caption' => $msg,
                'parse_mode' => 'HTML',
            ]);

        $post->update(['telegram_id' => $result->message_id]);
    }

    /**
     * @param Post $post
     * @throws Exception
     */
    public function comment(Post $post)
    {
        if ($post->telegram_id <= 0)
            throw new Exception('Telegram post ID error');

        $platforms = [
            'Facebook',
            'Plurk',
            'Twitter',
            'Instagram',
        ];

        $buttons = [];
        foreach ($platforms as $platform)
            if ($post->getUrl($platform))
                $buttons[] = [
                    'text' => $platform,
                    'url' => $post->getUrl($platform),
                ];

        Telegram::editMessageReplyMarkup([
            'chat_id' => '@' . env('TELEGRAM_USERNAME'),
            'message_id' => $post->telegram_id,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    $buttons,
                ],
            ]),
        ]);
    }
}
