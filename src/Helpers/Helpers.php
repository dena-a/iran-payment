<?php

namespace Dena\IranPayment\Helpers;

class Helpers
{
    public static function urlQueryBuilder(string $url, array $params = [])
    {
        $query = count($params) ? http_build_query($params) : '';

        $question_mark = strpos($url, '?');
        if ($question_mark) {
            return $url.'&'.$query;
        } else {
            return $url.'?'.$query;
        }
    }
}
