<?php

namespace Dena\IranPayment\Providers\Test;

use Dena\IranPayment\Models\IranPaymentTransaction;
use Dena\IranPayment\Providers\BaseProvider;
use Dena\IranPayment\Providers\ProviderInterface;

class TestGateway extends BaseProvider implements ProviderInterface {
    
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
        return view('iranpayment.pages.test')->with([
            'transaction' => $this->getTransaction(),
        ]);
    }

	public function payBack() {
    }
}