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

class ZarrinPal
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
        $data = array('MerchantID' => $settings->zarrin_pin,
            'Amount' => $amount,
            'Email' =>  Auth::guard('remote')->user()->email,
            'CallbackURL' => env('Zarrin_Remote_hardware_Callback'),
            'Description' => 'فروشگاه اینترنتی قطعات الکترونیکی');
        $jsonData = json_encode($data);
        $ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/PaymentRequest.json');
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        $result = curl_exec($ch);
        $err = curl_error($ch);
        $result = json_decode($result, true);
        curl_close($ch);
        if ($err) {
            return 404;
        } else {
            if ($result["Status"] == '100' ) {

                $trans = new RemoteTransaction();
                $trans->code = 'Zarrin_'.strtoupper(uniqid());
                $trans->status = 'unpaid';
                $trans->amount = $amount;
                $trans->country = $country;
                $trans->authority = $result['Authority'];
                $trans->user_id = Auth::guard('remote')->id();
                $trans->save();
                return [$result,$trans->id];
            } else {
                return 404;
            }
        }

    }

    public function verify(){

        $transactionId = $this->request->Authority;
        $trans = RemoteTransaction::where('authority',$transactionId)->first();
        if(is_null($trans)){
            return 'کد تراکنش نادرست است';
        }
        $settings = Setting::first();

        $data = array('MerchantID' => $settings->zarrin_pin, 'Authority' => $transactionId, 'Amount'=>$trans->amount);

        $jsonData = json_encode($data);
        $ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/PaymentVerification.json');
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            if ($result['Status'] == '100') {

                $this->ZarrinPaymentConfirm($trans);

                return redirect()->route('RemotePaymentSuccess',['locale'=>App::getLocale(),'transid'=>$trans->code]);

            } else {

                return redirect()->route('RemotePaymentCanceled', ['locale'=>App::getLocale(),'transid' => $trans->code]);
            }
        }
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
