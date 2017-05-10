<?php

namespace Dena\IranPayment;

use Dena\IranPayment\Exceptions\InvalidDataException;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Dena\IranPayment\IranPayment;

use Dena\IranPayment\Providers\Saman\Saman;
use Dena\IranPayment\Providers\Zarinpal\Zarinpal;

use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;

abstract class GatewayAbstract
{
	private $store;
	// protected $card_number		= null;
	// protected $description		= null;

	const PC_RIAL		= 1;
	const PC_TOMAN		= 2;

	const PCN_RIAL		= 'IRR';
	const PCN_TOMAN		= 'IRT';

	protected $amount;
	protected $currency;
	protected $callback_url;
	protected $reference_id;
	protected $gateway_code;
	protected $transaction;
	protected $transaction_id;
	// protected $user_id;
	// protected $gateway;
	// protected $tracking_code;

	abstract protected function getGatewayName();

	abstract protected function prepare();

	abstract protected function payRequest();

	abstract protected function verifyRequest();

	abstract protected function redirect();

	// abstract protected function referenceId();

	// abstract protected function trackingCode();

	// abstract protected function gateway();

	// abstract protected function transactionId();

	// abstract protected function cardNumber();

	public function __construct()
	{
		$this->setDefaults();
	}

	private function setDefaults()
	{
		$this->setStoreConfig();
		$this->setHashidsConfig();
		$this->setCurrency(self::PCN_RIAL);
		$this->setGatewayCode(IranPayment::gatewayCodeFromName($this->getGatewayName()));
	}

	// public function gateway()
	// {
	// 	return $this->gateway;
	// }

	// public function cardNumber()
	// {
	// 	return $this->card_number;
	// }

	// public function trackingCode()
	// {
	// 	return $this->tracking_code;
	// }

	// public function transactionId()
	// {
	// 	return $this->transaction_id;
	// }

	// public function referenceId()
	// {
	// 	return $this->reference_id;
	// }

	// public function userId()
	// {
	// 	return $this->user_id;
	// }

	// public function setUserId($user_id)
	// {
	// 	$this->user_id = $user_id;
	// 	return $this;
	// }

	public function setStoreConfig()
	{
		$this->store	= config('iranpayment.store.use_iranpayment_table', true);
	}

	public function setHashidsConfig()
	{
		if (!config('hashids.connections.iranpayment', false)) {
			config(['hashids.connections.iranpayment' => [
				'salt'		=> config('iranpayment.hashids.salt' ,'your-salt-string'),
				'length'	=> config('iranpayment.hashids.length' ,16),
				'alphabet'	=> config('iranpayment.hashids.alphabet' ,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
			]]);
		}
	}

	protected function setGatewayCode($gateway_code)
	{
		$this->gateway_code = $gateway_code;
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

	public function setReferenceId($reference_id)
	{
		$this->reference_id = $reference_id;
	}

	public function getReferenceId()
	{
		return $this->reference_id;
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
		if (!in_array($this->currency, [self::PCN_RIAL, self::PCN_TOMAN])) {
			throw new InvalidDataException(InvalidDataException::INVALID_CURRENCY);
		}
		if (filter_var($this->callback_url, FILTER_VALIDATE_URL) === false) {
			throw new InvalidDataException(InvalidDataException::INVALID_CALLBACK);
		}
		$this->payRequest();
		return $this;
	}

	public function verify($transaction)
	{
		$this->transaction		= $transaction;
		$this->transaction_id	= intval($transaction->id);
		$this->amount			= intval($transaction->amount);
		$this->reference_id		= $transaction->reference_id;
	}

	protected function newTransaction()
	{
		if (!$this->store) {
			return;
		}
		if (empty($this->user_id)) {
			throw new InvalidDataException(InvalidDataException::INVALID_USER_ID);
		}
		if (empty($this->amount) || $this->amount <= 0) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		$transaction	= new IranPaymentTransaction([
			'amount'	=> $this->amount,
			'gateway'	=> $this->gateway_code,
		]);
		$transaction->status	= IranPaymentTransaction::T_INIT;
		$transaction->user_id	= $this->user_id;
		$transaction->save();
		$this->transaction_id = $transaction->id;
	}

	protected function transactionSucceed($params = [])
	{
		if (!$this->store) {
			return;
		}
		$params				= array_merge(['payment_date' => Carbon::now()], $params);
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->fill($params);
		$this->transaction->status	= IranPaymentTransaction::T_SUCCEED;
		$this->transaction->save();
	}

	protected function transactionFailed()
	{
		if (!$this->store) {
			return;
		}
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->status		= IranPaymentTransaction::T_FAILED;
		$this->transaction->description	= $this->description;
		$this->transaction->save();
	}

	protected function transactionPending()
	{
		if (!$this->store) {
			return;
		}
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->status	= IranPaymentTransaction::T_PENDING;
		$this->transaction->save();
	}

	protected function transactionVerifyPending()
	{
		if (!$this->store) {
			return;
		}
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->status	= IranPaymentTransaction::T_VERIFY_PENDING;
		$this->transaction->save();
	}

	protected function transactionSetReferenceId()
	{
		if (!$this->store) {
			return;
		}
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->reference_id	= $this->reference_id;
		$this->transaction->save();
	}

	protected function transactionUpdate($params)
	{
		if (!$this->store) {
			return;
		}
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->fill($params);
		$this->transaction->save();
	}

	protected function callbackURL()
	{
		$query = http_build_query(['transaction' => $this->transactionHashids()]);

		$question_mark = strpos($this->callback_url, '?');
		if ($question_mark) {
			return $this->callback_url.'&'.$query;
		} else {
			return $this->callback_url.'?'.$query;
		}
	}

	public function transactionHashids()
	{
		return app('hashids')->connection('iranpayment')->encode($this->transaction_id);
	}

}