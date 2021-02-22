<?php

namespace Dena\IranPayment\Exceptions;

use Dena\IranPayment\Exceptions\IranPaymentException;

use Throwable;

class SucceedRetryException extends IranPaymentException
{
    public function __construct($message = "پرداخت موفقیت آمیز بوده و قبلا عملیات تایید تراکنش انجام شده است.", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
