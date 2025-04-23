<?php

namespace Dena\IranPayment\Traits;

use Carbon\Carbon;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;
use Dena\IranPayment\Models\IranPaymentTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait TransactionData
{
    /**
     * Transaction variable
     */
    protected ?IranPaymentTransaction $transaction = null;

    /**
     * Extra variable
     */
    protected ?array $extra = null;

    /**
     * Fillable data variable
     */
    protected array $fillableData = [];

    /**
     * Set Transaction  function
     *
     * @param  IranPaymentTransaction|Model  $transaction
     * @return $this
     */
    public function setTransaction(IranPaymentTransaction $transaction): self
    {
        $this->transaction = $transaction;

        if (isset($transaction->payable)) {
            $this->setPayable($transaction->payable);
        }

        if (isset($transaction->payable_id)) {
            $this->payable_id = $transaction->payable_id;
        }

        if (isset($transaction->payable_type)) {
            $this->payable_type = $transaction->payable_type;
        }

        return $this;
    }

    /**
     * Get Transaction function
     */
    public function getTransaction(): ?IranPaymentTransaction
    {
        return $this->transaction;
    }

    /**
     * Set FillableData function
     *
     * @param  FillableData  $fillableData
     * @return $this
     */
    public function setFillableData(array $fillableData): self
    {
        $this->fillableData = $fillableData;

        return $this;
    }

    /**
     * get FillableData function
     *
     * @return array $fillableData
     */
    public function getFillableData(): array
    {
        return $this->fillableData;
    }

    /**
     * Find Transaction function
     *
     * @param  string|int  $uid
     * @return $this
     *
     * @throws TransactionNotFoundException
     */
    public function findTransaction($uid): self
    {
        if (is_numeric($uid)) {
            $transaction = IranPaymentTransaction::find($uid);
        } elseif (is_string($uid)) {
            $transaction = IranPaymentTransaction::where('code', $uid)->first();
        }

        if (! isset($transaction)) {
            throw new TransactionNotFoundException;
        }

        $this->setTransaction($transaction);

        return $this;
    }

    public function findTransactionByReferenceNumber(string $referenceNumber): ?IranPaymentTransaction
    {
        return IranPaymentTransaction::where('reference_number', $referenceNumber)->first();
    }

    /**
     * Find Item Transactions
     */
    public function payableTransactions($payable_id, $payable_type = null, $status = null): Collection
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
        return isset($this->transaction) ? $this->transaction->extra : $this->extra;
    }

    /**
     * Add Extra function
     *
     * @param [type] $val
     * @param [type] $key
     *
     * @throws \Exception
     */
    public function addExtra($val, $key = null): self
    {
        if (! empty($this->transaction) && ! empty($this->transaction['id'])) {
            $extra = $this->getExtra();
            if (is_null($extra)) {
                $extra = [];
            }
            if (is_array($extra)) {
                if (! is_null($key)) {
                    $extra[$key] = $val;
                } else {
                    $extra[] = $val;
                }
            }

            $this->transactionUpdate(compact('extra'));
        } else {
            if (empty($this->extra)) {
                $this->extra = [];
            }
            if (! is_null($key)) {
                $this->extra[$key] = $val;
            } else {
                $this->extra[] = $val;
            }
        }

        return $this;
    }

    /**
     * Payable variable
     */
    protected ?Model $payable;

    /**
     * Payable id
     */
    protected ?int $payable_id;

    /**
     * Payable type
     */
    protected ?string $payable_type;

    /**
     * Set Payable function
     *
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
        if ($this->payable) {
            return $this->payable;
        } elseif ($this->payable_id) {
            return $this->payable_id;
        }

        return null;
    }

    /**
     * Set Payable function
     */
    public function setPayableId(int $payableId): self
    {
        $this->payable_id = $payableId;

        return $this;
    }

    /**
     * Get Payable id function
     */
    public function getPayableId(): int
    {
        return $this->payable_id;
    }

    /**
     * Set Payable function
     */
    public function setPayableType(string $payableType): self
    {
        $this->payable_type = $payableType;

        return $this;
    }

    /**
     * Get Payable id function
     */
    public function getPayableType(): string
    {
        return $this->payable_type;
    }

    protected function newTransaction(array $params = []): void
    {
        $transaction = new IranPaymentTransaction(array_merge(
            [
                'amount' => $this->getAmount(),
                'currency' => $this->getCurrency(),
                'gateway' => $this->getName(),
                'extra' => $this->getExtra(),
            ],
            $params
        ));

        $transaction->status = IranPaymentTransaction::T_INIT;
        $transaction->code = Str::random(app('config')->get('iranpayment.code_length', 16));

        if (isset($this->payable)) {
            $transaction->payable()->associate($this->payable);
        } elseif (isset($this->payable_id)) {
            $transaction->payable_id = $this->payable_id;
            $transaction->payable_type = $this->payable_type;
        }

        $transaction->save();

        $this->transaction = $transaction;
    }

    protected function fillTransaction(array $params = []): void
    {
        if (! empty($params['gateway_data']) && is_array($params['gateway_data'])) {
            $this->transaction->gateway_data = array_merge(
                $this->transaction->gateway_data ?? [],
                $params['gateway_data']
            );
            unset($params['gateway_data']);
        }
        if (! empty($params['extra']) && is_array($params['extra'])) {
            $this->transaction->extra = array_merge(
                $this->transaction->extra ?? [],
                $params['extra']
            );
            unset($params['extra']);
        }

        $this->transaction->fill($params);
    }

    protected function transactionSucceed(array $params = []): void
    {
        $this->transaction->fill($params);
        $this->transaction->paid_at = Carbon::now();
        $this->transaction->status = IranPaymentTransaction::T_SUCCEED;
        $this->transaction->save();
    }

    protected function transactionFailed(?string $errors = null): void
    {
        $this->transaction->status = IranPaymentTransaction::T_FAILED;
        $this->transaction->errors = $errors;
        $this->transaction->save();
    }

    protected function transactionPending(array $params = []): void
    {
        $this->transaction->fill($params);
        $this->transaction->status = IranPaymentTransaction::T_PENDING;
        $this->transaction->save();
    }

    protected function transactionVerifyPending(array $params = []): void
    {
        $this->transaction->fill($params);
        $this->transaction->status = IranPaymentTransaction::T_VERIFY_PENDING;
        $this->transaction->save();
    }

    protected function transactionUpdate(array $params = [], array $gatewayData = []): void
    {
        $this->transaction->gateway_data = array_merge(
            $this->transaction->gateway_data ?? [],
            $gatewayData
        );
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
