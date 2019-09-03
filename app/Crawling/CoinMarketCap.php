<?php
/**
 * Created by PhpStorm.
 * User: Sahand
 * Date: 11/25/18
 * Time: 5:50 PM
 */

namespace App\Crawling;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;

class CoinMarketCap
{

    public function getApi(){
        $cryptoName = [];
        $url = 'https://pay98.cash/%D9%82%DB%8C%D9%85%D8%AA-%D8%A7%D8%B1%D8%B2%D9%87%D8%A7%DB%8C-%D8%AF%DB%8C%D8%AC%DB%8C%D8%AA%D8%A7%D9%84';
        $config = [
            'referer' => true,
             'headers' => [
                 'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                 'Accept-Encoding' => 'gzip, deflate, br',
    ],
    ];
        $client = new GuzzleClient();
        $promise1 = $client->requestAsync('GET',$url,$config)->then(function (ResponseInterface $response) {
            $this->resp = $response->getBody()->getContents();
            return $this->resp;
        });
        $promise1->wait();
        $crawler = new Crawler($this->resp);
        $coinNames = $crawler->filterXPath('//p[contains(@class,"nametop1")]');
        // $coins = str_replace(' ', '', $coins);
        // $coins = str_replace("\n", '', $coins);
        // $coins = str_replace("\r", '', $coins);

        $crawledCoins = $coinNames->each(
            function (Crawler $node, $i) {
                $first = $node->children()->eq(1)->text();
                return $first;
            });

        $coinPrices = $crawler->filterXPath('//b[contains(@class,"ltr")]');

    //    Gets Just Crypto Names
        $crawledPrices = $coinPrices->each(
            function (Crawler $node, $i) {
                // $first = $node->eq(4)->text();
                return $node->text();
            });

            array_shift($crawledPrices);
            array_shift($crawledPrices);
            array_shift($crawledPrices);

        $CryptoCrawl = array_combine($crawledCoins,$crawledPrices);
        Config::push('constants.coinPrices',$CryptoCrawl);
        if($CryptoCrawl['Ethereum'] <= 100){

            $data = ['CryptoCrawl'=>$CryptoCrawl];
//            Mail::send('cryptoMailPage',$data,function($message){
//                $message->from('Admin@HashBazaar');
//                $message->to('s23.moghadam@gmail.com');
//                $message->subject('Crypto Prices');
//            });

        }

        Log::warning('done');

    }
}
