<?php

namespace Dena\IranPayment\Helpers;

class Currency
{
	public static function TomanToRial($rial)
	{
		return abs(intval($rial) * 10);
	}

	public static function RialToToman($toman)
	{
		$toman = abs(intval($toman));
		return ($toman == 0) ? 0 : intval($toman / 10);
	}
}