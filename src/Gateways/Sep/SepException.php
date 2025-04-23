<?php

namespace Dena\IranPayment\Gateways\Sep;

use Dena\IranPayment\Exceptions\GatewayException;

class SepException extends GatewayException
{
    public static array $verifyErrors = [
        -2 => 'تراکنش یافت نشد.',
        -6 => 'بیش از نیم ساعت از زمان اجرای تراکنش گذشته است.',
        -104 => 'ترمینال ارسالی غیرفعال می باشد.',
        -105 => 'ترمینال ارسالی در سیستم موجود نمی باشد.',
        -106 => 'آدرس آی پی درخواستی غیر مجاز می باشد.',
        2 => 'درخواست تکراری می باشد.',
        5 => 'تراکنش برگشت خورده می باشد.',
    ];

    public static array $purchaseErrors = [
        1 => 'تراکنش توسط خریدار لغو شده است.',
        2 => 'پرداخت با موفقیت انجام شد.',
        3 => 'پرداخت انجام نشد.',
        4 => 'کاربر در بازه زمانی تعیین شده پاسخی ارسال نکرده است.',
        5 => 'پارامترهای ارسالی نامعتبر است.',
        8 => 'آدرس سرور پذیرنده نامعتبر است.',
        9 => 'رمز کارت 3 مرتبه اشتباه وارد شده است در نتیجه کارت غیر فعال خواهد شد.',
        10 => 'توکن ارسال شده یافت نشد.',
        11 => 'با این شماره ترمینال فقط تراکنش های توکنی قابل پرداخت هستند.',
        12 => 'شماره ترمینال ارسال شده یافت نشد.',
        21 => 'محدودیت های مدل چند حسابی رعایت نشد.',
    ];

    public static function verifyError(int $error_code, ?string $description = null): SepException|GatewayException
    {
        if (! isset(self::$verifyErrors[$error_code])) {
            return self::unknownResponse($error_code.'-'.$description);
        }

        if (array_key_exists($error_code, self::$verifyErrors)) {
            return new self(self::$verifyErrors[$error_code]);
        }

        return self::unknownResponse();
    }

    public static function purchaseError(int $error_code, ?string $description = null): SepException|GatewayException
    {
        if (! isset(self::$purchaseErrors[$error_code])) {
            return self::unknownResponse($error_code.'-'.$description);
        }

        if (array_key_exists($error_code, self::$purchaseErrors)) {
            return new self(self::$purchaseErrors[$error_code]);
        }

        return self::unknownResponse();
    }
}
