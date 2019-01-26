<?php

namespace Dena\IranPayment\Exceptions;

use Dena\IranPayment\Exceptions\IranPaymentException;

use Throwable;

class InvalidDataException extends IranPaymentException
{
	public function __construct(string $message = 'اطلاعات وارد شده نامعتبر است.', int $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

	public static function invalidAmount()
	{
		throw new self('امکان پرداخت مبلغ مورد نظر وجود ندارد.');
	}

	public static function invalidCurrency()
	{
		throw new self('امکان پرداخت برای ارز مورد نظر وجود ندارد.');
	}

	public static function invalidCallbackUrl()
	{
		throw new self('آدرس بازگشتی وارد شده معتبر نمی‌باشد.');
	}
}