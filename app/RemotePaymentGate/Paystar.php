<?php

namespace App\RemotePaymentGate;

use App\Crawling\Dolar;
use App\Http\Helpers;
use App\RemotePlan;
use App\RemoteTransaction;
use App\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Stevebauman\Location\Facades\Location;

class Paystar
{
    public $request;
    protected $connection = 'mysql';
    public function __construct($request)
    {
        $this->request = $request;
    }

    public function create(){

        /*
   * Paystar Api
   */
        $settings = Setting::first();
        try{

            $country = strtolower(Location::get(Helpers::userIP())->countryCode);
        }catch (\Exception $ex){
            $country = 'ir';
        }

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $amount = $settings->remote_fee * $this->request->months * $this->request->devices;
        $fields = array(
            'amount' => $amount,
            'pin' => $settings->paystar_pin,
            'description' => 'هاستینگ ارز دیجیتال HashBazaar',
            'callback' => 'https://hashbazaar.com/api/remote/paystar/callback',
            'ip'=> $ip
        );
        $url = 'https://paystar.ir/api/create/';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        if(is_numeric($result)){

            return 404;

        }
        $trans = new RemoteTransaction();
        $trans->code = $result;
        $trans->status = 'unpaid';
        $trans->amount = $amount;
        $trans->country = $country;
        $trans->user_id = Auth::guard('remote')->id();
        $trans->save();
        Session::put('months',$this->request->months);
        Session::put('devices',$this->request->devices);
        return $result;
    }

    public function verify(){

        $transactionId = $this->request->transid;
        $trans = RemoteTransaction::where('code',$transactionId)->first();
        if(is_null($trans)){
            return 'کد تراکنش نادرست است';
        }
        $settings = Setting::first();

        $url = 'https://paystar.ir/api/verify/'; // don't change
        $fields = array(
            'amount' => $trans->amount,
            'pin' => $settings->paystar_pin,
            'transid' => $transactionId,
        );

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        if($result == 1){

            $this->PaystarPaymentConfirm($trans);

        return redirect()->route('RemotePaymentSuccess',['locale'=>App::getLocale(),'transid'=>$trans->code]);

        }else{

            return redirect()->route('RemotePaymentCanceled',['locale'=> App::getLocale(),'transid'=>$trans->code]);
        }
    }
    private function PaystarPaymentConfirm($trans){

        $transactionId = $trans->code;
        $user = $trans->user;
        $orderID = $transactionId;
        $settings = Setting::first();

        DB::connection('mysql')->table('remote_transactions')->where('code', $orderID)->update([
            'country' => $user->country,
            'status' => 'paid'
        ]);
        $remotePlan = new RemotePlan();
        $remotePlan->trans_id = $trans->id;
        $remotePlan->user_id = Auth::guard('remote')->id();
        $remotePlan->months = Session::get('months');
        $remotePlan->devices = Session::get('devices');
        $remotePlan->save();
        // TODO payment paystar mail page
//
        Mail::send('email.remote.paymentConfirmed', ['plan' => $remotePlan, 'trans' => $trans], function ($message) use ($user) {
            $message->from(env('Sales_Mail'));
            $message->to($user->email);
            $message->subject('Payment Confirmed');
        });

        Mail::send('email.newTrans', [], function ($message) use ($user) {
            $message->from(env('Sales_Mail'));
            $message->to(env('Admin_Mail'));
            $message->subject('New Payment');
        });
    }
}
