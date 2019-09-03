<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $connection = 'mysql';
    public function user(){

        return $this->belongsTo(User::class);
    }
}
