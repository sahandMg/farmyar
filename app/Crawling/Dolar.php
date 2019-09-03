<?php
/**
 * Created by PhpStorm.
 * User: Sahand
 * Date: 5/7/19
 * Time: 1:41 PM
 */

namespace App\Crawling;
use App\Setting;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;

class Dolar
{
    public $resp;
    public function getDolarInToman(){


        $url = 'https://www.tasnimnews.com/fa/currency';
        $config = [
            'referer' => true,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
            ],
        ];
        $client = new GuzzleClient();
        $promise = $client->requestAsync('GET',$url,$config)->then(function (ResponseInterface $response) {
            $this->resp = $response->getBody()->getContents();
            return $this->resp;
        });
        $promise->wait();
        $xPath = '//table[contains(@class, "coins-table")]';
        $crawler = new Crawler($this->resp);
        $DollarPrices = $crawler->filterXPath($xPath);
        $crawledPrices = $DollarPrices->each(
            function (Crawler $node, $i) {

                return $node->text();
            });
        $crawledPrices = explode(' ',$crawledPrices[0])[19];
        // convert persian num to english
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١','٠'];

        $num = range(0, 9);
        $convertedPersianNums = str_replace($persian, $num, $crawledPrices);
        $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);
        $toman = floatval(str_replace(',','',$englishNumbersOnly))/10;
        $settings = Setting::first();
        $settings->update(['usd_toman'=>$toman]);
        return $toman;

    }
}