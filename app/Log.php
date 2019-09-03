<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Log extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';
    
    protected $fillable = [
        'env',
        'message',
        'level',
        'context',
        'extra'
    ];

    protected $casts = [
        'context' => 'array',
        'extra'   => 'array'
    ];
}
