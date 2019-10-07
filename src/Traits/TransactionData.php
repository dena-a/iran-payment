<?php

namespace Dena\IranPayment\Traits;

use Dena\IranPayment\Exceptions\TransactionNotFoundException;

use Dena\IranPayment\Helpers\Currency;
use Dena\IranPayment\Models\IranPaymentTransaction;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

trait TransactionData
{
    /**
     * Transaction variable
     *
     * @var IranPaymentTransaction
     */
	protected $transaction;

	/**
	 * Set Transaction  function
	 *
	 * @param IranPaymentTransaction $transaction
	 * @return self
	 */
	public function setTransaction(IranPaymentTransaction $transaction)
	{
		$this->transaction = $transaction;

		return $this;
	}

	/**
	 * Find Transaction function
	 *
	 * @param int $transaction_id
	 * @return self
	 */
	public function findTransaction(int $transaction_id)
	{
		$transaction = IranPaymentTransaction::find($transaction_id);
		if (!$transaction) {
			throw new TransactionNotFoundException;
		}

		return $this->setTransaction($transaction);
	}

	/**
	 * Search Transaction Code function
	 *
	 * @param string $transaction_code
	 * @return self
	 */
	public function searchTransactionCode(string $transaction_code)
	{
		$transaction = IranPaymentTransaction::where('code', $transaction_code)->first();
		if (!isset($transaction)) {
			throw new TransactionNotFoundException;
		}

		return $this->setTransaction($transaction);
	}
	
	/**
     * Get Transaction function
     *
     * @return IranPaymentTransaction
     */
	public function getTransaction()
	{
		return $this->transaction;
	}
	    
	/**
	 * Get Transaction Gateway function
	 *
	 * @return string
	 */
	public function getGateway()
	{
		return isset($this->transaction) ? $this->transaction->gateway : null;
	}
	    
	/**
	 * Get Transaction Card Number function
	 *
	 * @return int
	 */
	public function getCardNumber()
	{
		return isset($this->transaction) ? $this->transaction->card_number : null;
	}

	/**
     * Get Transaction Tracking Code function
     *
     * @return int
     */
	public function getTrackingCode()
	{
		return isset($this->transaction) ? $this->transaction->tracking_code : null;
	}

	/**
     * Get Transaction Reference Number function
     *
     * @return int
     */
	public function getReferenceNumber()
	{
		return isset($this->transaction) ? $this->transaction->reference_number : null;
	}

	/**
     * Get Transaction Code function
     *
     * @return string
     */
	public function getTransactionCode()
	{
		return isset($this->transaction) ? $this->transaction->code : null;
	}

	/**
     * Get Transaction Extra Data function
     *
     * @return array
     */
	public function getExtra()
	{
		return isset($this->transaction) ? $this->transaction->extra : null;
	}

	/**
	 * Add Extra function
	 *
	 * @param [type] $val
	 * @param [type] $key
	 * @return self
	 */
	public function addExtra($val, $key = null)
	{
		if (isset($this->transaction)) {
			$extra = $this->getExtra();
			if(is_null($extra)) {
				$extra = [];
			}
			if(is_array($extra)) {
				if(!is_null($key)) {
					$extra[$key] = $val;
				} else {
					$extra[] = $val;
				}
			} else {
				throw new \Exception('addExtra method only works when extra field is an array');
			}

			$this->transactionUpdate(compact('extra'));
		}
		return $this;
	}
    
    /**
     * Payable variable
     *
     * @var Model
     */
	protected $payable;

    /**
     * Set Payable function
     *
     * @param Model $payable
     * @return self
     */
	public function setPayable(Model $payable)
	{
		$this->payable = $payable;
		return $this;
	}

    /**
     * Get Payable function
     *
     * @return Model
     */
	public function getPayable()
	{
		return $this->payable;
	}
	
	protected function newTransaction()
	{
		app('db')->transaction(function() {
			$this->transaction	= new IranPaymentTransaction([
				'amount'		=> $this->amount,
				'currency'		=> $this->currency,
				'gateway'		=> $this->gatewayName(),
				'extra'			=> is_array($this->getExtra()) ? json_encode($this->getExtra()) : $this->getExtra(),
			]);
			$this->transaction->status	= IranPaymentTransaction::T_INIT;
			$this->transaction->payable()->associate($this->payable);
			$this->transaction->save();
			$this->transaction->code = Str::random(config('iranpayment.code_length' ,16));
			$this->transaction->save();
		});
	}

	protected function transactionSucceed(array $params = [])
	{
		$this->transaction->fill($params);
		$this->transaction->paid_at	= Carbon::now();
		$this->transaction->status	= IranPaymentTransaction::T_SUCCEED;
		$this->transaction->save();
	}

	protected function transactionFailed(string $errors = null)
	{
		$this->transaction->status	= IranPaymentTransaction::T_FAILED;
		$this->transaction->errors	= $errors;
		$this->transaction->save();
	}

	protected function transactionPending(array $params = [])
	{
		$this->transaction->fill($params);
		$this->transaction->status	= IranPaymentTransaction::T_PENDING;
		$this->transaction->save();
	}

	protected function transactionVerifyPending(array $params = [])
	{
		$this->transaction->fill($params);
		$this->transaction->status	= IranPaymentTransaction::T_VERIFY_PENDING;
		$this->transaction->save();
	}

	protected function transactionUpdate(array $params = [])
	{
		$this->transaction->fill($params);
		$this->transaction->save();
	}

	protected function transactionPaidBack(array $params = [])
	{
		$this->fill($params);
		$this->status = IranPaymentTransaction::T_PAID_BACK;
		$this->save();
	}
}