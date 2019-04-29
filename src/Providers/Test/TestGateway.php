<?php

namespace Dena\IranPayment\Providers\Test;

use Dena\IranPayment\Models\IranPaymentTransaction;
use Dena\IranPayment\Providers\BaseProvider;
use Dena\IranPayment\Providers\GatewayInterface;

class TestGateway extends BaseProvider implements GatewayInterface {
    
    public function getName() {
        return 'test-gateway';
    }

	public function payRequest() {
        $code = rand(1, 10000);
        $this->setReferenceNumber($code);
        $this->transactionPending([
            'reference_number'	=> intval($code)
        ]);
    }

	public function verifyRequest() {
        $this->transactionVerifyPending();
        $code = rand(1, 10000);
        $this->setTrackingCode($code);
		$this->transactionSucceed(['tracking_code' => $code]);
    }

	public function redirectView() {
        return view('iranpayment::pages.test')->with([
            'transaction_code' => $this->getTransaction()->transaction_code,
            'reference_number' => $this->getReferenceNumber(),
        ]);
    }

	public function payBack() {
    }
}