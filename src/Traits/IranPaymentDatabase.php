<?php

namespace Dena\IranPayment\Traits;

trait IranPaymentDatabase
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

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
        return $this->table = app('config')->get('iranpayment.table', $this->iranpayment_table);
    }
}
