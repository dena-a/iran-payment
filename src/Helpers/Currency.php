<?php

namespace Dena\IranPayment\Helpers;

class Currency
{
    const IRR = 'IRR';

    const IRT = 'IRT';

    public static function TomanToRial($rial): int
    {
        return abs(intval($rial) * 10);
    }

    public static function RialToToman($toman): int
    {
        $toman = abs(intval($toman));

        return ($toman == 0) ? 0 : intval($toman / 10);
    }
}
