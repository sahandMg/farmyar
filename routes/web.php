<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\BitCoinPrice;
use App\BitHash;
use App\RemoteData;
use App\Transaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Morilog\Jalali\Jalalian;
use Spatie\Sitemap\SitemapGenerator;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;


Route::get('create-sitemap',function(){

    SitemapGenerator::create('https://hashbazaar.com')->writeToFile(public_path('sitemap.xml'));

});
Route::get('job',function(){

    $url = 'http://ip-api.com/php/192.119.12.118';
    unserialize(file_get_contents($url))['countryCode'];
    $ch = curl_init($url); // such as http://example.com/example.xml
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    dd($data);
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
    return view('cryptoMailPage',compact('CryptoCrawl'));
});

Route::get('f2pool',function (){

    $url = 'http://api.f2pool.com/bitcoin/mvs1995';
    $client = new GuzzleClient();
    $promise1 = $client->requestAsync('GET',$url)->then(function (ResponseInterface $response) {
        return $response->getBody()->getContents();
    });
    $resp = $promise1->wait();

    return json_decode($resp,true);

});

Route::get('antpool',function (){

    $antpool = new \App\Pools\Antpool();

    dd($antpool->hashRate());

});

Route::get('qr',function(){

//        $qr = QrCode::format('eps')->backgroundColor(210,168,103)->size(90)->generate('12X6ugSiEUUixJS17W1VghCA4zdPrHmzkY');
//        $output_file = "/img/qr-codes/test2".'.eps';
//        Storage::disk('local')->put($output_file, $qr);
//        dd('dsa');


});


Route::get('test',function (){

    dd('salam');

});


Route::get('sms',function (\Illuminate\Http\Request $request) {



    try{
        $api = new \Kavenegar\KavenegarApi( "796C4E505946715933687269672B6F6B5648564562585250533251356B6B6361" );
        $sender = "10008000800600";
        $message = "خدمات پیام کوتاه کاوه نگار";
        $receptor = array("09387728916","09351635933");
        $api->Send($sender,$receptor,$message);

    }
    catch(\Kavenegar\Exceptions\ApiException $e){
        // در صورتی که خروجی وب سرویس 200 نباشد این خطا رخ می دهد
        echo $e->errorMessage();
    }
    catch(\Kavenegar\Exceptions\HttpException $e){
        // در زمانی که مشکلی در برقرای ارتباط با وب سرویس وجود داشته باشد این خطا رخ می دهد
        echo $e->errorMessage();
    }


//    $url = 'https://hashbazaar.com/ReceiveCallbackUrl';
//    $fields = [
//        'sender'=>'10008000800600',
//        'to'=>'09387728916',
//        'message'=> $message =  "سلام",
//        'messageid'=>2
//    ];
//    $ch = curl_init();
//    curl_setopt($ch,CURLOPT_URL, $url);
//    curl_setopt($ch,CURLOPT_POST, count($fields));
//    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    $result = curl_exec($ch);
//    curl_close($ch);


});


Route::get('export-data','PageController@export')->name('export');

Route::get('change-language','PageController@ChangeLanguage')->name('changeLanguage');

Route::get('btc-price','PageController@btcPrice')->name('btcPrice');

// Route::get('language/{locale}', function ($locale) {

//     $allLanguages = ["en", "fa"];

//     $lng = ( in_array( $locale, $allLanguages) ) ? $locale : 'fa';
//         session()->put(['locale'=>$lng]);
//         App::setLocale($lng);
//     return redirect()->route('index',['locale'=>$lng]);

// })->name('locale');



Route::get('/', function (){

    return redirect('/landing');
})->name('index');

