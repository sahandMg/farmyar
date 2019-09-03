<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class BlockMiddleware
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

        if(Auth::guard('user')->check() ){
            if(Auth::guard('user')->user()->block == 1 ){
                Auth::guard('user')->logout();
                return response()->view('errors.block');
            }
        }
        return $next($request);
    }
}
