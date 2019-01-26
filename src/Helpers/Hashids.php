<?php

namespace Dena\IranPayment\Helpers;

use Hashids\Hashids as BaseHashids;

class Hashids
{
    public static function encode($id)
	{
		$salt = config('iranpayment.hashids.salt' ,'your-salt-string');
		$length	= config('iranpayment.hashids.length' ,16);
		$alphabet = config('iranpayment.hashids.alphabet' ,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
		
        $hashids = new BaseHashids($salt, $length, $alphabet);
        return $hashids->encode($id);
	}
}