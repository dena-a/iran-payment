<?php

namespace Dena\IranPayment\Exceptions;

use Dena\IranPayment\Exceptions\IranPaymentException;

use Throwable;

class TransactionNotFoundException extends IranPaymentException
{
    public function __construct(string $message = 'تراکنش مورد نظر یافت نشد.', int $code = 404, Throwable $previous)
    {
        parent::__construct($message, $code, $previous);
    }
}