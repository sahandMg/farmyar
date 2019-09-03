<?php

namespace App\Http\Controllers;

use App\BitCoinPrice;
use App\BitHash;
use App\CustomCode;
use App\Events\Contact;
use App\ExpiredCode;
use App\Message;
use App\Mining;
use App\MiningReport;
use App\Referral;
use App\Setting;
use App\Sharing;
use App\Transaction;
use App\VerifyUser;
use App\Wallet;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\CryptoJob;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use League\Flysystem\Exception;

class PanelController extends Controller
{
    public $settings;
    public function __construct()
    {
        $this->middleware('auth:user');
        $this->settings = Setting::first();
    }

    public function dashboard(){

        $hashes = BitHash::where('user_id',Auth::guard('user')->id())->where('confirmed',1)->orderBy('created_at','desc')->get();
        $unusedCodes = DB::connection('mysql')->table('expired_codes')->where('user_id',Auth::guard('user')->id())->where('used',0)->first();
        if(!is_null($unusedCodes)){

            if($unusedCodes->is_custom == 1){
                $discount = CustomCode::where('code',$unusedCodes->code)->first()->discount;
            }else{
                $discount = $this->settings->sharing_discount;
            }
            $apply_discount = 1;
        }else{
            $apply_discount = 0;
            $discount = 0;
        }

        if(session()->has('hashPower')){
            $hashPower = session('hashPower');
            return view('panel.dashboard',compact('hashes','apply_discount','hashPower','discount'));
        }
//        dd(App::getLocale());
        return view('panel.dashboard',compact('hashes','apply_discount','discount'));
    }

    /*
     * Get Total mining Ajax
     */

    public function totalEarn(Request $request){
// getting bitcoin price in usd

        $bitCoinPriceInst = new BitCoinPrice();
        $bitCoinPrice = $bitCoinPriceInst->getPrice();
        $user = DB::connection('mysql')->table('users')->where('code',$request->user)->first();
        if(is_null($user)){
            return 404;
        }
        $mining = DB::connection('mysql')->table('minings')->where('user_id',$user->id)->where('block',0)->get();
        if( $mining->isEmpty()) {
            return [0,0];
        }
        else{
            $total_paid_btc = Transaction::where('user_id',$user->id)->where('status','paid')->where('checkout','in')->sum('amount_btc');
            $pending =  $mining->sum('mined_btc') - $total_paid_btc;
            return $userEarn = [$mining->sum('mined_btc') ,$bitCoinPrice * $mining->sum('mined_btc'),floatval(number_format($pending,8))];
        }
    }
    /*
     *  bitcoin mining chart data
     */
    public function chartData(Request $request){

        $user = DB::connection('mysql')->table('users')->where('code',$request->user)->first();
        $reports = MiningReport::where('user_id',$user->id)->get();
        $btcPriceInst = new BitCoinPrice();
        $btcPrice = $btcPriceInst->getPrice();
        if(count($reports) == 0){
            return 404;
        }
        $data = [];
        for($i = 0 ; $i <= date('t') ; $i++){
            $time = Carbon::now()->subDay($i);
            $reports = MiningReport::whereDate('created_at',$time)->where('user_id',$user->id)->get();
            if(count($reports) > 0){
                $totalUsd =  $reports->sum('mined_btc')*$btcPrice;
                $data[$i] = ['time'=>$time->format('d M') , 'mined'=>$totalUsd];
            }

        }

        $data = array_reverse( array_values($data)) ;
        return $data;


    }

    public function post_changePassword(Request $request){

        $this->validate($request,[
            'password'=>'required|min:8',
            'confirm_password'=>'required|same:password'
        ]);

        $user = Auth::user();
        $user->password = $request->password;
        $user->save();

        return redirect()->back()->with(['message'=>'New password has been set!']);

    }

