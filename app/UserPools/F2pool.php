<?php
/**
 * Created by PhpStorm.
 * User: Sahand
 * Date: 8/23/19
 * Time: 5:05 PM
 */

namespace App\UserPools;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Auth;
use Psr\Http\Message\ResponseInterface;

class F2pool
{
    public $f2pool;
    public $username;
    public function __construct($f2pool)
    {
        $this->username = decrypt($f2pool->username);
    }

    public function mining(){

     $url = 'http://api.f2pool.com/bitcoin/'.$this->username;
//     $url = 'http://api.f2pool.com/bitcoin/mvs1995';
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