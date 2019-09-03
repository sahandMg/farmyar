<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::any('cryptobox.callback.php','PaymentController@paymentCallback')->name('paymentCallback');

Route::any('payment/confirmed',['as'=>'PaymentConfirmed','uses'=>'PaymentController@PaymentConfirmed']);
Route::any('payment/created',['as'=>'PaymentCreated','uses'=>'PaymentController@PaymentCreated']);
Route::any('payment/failed',['as'=>'PaymentFailed','uses'=>'PaymentController@PaymentFailed']);
Route::any('payment/pending',['as'=>'PaymentPending','uses'=>'PaymentController@PaymentPending']);
Route::any('payment/delayed',['as'=>'PaymentDelayed','uses'=>'PaymentController@PaymentDelayed']);
Route::any('payment/resolved',['as'=>'PaymentResolved','uses'=>'PaymentController@PaymentResolved']);
Route::post('paystar/callback','PaymentController@PaystarCallback')->name('PaymentCallback');

Route::post('remote/paystar/callback','Remote\TransactionController@PaystarCallback')->name('RemotePaystarCallback');

Route::post('remote/hardware/paystar/callback','Remote\RemoteOrderController@PaystarCallback')->name('RemoteOrderPaystarCallback');

Route::post('miner-data','Remote\RemoteController@minerDataApi')->name('minerData');

Route::post('remote','Remote\RemoteController@remoteApi')->name('remote');

Route::post('user-data','Remote\AuthController@userData')->name('userData');

Route::post('btc-price','PageController@btcPrice')->name('btcPrice');

Route::post('get-pool-data','Remote\RemoteController@getPoolDataApi')->name('getPoolDataApi');
