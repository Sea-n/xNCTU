<?php

namespace App\Console\Commands;

use Telegram;
use Illuminate\Console\Command;

class TelegramCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tg:cmd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up Telegram command';


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
        $pm = [
            [
                'command' => 'name',
                'description' => '更改網站暱稱',
            ],
            [
                'command' => 'unlink',
                'description' => '解除 Telegram 綁定',
            ],
            [
                'command' => 'help',
                'description' => '顯示幫助訊息',
            ],
        ];
        $group = [
            [
                'command' => 'update',
                'description' => '編輯投稿資訊',
            ],
            [
                'command' => 'delete',
                'description' => '刪除投稿',
            ],
            [
                'command' => 'migrate',
                'description' => '轉移帳號',
            ],
            [
                'command' => 'adduser',
                'description' => '新增使用者',
            ],
            [
                'command' => 'update',
                'description' => '編輯使用者',
            ],
        ];

        $r = Telegram::post('setMyCommands', [
            'commands' => json_encode($pm),
        ]);

        Telegram::post('setMyCommands', [
            'commands' => json_encode($group),
            'scope' => json_encode([
                'type' => 'all_group_chats',
            ]),
        ]);
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
