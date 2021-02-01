<?php

namespace App\Services;

use App\Models\Post;
use Exception;
use Telegram;
use Telegram\Bot\FileUpload\InputFile;

class InstagramService extends BaseService implements PostContract
{
    public function __construct()
    {
        //
    }

    public function publish(Post $post)
    {
        exec('node ' . __DIR__ . "/InstagramService.js {$post->id}");
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

