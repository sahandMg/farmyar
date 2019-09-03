<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomCode extends Model
{
    protected $fillable = ['sharing_number','user_id'];
    protected $connection = 'mysql';
}
