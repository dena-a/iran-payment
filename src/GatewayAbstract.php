<?php

namespace Dena\IranPayment;

use Dena\IranPayment\Exceptions\InvalidDataException;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;

use Config;

abstract class GatewayAbstract
{
	protected $transaction_id	= null;
	protected $transaction		= null;
	protected $card_number		= null;
	protected $description		= null;

	protected $user_id;
	protected $gateway;
	protected $reference_id;
	protected $amount;
	protected $tracking_code;

	public function __construct($gateway)
	{
		$this->gateway = $gateway;

		if (!Config::has('hashids.connections.iranpayment')) {
			Config::set('hashids.connections.iranpayment', [
				'salt'		=> Config::get('iranpayment.hashids.salt' ,'your-salt-string'),
				'length'	=> Config::get('iranpayment.hashids.length' ,16),
				'alphabet'	=> Config::get('iranpayment.hashids.alphabet' ,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
			]);
		}
	}

	public function gateway()
	{
		return $this->gateway;
	}

	public function cardNumber()
	{
		return $this->card_number;
	}

	public function trackingCode()
	{
		return $this->tracking_code;
	}

	public function transactionId()
	{
		return $this->transaction_id;
	}

	public function referenceId()
	{
		return $this->reference_id;
	}

	public function userId()
	{
		return $this->user_id;
	}

	public function setUserId($user_id)
	{
		$this->user_id = $user_id;
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
		if (empty($this->user_id)) {
			throw new InvalidDataException(InvalidDataException::INVALID_USER_ID);
		}
		if (empty($this->amount) || $this->amount < 0) {
			throw new InvalidDataException(InvalidDataException::INVALID_AMOUNT);
		}
		$transaction	= new IranPaymentTransaction([
			'amount'	=> $this->amount,
			'status'	=> IranPaymentTransaction::T_INIT,
		]);
		$transaction->gateway = $this->gateway;
		$transaction->user_id = $this->user_id;
		$transaction->save();
		$this->transaction_id = $transaction->id;
		return $this->transaction_id;
	}

	protected function transactionSucceed($params = [])
	{
		$params				= array_merge(['payment_date' => Carbon::now()], $params);
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->fill($params);
		$this->transaction->status	= IranPaymentTransaction::T_SUCCEED;
		$this->transaction->save();
	}

	protected function transactionFailed()
	{
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->status		= IranPaymentTransaction::T_FAILED;
		$this->transaction->description	= $this->description;
		$this->transaction->save();
	}

	protected function transactionPending()
	{
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->status	= IranPaymentTransaction::T_PENDING;
		$this->transaction->save();
	}

	protected function transactionVerifyPending()
	{
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->status	= IranPaymentTransaction::T_VERIFY_PENDING;
		$this->transaction->save();
	}

	protected function transactionSetReferenceId()
	{
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->reference_id	= $this->reference_id;
		$this->transaction->save();
	}

	protected function transactionUpdate($params)
	{
		$this->transaction	= IranPaymentTransaction::find($this->transaction_id);
		$this->transaction->fill($params);
		$this->transaction->save();
	}

	protected function callbackURL()
	{
		$query = http_build_query(['transaction' => $this->transactionHashids()]);

		$question_mark = strpos($this->callback_url, '?');
		if (!$question_mark) {
			return $this->callback_url.'?'.$query;
		} else {
			return $this->callback_url."&".$query;
		}
	}

	protected function generateRandomString($length = 16) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$characters_length = strlen($characters);
		$random_string = '';
		for ($i = 0; $i < $length; $i++) {
			$random_string .= $characters[rand(0, $characters_length - 1)];
		}
		return $random_string;
	}

	public function transactionHashids()
	{
		return Hashids::connection('iranpayment')->encode($this->transaction_id);
	}

}