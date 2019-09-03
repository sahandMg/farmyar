<?php

namespace App\Http\Controllers;


use App\Http\Helpers;
use App\Jobs\subscriptionMailJob;
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
    /*
   * Gets user identification
   * generate referral code
   */
    public function subscribe(Request $request,User $user){
        $this->validate($request,[
         'name' => 'required',
        'email'=>'required|email|unique:users',
        'password'=> 'required',
        'confirm_password' => 'required|same:password'

        ]);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->code = uniqid('hashBazaar_');
        $user->password = Hash::make($request->password);
        $user->reset_password = str_random(10);
        $user->ip = Helpers::userIP();
        $user->total_mining = 0;
        $user->pending = 0;
        try{

            $user->country = strtolower(Location::get(Helpers::userIP())->countryCode);
        }catch (\Exception $exception){
            $user->country = 'fr';
        }
        // $user->plan_id = DB::table('plans')->where('name',$request->plan)->first()->id;
        // $user->period_id = DB::table('periods')->where('name',$request->period)->first()->id;
        $user->save();
//        subscriptionMailJob::dispatch($user->email,$user->code);
        Auth::guard('user')->login($user);
        $data = [
            'code'=> $user->code,
            'email'=>$user->email
        ];
        event(new \App\Events\ReferralQuery(Auth::user()));
        Mail::send('email.thanks',$data,function($message) use($data){
            $message->from ('Admin@HashBazaar.com');
            $message->to ($data['email']);
            $message->subject ('Subscription Email');
        });

        Session::put('code',$user->code);
        return redirect()->route('subscription',['locale'=>session('locale')]);
    }

    /*
     * Subscription page after filling the form
     */
    public function subscription(){

        if(session()->has('code')){
            $code = session('code');
            session()->forget('code');
           return view('subscription',compact('code'));
        }else{
//            if session has been expired, 404 error will be appear
            return redirect()->back();
        }


    }
    /*
     *  get referral code from friends and do what ??
     */
    public function post_subscription(Request $request){


    }

    public function signup(){

        return view('auth.signup');
    }

    public function post_signup(Request $request){

        $this->validate($request,[
            'name' => 'required',
            'email'=>"required|email|unique:mysql.users",
            'password'=> 'required',
            'confirm_password' => 'required|same:password',
            'captcha'=>'required|captcha'

        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->ip = Helpers::userIP();
        $user->total_mining = 0;
        $user->pending = 0;
        if($request->has('plan')){
            $plans = DB::connection('mysql')->table('plans')->get()->pluck('name')->toArray();
            if(in_array($request->plan,$plans)){
                // +1 because it begins from zero
                $plan_id = array_search($request->plan,$plans)+1;
                $user->plan_id = $plan_id;
            }else{
                return 'Invalid Plan!';
            }

        }else{
            return 'No plan on request!';
        }

        try{

            $user->country = strtolower(Location::get(Helpers::userIP())->countryCode);
        }catch (\Exception $exception){
            $user->country = 'fr';
        }

        $user->code = uniqid('hashBazaar_');
        $user->password = Hash::make($request->password);
        $user->reset_password = str_random(10);
        $user->save();
//        subscriptionMailJob::dispatch($user->email,$user->code);
        event(new \App\Events\ReferralQuery($user));
        $data = [
            'code'=> $user->code,
            'email'=>$user->email
        ];
        $token = str_random(40);
        VerifyUser::create([
            'user_id' => $user->id,
            'token' => $token
        ]);

        Mail::send('email.VerifyEmail',['user'=>$user,'route'=>'userVerify'],function($message) use($data){
            $message->from ('Admin@HashBazaar.com');
            $message->to ($data['email']);
            $message->subject ('فعال سازی حساب');
        });

        Mail::send('email.newUser',['user'=>$user],function($message) use($data){
            $message->from ('Admin@HashBazaar.com');
            $message->to ('info@hashbazaar.com');
            $message->subject ('New User');
        });

        if(session('locale') == 'fa'){

            Session::flash('message', 'ایمیل فعال سازی حساب ارسال شد. درصورت دریافت نکردن ایمیل روی ارسال مجدد کلیک کنید');
        }else{

            Session::flash('message', 'Verification email sent');
        }
        Session::put('userToken', $token);
        return redirect()->route('VerifyUserPage',['locale'=>session('locale')]);
    }
  // redirect users to verification page for sending verification link again
    public function VerifyUserPage(){

        $token = Session::get('userToken');
        if(!$token){
            return redirect()->route('login',['locale'=>session('locale')]);
        }
        $user = VerifyUser::where('token',$token)->first()->user;
        if(is_null($user)){
            return 'Error User';
        }
        return view('auth.emailVerification',compact('token'));
    }
// verifying user email
    public function VerifyUser(Request $request){

        $user = VerifyUser::where('token',$request->token)->first()->user;

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
        Auth::guard('user')->login($user);
        session(['pop'=>1]);
        return redirect()->route('dashboard',['locale'=>session('locale')]);
    }

    // resend verification link
    public function ResendVerification(Request $request){

        $this->validate($request,[
            'captcha'=>'required|captcha'
        ]);
        $token = $request->userToken;
        $VerifyUser = VerifyUser::where('token',$token)->first();
        $user = $VerifyUser->user;
        $VerifyUser->update(['token'=>$token]);
        session(['userToken'=>$token]);
        $VerifyUser->update(['token'=>$token]);
        $data = [
            'email'=>$user->email,
            'token'=>$user->verifyUser->token,
        ];
        Mail::send('email.VerifyEmail',['user'=>$user,'route'=>'userVerify'],function($message) use($data){
            $message->from (env('Admin_Mail'));
            $message->to ($data['email']);
            $message->subject ('فعال سازی حساب');
        });
        Session::flash('message','لینک فعال سازی ارسال شد');

        return redirect()->route('login',['locale'=>session('locale')]);

    }

    public function login(Request $request){
        $hashPower = $request->hashPower;
        return view('auth.login',compact('hashPower'));
    }

    public function post_login(Request $request){

        $this->validate($request,[
            'email'=> 'required|email',
            'password'=>'required|min:6',
            'captcha'=>'required|captcha'
        ]);

        if(Auth::guard('user')->attempt(['email'=>$request->email,'password'=>$request->password],true)){

            if(Auth::guard('user')->user()->verified == 0){

                $token = Auth::guard('user')->user()->verifyUser->token;
                Auth::guard('user')->logout();
                if(App::getLocale() == 'fa'){

                    Session::flash('error','حساب کاربری شما فعال نیست. با مراجعه به لینک ارسال شده به ایمیلتان، اقدام به فعال سازی حساب خود کنید');
                }else{

                    Session::flash('message', 'Your email address is not verified');
                }
                Session::flash('userToken',$token);
                return redirect()->route('VerifyUserPage',['locale'=> App::getLocale()]);
            }

            try{

                $country = strtolower(Location::get(Helpers::userIP())->countryCode);
            }catch (\Exception $exception){
                $country = 'fr';
            }
            Auth::guard('user')->user()->update(['ip'=>Helpers::userIP(),'country'=>$country]);

            if(!is_null($request->hashPower)){
                $hashPower = $request->hashPower;
                return redirect()->route('dashboard',['locale'=>session('locale')])->with(['hashPower'=>$hashPower]);
            }else{
                return redirect()->route('dashboard',['locale'=>session('locale')]);
                }
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

        $user = User::where('email',$client->email)->first();
        if(!is_null($user)){
            $user->avatar = $client->avatar;
            $user->ip = Helpers::userIP();
            try{

                $user->country = strtolower(Location::get(Helpers::userIP())->countryCode);
            }catch (\Exception $exception){
                $user->country = 'fr';
            }
            $user->plan_id = session('planId');
            $user->save();

            Auth::guard('user')->login($user);
            return redirect()->route('dashboard',['locale'=>session('locale')]);
        }

        $user = new User();
        $user->name = $client->name;
        $user->email = $client->email;
        $user->code = uniqid('hashBazaar_');
        $user->avatar = $client->avatar;
        $user->ip = Helpers::userIP();
        $user->verified = 1;
        $user->total_mining = 0;
        $user->pending = 0;
        try{

            $user->country = strtolower(Location::get(Helpers::userIP())->countryCode);
        }catch (\Exception $exception){
            $user->country = 'fr';
        }

        $user->save();

        $data = [
            'code'=> $user->code,
            'email'=>$user->email
        ];
        event(new \App\Events\ReferralQuery($user));
        Auth::guard('user')->login($user);

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
        return redirect()->route('dashboard',['locale'=>session('locale')]);
    }

    public function passwordReset(){

        return view('password_reset');
    }

    public function post_passwordReset(Request $request){

        $this->validate($request,[
            'email'=> 'required|email'
        ]);

        $user = User::where('email',$request->email)->first();
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

        return redirect()->route('login',['locale'=>session('locale')])->with(['message'=>'An Email with a new password has been sent to your email address']);

    }

    public function logout(){

        Session::flush();
        Auth::guard('user')->logout();
        return redirect()->route('index',['locale'=> App::getLocale()]);
    }
}
