<?php

namespace Dena\IranPayment\Gateways\Test;

use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Models\IranPaymentTransaction;

use Illuminate\Http\Request;

class TestGatewayController {

    public function paymentView($code)
    {
        $payment = (new IranPayment('test'))->build();
        $transaction = IranPaymentTransaction::where('reference_number', $code)->first();
        $payment->setTransaction($transaction);

        return $payment->view();
    }

    public function verify(Request $request, $code)
    {
        $transaction = IranPaymentTransaction::where('code', $code)->first();

        $queryParams = http_build_query([
            app('config')->get('iranpayment.transaction_query_param') => $code
        ]);

        $callback = $transaction->extra['callback_url'];;

        $question_mark = strpos($callback, '?');
        if($question_mark) {
            return redirect($callback.'&'.$queryParams);
        }

        return redirect($callback.'?'.$queryParams);
    }
}
