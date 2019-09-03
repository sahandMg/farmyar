<?php
namespace App\RemotePaymentGate;

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

class ZarrinPalTest
{

    public $request;
    protected $connection = 'mysql';

    public function __construct($request)
    {
        $this->request = $request;
    }
    public function create(){

        $settings = Setting::first();
        try{

            $country = strtolower(Location::get(Helpers::userIP())->countryCode);
        }catch (\Exception $ex){
            $country = 'ir';
        }
        $amount = $settings->remote_fee * $this->request->months * $this->request->devices;

                $trans = new RemoteTransaction();
                $trans->code = 'Zarrin_'.strtoupper(uniqid());
                $trans->status = 'unpaid';
                $trans->amount = $amount;
                $trans->country = $country;
                $trans->authority = "TEST__000000000000000000210312000000".rand(0,100);
                session(['authority'=> $trans->authority]);
                $trans->user_id = Auth::guard('remote')->id();
                $trans->save();
                Session::put('months',$this->request->months);
                Session::put('devices',$this->request->devices);
                return 200;

    }

    public function verify(){

        $transactionId = session('authority');
        $trans = RemoteTransaction::where('authority',$transactionId)->first();
        if(is_null($trans)){
            return 'کد تراکنش نادرست است';
        }
                $this->ZarrinPaymentConfirm($trans);

                return redirect()->route('RemotePaymentSuccess',['locale'=>App::getLocale(),'transid'=>$trans->code]);

//                return redirect()->route('RemotePaymentCanceled', ['locale'=>App::getLocale(),'transid' => $trans->code]);
    }
    private function ZarrinPaymentConfirm($trans)
    {
        $transactionId = $trans->code;
        $orderID = $transactionId;
        $user = $trans->user;
        // update created transaction record
        DB::connection('mysql')->table('remote_transactions')->where('code', $orderID)->update([
            'status' => 'paid'
        ]);
        $remotePlan = new RemotePlan();
        $remotePlan->trans_id = $trans->id;
        $remotePlan->user_id = Auth::guard('remote')->id();
        $remotePlan->months = Session::get('months');
        $remotePlan->devices = Session::get('devices');
        $remotePlan->save();
        // TODO Transaction Mail
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
