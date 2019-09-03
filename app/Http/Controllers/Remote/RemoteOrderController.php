<?php

namespace App\Http\Controllers\Remote;

use App\RemoteOrder;
use App\RemoteHardwareGate\Paystar;
use App\RemoteHardwareGate\ZarrinPal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class RemoteOrderController extends Controller
{

    public function __construct()
    {

        $this->middleware('remoteAuth');
    }

    public function ZarrinPalPaying(Request $request){

        $this->validate($request,[
            'name'=>'required|alpha_dash',
            'address'=>'required',
            'phone'=>'required |numeric',
            'post'=>'required |numeric'
        ]);
        $zarrin = new ZarrinPal($request);
        $resp = $zarrin->create();
        $result = $resp[0];
        if($result != 404){
            $order = new RemoteOrder();
            $order->address = $request->address;
            $order->name = $request->name;
            $order->post = $request->post;
            $order->phone = $request->phone;
            $order->code = strtoupper(uniqid());
            $order->trans_id = $resp[1];
            $order->user_id = Auth::guard('remote')->id();
            $order->save();

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

        $this->validate($request,[
            'name'=>'required|alpha_dash',
            'address'=>'required|alpha_dash',
            'phone'=>'required |numeric',
            'post'=>'required |numeric'
        ]);

        $payStar = new Paystar($request);
        $result = $payStar->create();
        if($result[0] != 404){
            $order = new RemoteOrder();
            $order->address = $request->address;
            $order->name = $request->name;
            $order->post = $request->post;
            $order->phone = $request->phone;
            $order->code = $result;
            $order->trans_id = $result[1];
            $order->user_id = Auth::guard('remote')->id();
            $order->save();


            return redirect()->to('https://paystar.ir/paying/'.$result[0]);
        }else{
            return 'مشکلی در پرداخت پیش آمده';
        }
    }


    public function PaystarCallback(Request $request){

        $payStar = new Paystar($request);

        return $payStar->verify();

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

}