    /*
     * Apply referral code
     * check if a referral code is active or not
     */
    public function postDashboard(Request $request,Hash $hash){


        $code = strtolower($request->referralCode);
        $referralUser = DB::connection('mysql')->table('users')->where('code',$code)->where('id','!=',Auth::id())->first();
        $is_expired = DB::connection('mysql')->table('expired_codes')->where('user_id',Auth::guard('user')->id())->where('code',$code)->first();
        $settings = Setting::first();
        // check if it is a custom code or not

        $customCode = CustomCode::where('code',$code)->first();


        if(isset($customCode) && $customCode->expired == 0 && is_null($is_expired)){

            // use user_id column as an array that contains user ids that applied the discount code
            // first check if current user is in the array or not

//            $users_id = in_array(Auth::guard('user')->id(),unserialize($customCode->user_id));

//            if(!$users_id){
//                $ids = unserialize($customCode->user_id);
//                array_push($ids,Auth::guard('user')->id());
//                $customCode->update(['user_id'=> serialize($ids)]);
                $customCode->update(['sharing_number'=> $customCode->sharing_number + 1]);
//                session(['custom_code'=>$customCode->code]);
//                session(['discount'=> 0.1]);
                $expireCode = new ExpiredCode();
                $expireCode->user_id = Auth::guard('user')->id();
                $expireCode->code = $code;
                $expireCode->is_custom = 1;
                $expireCode->save();
//                return [
//                    'type'=>'custom',
//                    'body'=>'code accepted',
//                    'code'=>$customCode->code,
//                    'discount'=>$customCode->discount
//                ];
                return [
                    'type'=>'message',
                    'body'=>'code accepted',
                    'discount' => $customCode->discount
                ];

//            }
//            else{
//
//                return [
//                    'type'=>'error',
//                    'body'=>"Expired code entered",
//                ];
//            }

        }
        // check if the code is used before
        if(!is_null($is_expired)){

            return [
                'type'=>'error',
                'body'=>'Expired code entered'
            ];
        }

        $is_active_Code = DB::connection('mysql')->table('expired_codes')
            ->where('user_id',Auth::guard('user')->id())
            ->where('used',0)->count();

        if($is_active_Code > 0){

            return [
                'type'=>'error',
                'body'=>'Your previous referral code has not been used yet !'
            ];
        }
        // check code validation
        if(is_null($referralUser)){

            return [
                'type'=>'error',
                'body'=>'Code is not valid'
            ];
        }
        /*
         * 1. check sharing level
         * 2. update sharing benefit
         * 3. update sharing number
         * 4.
         */
        else{
            $referralUser = Referral::where('code',$code)->where('id','!=',Auth::id())->first();

            $expireCode = new ExpiredCode();
            $expireCode->user_id = Auth::guard('user')->id();
            $expireCode->code = $code;
            $expireCode->save();
            $referralUser->update([
                'total_sharing_num' => $referralUser->total_sharing_num + 1
            ]);
            $referralUser->save();
            session(['referralCode'=>$code]);

            return [
                'type'=>'message',
                'body'=>'code accepted',
                'discount'=> $settings->sharing_discount
            ];

        }
    }



    public function activity(){


        return view('panel.activity');
    }

    public function setting(){

        return view('panel.settings.setting');
    }

//    public function post_setting(Request $request){
//
//        $this->validate($request,[
//            'pass'=>'required|min:8',
//            'newpass'=>'required|min:8',
//            'confirm'=>'required|same:newpass',
//        ]);
//        $user = Auth::guard('user')->user();
//        $user->update([
//            'password'=>Hash::make($request->newpass)
//
//        ]);
//        $user->save();
//        return redirect()->back()->with(['message'=>'Password Changed']);
//    }

    public function userInfo(){

        return view('panel.settings.userInfo');
    }

