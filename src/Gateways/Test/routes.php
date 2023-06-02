<?php

use Illuminate\Support\Facades\Route;

if (app('config')->get('iranpayment.test.active', false) &&
    app('config')->get('iranpayment.test.url') !== null) {
    Route::post(
        app('config')->get('iranpayment.test.url'),
        'Dena\IranPayment\Gateways\Test\TestGatewayController@paymentView'
    )->name('iranpayment.test.pay');
}
