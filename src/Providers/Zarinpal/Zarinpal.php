<?php

namespace Dena\IranPayment\Providers\Zarinpal;

use Dena\IranPayment\Exceptions\InvalidDataException;

use Dena\IranPayment\GatewayAbstract;

use Dena\IranPayment\Helpers\Currency;

use Log;
use Exception;
use SoapFault;
use SoapClient;

class Zarinpal extends GatewayAbstract
{
	private $server_url;
	private $prepared_amount;
	private $connection_timeout;
	private $zp_email;
	private $zp_mobile;
	private $zp_description;

	private $gate_url		= 'https://www.zarinpal.com/pg/StartPay/';
	private $zaringate_url	= 'https://www.zarinpal.com/pg/StartPay/$Authority/ZarinGate';

	public function __construct()
	{
		parent::__construct();
		$this->setDefaults();
	}

	public function getGateway()
	{
		return 'zarinpal';
	}

	private function setDefaults()
	{
		switch (config('iranpayment.zarinpal.server', 'germany'))
		{
			case 'iran':
				$this->server_url	= config('iranpayment.zarinpal.iran_server_url', 'https://ir.zarinpal.com/pg/services/WebGate/wsdl');
			break;
			case 'germany':
			default:
				$this->server_url	= config('iranpayment.zarinpal.germany_server_url', 'https://de.zarinpal.com/pg/services/WebGate/wsdl');
			break;
		}
		$this->connection_timeout	= config('iranpayment.timeout', 30);

		$this->setCallbackUrl(config('iranpayment.zarinpal.callback-url', config('iranpayment.callback-url')));

		$this->zp_description	= config('iranpayment.zarinpal.description', 'description');
		$this->zp_email			= config('iranpayment.zarinpal.email', null);
		$this->zp_mobile		= config('iranpayment.zarinpal.mobile', null);
	}

	public function setParams(array $params)
	{
		if (isset($params['description']) && mb_strlen($params['description'])) {
			$this->zp_description	= $params['description'];
		}
		if (isset($params['email']) && mb_strlen($params['email'])) {
			$this->zp_email			= $params['email'];
		}
		if (isset($params['mobile']) && mb_strlen($params['mobile'])) {
			$this->zp_mobile		= $params['mobile'];
		}
	}

	private function prepareAmount()
	{
		$amount		= $this->getAmount();
		$amount		= intval($amount);
		if ($amount <= 0) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		$currency	= $this->getCurrency();
		if (!in_array($currency, [parent::IRR, parent::IRT])) {
			throw new InvalidDataException(InvalidDataException::INVALID_CURRENCY);
		}
		if ($currency == parent::IRR) {
			$amount	= Currency::RialToToman($amount);
		}
		if ($amount < 100) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		$this->prepared_amount	= $amount;
	}

	public function payPrepare()
	{	
		$this->prepareAmount();
	}

	protected function payRequest()
	{
		$this->payPrepare();

		$fields = [
			'MerchantID'	=> config('iranpayment.zarinpal.merchant-id'),
			'CallbackURL'	=> $this->callbackURL(),
			'Description'	=> $this->zp_description,
			'Email'			=> $this->zp_email,
			'Mobile'		=> $this->zp_mobile,
			'Amount'		=> $this->prepared_amount,
		];

		try {
			$soap = new SoapClient($this->server_url, [
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

		$this->setReferenceNumber(intval($response->Authority));
		$this->transactionUpdate([
			'reference_number'	=> intval($response->Authority)
		]);
	}

	protected function verifyPrepare()
	{
		$this->prepareAmount();

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

	protected function verifyRequest()
	{
		$this->verifyPrepare();

		$fields				= [
			'MerchantID'	=> config('iranpayment.zarinpal.merchant-id'),
			'Authority'		=> intval($this->transaction->reference_number)	,
			'Amount'		=> $this->prepared_amount,
		];

		try {
			$soap = new SoapClient($this->server_url, [
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

		$this->setTrackingCode($response->RefID);
		$this->transactionSucceed(['tracking_code' => $response->RefID]);
	}

	public function redirect()
	{
		$this->transactionPending();
		switch (config('iranpayment.zarinpal.type')) {
			case 'zarin-gate':
				$payment_url = str_replace('$Authority', $this->getReferenceNumber(), $this->zaringate_url);
				break;
			case 'normal':
			default:
				$payment_url = $this->gate_url.$this->getReferenceNumber();
				break;
		}
		return redirect($payment_url);
	}
}
