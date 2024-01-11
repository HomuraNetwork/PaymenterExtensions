<?php

use Illuminate\Support\Facades\Route;


Route::post('/epusdt/webhook', function () {
    return app('App\Extensions\Gateways\EPUSDTGateway\EPUSDTGateway')->EPUSDT_webhook(request());
});
Route::get('/epusdt/webhook', function () {
    return app('App\Extensions\Gateways\EPUSDTGateway\EPUSDTGateway')->EPUSDT_webhook(request());
});
Route::get('/epusdt/error', function () {
    $message = request('message', 'Unknown Error');
    return view('EPUSDTGateway::error', ['message' => $message]);
});
