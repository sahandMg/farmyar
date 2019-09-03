<?php

namespace App\Listeners;

use App\Events\Sms;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSms
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  Sms  $event
     * @return void
     */
    public function handle(Sms $event)
    {

        try{
            $api = new \Kavenegar\KavenegarApi( "796C4E505946715933687269672B6F6B5648564562585250533251356B6B6361" );
//            $sender = "10004346";
//            09371869568
            $sender = "10008000800600";
            $message =  $event->message;
            $receptor = array("09387728916","09371869568");
            $api->Send($sender,$receptor,$message);

        }
        catch(\Kavenegar\Exceptions\ApiException $e){
            // در صورتی که خروجی وب سرویس 200 نباشد این خطا رخ می دهد
            echo $e->errorMessage();
        }
        catch(\Kavenegar\Exceptions\HttpException $e){
            // در زمانی که مشکلی در برقرای ارتباط با وب سرویس وجود داشته باشد این خطا رخ می دهد
            echo $e->errorMessage();
        }
    }


}
