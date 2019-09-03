<?php

namespace App\RemoteHardwareGate;

use App\Crawling\Dolar;
use App\Http\Helpers;
use App\RemoteOrder;
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

class PaystarTest
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

        $amount = $settings->hardware_fee ;

        $trans = new RemoteTransaction();
        $trans->code = 'Paystar_8921371937298191';
        $trans->status = 'unpaid';
        $trans->amount = $amount;
        $trans->country = $country;
        $trans->user_id = Auth::guard('remote')->id();
        $trans->save();

        return [200,$trans->id];
    }

    public function verify(){

        $transactionId = 'Paystar_8921371937298191';
        $trans = RemoteTransaction::where('code',$transactionId)->first();
        if(is_null($trans)){
            return 'کد تراکنش نادرست است';
        }
            $this->PaystarPaymentConfirm($trans);

        return redirect()->route('RemotePaymentSuccess',['locale'=>App::getLocale(),'transid'=>$trans->code]);

    }
    private function PaystarPaymentConfirm($trans){

        $transactionId = 'Paystar_8921371937298191';
        $user = $trans->user;
        $orderID = $transactionId;
        DB::connection('mysql')->table('remote_transactions')->where('code', $orderID)->update([
            'country' => $user->country,
            'status' => 'paid'
        ]);
        // TODO payment paystar mail page
//        Mail::send('email.remote.paymentConfirmed', ['plan' => $remotePlan, 'trans' => $trans], function ($message) use ($user) {
//            $message->from(env('Sales_Mail'));
//            $message->to($user->email);
//            $message->subject('Payment Confirmed');
//        });
//
//        Mail::send('email.newTrans', [], function ($message) use ($user) {
//            $message->from(env('Sales_Mail'));
//            $message->to(env('Admin_Mail'));
//            $message->subject('New Payment');
//        });
//

    }
}
