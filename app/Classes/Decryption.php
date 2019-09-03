<?php
/**
 * Created by PhpStorm.
 * User: Sahand
 * Date: 7/4/19
 * Time: 11:28 PM
 */

namespace App\Classes;


use Illuminate\Support\Facades\Crypt;

class Decryption
{

    public function decryptEntry($value){

        return Crypt::decryptString($value);
    }
}