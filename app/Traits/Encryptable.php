<?php
///**
// * Created by PhpStorm.
// * User: Sahand
// * Date: 7/5/19
// * Time: 11:43 PM
// */
//
//namespace App\Traits;
//
//use Illuminate\Support\Facades\Crypt;
//trait Encryptable
//{
//    /**
//     * If the attribute is in the encryptable array
//     * then decrypt it.
//     *
//     * @param  $key
//     *
//     * @return $value
//     */
//    public function getAttribute($key)
//    {
//        $value = parent::getAttribute($key);
//        if (in_array($key, $this->encryptable) && $value !== '') {
//            $value = decrypt($value);
//        }
//        return $value;
//    }
//
//    /**
//     * If the attribute is in the encryptable array
//     * then encrypt it.
//     *
//     * @param $key
//     * @param $value
//     */
//    public function setAttribute($key, $value)
//    {
//        if (in_array($key, $this->encryptable)) {
//            $value = encrypt($value);
//        }
//        return parent::setAttribute($key, $value);
//    }
//}