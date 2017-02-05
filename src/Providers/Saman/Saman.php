<?php

namespace Dena\IranPayment\Providers\Saman;

use Dena\IranPayment\GatewayAbstract;
use Dena\IranPayment\IranPaymentInterface;

use Log;
use Config;
use Exception;
use SoapFault;
use SoapClient;
use Carbon\Carbon;

class Saman extends GatewayAbstract implements IranPaymentInterface
{
	protected $token_url		= 'https://sep.shaparak.ir/Payments/InitPayment.asmx?wsdl';
	protected $payment_url		= 'https://sep.shaparak.ir/Payment.aspx';
	protected $verify_url		= 'https://sep.shaparak.ir/Payment.aspx‬‬';

	protected $token;

	public function __construct($gateway)
	{
		parent::__construct($gateway);
	}

	public function setAmount($amount)
	{
		$this->amount = intval($amount);
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
		$this->generateResNum();
		$this->transactionSetReferenceId($this->reference_id);

		try{
			$soap = new SoapClient($this->token_url, [
				'encoding'				=> 'UTF-8', 
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> Config::get('iranpayment.timeout', 15),
			]);
			$token = $soap->RequestToken(
				Config::get('iranpayment.saman.merchant-id'),
				$this->transactionId(),
				$this->amount
			);
		} catch(SoapFault $e) {
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}

		if (is_numeric($token)) {
			$e	= new SamanException($token);
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		} else {
			$this->token = $token;
		}
	}

	protected function userPayment()
	{
		$this->authority = request()->Authority;
		if ($this->authority != $this->reference_id) {
			throw new SamanException(-11);
		}

		$status = request()->Status;
		if ($status == 'OK') {
			return true;
		}

		$this->transactionFailed();
		throw new SamanException(-22);
	}

	protected function verifyPayment()
	{
		$fields				= [
			'MerchantID'	=> Config::get('iranpayment.saman.merchant-id'),
			'Authority'		=> $this->reference_id,
			'Amount'		=> $this->amount,
		];

		try {
			$soap = new SoapClient($this->server_url, [
				'encoding'				=> 'UTF-8', 
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> Config::get('iranpayment.timeout', 15),
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
			throw new SamanException($response->Status);
		}

		$this->tracking_code = $response->RefID;
		$this->transactionSucceed();
		return true;
	}

	protected function generateResNum()
	{
		$this->reference_id = $this->generateRandomString();
	}

	public function redirect()
	{
		return view('iranpayment.pages.saman', [
			'token'			=> $this->token,
			'bank_url'		=> $this->payment_url,
			'redirect_url'	=> $this->buildQuery(Config::get('iranpayment.saman.callback-url'), ['transaction' => $this->transactionHashids()]),
		]);
	}

}