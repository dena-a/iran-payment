<?php

namespace Dena\IranPayment\Exceptions;

use Throwable;

class InvalidDataException extends IranPaymentException
{
    public function __construct(string $message = 'اطلاعات وارد شده نامعتبر است.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function invalidAmount(): self
    {
        return new self('امکان پرداخت مبلغ مورد نظر وجود ندارد.');
    }

    public static function invalidCurrency(): self
    {
        return new self('امکان پرداخت برای ارز مورد نظر وجود ندارد.');
    }

    public static function invalidCardNumber(): self
    {
        return new self('شماره کارت وارد شده معتبر نمی‌باشد.');
    }

    public static function invalidCallbackUrl(): self
    {
        return new self('آدرس بازگشتی وارد شده معتبر نمی‌باشد.');
    }

    public static function invalidCode(): self
    {
        return new self('کد تراکنش معتبر نمی‌باشد.');
    }
}
