<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $fillable = ['share_level','total_sharing_num'];
    protected $guarded = ['code','user_id'];
    protected $connection = 'mysql';
}
