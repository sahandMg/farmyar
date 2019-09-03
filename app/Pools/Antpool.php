<?php
/**
 * Created by PhpStorm.
 * User: Sahand
 * Date: 8/23/19
 * Time: 5:06 PM
 */

namespace App\Pools;


class Antpool
{

    public function mining(){

        $userId = '13741374';
        $apiKey = '7b07bc4b507b4d7584770f8ddddd02f1';
        $nonce = rand(0, 1000);
        $secret = '0585329bf8eb48509b1ad13b709d9390';
        $url = 'https://antpool.com/api/account.htm';
        $signature = strtoupper(hash_hmac('sha256', $userId . $apiKey . $nonce, $secret, false));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$url?key=$apiKey&nonce=$nonce&signature=$signature&coin=BTC");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $mining24 = json_decode($result,true);
//        $mining24 = json_decode($result)->data->earn24Hours;
        return $mining24;
    }

    public function hashRate(){

        $userId = '13741374';
        $apiKey = '7b07bc4b507b4d7584770f8ddddd02f1';
        $nonce = rand(0, 1000);
        $secret = '0585329bf8eb48509b1ad13b709d9390';
        $ch = curl_init();
        $urlHashRate = 'https://antpool.com/api/hashrate.htm';
        $signature = strtoupper(hash_hmac('sha256', $userId . $apiKey . $nonce, $secret, false));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$urlHashRate?key=$apiKey&nonce=$nonce&signature=$signature&coin=BTC");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resultHashRate = curl_exec($ch);
        curl_close($ch);
        $hashrate = json_decode($resultHashRate,true);
        return $hashrate;
    }
}