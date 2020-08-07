<?php

namespace Dena\IranPayment\Controllers;

use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Models\IranPaymentTransaction;
use Illuminate\Http\Request;

class TestGatewayController {

    public function paymentView($payableId) {
        $payment = (new IranPayment('test'))->build();
        $transaction = IranPaymentTransaction::where('payable_id', $payableId)
            ->orderBy('id', 'desc')
            ->first();
        $payment->setTransaction($transaction);

        return $payment->view();
    }

    public function verify(Request $request, $code) {
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
    }
}
