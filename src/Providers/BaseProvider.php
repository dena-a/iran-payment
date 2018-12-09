<?php

namespace Dena\IranPayment\Providers;

use Dena\IranPayment\Exceptions\RetryException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\SucceedRetryException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Dena\IranPayment\Traits\UserData;
use Dena\IranPayment\Traits\PaymentData;

use Carbon\Carbon;
use Dena\IranPayment\Helpers\Helpers;
use Dena\IranPayment\Helpers\Hashids;

abstract class BaseProvider
{
	use UserData, PaymentData;

	const IRR	= 'IRR';
	const IRT	= 'IRT';

	protected $request;
	protected $callback_url;
	protected $transaction_code;

	protected $transaction		= null;
	protected $card_number		= null;
	protected $reference_number	= null;
	protected $tracking_code	= null;
	protected $description		= null;
	protected $extra			= null;

	public function __construct()
	{
		$this->setDefaults();
	}

	private function setDefaults()
	{
		$this->request = app('request');
		$this->setCurrency(self::IRR);
	}

	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setExtra($extra)
	{
		$this->extra = $extra;
		return $this;
	}

	public function addExtra($val, $key = null)
	{
		if(is_null($this->extra)) {
			$this->extra = [];
		}
		if(is_array($this->extra)) {
			if(!is_null($key)) {
				$this->extra[$key] = $val;
			} else {
				$this->extra[] = $val;
			}
		} else {
			throw new \Exception('addExtra method only works when extra field is an array');
		}
		return $this;
	}

	public function getExtra()
	{
		return $this->extra;
	}

	public function setCardNumber($card_number)
	{
		$this->card_number = $card_number;
		return $this;
	}

	public function getCardNumber()
	{
		return $this->card_number;
	}

	public function setTrackingCode($tracking_code)
	{
		$this->tracking_code = $tracking_code;
		return $this;
	}

	public function getTrackingCode()
	{
		return $this->tracking_code;
	}

	public function setReferenceNumber($reference_number)
	{
		$this->reference_number = $reference_number;
		return $this;
	}

	public function getReferenceNumber()
	{
		return $this->reference_number;
	}

	public function getTransactionCode()
	{
		return $this->transaction->transaction_code;
	}

	public function setTransaction($transaction)
	{
		if($transaction instanceOf IranPaymentTransaction) {
			$this->transaction = $transaction;
		} else {
			if(is_numeric($transaction)) {
				$this->transaction = IranPaymentTransaction::find($transaction);
			} else {
				$this->transaction = IranPaymentTransaction::where('transaction_code', $transaction)->first();
			}
		}
		$this->fillAllFields();
		return $this;
	}

	public function getTransaction()
	{
		return $this->transaction;
	}

	public function setUserId($user_id)
	{
		$this->user_id = $user_id;
		return $this;
	}

	public function getUserId()
	{
		return $this->user_id;
	}

	public function setCallbackUrl($callback_url)
	{
		$this->callback_url = $callback_url;
		return $this;
	}

	public function getCallbackUrl()
	{
		return $this->callback_url;
	}

	protected function fillAllFields() {
		if($this->transaction) {
			$this->currency = $this->transaction->currency;
			$this->callback_url = $this->transaction->callback_url;
			$this->transaction_code = $this->transaction->transaction_code;
			$this->card_number = $this->transaction->card_number;
			$this->reference_number = $this->transaction->reference_number;
			$this->tracking_code = $this->transaction->tracking_code;
			$this->description = $this->transaction->description;
			$this->extra = $this->transaction->extra;
		}
	}

	public function ready()
	{
		if ($this->amount <= 0) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		if (!in_array($this->currency, [self::IRR, self::IRT])) {
			throw new InvalidDataException(InvalidDataException::INVALID_CURRENCY);
		}
		if (filter_var($this->callback_url, FILTER_VALIDATE_URL) === false) {
			throw new InvalidDataException(InvalidDataException::INVALID_CALLBACK);
		}
		$this->newTransaction();
		$this->payRequest();
		return $this;
	}

