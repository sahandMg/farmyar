<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RemoteId extends Model
{
    protected $connection = 'mysql';

    public function user(){

        return $this->belongsTo(RemoteUser::class,'user_id');
    }
}
