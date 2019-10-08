<?php

namespace App\Http\Controllers\Remote;

use App\Http\Controllers\Controller;
use App\Http\Helpers;
use App\Jobs\subscriptionMailJob;
use App\RemoteData;
use App\RemoteId;
use App\RemoteUser;
use App\User;
use App\VerifyUser;
use Illuminate\Support\Facades\App;
use Laravel\Socialite\Facades\Socialite;
use Stevebauman\Location\Facades\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function authorizing(){

        return view('remote.auth.authorizing');
    }

    public function post_authorizing(Request $request){

        if($request->action == 'login'){

            return $this->post_login($request);
        }else{

            return $this->post_signup($request);
        }
    }
    private function post_signup($request){

        $this->validate($request,[
            'name' => 'required',
            'email'=>"required|email|unique:mysql.remote_users",
            'password'=> 'required',
            'confirm_password' => 'required|same:password',
            'captcha'=>'required|captcha'

        ]);

        $user = new RemoteUser();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->ip = Helpers::userIP();
        try{

            $user->country = strtolower(Location::get(Helpers::userIP())->countryCode);
        }catch (\Exception $exception){
            $user->country = 'ir';
        }

        $user->code = strtoupper(uniqid());
        $user->password = Hash::make($request->password);
        $user->save();

        $data = [
            'code'=> $user->code,
            'email'=>$user->email
        ];
        $token = str_random(40);
        VerifyUser::create([
            'user_id' => $user->id,
            'token' => $token
        ]);

        Mail::send('email.VerifyEmail',['user'=>$user,'route'=>'VerifyRemoteUser'],function($message) use($data){
            $message->from ('Admin@HashBazaar.com');
            $message->to ($data['email']);
            $message->subject ('فعال سازی حساب');
        });

        Mail::send('email.newUser',['user'=>$user],function($message) use($data){
            $message->from ('Admin@HashBazaar.com');
            $message->to ('info@hashbazaar.com');
            $message->subject ('New User');
        });

        if(App::getLocale() == 'fa'){

            Session::flash('message', 'ایمیل فعال سازی حساب ارسال شد. درصورت دریافت نکردن ایمیل روی ارسال مجدد کلیک کنید');
        }else{

            Session::flash('message', 'Verification email sent');
        }
        Session::put('userToken', $token);
        return redirect()->route('RemoteVerifyUserPage',['locale'=> App::getLocale()]);
    }
    // redirect users to verification page for sending verification link again
    public function VerifyUserPage(){


        $token = Session::get('userToken');
        if(!$token){
            return redirect()->route('RemoteLogin',['locale'=>App::getLocale()]);
        }


        $user = VerifyUser::where('token',$token)->first()->remoteUser;
        if(is_null($user)){
            return 'Error User';
        }
        return view('remote.auth.emailVerification',compact('token'));
    }
