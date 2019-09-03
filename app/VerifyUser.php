<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VerifyUser extends Model
{

    protected $fillable = ['user_id','token','remote_user_id'];
    protected $connection = 'mysql';
    public function user(){

        return $this->belongsTo(User::class,'user_id');
    }

    public function remoteUser(){

        return $this->belongsTo(RemoteUser::class);
    }
}
