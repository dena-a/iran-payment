<?php

namespace Dena\IranPayment\Traits;

trait IranPaymentDatabase
{
    /**
     * IranPayment Table Name variable
     *
     * @var string
     */
    private string $iranpayment_table = 'iranpayment_transactions';

    /**
     * Get IranPayment Table Name function
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table = config('iranpayment.table', $this->iranpayment_table);
    }
}
