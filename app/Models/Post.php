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
 * @property string|null $author_id
 * @property User|null $author
 * @property string $ip_addr
 * @property string $ip_from
 *
 * @property integer $status
 * @property integer $approvals
 * @property integer $rejects
 * @property integer $fb_likes
 *
 * @property integer $telegram_id
 * @property integer $plurk_id
 * @property integer $twitter_id
 * @property integer $discord_id
 * @property integer $facebook_id
 * @property string $instagram_id
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $submitted_at
 * @property Carbon|null $posted_at
 * @property Carbon|null $deleted_at
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

    /**
     * @return User
     */
    public function author()
    {
        return $this->hasOne(User::class, 'stuid', 'author_id');
    }

    public function getUrl(string $platform)
    {
        switch (strtolower($platform)) {
            case 'website':
                return url(($this->id > 0 ? "/post/{$this->id}" : "/review/{$this->uid}"));
            case 'image':
                return $this->media == 0 ? '' : url("/img/{$this->uid}.jpg");
            case 'telegram':
                return $this->telegram_id > 0 ? 'https://t.me/' . env('TELEGRAM_USERNAME') . "/{$this->telegram_id}" : null;
            case 'discord':
                return $this->discord_id > 0 ? 'https://discord.com/channels/' . env('DISCORD_SERVER_ID') . '/'
                    . env('DISCORD_CHANNEL_ID') . "/{$this->discord_id}" : null;
            case 'plurk':
                return $this->plurk_id > 10 ? 'https://www.plurk.com/p/' . base_convert($this->plurk_id, 10, 36) : null;
            case 'twitter':
                return $this->twitter_id > 10 ? 'https://twitter.com/' . env('TWITTER_USERNAME') . "/status/{$this->twitter_id}" : null;
            case 'facebook':
                return $this->facebook_id > 10 ? 'https://www.facebook.com/' . env('FACEBOOK_USERNAME') . "/posts/{$this->facebook_id}" : null;
            case 'instagram':
                return strlen($this->instagram_id) > 1 ? 'https://www.instagram.com/p/' . $this->instagram_id : null;
            default:
                return null;
        }
    }
}
