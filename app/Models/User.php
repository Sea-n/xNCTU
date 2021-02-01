<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User
 *
 * @property string $stuid
 * @property string $name
 * @property string|null $email
 * @property integer|null $tg_id
 * @property string|null $tg_name
 * @property string|null $tg_username
 * @property string|null $tg_photo
 *
 * @property integer $approvals
 * @property integer $rejects
 * @property integer $current_vote_streak
 * @property integer $highest_vote_streak
 * @property Carbon $last_vote
 *
 * @property Carbon $last_login
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 * @mixin Eloquent
 */
class User extends Authenticatable
{
    /*
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'stuid';

    /*
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /*
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    use HasFactory, Notifiable;

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
        'last_vote',
        'last_login_tg',
        'last_login_nctu',
        'last_login_google',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];

    function dep() {
        return idToDep($this->stuid);
    }
}
