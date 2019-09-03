<?php

namespace App\Listeners;

use App\Events\CoinBaseNewProduct;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class makeProduct
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  CoinBaseNewProduct  $event
     * @return void
     */
    public function handle(CoinBaseNewProduct $event)
    {

        // first check if this product is new or not




        // add new product to checkouts
        $hash = $event->hash;
        $hashUsdAmount = $event->usdAmount;
        $apikey = $event->apikey;
        $name = $hash.'T Hash Power';
        $description = 'HashBazaar Bitcoin Cloud Mining';
        $url =  "https://api.commerce.coinbase.com/checkouts";
        $payload = [
            "name"=> $name,
            "description"=> $description,
            "local_price"=> [
                "amount"=> $hashUsdAmount,
                "currency"=> "USD"
            ],
            "pricing_type"=> "fixed_price"
        ];

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$apikey",
            "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

    }
}
