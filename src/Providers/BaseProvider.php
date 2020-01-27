<?php

namespace Dena\IranPayment\Providers;

use Exception;
use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\SucceedRetryException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;
use Dena\IranPayment\Exceptions\GatewayPaymentNotSupportViewException;
use Dena\IranPayment\Exceptions\GatewayPaymentNotSupportRedirectException;
use Dena\IranPayment\Exceptions\PayBackNotPossibleException;
use Illuminate\Http\Request;
use Dena\IranPayment\Models\IranPaymentTransaction;

use Dena\IranPayment\Traits\UserData;
use Dena\IranPayment\Traits\PaymentData;
use Dena\IranPayment\Traits\TransactionData;

use Dena\IranPayment\Helpers\Helpers;
use Dena\IranPayment\Helpers\Currency;
use Illuminate\Support\Facades\View;

/**
 * 'BaseProvider'
 *
 * @method gatewayPayPrepare()
 * @method gatewayName()
 * @method gatewayPay()
 * @method gatewayPayLink()
 * @method gatewayPayRedirect()
 * @method gatewayPayUri()
 * @method gatewayPayView()
 * @method detectGateway()
 * @method gatewayVerifyPrepare()
 * @method gatewayVerify()
 * @method gatewayPayBack()
 */
abstract class BaseProvider
{
	use UserData, PaymentData, TransactionData;

	protected $request;
	protected $callback_url;
	protected $timeout;
	protected $connection_timeout;

	/**
	 * Constructor function
	 */
	public function __construct()
	{
		$this->setDefaults();
	}

	/**
	 * Set Defaults function
	 *
	 * @return void
	 */
	private function setDefaults()
	{
		$this->setRequest(app('request'));
		$this->setCurrency(config('iranpayment.currency', Currency::IRR));
		$this->setCallbackUrl(config('iranpayment.callback-url'));

		$this->timeout				= config('iranpayment.timeout', 30);
		$this->connection_timeout	= config('iranpayment.connection_timeout', 30);
	}

	/**
	 * Set Request function
	 *
	 * @param Request $request
	 * @return self
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
		return $this;
	}

	/**
	 * Set Callback Url function
	 *
	 * @param string $callback_url
	 * @return self
	 */
	public function setCallbackUrl(string $callback_url)
	{
		if (filter_var($callback_url, FILTER_VALIDATE_URL) === false) {
			$this->callback_url = url($callback_url);
		} else {
			$this->callback_url = $callback_url;
		}
		
		return $this;
	}

	/**
	 * Get Callback Url function
	 *
	 * @return string
	 */
	public function getCallbackUrl()
	{
		return Helpers::urlQueryBuilder($this->callback_url, [
			config('iranpayment.transaction_query_param', 'tc') => $this->getTransactionCode()
		]);
	}

	/**
	 * Pay function
	 *
	 * @return self
	 */
	public function ready()
	{
		if ($this->amount <= 0) {
			throw InvalidDataException::invalidAmount();
		}

		if (!in_array($this->currency, [Currency::IRR, Currency::IRT])) {
			throw InvalidDataException::invalidCurrency();
		}

		if (filter_var($this->callback_url, FILTER_VALIDATE_URL) === false) {
			throw InvalidDataException::invalidCallbackUrl();
		}

		try {
			$this->newTransaction();

			$this->gatewayPayPrepare();

			$this->gatewayPay();
		} catch (InvalidDataException $idex) {
			throw $idex;
		} catch (GatewayException $gwex) {
			throw $gwex;
		}

		return $this;
	}

	/**
	 * Pay View function
	 *
	 * @return View
	 */
	public function view()
	{
		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
		}

		try {
			$this->transactionPending();

			return $this->gatewayPayView();
		} catch (GatewayPaymentNotSupportViewException $gpnsvex) {
			throw $gpnsvex;
		} catch (Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * Pay Uri function
	 *
	 * @return string
	 */
	public function uri()
	{
		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
		}

		try {
			$this->transactionPending();

			return $this->gatewayPayUri();
		} catch (Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * Pay Redirect function
	 *
	 * @return View
	 */
	public function redirect()
	{
		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
		}

		try {
			$this->transactionPending();

			return $this->gatewayPayRedirect();
		} catch (GatewayPaymentNotSupportRedirectException $gpnsrex) {
			throw $gpnsrex;
		} catch (Exception $ex) {
			throw $ex;
		}
	}

	/**
	 * General Redirect View function
	 *
	 * @return View
	 */
	public function generalRedirectView()
	{
		if (method_exists($this, 'gatewayTitle')) {
			$title = $this->gatewayTitle();
		}
		
		if (method_exists($this, 'gatewayImage')) {
			$image = $this->gatewayImage();
		}

		return view('iranpayment::pages.redirect', [
			'title'				=> $title ?? 'درحال انتقال به درگاه پرداخت...',
			'image'				=> $image ?? null,
			'transaction_code'	=> $this->getTransactionCode(),
			'bank_url'			=> $this->gatewayPayUri(),
		]);
	}

	public static function detectGateway(Request $request = null)
	{
		if (!isset($request)) {
			/**
			 * @return Request
			*/
			$request = app('request');
		}

		$transaction_code_field = config('iranpayment.transaction_query_param', 'tc');
		if (isset($request->$transaction_code_field)) {
			$transaction = IranPaymentTransaction::where('code', $request->$transaction_code_field)->first();
			if (isset($transaction, $transaction->gateway)) {
				return $transaction->gateway;
			}
		}
	}

	/**
	 * Verify function
	 *
	 * @return self
	 */
	public function verify(IranPaymentTransaction $transaction = null)
	{
		if(isset($transaction)) {
			$this->setTransaction($transaction);
		} elseif(!isset($this->transaction)) {
			$transaction_code_field = config('iranpayment.transaction_query_param', 'tc');
			if (isset($this->request->$transaction_code_field)) {
				$this->searchTransactionCode($this->request->$transaction_code_field);
			}
		}

		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
		}

		if ($this->transaction->status == IranPaymentTransaction::T_SUCCEED) {
			throw new SucceedRetryException;
		} elseif (!in_array($this->transaction->status, [
			IranPaymentTransaction::T_PENDING,
			IranPaymentTransaction::T_VERIFY_PENDING,
		])) {
			throw InvalidRequestException::unProcessableVerify();
		}

		$this->setCurrency($this->transaction->currency);
		$this->setAmount($this->transaction->amount);

		try {
			$this->gatewayVerifyPrepare();

			$this->gatewayVerify();

			$this->transactionSucceed();
		} catch (Exception $ex) {
			throw $ex;
		}

		return $this;
	}

	/**
	 * Pay Back function
	 *
	 * @return void
	 */
	protected function payBack(IranPaymentTransaction $transaction = null)
	{
		if(isset($transaction)) {
			$this->setTransaction($transaction);
		} elseif(!isset($this->transaction)) {
			$transaction_code_field = config('iranpayment.transaction_query_param', 'tc');
			if (isset($this->request->$transaction_code_field)) {
				$this->searchTransactionCode($this->request->$transaction_code_field);
			}
		}

		if (!isset($this->transaction)) {
			throw new TransactionNotFoundException();
		}

		try {
			$this->gatewayPayBack();

			$this->transactionPaidBack();
		} catch (PayBackNotPossibleException $pbex) {
			throw $pbex;
		} catch (GatewayException $gwex) {
			throw $gwex;
		} catch (Exception $ex) {
			throw $ex;
		}
	}
}
