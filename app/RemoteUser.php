<?php

namespace App;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class RemoteUser extends Authenticatable implements MustVerifyEmail
{
    protected $connection = 'mysql';
    protected $fillable = ['email','password','ip','country','block','avatar','verified'];

    public function verifyUser(){
        return $this->hasOne(VerifyUser::class);
    }

    public function data(){

        return $this->hasMany(RemoteData::class,'remote_id');
    }

    public function user(){

        return $this->hasMany(RemoteId::class,'user_id');
    }

    public function antpools(){

        return $this->hasMany(AntPool::class,'remote_id');
    }
    public function f2pools(){

        return $this->hasMany(F2Pool::class,'user_id');
    }
    public function slushpools(){

        return $this->hasMany(SlushPool::class,'user_id');
    }
}
