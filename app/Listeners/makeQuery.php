<?php

namespace App\Listeners;

use App\Referral;
use App\Events\ReferralQuery;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class makeQuery
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
     * @param  Referral  $event
     * @return void
     */
    public function handle(ReferralQuery $event)
    {
        $user = $event->user;
        $ref = new Referral();
        $ref->code = $user->code;
        $ref->user_id = $user->id;
        $ref->total_benefit = 0;
        $ref->total_sharing_num = 0;
        $ref->total_sharing_income = 0;
        $ref->user_income_share = 0;
        $ref->share_level = 1;
        $ref->save();
    }
}
