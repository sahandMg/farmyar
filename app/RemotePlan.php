<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RemotePlan extends Model
{
    protected $connection = 'mysql';

    public function transaction(){

        return $this->belongsTo(RemoteTransaction::class,'trans_id');
    }
}
