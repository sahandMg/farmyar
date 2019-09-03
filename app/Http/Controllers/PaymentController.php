<?php

namespace App\Http\Controllers;

use App\BitHash;
use App\CoinBaseCharge;
use App\CoinBaseCheckout;
use App\Crawling\CoinMarketCap;
use App\Crawling\Dolar;
use function App\CryptpBox\lib\cryptobox_selcoin;
use function App\CryptpBox\lib\run_sql;
use App\CustomCode;
use App\Events\CoinBaseNewProduct;
use App\ExpiredCode;
use App\Log;
use App\Mining;
use App\PaymentTest;
use App\Paystar;
use App\Redeem;
use App\Referral;
use App\Setting;
use App\Sharing;
use App\Transaction;
use App\User;
use App\ZarrinPal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\CryptpBox\lib\Cryptobox;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Stevebauman\Location\Facades\Location;
use App\Http\Helpers;

//require_once(app_path()."/CryptoBox/lib/cryptobox.class.php" );

class PaymentController extends Controller
{
    public $apikey;
    public $publickey;
    public $privatekey;
    public $th_usd;
    public $hash_life;
    public function __construct()
    {

        $settings = DB::connection('mysql')->table('settings')->first();
        $this->apikey = $settings->apikey;
        $this->publickey = $settings->publickey;
        $this->privatekey = $settings->privatekey;
        $this->th_usd = $settings->usd_per_hash;
        $this->hash_life = $settings->hash_life;
    }


    public function ZarrinPalPaying(Request $request){
        $zarrin = new ZarrinPal($request);
        $result = $zarrin->create();
        if($result != 404){
            $request->session()->save();
            return redirect()->to('https://www.zarinpal.com/pg/StartPay/' . $result["Authority"]);
        }else{
            return 'مشکلی در پرداخت پیش آمده';
        }
    }

    // verify payment status
    // paystar sends transactionID

    public function ZarrinCallback(Request $request){

        $zarrin = new ZarrinPal($request);

        return $zarrin->verify();
    }

    public function PaystarPaying(Request $request){

        $payStar = new Paystar($request);
        $result = $payStar->create();
        if($result != 404){
        $request->session()->save();

            return redirect()->to('https://paystar.ir/paying/'.$result);
        }else{
            return 'مشکلی در پرداخت پیش آمده';
        }


    }


    public function PaystarCallback(Request $request){

        $payStar = new Paystar($request);

        return $payStar->verify();

    }

 /*
  * Payment Callbacks
  */
    public function PaymentCanceled($transid = null){
        if(is_null($transid)){

            return redirect()->route('dashboard',['locale'=>session('locale')]);
        }
        $hash = BitHash::where('order_id',$transid)->first();
        if(is_null($hash)){
            return view('payment.PaymentCanceled');
        }

            BitHash::where('order_id',$transid)->delete();
            Mining::where('order_id',$transid)->delete();

//        if(session()->has('custom_code')){
//            session()->forget('custom_code');
//        }

        return view('payment.PaymentCanceled');
    }

    public function PaymentSuccess(){

        // TODO Place this code in callback from gateway
        if(session()->has('custom_code')){
            session()->forget('custom_code');
        }

        return view('payment.PaymentSuccess');
    }

    /*
     * Test Payment
     */
    public function TestPayment(Request $request){

        $payment = new PaymentTest($request);

         $code = $payment->create();

        return $this->PaymentCallbackTest($request,$code);
    }

    public function PaymentCallbackTest($request,$code){

        $payment = new PaymentTest($request);

        return $payment->verify($code);
    }

    /*
     *
     *  Coinbase Apis
     */
    // runs when a user click on shopping and redirects to coinbase payment page
    public function createCharge(Request $request){
//        dd($request->all());
        $settings = Setting::first();
        $request = $request->all();
        $discount = $request['discount'];
        $hash = $request['hash'];
        $name =  $request['hash'].'T Hash Power';
        $description = 'HashBazaar Bitcoin Cloud Mining';
        $amount = $this->th_usd * $hash * (1- $discount);
        $referralCode = $request['code'];
        $url =  "https://api.commerce.coinbase.com/charges";
        $payload = [
            "name"=> $name,
            "description"=> $description,
            "local_price"=> [
                "amount"=> $amount,
                "currency"=> "USD"
            ],
            "pricing_type"=> "fixed_price",
            "metadata"=> [
                "customer_id"=> Auth::id(),
                "customer_name"=> Auth::user()->name
            ],
            "redirect_url"=> "https://hashbazaar.com/".session('locale')."payment/success",
            "cancel_url"=> "https://hashbazaar.com/".session('locale')."payment/canceled",

        ];
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result,true)['data'];
        // record charge query in database
        $newCharge = new CoinBaseCharge();
        $newCharge->user_id = Auth::id();
        $newCharge->transaction_id = $result['code'];
        $newCharge->bitcoin_address = $result['addresses']['bitcoin'];
        $newCharge->charge_id = $result['id'];
        $newCharge->product_name = $result['name'];
        $newCharge->product_price = $result['pricing']['local']['amount'];
        $newCharge->product_btc = $result['pricing']['bitcoin']['amount'];
        $newCharge->expires_at = $result['expires_at'];
        $newCharge->status = 'new';
        $newCharge->save();
        // creates a new product if it's not available in checkout database
        try{

            $query = CoinBaseCheckout::where('name',$result['name'])->firstOrFail();
            $checkout_id = $query->checkout_id;
        }catch (\Exception $ex){

            $info = [
                'name'=>$name,
                'description'=>$description,
                'amount' => $this->th_usd * $hash
            ];
            $checkout_id = $this->createCheckout($info);
        }

