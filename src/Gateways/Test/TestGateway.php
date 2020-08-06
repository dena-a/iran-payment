<?php

namespace Dena\IranPayment\Gateways\Test;

use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Exception;

class TestGateway extends AbstractGateway implements GatewayInterface
{
    public function getName(): string
    {
        return 'test-gateway';
    }

	protected function prePurchase(): void
    {
        $this->transactionPending();
    }

	public function verify(): void
    {
        $this->transactionVerifyPending();
        $code = rand(1, 10000);
		$this->transactionSucceed(['tracking_code' => $code]);
    }

    /**
     * @throws Exception
     */
	public function redirect()
    {
        $this->transactionUpdate([
            'transaction_code'	=> uniqid(),
        ]);
        $this->addExtra($this->getCallbackUrl(), 'callback_url');
        return view('iranpayment::pages.test')->with([
            'transaction_code' => $this->getTransactionCode(),
            'reference_number' => $this->getReferenceNumber(),
        ]);
    }

	public function purchase(): void
    {
        $this->transactionUpdate([
            'reference_number'	=> uniqid(),
		]);
    }

	public function gatewayPayView()
    {
        return view('iranpayment::pages.test', [
            'reference_number'	=> uniqid(),
			'transaction_code'	=> $this->getTransactionCode(),
		]);
    }

    public function purchaseUri(): string
    {
        return route('iranpayment.test.pay', $this->getPayable());
    }

    public function initialize(array $parameters = []): self
    {
        return $this;
    }
}
