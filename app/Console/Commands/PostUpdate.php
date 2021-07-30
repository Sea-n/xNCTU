<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Log;

class PostUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:update-likes {count=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update fb_likes using API';


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
        if (empty(env('FACEBOOK_ACCESS_TOKEN', '')))
            return;

        $count = $this->argument('count');
        $posts = Post::where('status', 5)->orderByDesc('id')->limit($count)->get();

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
        ]);

        foreach ($posts as $post) {
            if (in_array($post->id, []))
                continue; // API error but post exists

            if ($post->facebook_id < 10)
                continue;

            $url = 'https://graph.facebook.com/v10.0/' . env('FACEBOOK_PAGES_ID') . '_' . $post->facebook_id .
                '?fields=reactions.summary(total_count)&access_token=' . env('FACEBOOK_ACCESS_TOKEN');

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
            ]);
            $result = curl_exec($curl);
            $result = json_decode($result);

            $json = json_encode($result, JSON_PRETTY_PRINT);
            \Storage::put("fb-stat/reactions-{$post->id}", $json);


            if (!isset($result->reactions->summary->total_count)) {
                Log::error('Update likes error: ' . $post->id);
                sleep(5);
                continue;
            }

            $likes = $result->reactions->summary->total_count;
            $likes = max($post->fb_likes, $likes);

            $post->update(['fb_likes' => $likes]);
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