        // create database record
        $custom_code = CustomCode::where('code',$referralCode)->first();
        $hashRecord = new BitHash();
        $hashRecord->hash = $hash;
        $hashRecord->user_id = Auth::guard('user')->id();
        $hashRecord->order_id = $result['code'];
        $hashRecord->confirmed = 0;
        // a custom code is not involved in affiliate
        if(isset($custom_code)){
            $hashRecord->referral_code = null;
        }else{
            $hashRecord->referral_code = $referralCode;
        }
        $hashRecord->life = $this->hash_life;
        $hashRecord->remained_day = Carbon::now()->diffInDays(Carbon::now()->addYears($hashRecord->life));
        $hashRecord->save();


        $mining = new Mining();
        $mining->mined_btc = 0;
        $mining->mined_usd = 0;
        $mining->user_id = Auth::guard('user')->id();
        $mining->order_id = $result['code'];
        $mining->block = 1;
        $mining->save();

        $trans = new Transaction();
        $trans->amount_btc = $newCharge->product_btc;
        $trans->code = $result['code'];
        $trans->status = 'unpaid';
        $trans->user_id = Auth::guard('user')->id();
        $trans->addr = $newCharge->bitcoin_address;
        $trans->checkout = 'out';
        $trans->save();



        return redirect('https://commerce.coinbase.com/charges/'.$result['code']);
//        return redirect('https://commerce.coinbase.com/checkout/dd47f6ee-9166-4f7a-8cbf-856811ac0df4');
    }


    // get specific charge record
    public function getCharges($id){

        $url =  "https://api.commerce.coinbase.com/charges/$id";

        try{

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
                "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }catch (\Exception $exception){

            return $exception;
        }


    }


    // get list of charges
    public function listCharges(Request $request){

        $url =  "https://api.commerce.coinbase.com/charges";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        dd(json_decode($result,true));
    }

    // cancel a specific charge
    public function cancelCharge(Request $request){
        $id = $request->id;
        $url =  "https://api.commerce.coinbase.com/charges/$id/cancel";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return($result);

    }


// define new product
// required amount , name , description
    public function createCheckout(array $info){
        $amount = $info['amount'];
        $name = $info['name'];
        $description = $info['description'];
        $url =  "https://api.commerce.coinbase.com/checkouts";
        $payload = [
            "name"=> $name,
            "description"=> $description,
            "local_price"=> [
                "amount"=> $amount,
                "currency"=> "USD"
            ],
            "pricing_type"=> "fixed_price",
            "requested_info" => []
        ];

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result,true)['data'];
        $newCheckout = new CoinBaseCheckout();
        $newCheckout->name = $result['name'];
        $newCheckout->description = $result['description'];
        $newCheckout->checkout_id = $result['id'];
        $newCheckout->price = $result['local_price']['amount'];
        $newCheckout->save();
        return $result['id'];
    }

    private function listCheckouts(){

        $url =  "https://api.commerce.coinbase.com/checkouts";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return(json_decode($result,true));


    }