// verifying user email
    public function VerifyUser(Request $request){

        $user = VerifyUser::where('token',$request->token)->first()->remoteUser;

        if(is_null($user)){
            return 'Corrupt Link';
        }
        $user->update(['verified'=>1]);
        $data = [
            'code'=> $user->code,
            'email'=>$user->email
        ];

        Mail::send('email.thanks',$data,function($message) use($data){
            $message->from (env('Admin_Mail'));
            $message->to ($data['email']);
            $message->subject ('Subscription Email');
        });
        Auth::guard('remote')->login($user);
        session(['pop'=>1]);
        return redirect()->route('remoteDashboard',['locale'=>App::getLocale()]);
    }

    // resend verification link
    public function ResendVerification(Request $request){

        $this->validate($request,[
//            'captcha'=>'required|captcha'
        ]);
        $token = $request->userToken;
        $VerifyUser = VerifyUser::where('token',$token)->first();
        $user = $VerifyUser->remoteUser;
        $token = str_random(40);
        $VerifyUser->update(['token'=>$token]);
        session(['userToken'=>$token]);
        $data = [
            'email'=>$user->email,
            'token'=>$user->verifyUser->token,
        ];
        Mail::send('email.VerifyEmail',['user'=>$user,'route'=>'VerifyRemoteUser'],function($message) use($data){
            $message->from (env('Admin_Mail'));
            $message->to ($data['email']);
            $message->subject ('فعال سازی حساب');
        });
        Session::flash('message','لینک فعال سازی ارسال شد');

        return redirect()->route('RemoteLogin',['locale'=>App::getLocale()]);

    }

    public function login(){

        return view('remote.auth.login');
    }

    private function post_login($request){

        $this->validate($request,[
            'email'=> 'required|email',
            'password'=>'required|min:6',
//            'captcha'=>'required|captcha'
        ]);

        if(Auth::guard('remote')->attempt(['email'=>$request->email,'password'=>$request->password],true)){

            if(Auth::guard('remote')->user()->verified == 0){
                $token = Auth::guard('remote')->user()->verifyUser->token;
                Auth::guard('remote')->logout();
                if(App::getLocale() == 'fa'){

                    Session::flash('error','حساب کاربری شما فعال نیست. با مراجعه به لینک ارسال شده به ایمیلتان، اقدام به فعال سازی حساب خود کنید');
                }else{

                    Session::flash('message', 'Your email address is not verified');
                }
                Session::flash('userToken',$token);
                return redirect()->route('RemoteVerifyUserPage',['locale'=> App::getLocale()]);
            }

            try{

                $country = strtolower(Location::get(Helpers::userIP())->countryCode);
            }catch (\Exception $exception){
                $country = 'ir';
            }
            Auth::guard('remote')->user()->update(['ip'=>Helpers::userIP(),'country'=>$country]);

            return redirect()->route('remoteDashboard',['locale'=>App::getLocale()]);

        }else{

            return redirect()->back()->with(['error'=>'Wrong email or password']);
        }

    }
    /*
     * Google Login Apis
     */

    public function redirectToProvider(Request $request)
    {

        if($request->has('plan')){
            $plans = DB::connection('mysql')->table('plans')->get()->pluck('name')->toArray();
            if(in_array($request->plan,$plans)){
                // +1 because it begins from zero
                $plan_id = array_search($request->plan,$plans)+1;
                session(['planId'=> $plan_id]);
            }else{
                return 'Invalid Plan!';
            }

        }else{
            return 'No plan on request!';
        }
        return Socialite::driver('google')->redirect();
    }

    public function handleProviderCallback(){

        $client =  Socialite::driver('google')->stateless()->user();

//        try{
//            $user = User::where('email',$client->email)->firstOrFaile();
//        }catch (\Exception $exception){
//
//            return 404;
//        }

//            $userData = JWTAuth::parseToken()->authenticate();

//            return ['token'=>$token,'userData'=>$user];

        $user = RemoteUser::where('email',$client->email)->first();
        if(!is_null($user)){
            $user->avatar = $client->avatar;
            $user->ip = Helpers::userIP();
            try{

                $user->country = strtolower(Location::get(Helpers::userIP())->countryCode);
            }catch (\Exception $exception){
                $user->country = 'ir';
            }
            $user->plan_id = session('planId');
            $user->save();

            Auth::guard('remote')->login($user);
            return redirect()->route('remoteDashboard',['locale'=>App::getLocale()]);
        }

        $user = new RemoteUser();
        $user->name = $client->name;
        $user->email = $client->email;
        $user->code = strtoupper(uniqid());
        $user->avatar = $client->avatar;
        $user->ip = Helpers::userIP();
        $user->verified = 1;
        $user->total_mining = 0;
        $user->pending = 0;
        try{

            $user->country = strtolower(Location::get(Helpers::userIP())->countryCode);
        }catch (\Exception $exception){
            $user->country = 'ir';
        }

        $user->save();

        $data = [
            'code'=> $user->code,
            'email'=>$user->email
        ];
        Auth::guard('remote')->login($user);

        Mail::send('email.newUser',['user'=>$user],function($message) use($data){
            $message->from (env('Admin_Mail'));
            $message->to (env('Info_Mail'));
            $message->subject ('New User');
        });
        Mail::send('email.thanks',$data,function($message) use($data){
            $message->from (env('Admin_Mail'));
            $message->to ($data['email']);
            $message->subject ('Subscription Email');
        });
        session(['pop'=>1]);
        return redirect()->route('remoteDashboard',['locale'=>App::getLocale()]);
    }

    public function passwordReset(){

        return view('password_reset');
    }

    public function post_passwordReset(Request $request){

        $this->validate($request,[
            'email'=> 'required|email'
        ]);

        $user = RemoteUser::where('email',$request->email)->first();
        if(is_null($user)){

            return redirect()->back()->with(['error'=>'Email address not found']);
        }
        $pass = strtolower(str_random(10));
        $user->password = bcrypt($pass);
        $user->save();
        Mail::send('email.reset_password',['pass'=>$pass,'user'=>$user],function($message) use($user){
            $message->from (env('Admin_Mail'));
            $message->to ($user->email);
            $message->subject ('Password Reset');
        });

        return redirect()->route('login',['locale'=>App::getLocale()])->with(['message'=>'An Email with a new password has been sent to your email address']);

    }

    public function logout(){

        Session::flush();
        Auth::guard('remote')->logout();
        return redirect()->route('index',['locale'=> App::getLocale()]);
    }

    public function userData(Request $request){

        if(!$request->has('id')){
            return ['error'=> 500 ,'body'=>'Token not provided'];
        }else{
            $user = RemoteUser::where('id',$request->id)->first();
            if(is_null($user)){
                return ['error'=> 404 ,'body'=>'Incorrect Token'];
            }else{
                return $user['name'];
            }
        }
    }
}

