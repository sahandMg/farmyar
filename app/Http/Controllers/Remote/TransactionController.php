<?php

namespace App\Http\Controllers\Remote;

use App\RemotePaymentGate\Paystar;
use App\RemotePaymentGate\ZarrinPal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct()
    {

        $this->middleware('remoteAuth');
    }
    public function successPayment($lang,$id){
        $trans = DB::connection('mysql')->table('remote_transactions')->where('code',$id)->first();
        if(is_null($trans)){
            return 'تراکنش نامعتبر';
        }
        $code = $trans->code;
        return view('remote.payment.success',compact('code'));
    }

    public function failedPayment($lang,$id){
        $trans = DB::connection('mysql')->table('remote_transactions')->where('code',$id)->first();
        if(is_null($trans)){
            return 'تراکنش نامعتبر';
        }
        $code = $trans->code;
        return view('remote.payment.failed',compact('code'));
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
}
