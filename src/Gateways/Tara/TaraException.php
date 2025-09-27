<?php

namespace Dena\IranPayment\Gateways\Tara;

use Dena\IranPayment\Exceptions\GatewayException;

class TaraException extends GatewayException
{
    public static array $errors = [
        /*0 => 'عملیات با موفقیت انجام شد',*/

        1       => 'غیر مجاز IP',
        2       => 'نام کاربری یا رمز عبور نامعتبر است',
        3       => 'کاربر دسترسی ندارد',
        4       => 'پذیرنده یافت نشد',
        5       => 'هدایت به صفحه پرداخت',
        6       => 'تراکنش یافت نشد',
        7       => 'توکن یافت نشد',
        8       => 'شماره پیگیری به پذیرنده تعلق ندارد',
        9       => 'شماره سرویس نامعتبر است',
        10      => 'توکن تکراری است',
        11      => 'مبالغ یکسان نیست',
        12      => 'کانال یافت نشد',
        13      => 'مبلغ بیشتر از حد مجاز',
        14      => 'مبلغ کمتراز حد مجاز',
        15      => 'خطای عمومی',
        87      => 'مبلغ نمی تواند خالی باشد',
        88      => 'IP نمی تواند خالی باشد',
        89      => 'مبلغ نامعتبر می باشد',
        90      => 'لیست مبالغ سرویس خالی می باشد',
        91      => 'شناسه سرویس نامعتبر',
        92      => 'فرمت آدرس برگشتی صحیح نمی باشد',
        102     => 'تراکنش اصلی موفق نبوده است',
        840430  => 'فرآیند خرید با موفقیت انجام نشد، لطفاً دوباره تلاش کنید',
    ];

    public static function error($error_code)
    {
        if (! isset(self::$errors[$error_code])) {
            return self::unknownResponse((string) $error_code);
        }

        return new self(self::$errors[$error_code], $error_code);
    }
}
