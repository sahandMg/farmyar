<?php
namespace App\Http\Controllers;
use App\BitCoinPrice;
use App\Crawling\CoinMarketCap;
use App\Events\Contact;
use App\Exports\DataExport;
use App\Jobs\MessageJob;
use App\Message;
use App\RemoteData;
use App\User;
use App\VerifyUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Morilog\Jalali\Jalalian;

class PageController extends Controller
{
    public function index(Request $request){

        if($request->has('code')){
            $code = $request->code;
            return view('index',compact('code'));
        }else{
            return view('index');
        }
    }
    /*
        Index Page Contact Form
    */
    public function message(Request $request,Message $message){
        $this->validate($request,['name'=>'required'
            ,'email'=>'required|email'
            ,'message'=>'required',
            'captcha'=>'required|captcha'
        ]);
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
    public function Pricing(){
        $coin = new CoinMarketCap();
        return $coin->getApi();
    }
    public function customerService(){
        return view('faq.index');
    }
    public function aboutUs(){
        return view('about');
    }
    public function affiliate(){
        return view('affiliate');
    }
    public function ChangeLanguage(Request $request){
        $lang = $request->lang;
        $lang = explode('.',$lang)[0];
        if($lang == 'uk'){
            session(['locale'=>'en']);
        }elseif ($lang == 'ir'){
            session(['locale'=>'fa']);
        }
        return 200;
    }
    public function RedirectWallet(Request $request){
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
        return view('walletRedirection');
    }
    public function export(){

        $name = Jalalian::fromCarbon(Carbon::now());
         Excel::store(new DataExport(), ('Excels/'.$name.'.xlsx'));
        return 'done';
    }

    public function shareOrder(Request $request){
        $this->validate($request,[
                'name'=>'required',
                'phone'=>'required|numeric',
                'body'=>'required',
                'email'=>'required|email',
        ]);
        $message = new Message();
        $message->name = $request->name;
        $message->message = $request->body;
        $message->phone = $request->phone;
        $message->email = $request->email;
        $message->save();
        $data = [
            'UserMessage'=> $request->body,
            'email'=> $request->email,
            'name' => $request->name,
            'phone' => $request->phone,
        ];

        Mail::send('email.shareOrderMailPage',$data,function($message) use($data){
            $message->from ($data['email']);
            $message->to (env('Cooperation_Mail'));
            $message->subject ('Message From User');
        });

        Mail::send('email.replyMessageMailPage',$data,function($message) use($data){
            $message->from (env('Cooperation_Mail'));
            $message->to ($data['email']);
            $message->subject ('Reply From HashBazaar');
        });
        return redirect()->back()->with(['message'=>'Your message has been sent!']);
    }

    public function btcPrice(BitCoinPrice $bitCoinPrice){

        return ['code'=>200,'message'=>$bitCoinPrice->getPrice()];
    }

}