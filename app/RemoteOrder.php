<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RemoteOrder extends Model
{
    protected $fillable = ['trans_id'];
    protected $connection = 'mysql';
}
