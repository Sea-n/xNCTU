<?php

namespace App\Console\Commands;

use App\Jobs\PublishFacebook;
use App\Jobs\PublishInstagram;
use App\Jobs\PublishPlurk;
use App\Jobs\PublishTelegram;
use App\Jobs\PublishTwitter;
use App\Jobs\ReviewDelete;
use App\Jobs\UpdateFacebook;
use App\Jobs\UpdatePlurk;
use App\Jobs\UpdateTelegram;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PostSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:send {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send eligible post to social media';


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
        /* Use command line argument to update Telegram inline keyboard */
        if ($this->argument('id')) {
            $post = Post::where('id', '=', $this->argument('id'))->firstOrFail();

            UpdateTelegram::dispatch($post);
            return;
        }


        /* Check unfinished post */
        $post = Post::where('status', '=', 4)->first();

        /* Get all pending submissions, oldest first */
        if (!isset($post)) {
            $submissions = Post::where('status', '=', 3)->orderBy('submitted_at')->get();

            foreach ($submissions as $item) {
                if ($this->checkEligible($item)) {
                    $post = $item;
                    $id = Post::orderBy('id', 'desc')->first()->id ?? 0;
                    $post->update([
                        'id' => $id + 1,
                        'status' => 4,
                        'posted_at' => Carbon::now(),
                    ]);
                    break;
                }
            }
        }

        if (!isset($post))
            return;

        /* Send post to each platforms */
        if (env('TELEGRAM_ENABLE', false) && $post->telegram_id == 0)
            PublishTelegram::dispatch($post);

        if (env('TWITTER_ENABLE', false) && $post->twitter_id == 0)
            PublishTwitter::dispatch($post);

        if (env('INSTAGRAM_ENABLE', false) && $post->instagram_id == '')
            PublishInstagram::dispatch($post);

        if (env('PLURK_ENABLE', false) && $post->plurk_id == 0)
            PublishPlurk::dispatch($post);

        if (env('FACEBOOK_ENABLE', false) && $post->facebook_id == 0)
            PublishFacebook::dispatch($post);


        /* Refresh to obtain post id on every platforms */
        $post1 = $post;
        $post2 = Post::find($post->uid);
        unset($post);

        /* Comment on some platforms */
        if (env('FACEBOOK_ENABLE', false) && $post1->facebook_id == 0 && $post2->facebook_id > 10)
            UpdateFacebook::dispatch($post2);

        if (env('PLURK_ENABLE', false) && $post1->plurk_id == 0 && $post2->plurk_id > 10)
            UpdatePlurk::dispatch($post2);

        if (env('TELEGRAM_ENABLE', false) && $post2->telegram_id > 10)
            UpdateTelegram::dispatch($post2);


        /* Remove un-voted messages in Telegram */
        ReviewDelete::dispatch($post2);

        $dt = floor(time() / 60) - floor(strtotime($post2->posted_at) / 60);
        if ($dt > 18) $post2->update(['status' => 5]);

        /* return if any enabled platform did not posted successfully */
        if (env('TELEGRAM_ENABLE', false) && $post2->telegram_id == 0) return;
        if (env('TWITTER_ENABLE', false) && $post2->twitter_id == 0) return;
        if (env('PLURK_ENABLE', false) && $post2->plurk_id == 0) return;
        if (env('FACEBOOK_ENABLE', false) && $post2->facebook_id == 0) return;
        // Intentionally not check Instagram post

        $post2->update(['status' => 5]);
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


    private function checkEligible(Post $post): bool
    {
        /* Prevent publish demo post */
        if ($post->id || $post->status != 3)
            return false;

        $dt = floor(time() / 60) - floor(strtotime($post->submitted_at) / 60);
        $vote = $post->approvals - $post->rejects;
        $vote2 = $post->approvals - $post->rejects * 2;

        /* Rule for Logged-in users */
        if ($post->author_id) {
            /* No reject: 3 votes */
            if ($dt < 10)
                return ($vote2 >= 3);

            /* More than 10 min */
            return ($vote >= 0);
        }

        /* Rule for NCTU IP address */
        if (($post->ip_from == '交大'
            || $post->ip_from == '陽明'
            || $post->ip_from == '陽交大')
            && $post->ip_addr != ip_mask($post->ip_addr)) {

            /* No reject: 5 votes */
            if ($dt < 10)
                return ($vote2 >= 5);

            /* 10 min - 1 hour */
            if ($dt < 60)
                return ($vote >= 3);

            /* More than 1 hour, during night */
            if (strtotime("03:00") <= time() && time() <= strtotime("09:00"))
                return ($vote >= 3);

            if (strtotime("02:30") <= time() && time() <= strtotime("09:30"))
                return ($vote >= 2);

            if (strtotime("02:00") <= time() && time() <= strtotime("10:00"))
                return ($vote >= 1);

            /* More than 1 hour, daytime */
            return ($vote >= 0);
        }

        /* Rule for Taiwan IP address */
        if (strpos($post->ip_from, '境外') === false) {
            /* No reject: 7 votes */
            if ($dt < 10)
                return ($vote2 >= 7);

            /* 10 min - 1 hour */
            if ($dt < 60)
                return ($vote >= 5);

            /* More than 1 hour */
            return ($vote >= 3);
        }

        /* Rule for Foreign IP address */
        if (true) {
            return ($vote >= 10);
        }
    }
}
