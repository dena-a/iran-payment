<?php

namespace Dena\IranPayment\Exceptions;

use Dena\IranPayment\Exceptions\IranPaymentException;

class GatewayException extends IranPaymentException
{
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
        return new self('اشکالی در اتصال به درگاه پرداخت پیش آمده است!');
    }
}