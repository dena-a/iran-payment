<?php

namespace Dena\IranPayment\Gateways\Test;

use Dena\IranPayment\Exceptions\GatewayException;

class TestException extends GatewayException
{
	public static array $errors = [
		-100	=> 'تراکنش ناموفق میباشد',
	];

    public static function error($error_code)
    {
        return new self(self::$errors[$error_code] ?? self::$errors[-100], $error_code);
    }
}
