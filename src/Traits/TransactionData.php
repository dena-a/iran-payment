<?php

namespace Dena\IranPayment\Traits;

use Dena\IranPayment\Exceptions\TransactionNotFoundException;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

trait TransactionData
{
    /**
     * Transaction variable
     *
     * @var IranPaymentTransaction|null
     */
	protected ?IranPaymentTransaction $transaction = null;

	/**
	 * Set Transaction  function
	 *
	 * @param IranPaymentTransaction|Model $transaction
	 * @return $this
	 */
	public function setTransaction(IranPaymentTransaction $transaction): self
	{
		$this->transaction = $transaction;

		return $this;
	}

    /**
     * Get Transaction function
     *
     * @return IranPaymentTransaction|null
     */
    public function getTransaction(): ?IranPaymentTransaction
    {
        return $this->transaction;
    }

    /**
     * Find Transaction function
     *
     * @param string|int $uid
     * @return $this
     * @throws TransactionNotFoundException
     */
    public function findTransaction($uid): self
    {
        if (is_numeric($uid)) {
            $transaction = IranPaymentTransaction::find($uid);
        } elseif (is_string($uid)) {
            $transaction = IranPaymentTransaction::where('code', $uid)->first();
        }

        if (!isset($transaction)) {
            throw new TransactionNotFoundException;
        }

        $this->setTransaction($transaction);

        return $this;
    }

	/**
	 * Find Item Transactions
	 *
	 * @param $payable_id
	 * @param $payable_type
	 * @param $status
	 * @return array
	 */
	public function payableTransactions($payable_id, $payable_type = null, $status = null): array
	{
		$transactions = IranPaymentTransaction::where('payable_id', $payable_id);

		if (isset($payable_type)) {
			$transactions->where('payable_type', $payable_type);
		}

		if (isset($status)) {
			$transactions->where('status', $status);
		}

		return $transactions->get();
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
     * @throws \Exception
     */
	public function addExtra($val, $key = null): self
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

			if(!empty($this->transaction['id'])) {
				$this->transactionUpdate(compact('extra'));
			} else {
				$this->transaction->extra = $extra;
			}
		}

		return $this;
	}

    /**
     * Payable variable
     *
     * @var Model|null
     */
	protected ?Model $payable;

	/**
     * Payable id
     *
     * @var int|null
     */
	protected ?int $payable_id;

	/**
     * Payable type
     *
     * @var string|null
     */
	protected ?string $payable_type;

    /**
     * Set Payable function
     *
     * @param Model $payable
     * @return $this
     */
	public function setPayable(Model $payable): self
	{
		$this->payable = $payable;

		return $this;
	}

    /**
     * Get Payable function
     *
     * @return Model|int|string|null
     */
	public function getPayable()
	{
		if ($this->payable)
		    return $this->payable;
		elseif($this->payable_id)
			return $this->payable_id;

		return null;
	}

	/**
     * Set Payable function
     *
     * @param int $payableId
     * @return self
     */
	public function setPayableId(int $payableId) : self
	{
		$this->payable_id = $payableId;
		return $this;
	}

    /**
     * Get Payable id function
     *
     * @return int
     */
	public function getPayableId(): int
	{
		return $this->payable_id;
	}

	/**
     * Set Payable function
     *
     * @param string $payableType
     * @return self
     */
	public function setPayableType(string $payableType): self
	{
		$this->payable_type = $payableType;

		return $this;
	}

    /**
     * Get Payable id function
     *
     * @return string
     */
	public function getPayableType(): string
	{
		return $this->payable_type;
	}

	protected function newTransaction(): void
    {
        $transaction = new IranPaymentTransaction([
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'gateway' => $this->getName(),
            'extra' => is_array($this->getExtra()) ? json_encode($this->getExtra()) : $this->getExtra(),
        ]);

        $transaction->status = IranPaymentTransaction::T_INIT;
        $transaction->code = Str::random(config('iranpayment.code_length' ,16));

        if (isset($this->payable)) {
            $transaction->payable()->associate($this->payable);
        } elseif (isset($this->payable_id)) {
            $transaction->payable_id = $this->payable_id;
            $transaction->payable_type = $this->payable_type;
        }

        $transaction->save();

        $this->transaction = $transaction;
	}

	protected function transactionSucceed(array $params = []): void
    {
		$this->transaction->fill($params);
		$this->transaction->paid_at	= Carbon::now();
		$this->transaction->status	= IranPaymentTransaction::T_SUCCEED;
		$this->transaction->save();
	}

	protected function transactionFailed(string $errors = null): void
    {
		$this->transaction->status	= IranPaymentTransaction::T_FAILED;
		$this->transaction->errors	= $errors;
		$this->transaction->save();
	}

	protected function transactionPending(array $params = []): void
    {
		$this->transaction->fill($params);
		$this->transaction->status	= IranPaymentTransaction::T_PENDING;
		$this->transaction->save();
	}

	protected function transactionVerifyPending(array $params = []): void
    {
		$this->transaction->fill($params);
		$this->transaction->status	= IranPaymentTransaction::T_VERIFY_PENDING;
		$this->transaction->save();
	}

	protected function transactionUpdate(array $params = []): void
    {
		$this->transaction->forceFill($params);
		$this->transaction->save();
	}

	protected function transactionPaidBack(array $params = []): void
	{
		$this->transaction->fill($params);
		$this->transaction->status = IranPaymentTransaction::T_PAID_BACK;
		$this->transaction->save();
	}
}
