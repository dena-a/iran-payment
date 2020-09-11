<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Dena\IranPayment\Gateways\Test'], function () {
    Route::get('/iranpayment/test/{reference}', 'TestGatewayController@paymentView')
        ->name('iranpayment.test.pay');

    Route::get('/iranpayment/test/{code}/verify',  'TestGatewayController@verify')
        ->name('iranpayment.test.verify');
});
