<?php

namespace App\Services;

use App\Models\Post;

interface PostContract
{
    public function publish(Post $post);
    public function comment(Post $post);
}
