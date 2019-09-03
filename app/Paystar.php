<?php
/**
 * Created by PhpStorm.
 * User: Sahand
 * Date: 5/8/19
 * Time: 1:52 PM
 */

namespace App;


use App\Crawling\Dolar;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
        $dollar = new Dolar();
        $dollarPriceInToman = $settings->usd_toman;
        $discount = $this->request['discount'];
        $hash = $this->request['hash'];
        if(Auth::guard('user')->user()->plan->id == 3){
            $amount = ($settings->usd_per_hash * $hash * (1- $discount)  + env('contractDays') * $settings->maintenance_fee_per_th_per_day) * $dollarPriceInToman;
        }else if(Auth::guard('user')->user()->plan->id == 2){
            $amount = $settings->usd_per_hash * $hash * (1- $discount)* $dollarPriceInToman;
        }
        $referralCode = $this->request['code'];

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $fields = array(
            'amount' => $amount,
            'pin' => $settings->paystar_pin,
            'description' => 'هاستینگ ارز دیجیتال HashBazaar',
            'callback' => 'https://hashbazaar.com/api/paystar/callback',
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

        // create database record
        $custom_code = CustomCode::where('code',$referralCode)->first();
        $hashRecord = new BitHash();
        $hashRecord->hash = $hash;
        $hashRecord->user_id = Auth::guard('user')->id();
        $hashRecord->order_id = $result;
        $hashRecord->confirmed = 0;
        // a custom code is not involved in affiliate
        if(isset($custom_code)){
            $hashRecord->referral_code = null;
        }else{
            $hashRecord->referral_code = $referralCode;
        }
        $hashRecord->life = $settings->hash_life;
        $hashRecord->remained_day = Carbon::now()->diffInDays(Carbon::now()->addYears($hashRecord->life));
        $hashRecord->save();


        $mining = new Mining();
        $mining->mined_btc = 0;
        $mining->mined_usd = 0;
        $mining->user_id = Auth::guard('user')->id();
        $mining->order_id = $result;
        $mining->block = 1;
        $mining->save();

        $trans = new Transaction();
        $trans->code = $result;
        $trans->status = 'unpaid';
        $trans->amount_toman = $amount;
        $trans->user_id = Auth::guard('user')->id();
        $trans->checkout = 'out';
        $trans->country = Auth::guard('user')->user()->country;
        $trans->save();

        return $result;
    }

    public function verify(){

        $transactionId = $this->request->transid;
        $trans = Transaction::where('code',$transactionId)->first();
        if(is_null($trans)){
            return 'کد تراکنش نادرست است';
        }
        $settings = Setting::first();

        $url = 'https://paystar.ir/api/verify/'; // don't change
        $fields = array(
            'amount' => $trans->amount_toman,
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

            return redirect()->route('PaymentSuccess');

        }else{

            return redirect()->route('PaymentCanceled',['transid'=>$transactionId]);
        }
    }
    private function PaystarPaymentConfirm($trans){

        $transactionId = $trans->code;
        $user = $trans->user;
        $orderID = $transactionId;
        $hashPower = BitHash::where('order_id', $orderID)->first();
        $mining = Mining::where('order_id', $orderID)->first();
        $settings = Setting::first();
        $hashPower->update(['confirmed' => 1]);
        $hashPower->save();
        $mining->update(['block' => 0]);
        $mining->save();
        // update created transaction record
        DB::connection('mysql')->table('transactions')->where('code', $orderID)->update([
            'country' => $user->country,
            'status' => 'paid'
        ]);
        $settings->update(['available_th'=>$settings->available_th - $hashPower->hash]);
        $settings->save();

        Mail::send('email.paymentConfirmed', ['hashPower' => $hashPower, 'trans' => $trans], function ($message) use ($user) {
            $message->from('Admin@HashBazaar');
            $message->to($user->email);
            $message->subject('Payment Confirmed');
        });

        Mail::send('email.newTrans', [], function ($message) use ($user) {
            $message->from('Admin@HashBazaar');
            $message->to('Admin@HashBazaar');
            $message->subject('New Payment');
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
}
