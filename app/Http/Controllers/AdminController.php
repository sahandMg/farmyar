<?php

namespace App\Http\Controllers;

use App\BitHash;
use App\CryptpBox\lib\Cryptobox;
use App\hashRate;
use App\Mining;
use App\MiningReport;
use App\Redeem;
use App\Setting;
use App\Transaction;
use App\User;
use App\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;


class AdminController extends Controller
{

    public $apikey;
    public $publickey;
    public $privatekey;

    public function __construct()
    {
        $settings = DB::connection('mysql')->table('settings')->first();
        $this->apikey = $settings->apikey;
        $this->publickey = $settings->publickey;
        $this->privatekey = $settings->privatekey;
    }

    public function login()
    {

        return view('admin.login');
    }

    public function post_login(Request $request)
    {

        $this->validate($request, [
            'email' => 'required',
            'password' => 'required|min:6',
            'captcha' => 'required|captcha'
        ]);

        if (Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password])) {

            return redirect()->route('adminHome',['locale'=>session('locale')]);
        } else {

            return redirect()->back()->with(['error' => 'wrong email or password']);
        }
    }

    public function index()
    {

        return view('admin.index');
    }

    public function transactions()
    {

        $transactions = DB::connection('mysql')->table('transactions')->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.transactions', compact('transactions'));
    }

    public function adminGetUsersList()
    {

        $users = User::get();
        return view('admin.users.list', compact('users'));
    }

    /*==================================================================================
     * Ajax request to admin transaction page
     */

    public function blockUser(Request $request)
    {

        $user = User::where('code', $request->code)->first();
        $user->update(['block' => !$user->block]);
        $user->save();
    }

    /*
     * gets redeem requests
     */
    public function adminRedeems()
    {

        return view('admin.redeem');
    }

    public function adminGetRedeems()
    {

        $transactions = Redeem::orderBy('created_at','desc')->get();
        foreach ($transactions as $key => $transaction){
             $name = User::find($transaction->user_id)->name;
            $transaction->name = $name;
        }
        return $transactions;
    }

    /*
     * gets latest user buying
     */
    public function getTransactions()
    {



    }

    private function getConfirmedPayment()
    {


        $all_keys = array(
            "bitcoin" => array(
                "public_key" => $this->publickey,
                "private_key" => $this->privatekey
            )
        );
        $transactions = DB::connection('mysql')->table('crypto_payments')->get();

        foreach ($transactions as $transaction) {


            $orderID = $transaction->orderID;
            $userFormat = 'SESSION';
            $userID = $transaction->userID;
            $amount = $transaction->amount;
            $public_key = $all_keys['bitcoin']["public_key"];
            $private_key = $all_keys['bitcoin']["private_key"];
            $period = "NOEXPIRY";      // one time payment, not expiry
            $def_language = "en";              // default Language in payment box
            $options = array(
                "public_key" => $public_key,        // your public key from gourl.io
                "private_key" => $private_key,    // your private key from gourl.io
                "webdev_key" => "",                // optional, gourl affiliate key
                "orderID" => $orderID,        // order id or product name
                "userID" => $userID,    // unique identifier for every user
                "userFormat" => $userFormat,    // save userID in COOKIE, IPADDRESS, SESSION  or MANUAL
                "amount" => $amount, // product price in btc/bch/bsv/ltc/doge/etc OR setup price in USD below
                "amountUSD" => 0,        // we use product price in USD
                "period" => $period,    // payment valid period
                "language" => $def_language    // text on EN - english, FR - french, etc
            );

            $box = new Cryptobox ($options);
            $response = $box->get_json_values();
            if ($response['confirmed'] == 1) {

                DB::connection('mysql')->table('crypto_payments')->where('orderID', $transaction->orderID)->update(['txConfirmed' => 1]);
                $mining = Mining::where('order_id', $transaction->orderID)->first();
                if (!is_null($mining)) {


                    $mining->update(['block' => 0]);
                    $mining->save();
                    // send email to user that transaction has been confirmed
                    // change transaction status in admin panel
                    return 1;
                }
            }
        }
    }

    public function adminCheckout()
    {

        $users = User::all();


        return view('admin.users.checkout', compact('users'));
    }

    public function LoginAsUser(Request $request)
    {

        $params = $request->all();
        if (!isset($params['email'])) {

            return redirect()->back()->with(['error' => 'send an email address']);
        }
        $user = User::where('email', $params['email'])->first();
        Auth::guard('admin')->logout();
        Auth::guard('user')->login($user);
        return redirect()->route('dashboard',['locale'=>session('locale')]);

    }

    public function collaboration(Request $request)
    {
        $id = $request->id;
        return view('admin.users.collaboration', compact('id'));
    }

    // get data from form in admin panel collaboration and create mining record for 30_70 users
    public function post_collaboration(Request $request)
    {
        $user = User::where('name', $request->name)->first();
        $hashRecord = new BitHash();
        $hashRecord->hash = $request->th;
        $hashRecord->user_id = $user->id;
        $hashRecord->order_id = '30_70_' . strtoupper(uniqid());
        $hashRecord->confirmed = 1;
        $hashRecord->life = 2;
        $hashRecord->remained_day = 720 - intval($request->remainedDay);
        $hashRecord->created_at = Carbon::now()->subDays(intval($request->remainedDay));
        $hashRecord->save();

        $mining = new Mining();
        $mining->mined_btc = $request->mined_btc;
        $mining->mined_usd = 0;
        $mining->user_id = $user->id;
        $mining->order_id = $hashRecord->order_id;
        $mining->block = 0;
        $mining->save();

        $trans = new Transaction();
        $trans->code = $hashRecord->order_id;
        $trans->status = 'paid';
        $trans->checkout = 'out';
        $trans->amount_toman = $request->price;
        $trans->user_id = $user->id;
        $trans->country = 'ir';
        $trans->created_at = Carbon::now()->subDays(intval($request->remainedDay));
        $trans->save();


        $user->update(['plan_id' => 1]);

        $data = ['trans'=>$trans,'hashPower'=>$hashRecord,'email'=>$user->email];
        Mail::send('email.paymentConfirmed',$data , function ($message) use ($data) {
            $message->from(env('Admin_Mail'));
            $message->to($data['email']);
            $message->subject('خرید تراهش');

        });
        // getting bitcoin price in dollar
//        $options = array('http' => array('method' => 'GET'));
//        $context = stream_context_create($options);
//        $contents = file_get_contents('https://www.blockonomics.co/api/price?currency=USD', false, $context);
//        $bitCoinPrice = json_decode($contents);
        // create mining report records to show in dashboard
//        $miningReport = new MiningReport();
//        $miningReport->mined_btc = $request->mined_btc;
//        $miningReport->mined_usd = $request->mined_btc * $bitCoinPrice->price;
//        $miningReport->user_id = $user->id;
//        $miningReport->order_id = $hashRecord->order_id;
//        $miningReport->save();

        return redirect()->back()->with(['message' => 'رکورد اضافه شد']);

    }

    public function adminLogout()
    {

        Auth::guard('admin')->logout();
        return redirect()->route('index',['locale'=>session('locale')]);
    }

    public function userSetting($id)
    {

        $user = User::find($id);
        return view('admin.users.setting', compact('user'));
    }

    public function post_userSetting(Request $request)
    {

        $user = User::find($request->id);

        if (!is_null($request->password)) {

            $user->update(['password' => Hash::make($request->password)]);
        }
        if (!is_null($request->address)) {

            if (is_null($user->wallet)) {

                $wallet = new Wallet();
                $wallet->addr = $request->address;
                $wallet->user_id = $user->id;
                $wallet->active = 1;
                $wallet->save();
            } else {

                $user->wallet->update(['addr' => $request->address]);
            }
        }
        if (!is_null($request->planid)) {

            $user->update(['plan_id' => $request->planid]);
        }

        return redirect()->back()->with(['message' => 'Form Edited']);

    }

    public function siteSetting()
    {

        $setting = Setting::first();

        return view('admin.setting', compact('setting'));
    }

    public function post_siteSetting(Request $request)
    {

        $setting = Setting::first();
        !is_null($request->total_th) ? $setting->update(['total_th' => $request->total_th]) : null;
        !is_null($request->usd_per_hash) ? $setting->update(['usd_per_hash' => $request->usd_per_hash]) : null;
        !is_null($request->usd_toman) ? $setting->update(['usd_toman' => $request->usd_toman]) : null;
        !is_null($request->maintenance_fee_per_th_per_day) ? $setting->update(['maintenance_fee_per_th_per_day' => $request->maintenance_fee_per_th_per_day]) : null;
        !is_null($request->bitcoin_income_per_month_per_th) ? $setting->update(['bitcoin_income_per_month_per_th' => $request->bitcoin_income_per_month_per_th]) : null;
        !is_null($request->available_th) ? $setting->update(['available_th' => $request->available_th]) : null;
        !is_null($request->sharing_discount) ? $setting->update(['sharing_discount' => $request->sharing_discount]) : null;
        !is_null($request->hash_life) ? $setting->update(['hash_life' => $request->hash_life]) : null;
        !is_null($request->minimum_redeem) ? $setting->update(['minimum_redeem' => $request->minimum_redeem]) : null;
        !is_null($request->zarrin_active) ? $setting->update(['zarrin_active' => $request->zarrin_active]) : null;
        !is_null($request->paystar_active) ? $setting->update(['paystar_active' => $request->paystar_active]) : null;
        !is_null($request->alarms) ? $setting->update(['alarms' => $request->alarms]) : null;

        return redirect()->back()->with(['message' => 'Form Updated']);
    }

    public function message()
    {

        return view('admin.message');
    }

    public function post_message(Request $request)
    {

        $message = DB::connection('mysql')->table('messages')->where('id',$request->id)->first();
        $data = ['body'=>$request->body,'name'=>$message->name,'email'=>$message->email];
        Mail::send('email.admin2userReply',$data , function ($message) use ($data) {
            $message->from(env('Admin_Mail'));
            $message->to($data['email']);
            $message->subject('User Reply');

        });

        return redirect()->route('AdminMessage',['locale'=>session('locale')])->with(['message'=>'Message Sent']);

    }

    public function post_deleteMessage(Request $request){

        $message = DB::connection('mysql')->table('messages')->where('id',$request->id)->delete();
        return 200;
    }

    public function chartData(){

        $data = [];
        $daysNumber = date('t');
        $queries = hashRate::orderBy('created_at','desc')->get()->take($daysNumber);
        $offset = 2;
        for($i = $offset ; $i <= $daysNumber ; $i++){
            $time = Carbon::now()->subDay($i);

                if(count($queries) > 0){
                    try{

                        $data[$i-$offset] = ['time'=>$time->format('d M') , 'mined'=> $queries[$i-$offset]->mined_btc];
                    }catch (\Exception $exception){
                        $data[$i-$offset] = ['time'=>$time->format('d M') , 'mined'=> 0];
                    }
                }
        }
        $data = array_reverse( array_values($data)) ;
        return $data;
    }

    public function chartDataProfit(){

        $data = [];
        $daysNumber = date('t');
        $queries = hashRate::orderBy('created_at','desc')->get()->take($daysNumber);
        $offset = 2;
        for($i = $offset ; $i <= $daysNumber ; $i++){
            $time = Carbon::now()->subDay($i);
            if(count($queries) > 0){
                try{

                    $data[$i-$offset] = ['time'=>$time->format('d M') , 'benefit'=> $queries[$i-$offset]->today_benefit];
                }catch (\Exception $exception){
                    $data[$i-$offset] = ['time'=>$time->format('d M') , 'benefit'=> 0];
                }
            }
        }
        $data = array_reverse( array_values($data)) ;
        return $data;
    }

    public function getLogs(){

        $logs = DB::connection('mysql')->table('logs')->orderBy('id','desc')->where(function($query){
            $query->where('message','!=','');
        })->paginate(20);

        return view('admin.logs',compact('logs'));
    }
    public function hardwareOrders(){

        $orders = DB::connection('mysql')->table('remote_orders')->orderBy('id','desc')->get();
        return view('admin.users.hardwareOrders',compact('orders'));
    }
}
