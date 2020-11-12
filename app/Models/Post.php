<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * Post
 *
 * @mixin Eloquent
 */

/**
 * @method static where(string $string, string $string1, array|string|null $argument)
 */
class Post extends Model
{
    /*
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uid';

    /*
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'char';

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
        'uid',
        'body',
        'media',
        'author',
        'ip_addr',
        'ip_from',
        'status',
        'submitted_at',
        'updated_at',
        'deleted_at',
        'delete_note',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];
}
