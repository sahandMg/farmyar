<?php

namespace App\Http\Controllers\Remote;

use App\RemotePlan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function __construct()
    {

        $this->middleware('remoteAuth');
    }

    public function index(){
      $orders = RemotePlan::where('user_id',Auth::guard('remote')->id())->get();
        return view('remote.panel.subscription',compact('orders'));
    }
}
