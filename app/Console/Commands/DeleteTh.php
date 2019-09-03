<?php

namespace App\Console\Commands;

use App\BitHash;
use App\Mining;
use App\Setting;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DeleteTh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hash:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'retrieves unpaid THs';

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
        $unpaids = BitHash::where('confirmed',0)->get();
        $unpaidMinings = Mining::where('block',1)->get();
        foreach ($unpaids as $key => $unpaid) {

            if (Carbon::parse($unpaid->created_at)->diffInHours(Carbon::now()) > 10) {

                $unpaid->delete();

                Transaction::where('code',$unpaid->order_id)->where('checkout','out')->delete();
            }

        }

        foreach ($unpaidMinings as $unpaidMining) {

            if (Carbon::parse($unpaidMining->created_at)->diffInHours(Carbon::now()) > 10) {
                $unpaidMining->delete();
            }
        }
    }
}
