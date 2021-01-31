<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Post
 *
 * @property string $uid
 * @property integer|null $id
 * @property string $body
 * @property string|null $orig
 * @property integer $media
 * @property string|null $author
 * @property string $ip_addr
 * @property string $ip_from
 *
 * @property integer $status
 * @property integer $approvals
 * @property integer $rejects
 * @property integer $fb_likes
 * @property integer $old_likes
 * @property integer $max_likes
 *
 * @property integer $telegram_id
 * @property integer $plurk_id
 * @property integer $twitter_id
 * @property integer $facebook_id
 * @property string $instagram_id
 *
 * @property Carbon $created_at;
 * @property Carbon $updated_at;
 * @property Carbon|null $submitted_at
 * @property Carbon|null $posted_at
 * @property Carbon|null $deleted_at;
 * @property string|null $delete_note
 *
 * @mixin Eloquent
 */
class Post extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uid';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'char';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = [
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    public function author()
    {
        return $this->hasOne(User::class);
    }
}
