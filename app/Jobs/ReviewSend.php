<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\ReviewService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class ReviewSend implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    /**
     * Create a new job instance.
     *
     * @param Post $post
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Execute the job.
     *
     * @param ReviewService $service
     * @throws Exception
     */
    public function handle(ReviewService $service)
    {
        try {
            $service->send($this->post);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
