<?php

namespace Dena\IranPayment\Providers;

use Dena\IranPayment\Exceptions\RetryException;
use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Exceptions\SucceedRetryException;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Dena\IranPayment\IranPayment;

use Dena\IranPayment\Providers\Saman\Saman;
use Dena\IranPayment\Providers\Zarinpal\Zarinpal;

use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;
use Dena\IranPayment\Helpers\Helpers;

abstract class BaseProvider
{
	const IRR	= 'IRR';
	const IRT	= 'IRT';

	protected $amount;
	protected $request;
	protected $currency;
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
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setExtra($extra)
	{
		$this->extra = $extra;
	}

	public function getExtra()
	{
		return $this->extra;
	}

	public function setCardNumber($card_number)
	{
		$this->card_number = $card_number;
	}

	public function getCardNumber()
	{
		return $this->card_number;
	}

	public function setTrackingCode($tracking_code)
	{
		$this->tracking_code = $tracking_code;
	}

	public function getTrackingCode()
	{
		return $this->tracking_code;
	}

	public function setReferenceNumber($reference_number)
	{
		$this->reference_number = $reference_number;
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
		$this->transaction = $transaction;
	}

	public function getTransaction()
	{
		return $this->transaction;
	}

	public function setUserId($user_id)
	{
		$this->user_id = $user_id;
	}

	public function getUserId()
	{
		return $this->user_id;
	}

	public function setCurrency($currency)
	{
		$this->currency = $currency;
	}

	public function getCurrency()
	{
		return $this->currency;
	}

	public function setAmount($amount)
	{
		$this->amount = $amount;
	}

	public function getAmount()
	{
		return $this->amount;
	}

	public function setCallbackUrl($callback_url)
	{
		$this->callback_url = $callback_url;
	}

	public function getCallbackUrl()
	{
		return $this->callback_url;
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

	public function verify()
	{
		$this->setCardNumber($this->transaction->card_number);
		$this->setReferenceNumber($this->transaction->reference_number);
		$this->setTrackingCode($this->transaction->tracking_code);
		$this->setCurrency($this->transaction->currency);
		$this->setAmount($this->transaction->amount);
		if ($this->transaction->status == IranPaymentTransaction::T_SUCCEED) {
			throw new SucceedRetryException;
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
				'extra'			=> $this->getExtra(),
			]);
			$this->transaction->status	= IranPaymentTransaction::T_INIT;
			$this->transaction->user_id	= isset($this->user_id) ? $this->user_id : null; // $this->user_id ?? null
			$this->transaction->save();
			$this->transaction->transaction_code = app('hashids')->connection('iranpayment')->encode($this->transaction->id);
			$this->transaction->save();
		});
	}

	protected function transactionSucceed(array $params = [])
	{
		$this->transaction	= IranPaymentTransaction::find($this->transaction->id);
		$this->transaction->fill($params);
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

	protected function transactionPending()
	{
		$this->transaction			= IranPaymentTransaction::find($this->transaction->id);
		$this->transaction->status	= IranPaymentTransaction::T_PENDING;
		$this->transaction->save();
	}

	protected function transactionVerifyPending()
	{
		$this->transaction			= IranPaymentTransaction::find($this->transaction->id);
		$this->transaction->status	= IranPaymentTransaction::T_VERIFY_PENDING;
		$this->transaction->save();
	}

	protected function transactionUpdate(array $params)
	{
		$this->transaction	= IranPaymentTransaction::find($this->transaction->id);
		$this->transaction->fill($params);
		$this->transaction->save();
	}

	protected function transactionPaidBack()
	{
		$this->transaction			= IranPaymentTransaction::find($this->transaction->id);
		$this->transaction->status	= IranPaymentTransaction::T_PAID_BACK;
		$this->transaction->save();
	}

	protected function callbackURL()
	{
		return Helpers::urlQueryBuilder($this->getCallbackUrl(), [
			'transaction' => $this->getTransactionCode()
		]);
	}
}