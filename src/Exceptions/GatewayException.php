<?php

namespace Dena\IranPayment\Exceptions;

use Dena\IranPayment\Exceptions\IranPaymentException;

use Exception;
use Throwable;

class GatewayException extends IranPaymentException
{
    public function __construct(string $message = 'خطای درگاه پرداخت', int $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function unknownResponse($response = null)
    {
        return new self('پاسخ ناشناخته!', 500, new Exception(['Unknown Response' => $response]));
    }

    public static function inconsistentResponse()
    {
        return new self('اطلاعات دریافتی با پایگاه‌داده همخوانی ندارند!');
    }

    public static function connectionProblem(Throwable $previous = null)
    {
        return new self('در اتصال به درگاه پرداخت اشکالی پیش آمده است!', 503, $previous);
    }
}
