<?php

namespace Dena\IranPayment\Gateways\PayPing;

use Dena\IranPayment\Exceptions\GatewayException;

class PayPingException extends GatewayException
{
    public static $httpcode_errors = [
        '400' => 'مشکلی در ارسال درخواست وجود دارد',
        '500' => 'مشکلی در سرور رخ داده است',
        '503' => 'سرور در حال حاضر قادر به پاسخگویی نمی‌باشد',
        '401' => 'عدم دسترسی',
        '403' => 'دسترسی غیر مجاز',
        '404' => 'آیتم درخواستی مورد نظر موجود نمی‌باشد',
    ];

    public static function httpCode($httpcode)
    {
        $httpcode = isset(self::$httpcode_errors[$httpcode]) ? $httpcode : '400';

        return new self(self::$httpcode_errors[$httpcode]);
    }
}
