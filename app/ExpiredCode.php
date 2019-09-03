<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpiredCode extends Model
{
    protected $fillable = ['used'];
    protected $connection = 'mysql';
}
