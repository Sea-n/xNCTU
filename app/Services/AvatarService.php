<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Exception;
use Telegram;
use Telegram\Bot\FileUpload\InputFile;

class AvatarService extends BaseService
{
    public function __construct()
    {
        //
    }

    /**
     * @param User $user
     */
    public function update(User $user)
    {
        // Todo: Download avatar to storage
    }
}

