<?php
/**
 * Created by PhpStorm.
 * User: Sahand
 * Date: 7/4/19
 * Time: 11:27 PM
 */

namespace App\Classes;


use Illuminate\Support\Facades\Crypt;

class Encryption
{

    public function encryptEntry($value){

        return Crypt::encryptString($value);

    }
}