<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Google Account
 *
 * @property string $sub
 * @property string $email
 * @property string $name
 * @property string $avatar
 * @property string|null $stuid
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $last_login
 * @property Carbon $last_verify
 * @property Carbon $deleted_at
 *
 * @mixin Eloquent
 */
class GoogleAccount extends Model
{
    /*
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'sub';

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

    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = [
    ];
}
