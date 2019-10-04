<?php

use Dena\IranPayment\IranPayment;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::get('/iranpayment/test/{payable_id}', function() {
    $payment = (new IranPayment('test'))->build();
    return $payment->gatewayPayView();
})->name('iranpayment.test.pay');

Route::get('/iranpayment/test/{code}/verify', function(Request $request, $code) {
    $payment = (new IranPayment('test'))->build();
    $payment->searchTransactionCode($code);
        
    $transaction = $payment->getTransaction();
        
    $payment->verify();
    
    $extra = $payment->getExtra();

    $queryParams = http_build_query([
        'payable_id' => $transaction->payable_id,
        'transaction_id' => $transaction->id
    ]);

    return redirect($extra['callback_url'].'&'.$queryParams);
})->name('iranpayment.test.verify');
