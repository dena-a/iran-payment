<?php

namespace Dena\IranPayment\Traits;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Dena\IranPayment\IranPayment;

trait Payable
{
    /**
     * Payment Amount variable
     *
     * @var integer
     */
    private $payment_amount;

    /**
     * Payment Amount Model Field Name variable
     * 
     * There is no need to call setPaymentAmount function
     * if this variable has been set in model.
     *
     * @var string
     */
    // private $payment_amount_field;

    /**
     * Get all of the payment's transactions.
     */
    public function transactions()
    {
        return $this->morphMany(IranPaymentTransaction::class, 'payable');
    }

    /**
     * Set Amount function
     *
     * @param integer $amount
     * @return void
     */
    protected function setPaymentAmount(int $amount)
    {
        $this->payment_amount = $amount;
        return $this;
    }

    /**
     * Pay function
     *
     * @return void
     */
    public function pay()
    {
        if (!isset($this->amount) && isset($this->payment_amount_field)) {
            $payment_amount_field = $this->payment_amount_field;
            $this->payment_amount = $this->$payment_amount_field;
        }

        $payment = new IranPayment;
		return $payment->build()->setAmount($this->payment_amount)->ready();
    }
}