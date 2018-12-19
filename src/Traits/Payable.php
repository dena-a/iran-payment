<?php

namespace Dena\IranPayment\Traits;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Dena\IranPayment\IranPayment;

trait Payable
{
    /**
     * IranPayment Amount variable
     *
     * @var integer
     */
    private $iranpayment_amount;

    /**
     * IranPayment Amount Model Field Name variable
     * 
     * There is no need to call setIranPaymentAmount function
     * if this variable has been set in model.
     *
     * @var string
     */
    // private $iranpayment_amount_field;

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
    protected function setIranPaymentAmount(int $amount)
    {
        $this->iranpayment_amount = $amount;
        return $this;
    }

    /**
     * Pay function
     *
     * @return void
     */
    public function pay()
    {
        if (!isset($this->iranpayment_amount) && isset($this->iranpayment_amount_field)) {
            $iranpayment_amount_field = $this->iranpayment_amount_field;
            $this->iranpayment_amount = $this->$iranpayment_amount_field;
        }

		return (new IranPayment)->build()->setAmount($this->iranpayment_amount)->setPayable($this)->ready();
    }
}