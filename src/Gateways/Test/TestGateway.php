<?php

namespace Dena\IranPayment\Gateways\Test;

use Dena\IranPayment\Models\IranPaymentTransaction;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

class TestGateway extends AbstractGateway implements GatewayInterface {

    public function gatewayName() {
        return 'test-gateway';
    }

	public function gatewayPayPrepare() {
        $this->transactionPending();
    }

    public function gatewayVerifyPrepare() {
    }

	public function gatewayVerify() {
        $this->transactionVerifyPending();
        $code = rand(1, 10000);
		$this->transactionSucceed(['tracking_code' => $code]);
    }

	public function gatewayPayRedirect() {
        $this->transactionUpdate([
            'transaction_code'	=> uniqid(),
        ]);
        $this->addExtra($this->getCallbackUrl(), 'callback_url');
        return view('iranpayment::pages.test')->with([
            'transaction_code' => $this->getTransaction()->code,
            'reference_number' => $this->getReferenceNumber(),
        ]);
    }

	public function gatewayPayBack() {
    }

	public function gatewayPay() {
        $this->transactionUpdate([
            'reference_number'	=> uniqid(),
		]);
    }

	public function gatewayPayView() {
        return view('iranpayment::pages.test', [
            'reference_number'	=> uniqid(),
			'transaction_code'	=> $this->getTransactionCode(),
		]);
    }

    public function gatewayPayUri() {
        return route('iranpayment.test.pay', $this->getPayable());
    }
}
