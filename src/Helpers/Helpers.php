<?php

namespace Dena\IranPayment\Helpers;

class Helpers
{
	public static function generateRandomString($length = 16)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$characters_length = strlen($characters);
		$random_string = '';
		for ($i = 0; $i < $length; $i++) {
			$random_string .= $characters[rand(0, $characters_length - 1)];
		}
		return $random_string;
	}

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