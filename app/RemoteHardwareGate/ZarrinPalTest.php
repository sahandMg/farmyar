<?php
namespace App\RemoteHardwareGate;

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
        $amount = $settings->hardware_fee ;

                $trans = new RemoteTransaction();
                $trans->code = 'Zarrin_'.strtoupper(uniqid());
                $trans->status = 'unpaid';
                $trans->amount = $amount;
                $trans->country = $country;
                $trans->authority = "TEST__000000000000000000210312000000".rand(0,100);
                session(['authority'=> $trans->authority]);
                $trans->user_id = Auth::guard('remote')->id();
                $trans->save();

        return [200,$trans->id];

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
        // update created transaction record
        DB::connection('mysql')->table('remote_transactions')->where('code', $orderID)->update([
            'status' => 'paid'
        ]);
        $user = $trans->user;
        // TODO Transaction Mail
        Mail::send('email.remote.hardwareConfirmed', ['trans' => $trans], function ($message) use ($user) {
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