	public function verify($transaction = null)
	{
		if($transaction) {
			$this->setTransaction($transaction);
		} else {
			if(!$this->transaction) {
				if (isset($request->transaction)) {
					$this->setTransaction($request->transaction);
				} else {
					throw new InvalidRequestException;
				}
			}
		}
		
		$this->setCardNumber($this->transaction->card_number);
		$this->setReferenceNumber($this->transaction->reference_number);
		$this->setTrackingCode($this->transaction->tracking_code);
		$this->setCurrency($this->transaction->currency);
		$this->setAmount($this->transaction->amount);
		if ($this->transaction->status == IranPaymentTransaction::T_SUCCEED) {
			return $this;
			// throw new SucceedRetryException;
		} elseif ($this->transaction->status != IranPaymentTransaction::T_PENDING) {
			throw new RetryException;
		}
		if ($this->amount <= 0) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		if (!in_array($this->currency, [self::IRR, self::IRT])) {
			throw new InvalidDataException(InvalidDataException::INVALID_CURRENCY);
		}
		$this->verifyRequest();
		return $this;
	}

	protected function newTransaction()
	{
		// if (empty($this->user_id)) {
		// 	throw new InvalidDataException(InvalidDataException::INVALID_USER_ID);
		// }
		if (empty($this->amount) || $this->amount <= 0) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		app('db')->transaction(function() {
			$this->transaction	= new IranPaymentTransaction([
				'amount'		=> $this->amount,
				'currency'		=> $this->currency,
				'gateway'		=> $this->getName(),
				'extra'			=> is_array($this->getExtra()) ? json_encode($this->getExtra()) : $this->getExtra(),
			]);
			$this->transaction->status	= IranPaymentTransaction::T_INIT;
			$this->transaction->user_id	= isset($this->user_id) ? $this->user_id : null; // $this->user_id ?? null
			$this->transaction->save();
			$this->transaction->transaction_code = Hashids::encode($this->transaction->id);
			$this->transaction->save();
		});
	}

	protected function transactionSucceed(array $params = null)
	{
		$this->transaction	= IranPaymentTransaction::find($this->transaction->id);
		if($params) {
			$this->transaction->fill($params);
		}
		$this->transaction->paid_at	= Carbon::now();
		$this->transaction->status	= IranPaymentTransaction::T_SUCCEED;
		$this->transaction->save();
	}

	protected function transactionFailed()
	{
		$this->transaction				= IranPaymentTransaction::find($this->transaction->id);
		$this->transaction->status		= IranPaymentTransaction::T_FAILED;
		$this->transaction->description	= $this->description;
		$this->transaction->save();
	}

	protected function transactionPending(array $params = null)
	{
		$this->checkTransaction();
		$this->transaction->status	= IranPaymentTransaction::T_PENDING;
		if($params) {
			$this->transaction->fill($params);
		}
		$this->transaction->save();
	}

	protected function transactionVerifyPending()
	{
		$this->checkTransaction();
		$this->transaction->status	= IranPaymentTransaction::T_VERIFY_PENDING;
		$this->transaction->save();
	}

	protected function transactionUpdate(array $params = null)
	{
		$this->transaction	= IranPaymentTransaction::find($this->transaction->id);
		if($params) {
			$this->transaction->fill($params);
		}
		$this->transaction->save();
	}

	protected function transactionPaidBack()
	{
		$this->checkTransaction();
		$this->transaction->status	= IranPaymentTransaction::T_PAID_BACK;
		$this->transaction->save();
	}

	protected function transactionCanceled(array $params = null)
	{
		$this->checkTransaction();
		if($params) {
			$this->transaction->fill($params);
		}
		$this->transaction->status	= IranPaymentTransaction::T_CANCELED;
		$this->transaction->save();
	}

	protected function checkTransaction() {
		if(!$this->transaction || !$this->transaction instanceOf IranPaymentTransaction) {
			throw new TransactionNotFoundException;
		}
	}

	public function getTransactionStatusText() {
		//@TODO::add translation
		switch($this->transaction->status) {
			case IranPaymentTransaction::T_INIT:
				return 'ایجاد شده';
			case IranPaymentTransaction::T_SUCCEED:
				return 'موفق';
			case IranPaymentTransaction::T_FAILED:
				return 'ناموفق';
			case IranPaymentTransaction::T_PENDING:
				return 'درجریان';
			case IranPaymentTransaction::T_VERIFY_PENDING:
				return 'در انتظار تایید';
			case IranPaymentTransaction::T_PAID_BACK:
				return 'برگشت وجه';
			case IranPaymentTransaction::T_CANCELED:
				return 'انصراف';
			default:
				return '';
		}
	}

	protected function callbackURL()
	{
		return Helpers::urlQueryBuilder($this->getCallbackUrl(), [
			config('iranpayment.transaction_query_param') => $this->getTransactionCode()
		]);
	}
}
