<?php

namespace Dena\IranPayment\Exceptions;

use Exception;
use Throwable;

class IranPaymentException extends Exception
{
    public static function unknown(Throwable $previous = null)
    {
        return new self('متاسفانه خطای ناشناخته ای رخ داده است', 500, $previous);
    }
}
