<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    protected $fillable = ['pu_key','pr_key','btc_amount','used'];
    protected $connection = 'mysql';
}