// update a product
// name, description,amount,productid
    public function updateCheckout(Request $request){
        $request = $request->all();
        $productId = $request['productid'];
        $payload = [
            "name"=> $request['name'],
            "description"=> $request['description'],
            "local_price"=> [
                "amount"=> $request['amount'],
                "currency"=> "USD"
            ],
            "requested_info"=> ["email"]
        ];
        $url =  "https://api.commerce.coinbase.com/checkouts/$productId";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return($result);
    }
    // delete product
    public function deleteCheckout(Request $request){
        $productId = $request->productid;
        $url =  "https://api.commerce.coinbase.com/checkouts/$productId";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function showCheckout(Request $request){

        $url =  "https://api.commerce.coinbase.com/checkouts".$request->checkout_id;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return(json_decode($result,true));

    }

    public function eventList(){
        $url = "https://api.commerce.coinbase.com/events";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        dd(json_decode($result,true));
    }

    public function eventShow(Request $request){
        $productId = $request->productid;
        $url = "https://api.commerce.coinbase.com/events/$productId";
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-CC-Api-Key:$this->apikey",
            "X-CC-Version: 2018-03-22",'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }


    public function PaymentConfirmed(Request $request){

        $event = $request->all();

        $transaction_id = $event['event']['data']['code'];
        if($event['event']['type'] == 'charge:confirmed'){

            $confirmedCharge = $this->getCharges('TBMZD7QA');
            $charge = json_decode($confirmedCharge,true)['data'];
            $address = $charge['addresses']['bitcoin'];
//            This means the payment is successful
            if(isset($charge['confirmed_at'])){

                $this->confirmMining($transaction_id,$address);
            }
        }

    }

    // used for just coinbase
    private function confirmMining($transaction_id,$address)
    {

        $orderID = $transaction_id;
        $hashPower = BitHash::where('order_id', $orderID)->first();
        $mining = Mining::where('order_id', $orderID)->first();
        $settings = Setting::first();
        $coinbaseChargeRecord = CoinBaseCharge::where('transaction_id', $transaction_id)->first();
        $settings->update(['available_th'=>$settings->available_th - $hashPower->hash]);
        $settings->save();
        $coinbaseChargeRecord->update(['status'=>'confirmed']);
        $user = $coinbaseChargeRecord->user;
        $hashPower->update(['confirmed' => 1]);
        $hashPower->save();
        $mining->update(['block' => 0]);
        $mining->save();
        // update created transaction record
        DB::connection('mysql')->table('transactions')->where('code', $orderID)->update([
            'addr' => $address,
            'country' => $user->country,
            'status' => 'paid'
        ]);

        $trans = DB::connection('mysql')->table('transactions')->where('code', $orderID)->first();

        Mail::send('email.paymentConfirmed', ['hashPower' => $hashPower, 'trans' => $trans], function ($message) use ($user) {
            $message->from(env('Admin_Mail'));
            $message->to($user->email);
            $message->subject('Payment Confirmed');
        });

//                $referralUser = DB::connection('mysql')->table('expired_codes')->where('user_id',$user->id)->where('used',0)->first();
        $referralCode = $hashPower->referral_code;
        $referralQuery = Referral::where('code', $referralCode)->first();
        // if any referral code used for hash owner purchasing
        if (!is_null($referralCode)) {

            $codeCaller = User::where('code', $referralCode)->first();// code caller user
            /*
             * reward new th to the code caller
             * ============================
             * increasing share level
             */

            $sharings = Sharing::all()->toArray();
            $total_sharing_num = $referralQuery->total_sharing_num;

            for ($i = 0; $i < count($sharings); $i++) {

                if ($sharings[$i]['sharing_number'] < $total_sharing_num) {

                    if ($i == count($sharings) - 1) {

                        $referralQuery->update([
                            'share_level' => $sharings[$i]['level']
                        ]);
                        $referralQuery->save();
                    } else {
                        $referralQuery->update([
                            'share_level' => $sharings[$i + 1]['level']
                        ]);
                        $referralQuery->save();
                    }

                }
            }

            $share_level = $referralQuery->share_level;
            $share_value = DB::connection('mysql')->table('sharings')->where('level', $share_level)->first()->value;
            $hash = new BitHash();
            $hash->hash = $hashPower->hash * $share_value;
            $hash->user_id = $codeCaller->id;
            $hash->order_id = 'referral';
            $hash->confirmed = 1;
            $hash->life = $settings->hash_life;
            $hash->remained_day = Carbon::now()->diffInDays(Carbon::now()->addYears($hash->life));
            $hash->save();
            $mining = new Mining();
            $mining->mined_btc = 0;
            $mining->mined_usd = 0;
            $mining->user_id = $codeCaller->id;
            $mining->order_id = 'referral';
            $mining->block = 0;
            $mining->save();
        }
    }

// Executes when a new charge is create
    public function PaymentCreated(Request $request){

        $event = $request->all();
        $transaction_id = $event['event']['data']['code'];
        $user = CoinBaseCharge::where('transaction_id', $transaction_id)->first()->user;
        $query = DB::connection('mysql')->table('expired_codes')->where('user_id',$user->id)->where('used',0)->first();
        if(!is_null($query)){
            DB::connection('mysql')->table('expired_codes')->where('user_id',$user->id)->where('used',0)->update(['used'=>1]);
        }


    }

    public function PaymentFailed(Request $request){

        $event = $request->all();
        $transaction_id = $event['event']['data']['code'];
        CoinBaseCharge::where('transaction_id', $transaction_id)->first()->update(['status'=>'failed']);
        $allocated_hash = BitHash::where('order_id',$transaction_id)->first();
        $allocated_mining = Mining::where('order_id',$transaction_id)->first();
        $settings = Setting::first();
        $settings->update(['available_th'=> $settings->available_th + $allocated_hash->hash]);
        $allocated_hash->delete();
        $allocated_mining->delete();
    }

    public function PaymentPending(Request $request){

        $event = $request->all();
        $transaction_id = $event['event']['data']['code'];
        CoinBaseCharge::where('transaction_id', $transaction_id)->first()->update(['status'=>'pending']);
    }

    public function PaymentDelayed(Request $request){

        $event = $request->all();
        $transaction_id = $event['event']['data']['code'];
        CoinBaseCharge::where('transaction_id', $transaction_id)->first()->update(['status'=>'delayed']);
    }

    public function PaymentResolved(Request $request){

        $event = $request->all();
        $transaction_id = $event['event']['data']['code'];
        CoinBaseCharge::where('transaction_id', $transaction_id)->first()->update(['status'=>'resolved']);
    }

    public function postPayment(Request $request){

        DEFINE("CRYPTOBOX_PHP_FILES_PATH", app_path()."/CryptoBox/lib/");        	// path to directory with files: cryptobox.class.php / cryptobox.callback.php / cryptobox.newpayment.php;
        // cryptobox.newpayment.php will be automatically call through ajax/php two times - payment received/confirmed
        DEFINE("CRYPTOBOX_IMG_FILES_PATH", app_path()."images/");      // path to directory with coin image files (directory 'images' by default)
        DEFINE("CRYPTOBOX_JS_FILES_PATH", app_path()."js/");			// path to directory with files: ajax.min.js/support.min.js
        $settings = Setting::first();
        // updating bitcoin price
        $options = array( 'http' => array( 'method'  => 'GET') );
        $context = stream_context_create($options);
        $contents = file_get_contents('https://www.blockonomics.co/api/price?currency=USD', false, $context);
        $bitCoinPrice = json_decode($contents);
        if($bitCoinPrice->price == 0){

            return 'bitcoin api failed';
        }
        $userID = Auth::guard('user')->user()->name;	  // place your registered userID or md5(userID) here (user1, user7, uo43DC, etc).
        // You can use php $_SESSION["userABC"] for store userID, amount, etc
        // You don't need to use userID for unregistered website visitors - $userID = "";
        // if userID is empty, system will autogenerate userID and save it in cookies
        $userFormat		= "SESSION";       // save userID in cookies (or you can use IPADDRESS, SESSION, MANUAL)
        $orderID		= str_random(10);	  // invoice #000383
        if(!$request->has('hash')){

            $amount = $request->amount;

        }else{
            $amount = $request->hash * $settings->usd_per_hash * (1 - $request->discount)/$bitCoinPrice->price;
        }
//        dd($request->all());
        $period			= "NOEXPIRY";	  // one time payment, not expiry
        $def_language	= "en";			  // default Language in payment box
        $def_coin		= "bitcoin";      // default Coin in payment box

        // List of coins that you accept for payments
        //$coins = array('bitcoin', 'bitcoincash', 'bitcoinsv', 'litecoin', 'dash', 'dogecoin', 'speedcoin', 'reddcoin', 'potcoin', 'feathercoin', 'vertcoin', 'peercoin', 'monetaryunit', 'universalcurrency');
        $coins = ['bitcoin'];  // for example, accept payments in bitcoin, bitcoincash, litecoin, dash, speedcoin

        // Demo Keys; for tests	(example - 5 coins)
        $all_keys = array(
            "bitcoin" => array(
                "public_key" => $this->publickey,
                "private_key" => $this->privatekey
            )
        );

        // Re-test - all gourl public/private keys
        $def_coin = strtolower($def_coin);
        if (!in_array($def_coin, $coins)) $coins[] = $def_coin;
        foreach($coins as $v)
        {

            if (!isset($all_keys[$v]["public_key"]) || !isset($all_keys[$v]["private_key"])){

                die("Please add your public/private keys for '$v' in \$all_keys variable");
            }
            elseif (!strpos($all_keys[$v]["public_key"], "PUB")){
                die("Invalid public key for '$v' in \$all_keys variable");
            }
            elseif (!strpos($all_keys[$v]["private_key"], "PRV")) {

                die("Invalid private key for '$v' in \$all_keys variable");

            }
            elseif (strpos(CRYPTOBOX_PRIVATE_KEYS, $all_keys[$v]["private_key"]) === false){

                die("Please add your private key for '$v' in variable \$cryptobox_private_keys, file /lib/cryptobox.config.php.");
            }
        }

        // Current selected coin by user
        $coinName = cryptobox_selcoin($coins, $def_coin);
        // Current Coin public/private keys
        $public_key  = $all_keys[$coinName]["public_key"];
        $private_key = $all_keys[$coinName]["private_key"];
        /** PAYMENT BOX **/
        $options = array(
            "public_key"  	=> $public_key,	    // your public key from gourl.io
            "private_key" 	=> $private_key,	// your private key from gourl.io
            "webdev_key"  	=> "", 			    // optional, gourl affiliate key
            "orderID"     	=> $orderID, 		// order id or product name
            "userID"      	=> $userID, 	// unique identifier for every user
            "userFormat"  	=> $userFormat, 	// save userID in COOKIE, IPADDRESS, SESSION  or MANUAL
            "amount"   	  	=> $amount, // product price in btc/bch/bsv/ltc/doge/etc OR setup price in USD below
            "amountUSD"   	=> null,	    // we use product price in USD
             "period"      	=> $period, 	// payment valid period
            "language"	  	=> $def_language    // text on EN - english, FR - french, etc
        );


        // Initialise Payment Class
        $box = new Cryptobox ($options);
        // new bitcoin hashpower query

         $hash = new BitHash();
         $hash->hash = $request->hash;
         $hash->user_id = Auth::guard('user')->id();
         $hash->order_id = $orderID;
         $hash->confirmed = 0;
         $hash->referral_code = $request->code;
         $hash->life = $settings->hash_life;
         $hash->remained_day = Carbon::now()->diffInDays(Carbon::now()->addYears($hash->life));
         $hash->save();

        $settings->update(['available_th'=>$settings->available_th - $hash->hash]);
        $settings->save();

        $mining = new Mining();
        $mining->mined_btc = 0;
        $mining->mined_usd = 0;
        $mining->user_id = Auth::guard('user')->id();
        $mining->order_id = $orderID;
        $mining->block = 1;
        $mining->save();

        $trans = new Transaction();
        $trans->amount_btc = $amount;
        $trans->code = $orderID;
        $trans->status = 'unpaid';
        $trans->user_id = Auth::guard('user')->id();
        $trans->checkout = 'out';
        $trans->save();

//        return view('payment.makePayment',compact('orderID','box','coins','def_coin','def_language'));
        session()->put('paymentData',['orderID'=>$orderID,'box'=>$box,'coins'=>$coins,'def_coin'=>$def_coin,'def_language'=>$def_language]);
        return redirect()->route('payment',['locale'=>session('locale')]);
    }

    public function payment(){

        if(!session()->has('paymentData')){

            return 'Invalid Transaction';
        }
        return view('payment.makePayment');
    }

    /*
     * checks if latest payments are confirmed or not by using API
     */
    public function confirmPayment(Request $request){

        if(!$request->has('orderid')){

            return 'Cant find transaction !';
        }

        $trans = DB::connection('mysql')->table('crypto_payments')->where('orderID',$request->orderid)->first();

        if(is_null($trans)){

            return 'Transaction not found';
        }

        $all_keys = array(
            "bitcoin" => array(
                "public_key" => $this->publickey,
                "private_key" => $this->privatekey
            )
        );
        $orderID = $trans->orderID;
        $userFormat = 'SESSION';
        $userID = $trans->userID;
        $amount = $trans->amount;
        $public_key  = $all_keys['bitcoin']["public_key"];
        $private_key = $all_keys['bitcoin']["private_key"];
        $period			= "NOEXPIRY";	  // one time payment, not expiry
        $def_language	= "en";			  // default Language in payment box
        $options = array(
            "public_key"  	=> $public_key,	    // your public key from gourl.io
            "private_key" 	=> $private_key,	// your private key from gourl.io
            "webdev_key"  	=> "", 			    // optional, gourl affiliate key
            "orderID"     	=> $orderID, 		// order id or product name
            "userID"      	=> $userID, 	// unique identifier for every user
            "userFormat"  	=> $userFormat, 	// save userID in COOKIE, IPADDRESS, SESSION  or MANUAL
            "amount"   	  	=> number_format($amount,8), // product price in btc/bch/bsv/ltc/doge/etc OR setup price in USD below
            "amountUSD"   	=> 0,	    // we use product price in USD
            "period"      	=> $period, 	// payment valid period
            "language"	  	=> $def_language    // text on EN - english, FR - french, etc
        );

        $box = new Cryptobox ($options);
        $response = $box->get_json_values();
        if($response['confirmed'] == 1){

            DB::connection('mysql')->table('crypto_payments')->where('orderID',$request->orderid)->update(['txConfirmed'=>1]);

//            DB::connection('mysql')->table('settings')->update(['available_th'=> DB::table('settings')->first()->total_th - $request->hash]);

            // send email to user that transaction has been confirmed
            // change transaction status in admin panel
            return 1;
        }else{

            return 0;
        }



    }


    public function paymentCallback2(){



        if(!defined("CRYPTOBOX_WORDPRESS")) define("CRYPTOBOX_WORDPRESS", false);

// a. check if private key valid
        $valid_key = false;
        if (isset($_POST["private_key_hash"]) && strlen($_POST["private_key_hash"]) == 128 && preg_replace('/[^A-Za-z0-9]/', '', $_POST["private_key_hash"]) == $_POST["private_key_hash"])
        {
            $keyshash = array();
            $arr = explode("^", CRYPTOBOX_PRIVATE_KEYS);
            foreach ($arr as $v) $keyshash[] = strtolower(hash("sha512", $v));
            if (in_array(strtolower($_POST["private_key_hash"]), $keyshash)) $valid_key = true;
        }


// b. alternative - ajax script send gourl.io json data
        if (!$valid_key && isset($_POST["json"]) && $_POST["json"] == "1")
        {
            $data_hash = $boxID = "";
            if (isset($_POST["data_hash"]) && strlen($_POST["data_hash"]) == 128 && preg_replace('/[^A-Za-z0-9]/', '', $_POST["data_hash"]) == $_POST["data_hash"]) { $data_hash = strtolower($_POST["data_hash"]); unset($_POST["data_hash"]); }
            if (isset($_POST["box"]) && is_numeric($_POST["box"]) && $_POST["box"] > 0) $boxID = intval($_POST["box"]);

            if ($data_hash && $boxID)
            {
                $private_key = "";
                $arr = explode("^", CRYPTOBOX_PRIVATE_KEYS);
                foreach ($arr as $v) if (strpos($v, $boxID."AA") === 0) $private_key = $v;

                if ($private_key)
                {
                    $data_hash2 = strtolower(hash("sha512", $private_key.json_encode($_POST).$private_key));
                    if ($data_hash == $data_hash2) $valid_key = true;
                }
                unset($private_key);
            }

            if (!$valid_key) die("Error! Invalid Json Data sha512 Hash!");

        }


// c.
        if ($_POST) foreach ($_POST as $k => $v) if (is_string($v)) $_POST[$k] = trim($v);



// d.
        if (isset($_POST["plugin_ver"]) && !isset($_POST["status"]) && $valid_key)
        {
            echo "cryptoboxver_" . (CRYPTOBOX_WORDPRESS ? "wordpress_" . GOURL_VERSION : "php_" . CRYPTOBOX_VERSION);
            die;
        }


// e.
        if (isset($_POST["status"]) && in_array($_POST["status"], array("payment_received", "payment_received_unrecognised")) &&
            $_POST["box"] && is_numeric($_POST["box"]) && $_POST["box"] > 0 && $_POST["amount"] && is_numeric($_POST["amount"]) && $_POST["amount"] > 0 && $valid_key)
        {

            foreach ($_POST as $k => $v)
            {
                if ($k == "datetime") 						$mask = '/[^0-9\ \-\:]/';
                elseif (in_array($k, array("err", "date", "period")))		$mask = '/[^A-Za-z0-9\.\_\-\@\ ]/';
                else								$mask = '/[^A-Za-z0-9\.\_\-\@]/';
                if ($v && preg_replace($mask, '', $v) != $v) 	$_POST[$k] = "";
            }

            if (!$_POST["amountusd"] || !is_numeric($_POST["amountusd"]))	$_POST["amountusd"] = 0;
            if (!$_POST["confirmed"] || !is_numeric($_POST["confirmed"]))	$_POST["confirmed"] = 0;


            $dt			= Carbon::now();
//            $dt			= gmdate('Y-m-d H:i:s');
            $obj 		= run_sql("select paymentID, txConfirmed from crypto_payments where boxID = ".$_POST["box"]." && orderID = '".$_POST["order"]."' && userID = '".$_POST["user"]."' && txID = '".$_POST["tx"]."' && amount = ".$_POST["amount"]." && addr = '".$_POST["addr"]."' limit 1");


            $paymentID		= ($obj) ? $obj->paymentID : 0;
            $txConfirmed	= ($obj) ? $obj->txConfirmed : 0;

            // Save new payment details in local database
            if (!$paymentID)
            {
                $sql = "INSERT INTO crypto_payments (boxID, boxType, orderID, userID, countryID, coinLabel, amount, amountUSD, unrecognised, addr, txID, txDate, txConfirmed, txCheckDate, recordCreated)
				VALUES (".$_POST["box"].", '".$_POST["boxtype"]."', '".$_POST["order"]."', '".$_POST["user"]."', '".$_POST["usercountry"]."', '".$_POST["coinlabel"]."', ".$_POST["amount"].", ".$_POST["amountusd"].", ".($_POST["status"]=="payment_received_unrecognised"?1:0).", '".$_POST["addr"]."', '".$_POST["tx"]."', '".$_POST["datetime"]."', ".$_POST["confirmed"].", '$dt', '$dt')";

                $paymentID = run_sql($sql);

                $box_status = "cryptobox_newrecord";
            }
            // Update transaction status to confirmed
            elseif ($_POST["confirmed"] && !$txConfirmed)
            {
                $sql = "UPDATE crypto_payments SET txConfirmed = 1, txCheckDate = '$dt' WHERE paymentID = $paymentID LIMIT 1";
                run_sql($sql);

                $box_status = "cryptobox_updated";
            }
            else
            {
                $box_status = "cryptobox_nochanges";
            }


            /**
             *  User-defined function for new payment - cryptobox_new_payment(...)
             *  For example, send confirmation email, update database, update user membership, etc.
             *  You need to modify file - cryptobox.newpayment.php
             *  Read more - https://gourl.io/api-php.html#ipn
             */
            if (in_array($box_status, array("cryptobox_newrecord", "cryptobox_updated")) && function_exists('cryptobox_new_payment'))
                $this->cryptobox_new_payment($paymentID, $_POST, $box_status);

        }

        else
            $box_status = "Only POST Data Allowed";


        echo $box_status; // don'

    }
/*
 * this function will executed after callback function
 */
    public function cryptobox_new_payment($paymentID = 0, $payment_details = array(), $box_status = ""){

        $orderID = DB::connection('mysql')->table('crypto_payments')->where('PaymentID',$paymentID)->first()->orderID;
        $paymentBox = DB::connection('mysql')->table('crypto_payments')->where('PaymentID',$paymentID)->first();
        $hashPower = BitHash::where('order_id',$orderID)->first();
        $mining = Mining::where('order_id',$orderID)->first();
        $settings = Setting::first();
        if(is_null($hashPower) || is_null($mining)){

            \Log::warning('PaymentID : '.$orderID);
        }else{

            $user = $hashPower->user;
            if($payment_details['confirmed'] == 1){ //second callback
                $hashPower->update(['confirmed'=>1]);
                $hashPower->save();
                $mining->update(['block' => 0]);
                $mining->save();
        // update created transaction record
                 DB::connection('mysql')->table('transactions')->where('code',$orderID)->update([
                    'addr'=>$paymentBox->addr,
                    'country'=>$paymentBox->countryID,
                     'status' => 'paid'
                ]);

                $trans = DB::connection('mysql')->table('transactions')->where('code',$orderID)->first();

                Mail::send('email.paymentConfirmed',['hashPower'=>$hashPower,'trans'=>$trans],function($message) use($user){
                    $message->from (env('Admin_Mail'));
                    $message->to ($user->email);
                    $message->subject ('Payment Confirmed');
                });

//                $referralUser = DB::connection('mysql')->table('expired_codes')->where('user_id',$user->id)->where('used',0)->first();
                $referralCode = $hashPower->referral_code;
                $referralQuery = Referral::where('code',$referralCode)->first();
                    // if any referral code used for hash ower purchasing
                    if(!is_null($referralCode)){

                        $codeCaller = User::where('code',$referralCode)->first();// code caller user
                        /*
                         * reward new th to the code caller
                         * ============================
                         * increasing share level
                         */

                        $sharings = Sharing::all()->toArray();
                        $total_sharing_num = $referralQuery->total_sharing_num;

                        for($i=0 ; $i<count($sharings); $i++){

                            if($sharings[$i]['sharing_number'] < $total_sharing_num ){

                                if($i == count($sharings) - 1){

                                    $referralQuery->update([
                                        'share_level' => $sharings[$i]['level']
                                    ]);
                                    $referralQuery->save();
                                }else{
                                    $referralQuery->update([
                                        'share_level' => $sharings[$i+1]['level']
                                    ]);
                                    $referralQuery->save();
                                }

                            }
                        }

                        $share_level = $referralQuery->share_level;
                        $share_value = DB::connection('mysql')->table('sharings')->where('level',$share_level)->first()->value;
                        $hash = new BitHash();
                        $hash->hash = $hashPower->hash * $share_value;
                        $hash->user_id = $codeCaller->id;
                        $hash->order_id = 'referral';
                        $hash->confirmed = 1;
                        $hash->life = $settings->hash_life;
                        $hash->remained_day = Carbon::now()->diffInDays(Carbon::now()->addYears($hash->life));
                        $hash->save();
                        $mining = new Mining();
                        $mining->mined_btc = 0;
                        $mining->mined_usd = 0;
                        $mining->user_id = $codeCaller->id;
                        $mining->order_id = 'referral';
                        $mining->block = 0;
                        $mining->save();

                        //  =======================================
                    }

                }else{

                DB::connection('mysql')->table('expired_codes')->where('user_id',$user->id)->where('used',0)->update(['used'=>1]);
                //check if any referral code used

                Mail::send('email.paymentReceived',[],function($message)use($user){
                    $message->from (env('Admin_Mail'));
                    $message->to ($user->email);
                    $message->subject ('Payment Received !');
                });
            }


        }


        /** .............
        .............

         *
        PLACE YOUR CODE HERE

        Update database with new payment, send email to user, etc
        Please note, all received payments store in your table `crypto_payments` also
        See - https://gourl.io/api-php.html#payment_history
        .............
        .............
        For example, you have own table `user_orders`...
        You can use function run_sql() from cryptobox.class.php ( https://gourl.io/api-php.html#run_sql )

        .............
        // Save new Bitcoin payment in database table `user_orders`
        $recordExists = run_sql("select paymentID as nme FROM `user_orders` WHERE paymentID = ".intval($paymentID));
        if (!$recordExists) run_sql("INSERT INTO `user_orders` VALUES(".intval($paymentID).",'".$payment_details["user"]."','".$payment_details["order"]."',".floatval($payment_details["amount"]).",".floatval($payment_details["amountusd"]).",'".$payment_details["coinlabel"]."',".intval($payment_details["confirmed"]).",'".$payment_details["status"]."')");

        .............
        // Received second IPN notification (optional) - Bitcoin payment confirmed (6+ transaction confirmations)
        if ($recordExists && $box_status == "cryptobox_updated")  run_sql("UPDATE `user_orders` SET txconfirmed = ".intval($payment_details["confirmed"])." WHERE paymentID = ".intval($paymentID));
        .............
        .............

        // Onetime action when payment confirmed (6+ transaction confirmations)
        $processed = run_sql("select processed as nme FROM `crypto_payments` WHERE paymentID = ".intval($paymentID)." LIMIT 1");
        if (!$processed && $payment_details["confirmed"])
        {
        // ... Your code ...

        // ... and update status in default table where all payments are stored - https://github.com/cryptoapi/Payment-Gateway#mysql-table
        $sql = "UPDATE crypto_payments SET processed = 1, processedDate = '".gmdate("Y-m-d H:i:s")."' WHERE paymentID = ".intval($paymentID)." LIMIT 1";
        run_sql($sql);
        }

        .............

         */

        // Debug - new payment email notification for webmaster
        // Uncomment lines below and make any test payment
        // --------------------------------------------
        // $email = "....your email address....";
        // mail($email, "Payment - " . $paymentID . " - " . $box_status, " \n Payment ID: " . $paymentID . " \n\n Status: " . $box_status . " \n\n Details: " . print_r($payment_details, true));




        return true;

    }

    /*
     * Send Users their daily shares
     */

    public function redeem(Request $request){


        $user = User::where('code',$request->code)->first();
        if(is_null($user)){
            return ['type'=>'error','body'=>'user not found'];
        }
//        $btcSum = Mining::where('user_id',$user->id)->where('block',0)->sum('mined_btc');
        $btcSum = User::userPending($user);
        $wallet = DB::connection('mysql')->table('wallets')->where('user_id',$user->id)->where('active',1)->first();
        if(is_null($wallet)){
            return ['type'=>'error','body'=>'wallet not found or is not active'];
        }

        $trans = new Transaction();
        $trans->addr = $wallet->addr;
        $trans->code = strtoupper(uniqid());
        try{

            $trans->country = strtolower(Location::get(Helpers::userIP())->countryCode);
        }catch (\Exception $exception){
            $trans->country = 'ir';
        }
        $trans->amount_btc = $btcSum;
        $trans->status = 'paid';
        $trans->user_id = $user->id;
        $trans->checkout = 'in';
        $trans->save();

        $redeem = new Redeem();
        $redeem->amount_btc = $btcSum;
        $redeem->user_id = $user->id;
        $redeem->code = $trans->code;
        $redeem->addr = $wallet->addr;
        $redeem->save();

//
//        $data = [
//            'amount'=>$btcSum,
//            'user'=> $user->name,
//            'email'=> $user->email,
//            'user_wallet' => $wallet->addr,
//            'country' =>  $trans->country,
//            'transId' => $trans->code
//        ];
        try{


            $data = ['trans'=>$trans,'user'=> $user,'email'=>$user->email];
            Mail::send('email.checkout',$data , function ($message) use ($data) {
                $message->from(env('Admin_Mail'));
                $message->to($data['email']);
                $message->subject('پرداخت بیتکوین');

            });
        }catch (\Exception $exception){

        }


        return ['type'=>'success','body'=>'Transaction Paid'];

    }

    public function checkPaymentReceived(Request $request){

        $query = DB::connection('mysql')->table('crypto_payments')->where('orderID',$request->orderID)->first();

        if(is_null($query)){
            return 404;
        }else{
            return 200;
        }
    }
}
