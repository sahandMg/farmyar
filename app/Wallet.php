<?php

namespace App;


use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{

    protected $fillable = ['addr'];
    protected $connection = 'mysql';
//    protected $encryptable = [
//        'addr'
//    ];
}
