<?php

namespace App\Services;

use App\Models\Post;
use Exception;
use Log;
use Telegram;
use Telegram\Bot\FileUpload\InputFile;

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
        /* Check latest line */
        $lines = explode("\n", $post->body);
        $end = end($lines);
        $is_url = filter_var($end, FILTER_VALIDATE_URL);
        if ($post->media == 0 && $is_url)
            $msg = "<a href='$end'>#</a><a href='{$post->getUrl('website')}'>" . env('HASHTAG') . "{$post->id}</a>";
        else
            $msg = "<a href='{$post->getUrl('website')}'>#" . env('HASHTAG') . "{$post->id}</a>";

        $msg .= "\n\n" . enHTML($post->body);


        /* Send to channel */
        if ($post->media == 0)
            $result = Telegram::sendMessage([
                'chat_id' => '@' . env('TELEGRAM_USERNAME'),
                'text' => $msg,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => !$is_url,
            ]);
        else
            $result = Telegram::sendPhoto([
                'chat_id' => '@' . env('TELEGRAM_USERNAME'),
                'photo' => new InputFile(public_path("img/{$post->uid}.jpg")),
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

        if (!count($buttons))
            return;

        try {
            Telegram::editMessageReplyMarkup([
                'chat_id' => '@' . env('TELEGRAM_USERNAME'),
                'message_id' => $post->telegram_id,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        $buttons,
                    ],
                ]),
            ]);
        } catch (Exception $e) {
            Log::error($e->getCode() . ': ' . $e->getMessage());
        }
    }
}

