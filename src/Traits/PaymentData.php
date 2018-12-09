<?php

namespace Dena\IranPayment\Traits;

use Dena\IranPayment\Helpers\Currency;
use Dena\IranPayment\Exceptions\InvalidDataException;

trait PaymentData
{
    /**
     * Payment Amount variable
     *
     * @var int
     */
	protected $amount;

    /**
     * Set Amount function
     *
     * @param int $amount
     * @return void
     */
    public function setAmount(int $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get Amount function
     *
     * @return void
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Payment Currency variable
     *
     * @var string
     */
    protected $currency;
    
    /**
     * Set Payment Currency function
     *
     * @param string $currency
     * @return void
     */
    public function setCurrency(string $currency)
    {
        $currency = strtoupper($currency);

		if (!in_array($currency, [Currency::IRR, Currency::IRT])) {
			throw new InvalidDataException(InvalidDataException::INVALID_CURRENCY);
        }
        
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get Payment Currency function
     *
     * @return void
     */
    public function getCurrency()
    {
        return $this->currency;
    }
}