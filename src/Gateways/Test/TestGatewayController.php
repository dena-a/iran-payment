<?php

namespace Dena\IranPayment\Gateways\Test;

use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Models\IranPaymentTransaction;

class TestGatewayController
{
    public function paymentView()
    {
        $payment = IranPayment::create('test');
        $transaction = IranPaymentTransaction::where('reference_number', request()->get('reference_number'))->first();
        $payment->setTransaction($transaction);

        return $payment->bankView();
    }
}
