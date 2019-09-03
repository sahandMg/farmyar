<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RemoteTransaction extends Model
{
    protected $connection = 'mysql';
    protected $fillable = ['status'];

    public function user(){

        return $this->belongsTo(RemoteUser::class);
    }

    public function subscription(){

        return $this->hasOne(RemotePlan::class,'trans_id');
    }

    public function hardware(){

        return $this->hasOne(RemoteOrder::class,'trans_id');
    }
}
