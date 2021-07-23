<?php

namespace App\Services;

use App\Models\Post;
use Log;

class DiscordService extends BaseService implements PostContract
{
    protected $reactions = [
        'thumbsup' => '👍',
        'heart' => '❤️',
        'poop' => '💩',
        'rofl' => '🤣',
        'rage' => '😡',
    ];

    public function __construct()
    {
        //
    }

    public function publish(Post $post)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://discord.com/api/v9/channels/' . env('DISCORD_CHANNEL_ID') . '/messages',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bot ' . env('DISCORD_TOKEN')
            ],
            CURLOPT_POSTFIELDS => [
                'payload_json' => json_encode(['embeds' => [
                    $this->genMsg($post),
                ]]),
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $result = curl_exec($curl);
        $result = json_decode($result);
        curl_close($curl);

        if ($result && isset($result->id))
            $post->update(['discord_id' => $result->id]);
        else
            Log::error('Discord error: ' . json_encode($result));
    }

    public function comment(Post $post)
    {
        $curl = curl_init();

        foreach ($this->reactions as $name => $reaction) {
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://discord.com/api/v9/channels/' . env('DISCORD_CHANNEL_ID') .
                "/messages/{$post->discord_id}/reactions/" . urlencode($reaction) . '/@me',
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bot ' . env('DISCORD_TOKEN')
                ],
                CURLOPT_POSTFIELDS => '',
                CURLOPT_RETURNTRANSFER => true,
            ]);
            curl_exec($curl);
        }

        curl_close($curl);
    }

    private function genMsg(Post $post)
    {
        $msg = $post->body;  // XXX: should be escaped

        $msg .= "\n\n[Telegram]({$post->getUrl('telegram')})";
        if ($post->twitter_id > 10)
            $msg .= " · [Twitter]({$post->getUrl('twitter')})";
        if ($post->plurk_id > 10)
            $msg .= " · [Plurk]({$post->getUrl('plurk')})";
        if ($post->instagram_id != '')
            $msg .= " · [Instagram]({$post->getUrl('instagram')})";
        if ($post->facebook_id > 10)
            $msg .= " · [Facebook]({$post->getUrl('facebook')})";

        $embed = [
            'type' => 'article',
            'title' => '#' . env('HASHTAG') . $post->id,
            'url' => $post->getUrl('website'),
            'color' => 0x77B55A,
            'description' => $msg,
        ];

        if ($post->media != 0)
            $embed['image'] = [
                'url' => $post->getUrl('image'),
            ];

        return $embed;
    }
}
