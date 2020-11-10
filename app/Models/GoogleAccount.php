<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sub',
        'email',
        'name',
        'avatar',
        'last_login',
    ];
}
