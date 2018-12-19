<?php

namespace Dena\IranPayment\Providers\PayIr;

use Dena\IranPayment\Exceptions\GatewayException;

class PayIrException extends GatewayException
{
    public static $send_errors = [
        '-1'        => 'ارسال api الزامی می باشد',
        '-2'        => 'ارسال مبلغ الزامی می باشد',
        '-3'        => 'مبلغ باید به صورت عددی باشد',
        '-4'        => 'مبلغ نباید کمتر از 1000 باشد',
        '-5'        => 'ارسال آدرس بازگشتی الزامی می باشد',
        '-6'        => 'درگاه پرداختی با api ارسالی یافت نشد و یا غیر فعال می باشد',
        '-7'        => 'فروشنده غیر فعال می باشد',
        '-8'        => 'آدرس بازگشتی با آدرس درگاه پرداخت ثبت شده همخوانی ندارد',
        '-12'       => 'طول فیلد description بیشتر از 255 کاراکتر می باشد',
        'failed'    => 'تراکنش با خطا مواجه شد',
    ];

    public static $verify_errors = [
        '-1'        => 'ارسال api الزامی می باشد',
        '-2'        => 'ارسال transId الزامی می باشد',
        '-3'        => 'درگاه پرداختی با api ارسالی یافت نشد و یا غیر فعال می باشد',
        '-4'        => 'فروشنده غیر فعال می باشد',
        '-5'        => 'تراکنش با خطا مواجه شد',
        'failed'    => 'تراکنش با خطا مواجه شد',
    ];

    public static function pay($error_code)
    {
        $error_code = isset(self::$send_errors[$error_code]) ? $error_code : 'failed';
        return new self(self::$send_errors[$error_code], $error_code);
    }

    public static function verify($error_code)
    {
        $error_code = isset(self::$verify_errors[$error_code]) ? $error_code : 'failed';
        return new self(self::$verify_errors[$error_code], $error_code);
    }
}