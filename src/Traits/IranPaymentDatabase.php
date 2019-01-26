<?php

namespace Dena\IranPayment\Traits;

trait IranPaymentDatabase
{
    /**
     * Table Name variable
     *
     * @var string
     */
    private $iranpayment_table = 'iranpayment_transactions';

    /**
     * Get Table Name function
     *
     * @return string
     */
    public function getTable(): string
    {
        $this->table = config('iranpayment.table', $this->iranpayment_table);

        return $this->table;
    }
}