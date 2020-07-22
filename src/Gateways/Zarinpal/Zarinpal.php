<?php

namespace Dena\IranPayment\Gateways\Zarinpal;

use Dena\IranPayment\Exceptions\PayBackNotPossibleException;

use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Dena\IranPayment\Helpers\Currency;

use Exception;
use SoapFault;
use SoapClient;

class Zarinpal extends AbstractGateway implements GatewayInterface
{
	/**
	 * Merchant ID variable
	 *
	 * @var string
	 */
	protected string $merchant_id;

	public function __construct()
	{
		parent::__construct();
		$this->setDefaults();
	}

	/**
	 * Gateway Name function
	 *
	 * @return string
	 */
	public function gatewayName(): string
	{
		return 'zarinpal';
	}

	private function getServerUrl()
	{
		if (config('iranpayment.zarinpal.server') == 'germany') {
			return 'https://de.zarinpal.com/pg/services/WebGate/wsdl';
		}

		return 'https://ir.zarinpal.com/pg/services/WebGate/wsdl';
	}

	private function setDefaults()
	{
		$this->setGatewayCurrency(Currency::IRT);
		$this->setMerchantId(config('iranpayment.zarinpal.merchant-id'));
		$this->setCallbackUrl(config('iranpayment.zarinpal.callback-url', config('iranpayment.callback-url')));
		$this->setDescription(config('iranpayment.zarinpal.description', 'پرداخت'));
	}

	/**
	 * Set Merchant ID function
	 *
	 * @param string $merchant_id
	 * @return $this
	 */
	public function setMerchantId(string $merchant_id): self
	{
		$this->merchant_id = $merchant_id;

		return $this;
	}

	public function gatewayPayPrepare(): void
	{
		//
	}

	public function gatewayPay(): void
	{
		$amount = $this->getPreparedAmount();

		$fields = [
			'MerchantID'	=> $this->merchant_id,
			'CallbackURL'	=> $this->getCallbackUrl(),
			'Mobile'		=> $this->getMobile(),
			'Description'	=> $this->getDescription(),
			'Amount'		=> $amount,
		];

		try {
			$soap = new SoapClient($this->getServerUrl(), [
				'encoding'				=> 'UTF-8',
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> $this->connection_timeout,
			]);
			$response = $soap->PaymentRequest($fields);
		} catch(SoapFault $e) {
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		if ($response->Status != 100) {
			$e	= new ZarinpalException($response->Status);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		$this->transactionUpdate([
			'reference_number'	=> intval($response->Authority)
		]);
	}

	/**
	 * Pay Link function
	 *
	 * @return string
	 */
	public function gatewayPayUri(): string
	{
		if (config('iranpayment.zarinpal.type') == 'zarin-gate') {
			return "https://www.zarinpal.com/pg/StartPay/{$this->getReferenceNumber()}/ZarinGate";
		}

		return "https://www.zarinpal.com/pg/StartPay/{$this->getReferenceNumber()}";
	}

	/**
	 * Pay View function
	 *
     * @return mixed
     */
	public function gatewayPayView()
	{
		$this->transactionPending();

		return view('iranpayment::pages.zarinpal', [
			'transaction_code'	=> $this->getTransactionCode(),
			'bank_url'			=> $this->gatewayPayUri(),
		]);
	}

	/**
	 * Pay Redirect function
	 *
	 * @return mixed
	 */
	public function gatewayPayRedirect()
	{
		return redirect($this->gatewayPayUri());
	}

	public function gatewayVerifyPrepare(): void
	{
		if (intval($this->request->Authority) !== intval($this->transaction->reference_number)) {
			$e = new ZarinpalException(-11);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}
		if ($this->request->Status != 'OK') {
			$e = new ZarinpalException(-22);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}
		$this->transactionVerifyPending();
	}

	public function gatewayVerify(): void
	{
		$amount = $this->getPreparedAmount();

		$fields				= [
			'MerchantID'	=> $this->merchant_id,
			'Authority'		=> intval($this->transaction->reference_number)	,
			'Amount'		=> $amount,
		];

		try {
			$soap = new SoapClient($this->getServerUrl(), [
				'encoding'				=> 'UTF-8',
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> $this->connection_timeout,
			]);
			$response = $soap->PaymentVerification($fields);
		} catch(SoapFault $e) {
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}
		if ($response->Status != 100) {
			$e = new ZarinpalException($response->Status);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		$this->transactionSucceed(['tracking_code' => $response->RefID]);
	}

    /**
     * @throws PayBackNotPossibleException
     */
	public function gatewayPayBack(): void
	{
		throw new PayBackNotPossibleException;
	}
}
