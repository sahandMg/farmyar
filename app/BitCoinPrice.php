<?php
/**
 * Created by PhpStorm.
 * User: Sahand
 * Date: 4/3/19
 * Time: 10:02 AM
 */

namespace App;


class BitCoinPrice
{
    protected $connection = 'mysql';
    public function getPrice(){

        try{

            $options = array('http' => array('method' => 'GET'));
            $context = stream_context_create($options);
            $contents = file_get_contents('https://api.coindesk.com/v1/bpi/currentprice.json', false, $context);
            $bitCoinObject = json_decode($contents,true);
            $bitCoinPrice = $bitCoinObject['bpi']['USD']['rate_float'];
            return $bitCoinPrice;
        }catch (\Exception $exception){

            return 'bitcoin api failed';
        }
        }

}
