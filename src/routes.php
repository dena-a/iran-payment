<?php

use Illuminate\Support\Facades\Route;

if(config('app.debug')) {
    Route::get('/iranpayment/test/{payable_id}', 'Dena\IranPayment\Controllers\TestGatewayController@paymentView')
        ->name('iranpayment.test.pay');

    Route::get('/iranpayment/test/{code}/verify',  'Dena\IranPayment\Controllers\TestGatewayController@verify')
        ->name('iranpayment.test.verify');
}
