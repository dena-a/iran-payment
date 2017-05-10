<?php

namespace Dena\IranPayment\Helpers;

class Currency
{

	public static function RialToToman($rial) {
		return abs(intval($rial) * 10);
	}

	public static function TomanToRial($toman) {
		$toman = abs(intval($toman));
		if ($toman == 0) {
			return 0;
		}
		return intval($toman / 10);
	}

}