Route::group(['prefix'=> '{lang}'],function() {

    Route::get('captcha-refresh',function (){

        $captcha = \Mews\Captcha\Facades\Captcha::create();
        return Captcha::src();

    })->name('refreshCaptcha');

    // Route::get('/', 'PageController@index')->name('index');
    Route::post('shared-order','PageController@shareOrder')->name('shareOrder');

    // ============================ Blog Routes ==============================

    Route::group(['prefix'=>'blog'],function(){

        Route::get('/', 'BlogController@index')->name('Blog');

        Route::get('{slug}', 'BlogController@showPost')->name('showPost');

    });
    // Route::get('about', 'PageController@aboutUs')->name('aboutUs');

    // Route::get('affiliate', 'PageController@affiliate')->name('affiliate');

    // Route::get('login/{hashpower?}', 'AuthController@login')->name('login')->middleware('guest');

    // Route::post('login', 'AuthController@post_login')->name('login');

    // Route::get('user/verify/{token}', 'AuthController@VerifyUser')->name('userVerify');

    // Route::post('resend-verification', 'AuthController@ResendVerification')->name('ResendVerification');

    // Route::get('user/email-verify', 'AuthController@VerifyUserPage')->name('VerifyUserPage');

    // Route::get('subscription', 'AuthController@subscription')->name('subscription');

    // Route::get('google/login', 'AuthController@redirectToProvider')->name('redirectToProvider');

    // Route::get('google/login/callback', 'AuthController@handleProviderCallback')->name('handleProviderCallback');

    // Route::get('signup', 'AuthController@signup')->name('signup')->middleware('guest');

    // Route::get('password-reset', 'AuthController@passwordReset')->name('passwordReset');

    // Route::get('pricing', 'PageController@Pricing');

    // Route::get('received/{orderID?}', 'PaymentController@checkPaymentReceived')->name('checkPaymentReceived');

    // Route::get('payment/canceled/{transid?}', 'PaymentController@PaymentCanceled')->name('PaymentCanceled')->middleware('auth');

    // Route::get('payment/success', 'PaymentController@PaymentSuccess')->name('PaymentSuccess')->middleware('auth');

    // Route::get('wallet-redirect', 'PageController@RedirectWallet')->name('RedirectWallet');

    // Route::get('zarrin/callback', 'PaymentController@ZarrinCallback')->name('ZarrinCallback');

    // Route::post('subscription', 'AuthController@post_subscription')->name('subscription');

    // Route::post('subscribe', 'AuthController@subscribe')->name('subscribe');

    // Route::post('signup', 'AuthController@post_signup')->name('signup');

    // Route::post('password-reset', 'AuthController@post_passwordReset')->name('passwordReset');

    // Route::post('paystar/paying', 'PaymentController@PaystarPaying')->name('PaystarPaying')->middleware('auth');

    // Route::post('message', 'PageController@message')->name('message');

    // Route::post('zarrin/paying', 'PaymentController@ZarrinPalPaying')->name('ZarrinPalPaying')->middleware('auth');

    // Route::post('payment/test', 'PaymentController@TestPayment')->name('TestPayment')->middleware('auth');


    // Route::get('faq', ['as' => 'customerService', 'uses' => 'PageController@customerService']);

    // Route::get('@admin/login', ['as' => 'AdminLogin', 'uses' => 'AdminController@login'])->middleware('guest');

    // Route::post('@admin/login', ['as' => 'AdminLogin', 'uses' => 'AdminController@post_login'])->middleware('guest');
// ============================ Admin Routes ==============================================
    Route::group(['prefix' => '@admin', 'middleware' => 'admin'], function () {

        // Route::get('home', ['as' => 'adminHome', 'uses' => 'AdminController@index']);

        // Route::get('transactions', ['as' => 'adminTransactions', 'uses' => 'AdminController@transactions']);

        // Route::get('get-transactions', ['as' => 'adminGetTransactions', 'uses' => 'AdminController@getTransactions']);

        // Route::get('checkout', ['as' => 'adminCheckout', 'uses' => 'AdminController@adminCheckout']);

        // Route::get('redeems', ['as' => 'adminRedeems', 'uses' => 'AdminController@adminRedeems']);

        // Route::get('get-redeems', ['as' => 'adminGetRedeems', 'uses' => 'AdminController@adminGetRedeems']);

        // Route::get('users/list', ['as' => 'adminGetUsersList', 'uses' => 'AdminController@adminGetUsersList']);

        // Route::get('block-user', ['as' => 'blockUser', 'uses' => 'AdminController@blockUser']);

        // Route::post('login-as-user', ['as' => 'LoginAsUser', 'uses' => 'AdminController@LoginAsUser']);

        // Route::get('collaboration/{id?}', ['as' => 'collaboration', 'uses' => 'AdminController@collaboration']);

        // Route::post('collaboration', ['as' => 'collaboration', 'uses' => 'AdminController@post_collaboration']);

        // Route::get('user-setting/{id?}', ['as' => 'userSetting', 'uses' => 'AdminController@userSetting']);

        // Route::post('user-setting/{id?}', ['as' => 'userSetting', 'uses' => 'AdminController@post_userSetting']);

        // Route::get('site-setting', ['as' => 'siteSetting', 'uses' => 'AdminController@siteSetting']);

        // Route::post('site-setting', ['as' => 'siteSetting', 'uses' => 'AdminController@post_siteSetting']);

        // Route::get('logout', ['as' => 'adminLogout', 'uses' => 'AdminController@adminLogout']);

        // Route::get('message', ['as' => 'AdminMessage', 'uses' => 'AdminController@message']);

        // Route::post('message', ['as' => 'AdminMessage', 'uses' => 'AdminController@post_message']);

        // Route::post('delete-message', ['as' => 'deleteMessage', 'uses' => 'AdminController@post_deleteMessage']);

        // Route::get('chartData', ['as' => 'chartDataAdmin', 'uses' => 'AdminController@chartData']);

        // Route::get('chartData-profit', ['as' => 'chartDataProfit', 'uses' => 'AdminController@chartDataProfit']);

        // Route::get('hardware-order','AdminController@hardwareOrders')->name('hardwareOrders');

        // Route::get('get-logs', ['as' => 'getLogs', 'uses' => 'AdminController@getLogs']);

//   Voyager::routes();
    });

    // Route::post('send-code', 'PanelController@postDashboard')->name('SendCode');

    // ========================== Remote Pages Routes ===========================

    Route::group(['prefix'=>'remote'],function(){

        Route::get('dashboard','Remote\RemoteController@dashboard')->name('remoteDashboard');

        Route::get('status',['as'=>'minerStatus','uses'=>'Remote\RemoteController@minerStatus']);

        Route::get('subscription','Remote\SubscriptionController@index')->name('remoteSubscription');

        Route::get('zarrin/paying', 'Remote\TransactionController@ZarrinPalPaying')->name('RemoteZarrinPalPaying');

        Route::get('zarrin/callback', 'Remote\TransactionController@ZarrinCallback')->name('RemoteZarrinCallback');

        Route::get('paystar/paying', 'Remote\TransactionController@PaystarPaying')->name('RemotePaystarPaying');

        Route::group(['prefix'=>'hardware'],function(){

            Route::post('zarrin/paying', 'Remote\RemoteOrderController@ZarrinPalPaying')->name('RemoteOrderZarrinPalPaying');

            Route::get('zarrin/callback', 'Remote\RemoteOrderController@ZarrinCallback')->name('RemoteOrderZarrinCallback');

            Route::post('paystar/paying', 'Remote\RemoteOrderController@PaystarPaying')->name('RemoteOrderPaystarPaying');
        });

        Route::get('transactions','Remote\RemoteController@Transactions')->name('TransactionsList');

        Route::post('register-pool','Remote\RemoteController@PoolRegister')->name('PoolRegister');

        Route::get('payment/success/{transid}',['as'=>'RemotePaymentSuccess','uses'=>'Remote\TransactionController@successPayment']);

        Route::get('payment/canceled/{transid}',['as'=>'RemotePaymentCanceled','uses'=>'Remote\TransactionController@FailedPayment']);

        Route::get('hardware','Remote\RemoteController@hardware')->name('hardware');

        Route::get('tutorials','Remote\RemoteController@tutorials')->name('tutorials');

        Route::post('register-farm','Remote\RemoteController@RegisterFarm')->name('RegisterFarm');

        Route::post('get-pool-data','Remote\RemoteController@getPoolData')->name('getPoolData');

        Route::get('logout', ['as' => 'logout', 'uses' => 'Remote\AuthController@logout']);
        // ====================== Remote Test Routes =====================

        Route::group(['prefix'=>'test'],function(){

            Route::get('gate','Remote\Test\TestController@gateTest')->name('gateTest');

            Route::post('zarrin-paying','Remote\Test\TestController@ZarrinPalPaying')->name('RemoteZarrinPalPayingTest');

            Route::post('paystar-paying','Remote\Test\TestController@PaystarPaying')->name('RemotePaystarPayingTest');

            Route::post('zarrin-paying-hardware','Remote\Test\TestHardwareController@ZarrinPalPaying')->name('RemoteHardwareZarrinPalPayingTest');

            Route::post('paystar-paying-hardware','Remote\Test\TestHardwareController@PaystarPaying')->name('RemoteHardwarePaystarPayingTest');
        });
        // -----> unauthorized remote routes <-----------------

        Route::group(['middleware'=>'guest'],function(){


            Route::get('verify/{token}', 'Remote\AuthController@VerifyUser')->name('VerifyRemoteUser');

            Route::post('resend-verification', 'Remote\AuthController@ResendVerification')->name('RemoteResendVerification');

            Route::get('email-verify', 'Remote\AuthController@VerifyUserPage')->name('RemoteVerifyUserPage');

            Route::get('google/login', 'Remote\AuthController@redirectToProvider')->name('RemoteRedirectToProvider');

            Route::get('google/login/callback', 'Remote\AuthController@handleProviderCallback')->name('handleProviderCallbackRemote');

            Route::get('authorizing', 'Remote\AuthController@authorizing')->name('authorizing');

            Route::post('authorizing', 'Remote\AuthController@post_authorizing')->name('authorizing');

            Route::get('password-reset', 'Remote\AuthController@passwordReset')->name('passwordReset');

        });


    });

});
