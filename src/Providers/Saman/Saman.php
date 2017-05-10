<?php

namespace Dena\IranPayment\Providers\Saman;

use Dena\IranPayment\Exceptions\InvalidDataException;

use Dena\IranPayment\GatewayAbstract;

use Dena\IranPayment\Helpers\Helpers;
use Dena\IranPayment\Helpers\Currency;

use Log;
use Exception;
use SoapFault;
use SoapClient;
use Carbon\Carbon;

class Saman extends GatewayAbstract
{
	private $token;
	private $token_url;
	private $verify_url;
	private $payment_url;
	private $merchant_id;
	private $prepared_amount;
	private $connection_timeout;

	public function __construct()
	{
		parent::__construct();
		$this->setDefaults();
	}

	public function getGatewayName()
	{
		return 'saman';
	}

	private function setDefaults()
	{
		$this->merchant_id			= config('iranpayment.saman.merchant-id');
		$this->connection_timeout	= config('iranpayment.timeout', 30);
		$this->token_url			= config('iranpayment.saman.token_url', 'https://sep.shaparak.ir/Payments/InitPayment.asmx?wsdl');
		$this->payment_url			= config('iranpayment.saman.payment_url', 'https://sep.shaparak.ir/Payment.aspx');
		$this->verify_url			= config('iranpayment.saman.verify_url', 'https://verify.sep.ir/Payments/ReferencePayment.asmx?wsdl');

		$this->setCallbackUrl(config('iranpayment.saman.callback-url', config('iranpayment.callback-url')));
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
		if ($currency == parent::PCN_TOMAN) {
			$amount	= Currency::TomanToRial($amount);
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
		$this->prepare();
		$this->newTransaction();

		try{
			$soap = new SoapClient($this->token_url, [
				'encoding'				=> 'UTF-8', 
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> $this->connection_timeout,
			]);
			$token = $soap->RequestToken(
				$this->merchant_id,
				$this->getReferenceId(),
				$this->prepared_amount
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

	protected function verifyRequest()
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