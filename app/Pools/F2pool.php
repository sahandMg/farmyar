<?php
/**
 * Created by PhpStorm.
 * User: Sahand
 * Date: 8/23/19
 * Time: 5:05 PM
 */

namespace App\Pools;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;

class F2pool
{

    public function run(){

     $url = 'http://api.f2pool.com/bitcoin/mvs1995';
        $client = new GuzzleClient();
        try{
            $promise1 = $client->requestAsync('GET',$url)->then(function (ResponseInterface $response) {
                return $response->getBody()->getContents();
            });
            $resp = $promise1->wait();
        }catch (\Exception $exception){


            return ['code'=>$exception->getCode(),'message'=>$exception->getMessage()];
        }
        return ['message'=>json_decode($resp, true),'code'=>200];
    }
}