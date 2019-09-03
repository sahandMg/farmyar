<?php

namespace App\Console\Commands;

use App\Exports\DataExport;
use App\RemoteData;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Morilog\Jalali\Jalalian;

class ExportData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exporting miners data';

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
        $name = Jalalian::fromCarbon(Carbon::now());
        Excel::store(new DataExport(), ('Excels/'.$name.'.xlsx'));
        RemoteData::truncate();
    }
}
