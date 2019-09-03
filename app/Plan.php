<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['value'];
    protected $connection = 'mysql';

    public function users(){

        return $this->hasMany(User::class);
    }
}
