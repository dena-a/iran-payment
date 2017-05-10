<?php

namespace Dena\IranPayment\Providers\Zarinpal;

use Dena\IranPayment\GatewayAbstract;
use Dena\IranPayment\IranPaymentInterface;

use Log;
use Config;
use Exception;
use SoapFault;
use SoapClient;

class Zarinpal extends GatewayAbstract
{
	private $prepared_amount;

	protected $germany_server	= 'https://de.zarinpal.com/pg/services/WebGate/wsdl';
	protected $iran_server		= 'https://ir.zarinpal.com/pg/services/WebGate/wsdl';
	protected $gate_url			= 'https://www.zarinpal.com/pg/StartPay/';
	protected $zaringate_url	= 'https://www.zarinpal.com/pg/StartPay/$Authority/ZarinGate';

	protected $server_url;
	protected $callback_url;
	protected $connection_timeout;

	protected $description;
	protected $email;
	protected $mobile;

	public function __construct()
	{
		parent::__construct();
		$this->setDefaults();
	}

	public function getGatewayName()
	{
		return 'zarinpal';
	}

	private function setDefaults()
	{
		$this->setServer();
		$this->callback_url			= config('iranpayment.zarinpal.callback-url', config('iranpayment.callback-url'));
		$this->connection_timeout	= config('iranpayment.timeout', 30);

		$this->setDescription(config('iranpayment.zarinpal.description', null));
		$this->setEmail(config('iranpayment.zarinpal.email', null));
		$this->setMobile(config('iranpayment.zarinpal.mobile', null));
	}

	public function setAmount($amount)
	{
		$this->amount = intval($amount / 10);
		return $this;
	}

	public function setParams(array $params)
	{
		if (isset($params['description']) && mb_strlen($params['description'])) {
			$this->setDescription($params['description']);
		}
		if (isset($params['email']) && mb_strlen($params['email'])) {
			$this->setEmail($params['email']);
		}
		if (isset($params['mobile']) && mb_strlen($params['mobile'])) {
			$this->setMobile($params['mobile']);
		}
	}

	public function setDescription($description)
	{
		$this->description = $description;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setEmail($email)
	{
		$this->email = $email;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setMobile($mobile)
	{
		$this->mobile = $mobile;
	}

	public function getMobile()
	{
		return $this->mobile;
	}

	public function prepare()
	{
		$amount		= $this->getAmount();
		$amount		= intval($amount);
		if ($amount < 0) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		$currency	= $this->getCurrency();
		if (!in_array($currency, [parent::PCN_RIAL, parent::PCN_TOMAN])) {
			throw new InvalidDataException(InvalidDataException::INVALID_CURRENCY);
		}
		if ($currency == parent::PCN_RIAL) {
			$amount	= Currency::RialToToman($amount);
		}
		if ($amount < 100) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		$this->prepared_amount	= $amount;
		$this->setReferenceId(Helpers::generateRandomString());
	}

	public function verify($transaction)
	{
		parent::verify($transaction);

		$this->checkPayment();
		$this->verifyPayment();

		return $this;
	}

	protected function payRequest()
	{
		$this->newTransaction();

		$fields = [
			'MerchantID'	=> config('iranpayment.zarinpal.merchant-id'),
			'CallbackURL'	=> $this->callbackURL(),
			'Description'	=> $this->description,
			'Email'			=> $this->email,
			'Mobile'		=> $this->mobile,
			'Amount'		=> $this->amount,
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
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}

		if ($response->Status != 100) {
			$e	= new ZarinpalException($response->Status);
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}

		$this->reference_id = $response->Authority;
		$this->transactionSetReferenceId($this->transaction_id);
	}

	protected function checkPayment()
	{
		if (request()->Authority !== $this->reference_id) {
			$e = new ZarinpalException(-11);
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}
		if (request()->Status != 'OK') {
			$e = new ZarinpalException(-22);
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}
		$this->transactionVerifyPending();
	}

	protected function verifyRequest()
	{
		$fields				= [
			'MerchantID'	=> config('iranpayment.zarinpal.merchant-id'),
			'Authority'		=> $this->reference_id,
			'Amount'		=> $this->amount,
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
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}
		if ($response->Status != 100) {
			$e = new ZarinpalException($response->Status);
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}

		$this->tracking_code = $response->RefID;
		$this->transactionSucceed(['tracking_code' => $this->tracking_code]);
	}

	protected function setServer()
	{
		switch (config('iranpayment.zarinpal.server', 'germany'))
		{
			case 'iran':
				$this->server_url = $this->iran_server;
			break;
			case 'germany':
			default:
				$this->server_url = $this->germany_server;
			break;
		}
	}

	public function redirect()
	{
		$this->transactionPending();
		switch (config('iranpayment.zarinpal.type')) {
			case 'zarin-gate':
				$payment_url = str_replace('$Authority', $this->reference_id, $this->zaringate_url);
				break;
			case 'normal':
			default:
				$payment_url = $this->gate_url.$this->reference_id;
				break;
		}
		return redirect($payment_url);
	}
}
