<?php

namespace Dena\IranPayment\Gateways\Test;

use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;
use Dena\IranPayment\Helpers\Currency;
use Exception;

class TestGateway extends AbstractGateway implements GatewayInterface
{
    public function getName(): string
    {
        return 'test-gateway';
    }

    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);

        $this->setGatewayCurrency(Currency::IRR);

        $this->setCallbackUrl($parameters['callback_url']
            ?? app('config')->get('iranpayment.test.callback-url')
            ?? app('config')->get('iranpayment.callback-url')
        );

        return $this;
    }

	protected function prePurchase(): void
	{
        parent::prePurchase();

        if ($this->preparedAmount() < 1000 || $this->preparedAmount() > 500000000) {
            throw InvalidDataException::invalidAmount();
        }
    }
    
    public function preVerify(): void
    {
        parent::preVerify();
    }

	public function verify(): void
    {
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

	public function purchaseView(array $arr = [])
    {
        return view('iranpayment::pages.test', [
            'reference_number'	=> uniqid(),
			'transaction_code'	=> $this->getTransactionCode(),
		]);
    }

    public function purchaseUri(): string
    {
        return route('iranpayment.test.pay', $this->getReferenceNumber());
    }
}
