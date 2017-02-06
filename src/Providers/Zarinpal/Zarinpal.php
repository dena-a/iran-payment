<?php

namespace Dena\IranPayment\Providers\Zarinpal;

use Dena\IranPayment\GatewayAbstract;
use Dena\IranPayment\IranPaymentInterface;

use Log;
use Config;
use Exception;
use SoapFault;
use SoapClient;

class Zarinpal extends GatewayAbstract implements IranPaymentInterface
{
	protected $germany_server	= 'https://de.zarinpal.com/pg/services/WebGate/wsdl';
	protected $iran_server		= 'https://ir.zarinpal.com/pg/services/WebGate/wsdl';
	protected $gate_url			= 'https://www.zarinpal.com/pg/StartPay/';
	protected $zaringate_url	= 'https://www.zarinpal.com/pg/StartPay/$Authority/ZarinGate';

	protected $server_url;
	protected $callback_url;
	protected $connection_timeout;

	public function __construct($gateway)
	{
		parent::__construct($gateway);
		$this->setServer();
		$this->callback_url			= Config::get('iranpayment.zarinpal.callback-url', Config::get('iranpayment.callback-url'));
		$this->connection_timeout	= Config::get('iranpayment.timeout', 30);
	}

	public function setAmount($amount)
	{
		$this->amount = intval($amount / 10);
		return $this;
	}

	public function ready()
	{
		$this->sendPayRequest();
		return $this;
	}

	public function verify($transaction)
	{
		parent::verify($transaction);

		$this->checkPayment();
		$this->verifyPayment();

		return $this;
	}

	protected function sendPayRequest()
	{
		$this->newTransaction();

		$fields = [
			'MerchantID'	=> Config::get('iranpayment.zarinpal.merchant-id'),
			'CallbackURL'	=> $this->callbackURL(),
			'Description'	=> Config::get('iranpayment.zarinpal.description'),
			'Email'			=> Config::get('iranpayment.zarinpal.email'),
			'Mobile'		=> Config::get('iranpayment.zarinpal.mobile'),
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

	protected function verifyPayment()
	{
		$fields				= [
			'MerchantID'	=> Config::get('iranpayment.zarinpal.merchant-id'),
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
		switch (Config::get('iranpayment.zarinpal.server', 'germany'))
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
		switch (Config::get('iranpayment.zarinpal.type')) {
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
