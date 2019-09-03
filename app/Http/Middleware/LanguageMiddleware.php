<?php

namespace App\Http\Middleware;

use App\Http\Helpers;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Stevebauman\Location\Facades\Location;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {

            $country = strtolower(Location::get(Helpers::userIP())->countryCode);
        } catch (\Exception $exception) {
            $country = 'ir';
        }

            // if (Session::has('locale')) {
            //     App::setLocale(Session::get('locale'));

            // } else {
            //     session(['locale' => App::getLocale()]);
            // }
            // $lang = ['fa', 'en'];
            // if (in_array($request->segment(1), $lang)) {
            //     App::setLocale($request->segment(1));
            //     session(['locale' => App::getLocale()]);
            // } // if there is no lang on url
            // else {
            //     session(['locale' => App::getLocale()]);
            //     return redirect(session('locale') . $request->getRequestUri());

            // }

        return $next($request);


    }
}
