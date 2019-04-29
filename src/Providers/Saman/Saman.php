<?php

namespace Dena\IranPayment\Providers\Saman;

use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\PayBackNotPossibleException;

use Dena\IranPayment\Providers\BaseProvider;

use Dena\IranPayment\Helpers\Currency;
use Dena\IranPayment\Providers\GatewayInterface;

use Log;
use Exception;
use SoapFault;
use SoapClient;

class Saman extends BaseProvider implements GatewayInterface
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

	public function getName()
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

	private function prepareAmount()
	{
		$amount		= $this->getAmount();
		$amount		= intval($amount);
		if ($amount <= 0) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		$currency	= $this->getCurrency();
		if (!in_array($currency, [Currency::IRR, Currency::IRT])) {
			throw new InvalidDataException(InvalidDataException::INVALID_CURRENCY);
		}
		if ($currency == Currency::IRT) {
			$amount	= Currency::TomanToRial($amount);
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

	public function payRequest()
	{
		$this->payPrepare();

		try{
			$soap = new SoapClient($this->token_url, [
				'encoding'				=> 'UTF-8', 
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> $this->connection_timeout,
			]);
			$token = $soap->RequestToken(
				$this->merchant_id,
				$this->getTransactionCode(),
				$this->prepared_amount
			);
		} catch(SoapFault $e) {
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		if (is_numeric($token)) {
			$e	= new SamanException($token);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		} else {
			$this->token = $token;
		}
	}

	public function verifyPrepare()
	{
		$this->prepareAmount();
		if ($this->request->State != 'OK' || $this->request->StateCode != '0' ) {
			switch ($this->request->StateCode) {
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
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}
		if ($this->request->transaction !== $this->getTransactionCode()) {
			$e	= new SamanException(-14);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}
		if ($this->request->MID !== $this->merchant_id) {
			$e	= new SamanException(-4);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		$this->setTrackingCode($this->request->TRACENO);
		$this->setCardNumber($this->request->SecurePan);
		$this->setReferenceNumber($this->request->RefNum);
		
		$this->transactionUpdate([
			'card_number'		=> $this->request->SecurePan,
			'tracking_code'		=> $this->request->TRACENO,
			'reference_number'	=> $this->request->RefNum,
		]);
		$this->transactionVerifyPending();
	}

	public function verifyRequest()
	{
		$this->verifyPrepare();

		try{
			$soap = new SoapClient($this->verify_url, [
				'encoding'				=> 'UTF-8', 
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> $this->connection_timeout,
			]);
			$amount = $soap->verifyTransaction(
				$this->getReferenceNumber(),
				$this->merchant_id
			);
		} catch(SoapFault $e) {
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		if ($amount <= 0) {
			$e	= new SamanException($amount);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		if ($amount != $this->prepared_amount) {
			$e	= new SamanException(-102);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		$this->transactionSucceed();
	}

	public function redirectView()
	{
		$this->transactionPending();
		return view('iranpayment::pages.saman', [
			'transaction_code'	=> $this->getTransactionCode(),
			'token'				=> $this->token,
			'bank_url'			=> $this->payment_url,
			'redirect_url'		=> $this->getCallbackUrl(),
		]);
	}

	public function payBack()
	{
		throw new PayBackNotPossibleException;
	}

}