<?php

use Illuminate\Support\Facades\Route;

if(config('app.debug')) {
    Route::get('/iranpayment/test/{payable_id}', 'TestGatewayController@paymentView')
        ->name('iranpayment.test.pay');

    Route::get('/iranpayment/test/{code}/verify',  'TestGatewayController@verify')
        ->name('iranpayment.test.verify');
}