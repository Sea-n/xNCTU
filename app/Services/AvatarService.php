<?php

namespace App\Services;

use App\Models\User;

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
        if (!$user->tg_photo) return;

        $dir = storage_path("app/avatar/tg");
        if (!file_exists($dir))
            mkdir($dir);
        $x320 = $dir . "/{$user->tg_id}-x320.jpg";
        $x64 = $dir . "/{$user->tg_id}-x64.jpg";

        copy($user->tg_photo, $x320);
        exec("ffmpeg -y -i {$x320} -q:v 1 -vf scale=64x64 {$x64}");
    }
}

