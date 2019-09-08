<?php

namespace App\Providers;

use App\Decryption;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
       if(config('app.env') === 'production' || config('app.env') === 'local') {
           URL::forceScheme('https');
       }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
//        $this->app->bind('\App\Encryption',function($app){
//
//            return new \App\Encryption($app->make('Encryption'));
//        });
//
//        $this->app->bind('\App\Decryption',function($app){
//            return new \App\Decryption($app->make('Decryption'));
//        });
    }
}
