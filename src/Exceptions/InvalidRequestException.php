<?php

namespace Dena\IranPayment\Exceptions;

use Dena\IranPayment\Exceptions\IranPaymentException;

class InvalidRequestException extends IranPaymentException
{
    public static function notFound()
    {
        return new self('درخواست مورد نظر یافت نشد');
    }

    public static function unProcessableVerify()
    {
        return new self('امکان انجام عملیات تایید بر روی این تراکنش وجود ندارد');
    }
}