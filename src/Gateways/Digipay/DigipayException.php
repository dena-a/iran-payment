<?php

namespace Dena\IranPayment\Gateways\Digipay;

use Dena\IranPayment\Exceptions\GatewayException;

class DigipayException extends GatewayException
{
	public static array $errors = [
		0		    => 'عملیات با موفقیت انجام شد',

		1054		=> 'اطلاعات ورودی اشتباه می باشد',

		9000		=> 'اطلاعات خرید یافت نشد',
		9001	    => 'توکن پرداخت معتبر نمی باشد',

		9003		=> 'خرید مورد نظر منقضی شده است',
		9004		=> 'خرید مورد نظر درحال انجام است',
		9005		=> 'خرید قابل پرداخت نمی باشد',
		9006		=> 'خطا در برقراری ارتباط با درگاه پرداخت',
		9007		=> 'خرید با موفقیت انجام نشده است',
		9008		=> 'این خرید با داده های متفاوتی قبلا ثبت شده است',
		9009		=> 'محدوده زمانی تایید تراکنش گذشته است',
		9010		=> 'تایید خرید ناموفق بود',
		9011		=> 'نتیجه تایید خرید نامشخص است',
		9012		=> 'وضعیت خرید برای این درخواست صحیح نمی باشد',

		9030		=> 'ورود شماره همراه برای کاربران ثبت نام شده الزامی است',
		9031		=> 'اعطای تیکت برای کاربر مورد نظر امکان پذیر نمی‌باشد',
	];

    public static function error($error_code)
    {
        if (! isset(self::$errors[$error_code])) {
            return self::unknownResponse((string) $error_code);
        }

        return new self(self::$errors[$error_code], 422);
    }
}
