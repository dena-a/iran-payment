<?php

namespace Dena\IranPayment\Gateways\PayPing;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\TransactionFailedException;

class PayPingException extends GatewayException
{
    public static array $http_code_errors = [
        400 => 'مشکلی در ارسال درخواست وجود دارد',
        500 => 'مشکلی در سرور رخ داده است',
        503 => 'سرور در حال حاضر قادر به پاسخگویی نمی‌باشد',
        401 => 'عدم دسترسی',
        403 => 'دسترسی غیر مجاز',
        404 => 'آیتم درخواستی مورد نظر موجود نمی‌باشد',
    ];

    public static array $errors = [
        1 => 'تراكنش توسط شما لغو شد',
        2 => 'رمز کارت اشتباه است.',
        3 => 'cvv2 یا تاریخ انقضای کارت وارد نشده است',
        4 => 'موجودی کارت کافی نیست.',
        5 => 'تاریخ انقضای کارت گذشته است و یا اشتباه وارد شده.',
        6 => 'کارت شما مسدود شده است',
        7 => 'تراکنش مورد نظر توسط درگاه یافت نشد',
        8 => 'بانک صادر کننده کارت شما مجوز انجام تراکنش را صادر نکرده است',
        9 => 'مبلغ تراکنش مشکل دارد',
        10 => 'شماره کارت اشتباه است.',
        11 => 'ارتباط با درگاه برقرار نشد، مجددا تلاش کنید',
        12 => 'خطای داخلی بانک رخ داده است',
        15 => 'این تراکنش قبلا تایید شده است',
        18 => 'کاربر پذیرنده تایید نشده است',
        19 => 'هویت پذیرنده کامل نشده است و نمی تواند در مجموع بیشتر از ۵۰ هزار تومان دریافتی داشته باشد',
        25 => 'سرویس موقتا از دسترس خارج است، لطفا بعدا مجددا تلاش نمایید',
        26 => 'کد پرداخت پیدا نشد',
        27 => 'پذیرنده مجاز به تراکنش با این مبلغ نمی باشد',
        28 => 'لطفا از قطع بودن فیلتر شکن خود مطمئن شوید',
        29 => 'ارتباط با درگاه برقرار نشد',
        31 => 'امکان تایید پرداخت قبل از ورود به درگاه بانک وجود ندارد',
        38 => 'آدرس سایت پذیرنده نا معتبر است',
        39 => 'پرداخت ناموفق، مبلغ به حساب پرداخت کننده برگشت داده خواهد شد',
        44 => 'RefId نامعتبر است',
        46 => 'توکن ساخت پرداخت با توکن تایید پرداخت مغایرت دارد',
        47 => 'مبلغ تراکنش مغایرت دارد',
        48 => 'پرداخت از سمت شاپرک تایید نهایی نشده است',
        49 => 'ترمینال فعال یافت نشد، لطفا مجددا تلاش کنید',
    ];

    public static function httpError($http_code)
    {
        if (! isset(self::$http_code_errors[$http_code])) {
            return self::unknownResponse((string) $http_code);
        }

        return new self(self::$http_code_errors[$http_code], $http_code);
    }

    /**
     * @return $this
     *
     * @throws TransactionFailedException
     */
    public static function error($error_code)
    {
        if (! isset(self::$errors[$error_code])) {
            return self::unknownResponse((string) $error_code);
        }

        if (in_array($error_code, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 26, 27, 39, 44, 46, 47, 48])) {
            throw new TransactionFailedException(self::$errors[$error_code], $error_code);
        }

        return new self(self::$errors[$error_code], $error_code);
    }
}
