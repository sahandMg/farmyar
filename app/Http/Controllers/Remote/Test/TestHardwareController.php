<?php
namespace App\Http\Controllers\Remote\Test;
use App\RemoteOrder;
use App\RemoteHardwareGate\PaystarTest;
use App\RemoteHardwareGate\ZarrinPalTest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestHardwareController extends Controller
{
    public function ZarrinPalPaying(Request $request){


        $zarrin = new ZarrinPalTest($request);
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

             return $this->ZarrinCallback($request);;
        }else{
            return 'مشکلی در پرداخت پیش آمده';
        }
    }

    public function ZarrinCallback(Request $request){

        $zarrin = new ZarrinPalTest($request);

        return $zarrin->verify();
    }

    public function PaystarPaying(Request $request){


        $payStar = new PaystarTest($request);
        $result = $payStar->create();

        if($result[0] != 404){
            $order = new RemoteOrder();
            $order->address = $request->address;
            $order->name = $request->name;
            $order->post = $request->post;
            $order->phone = $request->phone;
            $order->code = strtoupper(uniqid());
            $order->trans_id = $result[1];
            $order->user_id = Auth::guard('remote')->id();
            $order->save();


            return $this->PaystarCallback($request);
        }else{
            return 'مشکلی در پرداخت پیش آمده';
        }
    }


    public function PaystarCallback(Request $request){

        $payStar = new PaystarTest($request);

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