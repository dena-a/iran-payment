<?php

namespace Dena\IranPayment\Gateways\Test;

use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Models\IranPaymentTransaction;

use Illuminate\Http\Request;

class TestGatewayController {

    public function paymentView($code)
    {
        $payment = IranPayment::create('test');
        $transaction = IranPaymentTransaction::where('reference_number', $code)->first();
        $payment->setTransaction($transaction);

        return $payment->view();
    }

    public function verify(Request $request, $code)
    {
        $payment = IranPayment::create('test');
        $payment->findTransaction($code);

        $transaction = $payment->getTransaction();

        if (!$transaction) {
            throw new GatewayException('تراکنش پیدا نشد.');
        }

        $extra = $payment->getExtra();

        $queryParams = http_build_query([
            app('config')->get('iranpayment.transaction_query_param') => $code
        ]);

        $callback = $payment->getCallbackUrl();

        $question_mark = strpos($callback, '?');
        if($question_mark) {
            return redirect($callback.'&'.$queryParams);
        }

        return redirect($callback.'?'.$queryParams);
    }
}
