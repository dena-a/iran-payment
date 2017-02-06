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
	protected $verify_url		= 'https://verify.sep.ir/Payments/ReferencePayment.asmx?wsdl';

	protected $token;
	protected $merchant_id;
	protected $callback_url;
	protected $connection_timeout;

	public function __construct($gateway)
	{
		parent::__construct($gateway);
		$this->merchant_id			= Config::get('iranpayment.saman.merchant-id');
		$this->callback_url			= Config::get('iranpayment.saman.callback-url', Config::get('iranpayment.callback-url'));
		$this->connection_timeout	= Config::get('iranpayment.timeout', 30);
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

		$this->checkPayment();
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
				'connection_timeout'	=> $this->connection_timeout,
			]);
			$token = $soap->RequestToken(
				$this->merchant_id,
				$this->reference_id,
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

	protected function checkPayment()
	{
		if (request()->State != 'OK' || request()->StateCode != '0' ) {
			switch (request()->StateCode) {
				case '-1':
					$e	= new SamanException(-101);
					break;
				case '51':
					$e	= new SamanException(51);
					break;
				default:
					$e	= new SamanException(-100);
					break;
			}
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}
		if (request()->ResNum !== $this->reference_id) {
			$e	= new SamanException(-14);
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}
		if (request()->MID !== $this->merchant_id) {
			$e	= new SamanException(-4);
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}

		$this->tracking_code	= request()->TRACENO;
		$this->transactionUpdate([
			'card_number'		=> request()->SecurePan,
			'tracking_code'		=> $this->tracking_code,
			'receipt_number'	=> request()->RefNum,
		]);
		$this->transactionVerifyPending();
	}

	protected function verifyPayment()
	{
		try{
			$soap = new SoapClient($this->verify_url, [
				'encoding'				=> 'UTF-8', 
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> $this->connection_timeout,
			]);
			$amount = $soap->verifyTransaction(
				$this->transaction->receipt_number,
				$this->merchant_id
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

		if ($amount <= 0) {
			$e	= new SamanException($amount);
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}

		if ($amount != $this->amount) {
			$e	= new SamanException(-102);
			$this->description = $e->getMessage();
			$this->transactionFailed();
			throw $e;
		}

		$this->transactionSucceed();
	}

	protected function generateResNum()
	{
		$this->reference_id = $this->generateRandomString();
	}

	public function redirect()
	{
		$this->transactionPending();
		return view('iranpayment.pages.saman', [
			'reference_id'	=> $this->reference_id,
			'token'			=> $this->token,
			'bank_url'		=> $this->payment_url,
			'redirect_url'	=> $this->callbackURL(),
		]);
	}

}