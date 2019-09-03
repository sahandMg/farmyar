<?php

namespace App\Console\Commands;

use App\Crawling\CoinMarketCap;
use Illuminate\Console\Command;

class Crypto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crypto:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks CryptoCurrencies Prices from CoinMarketCap';

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
        $coin = new CoinMarketCap();
        $coin->getApi();

    }
}
