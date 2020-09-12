<?php

namespace Dena\IranPayment\Gateways\Zarinpal;

use Dena\IranPayment\Exceptions\GatewayException;
use Dena\IranPayment\Exceptions\TransactionFailedException;

class ZarinpalException extends GatewayException
{
    public static array $errors = [
        -1  => 'اطلاعات ارسال شده ناقص است.',
        -2  => 'IP و یا مرچنت کد پذیرنده صحیح نیست.',
        -3  => 'با توجه به محدودیت های شاپرک امکان پرداخت با رقم درخواست شده میسر نمی باشد.',
        -4  => 'سطح تایید پذیرنده پایین تر از سطح نقره ای است.',
        -11 => 'درخواست مورد نظر یافت نشد.',
        -12 => 'امکان ویرایش درخواست میسر نمی باشد.',
        -21 => 'هیچ نوع عملیات مالی برای این تراکنش یافت نشد.',
        -22 => 'تراکنش نا موفق می باشد.',
        -33 => 'رقم تراکنش با رقم پرداخت شده مطابقت ندارد.',
        -34 => 'سقف تقسیم تراکنش از لحاظ تعداد یا رقم عبور نموده است',
        -40 => 'اجازه دسترسی به متد مربوطه وجود ندارد.',
        -41 => 'اطلاعات ارسال شده مربوط به AdditionalData غیرمعتبر می باشد.',
        -42 => 'مدت زمان معتبر طول عمر شناسه پرداخت باید بین 30 دقیقه تا 45 روز می باشد.',
        -54 => 'درخواست مورد نظر آرشیو شده است.',
        100 => 'عملیات با موفقیت انجام گردیده است.',
        101 => 'عملیات پرداخت موفق بوده و قبلا PaymentVerification تراکنش انجام شده است.',
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

        if (in_array($error_code, [-22, -33])) {
            throw new TransactionFailedException(self::$errors[$error_code], $error_code);
        }

        return new self(self::$errors[$error_code], $error_code);
    }
}
