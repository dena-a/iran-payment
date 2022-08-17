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
        return 'test';
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

	public function verify(): void
    {
        $trackingCode = rand(111111, 999999);
		$this->transactionSucceed([
            'card_number' => rand(1111, 9999).'********'.rand(1111, 9999),
            'tracking_code' => $trackingCode,
            'reference_number' => 'RefNum-'.$trackingCode,
        ]);
        if (
            (isset($this->request['State']) && $this->request['State'] !== 'OK') ||
            (isset($this->request['StateCode']) && $this->request['StateCode'] !== '0')
        ) {
            switch ($this->request['StateCode']) {
                case '-1':
                    throw SamanException::error(-101);
                case '51':
                    throw SamanException::error(51);
                default:
                    throw SamanException::error(-100);
            }
        }

        if (isset($this->request['MID']) && $this->request['MID'] !== $this->getMerchantId()) {
            throw SamanException::error(-4);
        }

        $this->transactionUpdate([
            'card_number' => $this->request['SecurePan'] ?? null,
            'tracking_code' => $this->request['TRACENO'] ?? null,
            'reference_number' => $this->request['RefNum'] ?? null,
        ]);
    }

    /**
     * @throws Exception
     */
	public function redirect()
    {
        $this->addExtra($this->getCallbackUrl(), 'callback_url');
        return view('iranpayment::pages.test')->with([
            'transaction_code' => $this->getTransactionCode(),
            'reference_number' => $this->getReferenceNumber(),
        ]);
    }

	public function purchase(): void
    {
        $this->transactionUpdate([
            'reference_number' => uniqid(),
		]);
    }

	public function purchaseView(array $arr = [])
    {
        return view('iranpayment::pages.test', [
            'reference_number' => uniqid(),
			'transaction_code' => $this->getTransactionCode(),
		]);
    }

    public function purchaseUri(): string
    {
        return route('iranpayment.test.pay', $this->getReferenceNumber());
    }
}
