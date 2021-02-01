<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Vote
 *
 * @property string $uid
 * @property string $stuid
 * @property integer $vote
 * @property string $reason
 * @property Post $post
 * @property User $user
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Eloquent
 */
class Vote extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = [
    ];

    /**
     * @return Post
     */
    public function post()
    {
        return $this->hasOne(Post::class, 'uid', 'uid');
    }

    /**
     * @return User
     */
    public function user()
    {
        return $this->hasOne(User::class, 'stuid', 'stuid');
    }
}
