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
	protected $server_url;
	protected $germany_server	= 'https://de.zarinpal.com/pg/services/WebGate/wsdl';
	protected $iran_server		= 'https://ir.zarinpal.com/pg/services/WebGate/wsdl';
	protected $gate_url			= 'https://www.zarinpal.com/pg/StartPay/';
	protected $zaringate_url	= 'https://www.zarinpal.com/pg/StartPay/$Authority/ZarinGate';

	public function __construct($gateway)
	{
		parent::__construct($gateway);
		$this->setServer();
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

		$this->userPayment();
		$this->verifyPayment();

		return $this;
	}

	protected function sendPayRequest()
	{
		$this->newTransaction();

		$fields = [
			'MerchantID'	=> Config::get('iranpayment.zarinpal.merchant-id'),
			'CallbackURL'	=> $this->buildQuery(Config::get('iranpayment.zarinpal.callback-url'), ['transaction' => $this->transactionHashids()]),
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
				'connection_timeout'	=> Config::get('iranpayment.zarinpal.timeout', 15),
			]);
			$response = $soap->PaymentRequest($fields);
		} catch(SoapFault $e) {
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
			$this->transactionFailed();
			throw $e;
		}

		if ($response->Status != 100) {
			throw new ZarinpalException($response->Status);
		}

		$this->reference_id = $response->Authority;
		$this->transactionSetReferenceId($this->transaction_id);
	}

	protected function userPayment()
	{
		$this->authority = request()->Authority;
		if ($this->authority != $this->reference_id) {
			throw new ZarinpalException(-11);
		}

		$status = request()->Status;
		if ($status == 'OK') {
			return true;
		}

		$this->transactionFailed();
		throw new ZarinpalException(-22);
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
				'connection_timeout'	=> Config::get('iranpayment.zarinpal.timeout', 15),
			]);
			$response = $soap->PaymentVerification($fields);
		} catch(SoapFault $e) {
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
			$this->transactionFailed();
			throw $e;
		}

		if ($response->Status != 100) {
			$this->transactionFailed();
			throw new ZarinpalException($response->Status);
		}

		$this->tracking_code = $response->RefID;
		$this->transactionSucceed();
		return true;
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
