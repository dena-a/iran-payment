<?php

namespace Dena\IranPayment\Providers\PayIr;

use Dena\IranPayment\Exceptions\InvalidDataException;

use Dena\IranPayment\Providers\BaseProvider;

use Dena\IranPayment\Helpers\Currency;
use Dena\IranPayment\Providers\ProviderInterface;

use Log;
use Config;
use Exception;
use SoapFault;
use SoapClient;

class PayIr extends BaseProvider implements ProviderInterface
{
	protected $timeout;
	protected $callback_url;
	protected $api;
	protected $factor_number	= null;

	public function __construct()
	{
		parent::__construct();
		
		$this->setDefaults();
	}

	public function getName()
	{
		return 'pay.ir';
	}

	private function setDefaults()
	{
		$this->api					= config('iranpayment.payir.merchant-id', 'test');
		$this->connection_timeout	= config('iranpayment.timeout', 30);
		$this->setCallbackUrl(config('iranpayment.payir.callback-url', config('iranpayment.callback-url')));
	}

	public function setParams(array $params)
	{
		if (!empty($params['factor_number'])) {
			$this->factor_number	= $params['factor_number'];
		}
	}

	private function prepareAmount()
	{
		$currency	= $this->getCurrency();
		if (!in_array($currency, [parent::IRR, parent::IRT])) {
			throw new InvalidDataException(InvalidDataException::INVALID_CURRENCY);
		}

		$amount		= $this->getAmount();
		$amount		= intval($amount);

		if ($currency == parent::IRT) {
			$amount	= Currency::TomanToRial($amount);
		}

		if ($amount < 1000) {
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

		// $fields		= "api=$this->api&amount=$this->prepared_amount&redirect=".urlencode($this->callback_url)."&factorNumber=$this->factor_number";
		$fields = http_build_query([
			'api'			=> $this->api,
			'amount'		=> $this->prepared_amount,
			'redirect'		=> urlencode($this->callback_url),
			'factorNumber'	=> $this->factor_number,
			'mobile'		=> $this->mobile,
			'description'	=> $this->description,
		]);

		try {
			$ch		= curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://pay.ir/pg/send');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->connection_timeout);
			$result	= curl_exec($ch);
			curl_close($ch);
			$result = json_decode($result);
		} catch(Exception $e) {
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		if(!isset($result->status)) {
			$this->setDescription($result->errorMessage);
			$this->transactionFailed();
			throw new Exception($result->errorMessage, 400);
		}

		$this->setReferenceNumber($result->transId);
		$this->transactionUpdate([
			'reference_number'	=> $result->transId
		]);
	}

	protected function verifyPrepare()
	{
		$this->prepareAmount();

		if (!isset($this->request->transId, $this->request->status)) {
			$e = new ZarinpalException(-11);
			$this->setDescription($e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		$this->transactionVerifyPending();
	}

	protected function verifyRequest()
	{
		$this->verifyPrepare();

		$payload	= $this->getReferenceNumber();
		$fields		= "api=$this->api&transId=$payload";
		try {
			$ch		= curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://pay.ir/payment/verify');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); 
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->connection_timeout);
			$result	= curl_exec($ch);
			curl_close($ch);
			$result = json_decode($result);
		} catch(Exception $e){
			$this->setDescription($result->errorMessage);
			$this->transactionFailed();
			throw new Exception($result->errorMessage, 400);
		}

		if (!isset($result->status) || !$result->status) {
			$this->setDescription($result->errorMessage);
			$this->transactionFailed();
			throw new Exception($result->errorMessage, 400);
		} elseif ($result->amount != $amount) {
			$this->setDescription($result->errorMessage);
			$this->transactionFailed();
			throw new Exception($result->errorMessage, 400);
		}

		$this->transactionSucceed();
	}

	public function redirectView()
	{
		$this->transactionPending();

		$reference_number = $this->getReferenceNumber();
		$payment_url = "https://pay.ir/payment/gateway/$reference_number";

		return view('iranpayment.pages.payir', [
			'transaction_code'	=> $this->getTransactionCode(),
			'bank_url'			=> $payment_url,
		]);

		// return redirect("https://pay.ir/payment/gateway/$reference_number");
	}

	public function payBack()
	{
		throw new PayBackNotPossibleException;
	}
}