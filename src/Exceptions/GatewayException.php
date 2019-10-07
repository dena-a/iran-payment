<?php

namespace Dena\IranPayment\Exceptions;

use Dena\IranPayment\Exceptions\IranPaymentException;

use Throwable;

class GatewayException extends IranPaymentException
{
    public function __construct(string $message = 'خطای درگاه پرداخت', int $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function unknownResponse()
    {
        return new self('پاسخ ناشناخته!');
    }

    public static function inconsistentResponse()
    {
        return new self('اطلاعات دریافتی با پایگاه‌داده همخوانی ندارند!');
    }

    public static function connectionProblem()
    {
        return new self('اشکالی در اتصال به درگاه پرداخت پیش آمده است!', 503);
    }
}