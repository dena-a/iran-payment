<?php

namespace Dena\IranPayment\Helpers;

class Currency
{

	public static function TomanToRial($rial) {
		return abs(intval($rial) * 10);
	}

	public static function RialToToman($toman) {
		$toman = abs(intval($toman));
		if ($toman == 0) {
			return 0;
		}
		return intval($toman / 10);
	}

}