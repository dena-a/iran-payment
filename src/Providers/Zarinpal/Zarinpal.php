<?php

namespace Dena\IranPayment\Providers\Zarinpal;

use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\PayBackNotPossibleException;

use Dena\IranPayment\Providers\BaseProvider;
use Dena\IranPayment\Providers\GatewayInterface;

use Dena\IranPayment\Helpers\Currency;

use Log;
use Exception;
use SoapFault;
use SoapClient;

class Zarinpal extends BaseProvider implements GatewayInterface
{
	/**
	 * Merchant ID variable
	 *
	 * @var string
	 */
	protected $merchant_id;

	/**
	 * Add Fees variable
	 *
	 * @var bool
	 */
	private $add_fees;

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
	public function gatewayName()
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

		$this->add_fees				= config('iranpayment.zarinpal.add_fees', false);
	}

	/**
	 * Set Merchant ID function
	 *
	 * @param string $merchant_id
	 * @return void
	 */
	public function setMerchantId(string $merchant_id)
	{
		$this->merchant_id = $merchant_id;

		return $this;
	}

	public function gatewayPayPrepare()
	{	
		//
	}

	public function gatewayPay()
	{
		$amount = $this->getPreparedAmount();
		if ($this->add_fees) {
			$amount += $amount * 1 / 100;
		}

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
	 * @return void
	 */
	private function payLink()
	{
		if (config('iranpayment.zarinpal.type') == 'zarin-gate') {
			return "https://www.zarinpal.com/pg/StartPay/{$this->getReferenceNumber()}/ZarinGate";
		}
		
		return "https://www.zarinpal.com/pg/StartPay/{$this->getReferenceNumber()}";
	}

	/**
	 * Pay View function
	 *
	 * @return void
	 */
	public function gatewayPayView()
	{
		$this->transactionPending();

		return view('iranpayment.pages.zarinpal', [
			'transaction_code'	=> $this->getTransactionCode(),
			'bank_url'			=> $this->payLink(),
		]);
	}

	/**
	 * Pay Redirect function
	 *
	 * @return void
	 */
	public function gatewayPayRedirect()
	{
		return redirect($this->payLink());
	}

	public function gatewayVerifyPrepare()
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

	public function gatewayVerify()
	{
		$amount = $this->getPreparedAmount();
		if ($this->add_fees) {
			$amount += $amount * 1 / 100;
		}

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

	public function gatewayPayBack()
	{
		throw new PayBackNotPossibleException;
	}
}
