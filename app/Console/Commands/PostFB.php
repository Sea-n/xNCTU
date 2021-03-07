<?php

namespace App\Console\Commands;

use App\Jobs\PublishFacebook;
use App\Jobs\UpdateFacebook;
use App\Jobs\UpdateTelegram;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PostFB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:fb {begin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send posts to Facebook after unbanned';


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
        $post = Post::where([
            ['status', '=', 5],
            ['id', '>', $this->argument('begin')],
            ['facebooK_id', '=', 0],
        ])->orderBy('id')->first();

        if (!isset($post))
            return;

        PublishFacebook::dispatch($post);

        $post->refresh();

        /* Ignore errors */
        if ($post->facebook_id == 0) {
            $post->update(['facebook_id', 1]);
            return;
        }

        UpdateFacebook::dispatch($post);
        UpdateTelegram::dispatch($post);
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
