<?php

namespace App\Listeners;

use App\Events\Contact;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendEmail
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
     * @param  Contact  $event
     * @return void
     */
    public function handle(Contact $event)
    {

        $data = $event->data;

        Mail::send('email.messageMailPage',$data,function($message) use($data){
            $message->from ($data['email']);
            $message->to (env('Admin_Mail'));
            $message->subject ('Message From User');
        });

        Mail::send('email.replyMessageMailPage',$data,function($message) use($data){
            $message->from (env('Admin_Mail'));
            $message->to ($data['email']);
            $message->subject ('Reply From HashBazaar');
        });
    }
}
