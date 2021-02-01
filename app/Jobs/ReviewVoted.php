<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Services\ReviewService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReviewVoted implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @param Post $post
     * @param User $user
     */
    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @param ReviewService $service
     * @throws Exception
     */
    public function handle(ReviewService $service)
    {
        $service->voted($this->post, $this->user);
    }
}
