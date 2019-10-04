<?php

namespace Dena\IranPayment\Exceptions;

use Dena\IranPayment\Exceptions\IranPaymentException;

use Throwable;

class InvalidGatewayMethodException extends IranPaymentException
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('این درگاه از این متد پشتیبانی نمی‌کند.', 404, $previous);
    }
}