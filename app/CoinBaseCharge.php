<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CoinBaseCharge extends Model
{
    protected $fillable = ['status'];
    protected $connection = 'mysql';
    public function user(){

        return $this->belongsTo(User::class);
    }
}
