<?php

namespace App\Services;

use App\Models\Post;
use Exception;
use File;
use Twitter;

class TwitterService extends BaseService implements PostContract
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
        $msg = '#' . env('HASHTAG') . "{$post->id}\n\n{$post->body}";
        if (mb_strlen($msg) > 120)
            $msg = mb_substr($msg, 0, 120) . '...';
        $msg .= "\n\n✅ {$post->getUrl('website')} .";

        $query = ['status' => $msg];

        if ($post->media == 1) {
            $uploaded_media = Twitter::uploadMedia(['media' => File::get(public_path("img/{$post->uid}.jpg"))]);
            $query['media_ids'] = $uploaded_media->media_id_string;
        }

        try {
            $tweet = Twitter::postTweet($query);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'link has been identified by Twitter or our partners as being potentially harmful') !== false) {
                $post->update(['twitter_id' => 1]);
                return;
            }
            throw $e;
        }

        $post->update(['twitter_id' => $tweet->id]);
    }

    /**
     * @param Post $post
     * @throws Exception
     */
    public function comment(Post $post)
    {
        throw new Exception('Not implemented.');
    }
}

