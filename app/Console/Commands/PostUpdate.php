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
        $count = $this->argument('count');
        $posts = Post::where('status', '=', 5)->orderByDesc('id')->limit($count)->get();

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
        ]);

        foreach ($posts as $post) {
            if (in_array($post->id, [581, 1597, 2211, 3849, 3870, 3975, 4275, 4575, 6894]))
                continue; // API error but post exists

            if ($post->facebook_id < 10)
                continue;

            $url = 'https://graph.facebook.com/v7.0/' . env('FACEBOOK_PAGES_ID') . '_' . $post->facebook_id . '/reactions' .
                '?fields=type,name,profile_type&limit=100000&access_token=' . env('FACEBOOK_ACCESS_TOKEN');

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
            ]);
            $result = curl_exec($curl);
            $result = json_decode($result);


            if (!isset($result->data)) {
                Log::error('Update likes error: ' . $post->id);
                $json = json_encode($result, JSON_PRETTY_PRINT);
                \Storage::put("fb-stat/error-{$post->id}", $json);
                sleep(5);
                continue;
            }

            $result = $result->data;
            $json = json_encode($result, JSON_PRETTY_PRINT);
            \Storage::put("fb-stat/reactions-{$post->id}", $json);

            $likes = count($result);

            $post->update([
                'fb_likes' => $likes,
                'max_likes' => $likes,
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
