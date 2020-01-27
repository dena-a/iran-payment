<?php

use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Models\IranPaymentTransaction;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::get('/iranpayment/test/{payable_id}', function($payable_id) {
    $payment = (new IranPayment('test'))->build();
    $transaction = IranPaymentTransaction::where('payable_id', $payable_id)->orderBy('id', 'desc')->first();
	$payment->setTransaction($transaction);
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

	$callback = $payment->getCallbackUrl();

    $question_mark = strpos($callback, '?');
    if($question_mark) {
        return redirect($callback.'&'.$queryParams);
    }
    return redirect($callback.'?'.$queryParams);
})->name('iranpayment.test.verify');