    public function post_setting(Request $request){

        $user = Auth::guard('user')->user();
        $input = $request->all();
        if(isset($input['email'])){

            $user->update(['email'=>$input['email']]);
        }

        if(Hash::check($input['password'],$user->password)){

            if(isset($input['password'])){
                $this->validate($request,[
                    'password' => 'required|min:6',
                    'newpass' => 'required|min:6',
                    'confirm' => 'required|same:newpass',
                ]);
                $user->update(['password' => bcrypt($input['newpass'])]);
            }
            return redirect()->back()->with(['message'=>'Password changed']);
        }else{
            return redirect()->back()->with(['error'=>'current password is wrong']);
        }

    }

    public function changePassword(){

        return view('panel.settings.changePassword');
    }


    public function post_wallet(Request $request){

        $this->validate($request,['wallet'=>'required']);
        $wallet = DB::connection('mysql')->table('wallets')->where('user_id',Auth::guard('user')->id())->first();
        if(is_null($wallet)){
            $wallet = new Wallet();
            $wallet->addr = $request->wallet;
            $wallet->user_id = Auth::guard('user')->id();
            $wallet->active = 1;
            $wallet->save();
            return redirect()->route('setting',['locale'=>session('locale')]);
        }else{
            return redirect()->back()->with(['error'=>'You have entered a wallet address before']);
        }




    }

    public function wallet(){

        if(!is_null(Auth::guard('user')->user()->wallet)){ // user has a wallet

            return redirect()->route('settings',['locale'=>session('locale')]);
        }
        return view('panel.settings.setting');
    }

    public function editWallet(Request $request){

        $user = Auth::guard('user')->user();
        $data = ['wallet'=> $request->address,'user'=>$user];
            Mail::send('email.walletConfirm',$data,function($message)use($user){
                $message->to($user->email);
                $message->from(env('Support_Mail'));
                $message->subject('Confirm new wallet address');
            });
        return redirect()->back()->with(['message'=>'Check Confirmation Email']);
    }

    public function confirmWallet(Request $request){

        if(!isset($request->address)){

            return 'Wrong Link!';
        }
        $tokenQuery = VerifyUser::where('token',$request->token)->first();
        if(is_null($tokenQuery)){
            return 'Fake Link';
        }
        $user = $tokenQuery->user;
        $wallet = $user->wallet;
        $wallet->update(['addr'=> $request->address]);
        return redirect()->back()->with(['message'=>'New wallet address saved']);
    }

    public function referral(){

        return view('panel.referral');
    }

    public function contact(){

        return view('panel.contact');
    }

    public function post_contact(Request $request,Message $message){

        $this->validate($request,['name'=>'required','email'=>'required|email','message'=>'required']);

        if($request->has('name')){

        }
        $message->name = $request->name;
        $message->email = $request->email;
        $message->message = $request->message;
        $message->save();

//        MessageJob::dispatch($request->email,$request->message);
        $data = [
            'UserMessage'=> $request->message,
            'email'=> $request->email,
            'name' => $request->name
        ];

        event(new Contact($data));

        return redirect()->back()->with(['message'=>'Your message has been sent!']);
    }

    public function downloadBanner($name = null){

         if( file_exists("gifs/$name.gif")){

            $file =  "gifs/$name.gif";
             $headers = array(
                 'Content-Type: application/gif',
             );

             return Response::download($file, $name.'.gif', $headers);
         }

         abort(404);

    }

    public function collaboration(){

        $hashes = BitHash::where('user_id',Auth::guard('user')->id())->where('confirmed',1)->orderBy('created_at','asc')->get();
        $unusedCodes = DB::connection('mysql')->table('expired_codes')->where('user_id',Auth::guard('user')->id())->where('used',0)->first();
        if(!is_null($unusedCodes)){

            if($unusedCodes->is_custom == 1){
                $discount = CustomCode::where('code',$unusedCodes->code)->first()->discount;
            }else{
                $discount = $this->settings->sharing_discount;
            }
            $apply_discount = 1;
        }else{
            $apply_discount = 0;
            $discount = 0;
        }


        return view('panel.collaboration',compact('hashes','apply_discount','discount'));
    }
}
