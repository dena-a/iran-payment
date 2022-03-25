<?php

namespace Dena\IranPayment\Gateways\Novinopay;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\TransactionFailedException;

class NovinopayException extends GatewayException
{
    public static array $errors = [
        -1  => 'صرفا درخواست با متد POST قابل پذیرش است',
        -2  => 'مقداری برای MerchantID ارسال نشده است',
        -3  => 'مقداری برای Amount ارسال نشده است',
        -4  => 'مقداری برای CallbackURL ارسال نشده است',
        -5  => 'مبلغ صحیح نمیباشد',
        -6  => 'MerchantID وارد شده در سیستم یافت نشد',
        -7  => 'MerchantID وارد شده فعال نیست',
        -8  => 'اکانت شما تجاری نیست, لذا امکان استفاده از وبسرویس را ندارید',
        -9  => 'IP معتبر نیست',
        -10 => 'آدرس بازگشتی با آدرس درگاه پرداخت ثبت شده همخوانی ندارد',
        -11 => 'خطای در وب سرویس - ایجاد تراکنش با خطا مواجه شد',
        -12 => 'مقدار Authority ارسالی معتبر نیست - تراکنش یافت نشد',
        -13 => 'مقداری برای Authority ارسال نشده است',
        -14 => 'اطلاعات تراکنش یافت نشد, مقدار Authority را برسی کرده و صحیح بودن آن اطمینان حاصل کنید',
        -15 => 'تراکنش در انتظار پرداخت میباشد',
        -16 => 'مبلغ ارسال شده با مبلغ تراکنش یکسان نیست, مقدار Amount را برسی کرده و نسبت به صحت آن اطمینان حاصل کنید',
        -17 => 'دسترسی شما به این تراکنش رد شد, Authority این تراکنش برای MerchantID شما ثبت نشده است',
        -18 => 'پرداخت تراکنش با موفقیت انجام شده است, اما در بروزرسانی کیف پول پذیرنده مشکلی پیش آمده است',
        -19 => 'وضعیت تراکنش نامشخص است',
        -20 => 'تراکنش ناموفق بوده و امکان عملیات تاییدیه تراکنش وجود ندارد',
        -21 => 'تراکنش منقضی شده است',
        -22 => 'تراکنش به حساب کاربرمرجوع شده است',
        -23 => 'مدت زمان عملیات تاییدیه تراکنش منقضی شده و مبلغ به حساب کاربر برگشت خواهد خورد',
        -24 => 'شماره کارت ارسالی جهت پرداخت معتبر نمیباشد',
        -25 => 'مجموع مبلغ تراکنش و کارمزد نباید بیش از 50.000.000 تومان باشد',
        100 => 'عملیات موفقیت آمیز',
        101 => 'عمليات پرداخت موفق بوده و قبلا تاییدیه تراكنش انجام شده است',
    ];

    /**
     * @param $error_code
     * @return $this
     * @throws TransactionFailedException
     */
    public static function error($error_code): self
    {
        if (!isset(self::$errors[$error_code])) {
            return self::unknownResponse((string) $error_code);
        }

        if (in_array($error_code, [-20, -21, -22, -23])) {
            throw new TransactionFailedException(self::$errors[$error_code], $error_code);
        }

        return new self(self::$errors[$error_code], $error_code);
    }
}
