<?php

use Illuminate\Support\Facades\Route;

Route::get('/iranpayment/test/{reference}', 'Dena\IranPayment\Controllers\TestGatewayController@paymentView')
    ->name('iranpayment.test.pay');

Route::get('/iranpayment/test/{code}/verify',  'Dena\IranPayment\Controllers\TestGatewayController@verify')
    ->name('iranpayment.test.verify');
