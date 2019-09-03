<?php

namespace App\Http\Controllers\Remote\Test;


use App\RemotePaymentGate\PaystarTest;
use App\RemotePaymentGate\ZarrinPalTest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class TestController extends Controller
{
    public function gateTest(){

        return view('remote.test.GateTest');
    }

    public function ZarrinPalPaying(Request $request){

        $zarrin = new ZarrinPalTest($request);
        $result = $zarrin->create();
        if($result != 404){
            return $this->ZarrinCallback($request);
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
        if($result != 404){
            $request->session()->save();

            return $this->PaystarCallback($request);
        }else{
            return 'مشکلی در پرداخت پیش آمده';
        }
    }


    public function PaystarCallback(Request $request){

        $payStar = new PaystarTest($request);

        return $payStar->verify();

    }



}
