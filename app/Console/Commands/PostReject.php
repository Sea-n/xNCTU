<?php

namespace App\Console\Commands;

use App\Jobs\ReviewDelete;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Telegram;
use Telegram\Bot\FileUpload\InputFile;

class PostReject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:reject';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find rejected posts';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $submissions = Post::where('status', '=', 3)->get();

        foreach ($submissions as $post) {
            $submitted = strtotime($post->submitted_at);
            $dt = floor(time() / 60) - floor($submitted / 60);
            if ($submitted % 5 == 0)
                $dt += 1;

            if (strpos($post->ip_from, '境外') === false) {
                /* Before 1 hour */
                if ($dt < 1 * 60 && $post->rejects < 5)
                    continue;

                /* 1 hour - 12 hour */
                if ($dt < 12 * 60 && $post->rejects < 3)
                    continue;
            } else {
                /* Before 1 hour */
                if ($dt < 1 * 60 && $post->rejects < 2)
                    continue;
            }

            $post->update([
                'status' => -2,
                'deleted_at' => Carbon::now(),
                'delete_note' => '已駁回',
            ]);

            /* Remove un-voted messages in Telegram */
            ReviewDelete::dispatch($post);
        }

        /* Unconfirmed submissions */
        $submissions = Post::where('status', '=', 0)->get();

        foreach ($submissions as $post) {
            $created = strtotime($post->created_at);
            $dt = floor(time() / 60) - floor($created / 60);

            /* Only send notify when 10 min */
            if ($dt != 10)
                continue;

            $msg = "<未確認投稿>\n\n";
            $msg .= $post->body;

            $keyboard = json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => '✅ 確認投稿',
                            'callback_data' => "confirm_{$post->uid}"
                        ],
                        [
                            'text' => '❌ 刪除投稿',
                            'callback_data' => "delete_{$post->uid}"
                        ]
                    ],
                    [
                        [
                            'text' => '開啟審核頁面',
                            'login_url' => [
                                'url' => url("/login/tg?r=%2Freview%2F{$post->uid}")
                            ]
                        ]
                    ]
                ]
            ]);

            if ($post->media == 0)
                Telegram::sendMessage([
                    'chat_id' => env('TELEGRAM_LOG_GROUP'),
                    'text' => $msg,
                    'reply_markup' => $keyboard,
                ]);
            else
                Telegram::sendPhoto([
                    'chat_id' => env('TELEGRAM_LOG_GROUP'),
                    'photo' => new InputFile(public_path("img/{$post->uid}.jpg")),
                    'caption' => $msg,
                    'reply_markup' => $keyboard,
                ]);
        }
    }

    /**
     *
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return base_path('stubs/posts.stub');
    }
}
