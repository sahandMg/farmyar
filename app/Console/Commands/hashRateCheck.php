<?php

namespace App\Console\Commands;

use App\Events\Sms;
use App\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;
use Morilog\Jalali\Jalalian;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Support\Facades\Mail;
class hashRateCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hash:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks hashRate status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $setting = Setting::first();
        $url = 'http://api.f2pool.com/bitcoin/mvs1995';
        $client = new GuzzleClient();
        $promise1 = $client->requestAsync('GET',$url)->then(function (ResponseInterface $response) {
            return $response->getBody()->getContents();
        });
        $resp = $promise1->wait();
        $hashRate = json_decode($resp,true)['hashrate'];
        if(!Cache::has('alarmNumber')){
            Cache::forever('alarmNumber',0);
        }
        if(Cache::get('alarmNumber') > 2){
            $setting->update(['alarms' => 0]);
            // reset the counter
            Cache::forever('alarmNumber',0);

            // calling sms event with custom message
            $message =  "ماینر ها خاموش اند. آلارم خاموش شد".Jalalian::forge(Carbon::now())->toString();
            $api = new \Kavenegar\KavenegarApi( "796C4E505946715933687269672B6F6B5648564562585250533251356B6B6361" );
//            $sender = "10004346";
            $sender = "10008000800600";
            $receptor = "09387728916";
            $api->Send($sender,$receptor,$message);

        }

        if($hashRate == 0 || round($hashRate / pow(10,12)) < 0.85 * $setting->total_th){

            if(!Cache::has('power_off')){
                Cache::forever('power_off',0);
            }else{
                Cache::forever('power_off',1);
            }
        }

    if($setting->alarms == 1){

        if($hashRate == 0){

            // calling sms event with custom message
            $message =  "ماینرها خاموش شدند ".Jalalian::forge(Carbon::now())->toString();
            $api = new \Kavenegar\KavenegarApi( "796C4E505946715933687269672B6F6B5648564562585250533251356B6B6361" );
//            $sender = "10004346";
            $sender = "10008000800600";
            $receptor = "09387728916";
            $api->Send($sender,$receptor,$message);

            Mail::send('email.hashRateCheck',[],function($message){
                $message->to(env('Admin_Mail'));
                $message->from(env('Tech_Mail'));
                $message->subject('Hash Rate Alllleeerrrrtttttt!!!!');
            });

            Cache::forever('alarmNumber',Cache::get('alarmNumber')+1);

        }elseif (round($hashRate / pow(10,12)) < 0.85 * $setting->total_th){

            $message =  "تعدادی از ماینرها کم کارند یا خاموش اند ".Jalalian::forge(Carbon::now())->toString() . 'مجموع تراهش'.round($hashRate / pow(10,12));
//            Sms::dispatch($message);
        }
    }


    }
}
