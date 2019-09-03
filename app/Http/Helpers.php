<?php

namespace App\Http;

class Helpers{

   public static function userIP(){

        if( isset($_SERVER['HTTP_CF_CONNECTING_IP']) ){
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } else if( isset($_SERVER['HTTP_X_REAL_IP']) ){
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    public static function addLog($ex){

    }
}
