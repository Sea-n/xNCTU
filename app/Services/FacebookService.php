<?php

namespace App\Services;

use App\Models\Post;
use Exception;

class FacebookService extends BaseService implements PostContract
{
    public function __construct()
    {
        //
    }

    public function publish(Post $post)
    {
        // TODO
    }

    /**
     * @param Post $post
     * @throws Exception
     */
    public function comment(Post $post)
    {
        // TODO
    }
}

