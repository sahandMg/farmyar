<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $connection = 'mysql';
    protected $fillable = ['available_th','usd_toman','total_th','usd_per_hash',
        'maintenance_fee_per_th_per_day','bitcoin_income_per_month_per_th',
        'available_th','sharing_discount','hash_life','minimum_redeem','zarrin_active','paystar_active','alarms',
        'total_mining','total_benefit'
    ];
//    protected $encryptable = [
//
//        'available_th','total_th','usd_per_hash',
//        'maintenance_fee_per_th_per_day','bitcoin_income_per_month_per_th',
//        'available_th','zarrin_active','paystar_active',
//    ];

}
