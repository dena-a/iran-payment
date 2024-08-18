<?php

namespace Dena\IranPayment\Traits;

trait Payable
{
    /**
     * IranPayment Amount variable
     */
    private ?int $iranpayment_amount;

    /**
     * IranPayment Amount Model Field Name variable
     *
     * There is no need to call setIranPaymentAmount function
     * if this variable has been set in model.
     *
     * @var string|null
     */
    // protected ?string $iranpayment_amount_field;

    /**
     * Get all of the payment's transactions.
     */
    public function transactions()
    {
        return $this->morphMany(\Dena\IranPayment\Models\IranPaymentTransaction::class, 'payable');
    }

    /**
     * Set IranPayment Amount function
     *
     * @return $this
     */
    protected function setIranPaymentAmount(int $amount): self
    {
        $this->iranpayment_amount = $amount;

        return $this;
    }

    /**
     * Call IranPayment Purchase Method
     *
     * @param  null  $gateway
     * @return mixed
     *
     * @throws \Dena\IranPayment\Exceptions\IranPaymentException
     */
    public function pay($gateway = null)
    {
        if (! isset($this->iranpayment_amount) && isset($this->iranpayment_amount_field)) {
            $this->iranpayment_amount = intval($this->{$this->iranpayment_amount_field});
        }

        return \Dena\IranPayment\IranPayment::create($gateway)
            ->setAmount($this->iranpayment_amount)
            ->setPayable($this)
            ->ready();
    }
}
