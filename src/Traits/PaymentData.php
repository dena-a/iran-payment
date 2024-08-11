<?php

namespace Dena\IranPayment\Traits;

use Dena\IranPayment\Exceptions\InvalidDataException;
use Dena\IranPayment\Helpers\Currency;
use Dena\IranPayment\Helpers\Helpers;

trait PaymentData
{
    /**
     * Payment Amount variable
     */
    protected int $amount;

    /**
     * Payment Currency variable
     */
    protected string $currency = Currency::IRR;

    /**
     * Gateway Currency variable
     */
    protected string $gateway_currency;

    /**
     * Payment Callback URL variable
     */
    protected ?string $callback_url;

    /**
     * Set Amount function
     *
     * @return $this
     */
    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get Amount function
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Set Payment Currency function
     *
     * @return $this
     *
     * @throws InvalidDataException
     */
    public function setCurrency(string $currency): self
    {
        $currency = strtoupper($currency);

        if (! in_array($currency, [Currency::IRR, Currency::IRT])) {
            throw InvalidDataException::invalidCurrency();
        }

        $this->currency = $currency;

        return $this;
    }

    /**
     * Get Payment Currency function
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Set Gateway Currency function
     *
     * @return $this
     *
     * @throws InvalidDataException
     */
    public function setGatewayCurrency(string $gateway_currency): self
    {
        $gateway_currency = strtoupper($gateway_currency);

        if (! in_array($gateway_currency, [Currency::IRR, Currency::IRT])) {
            throw InvalidDataException::invalidCurrency();
        }

        $this->gateway_currency = $gateway_currency;

        return $this;
    }

    /**
     * Get Payment Currency function
     */
    public function getGatewayCurrency(): string
    {
        return $this->gateway_currency;
    }

    /**
     * Set Callback Url function
     *
     * @return self
     */
    public function setCallbackUrl(string $callback_url)
    {
        $this->callback_url = $callback_url;

        return $this;
    }

    /**
     * Get Callback Url function
     */
    public function getCallbackUrl(): ?string
    {
        return $this->callback_url;
    }

    /**
     * Get Prepared Amount function
     */
    public function preparedAmount(): int
    {
        if ($this->currency === $this->gateway_currency) {
            return $this->amount;
        } elseif ($this->currency === Currency::IRR && $this->gateway_currency === Currency::IRT) {
            return Currency::RialToToman($this->amount);
        } elseif ($this->currency === Currency::IRT && $this->gateway_currency === Currency::IRR) {
            return Currency::TomanToRial($this->amount);
        }

        return $this->amount;
    }

    /**
     * Get Prepared Callback URL function
     */
    public function preparedCallbackUrl(): ?string
    {
        $callback_url = filter_var($this->callback_url, FILTER_VALIDATE_URL) === false
            ? url($this->callback_url)
            : $this->callback_url;

        return Helpers::urlQueryBuilder($callback_url, [
            app('config')->get('iranpayment.transaction_query_param', 'tc') => $this->getTransactionCode(),
        ]);
    }
}
