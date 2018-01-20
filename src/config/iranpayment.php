<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default gateway
	|--------------------------------------------------------------------------
	| [saman|zarinpal]
	*/
	'default' => 'saman',

	/*
	|--------------------------------------------------------------------------
	| Default currency
	|--------------------------------------------------------------------------
	| [IRR|IRT]
	*/
	'currency' => 'IRR',

	/*
	|--------------------------------------------------------------------------
	| Default callback url
	|--------------------------------------------------------------------------
	| You can use setCallbackUrl method to set a custom callback url for a payment.
	| You may set a specific callback url for each gateways in their config with callback-url parameter.
	*/
	'callback-url' => 'http://example.com/payments/callback',

	/*
	|--------------------------------------------------------------------------
	| Hashid
	|--------------------------------------------------------------------------
	| Salt: use your own random string for hash-id.
	*/
	'hashids' => [
		'salt' => env('IRANPAYMENT_HASH_SALT', 'your-salt-string'),
	],

	/*
	|--------------------------------------------------------------------------
	| Saman gateway
	|--------------------------------------------------------------------------
	*/
	'saman' => [
		'merchant-id' => env('SAMAN_MERCHANT_ID', 'xxxxxxxx'),
		// 'callback-url' => 'http://example.com/payments/saman/callback',
	],

	/*
	|--------------------------------------------------------------------------
	| Zarinpal gateway
	|--------------------------------------------------------------------------
	| Types: [zarin-gate || normal]
	| Servers: [germany || iran]
	*/
	'zarinpal' => [
		'merchant-id'	=> env('ZARINPAL_MERCHANT_ID', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
		'type'			=> 'normal',
		'server'		=> 'germany',
		'description'	=> 'payment description', // Required
		'email'			=> null, // Optional : user_email@example.com
		'mobile'		=> null, // Optional : 0912XXXYYZZ
		// 'callback-url'	=> 'http://example.com/payments/zarinpal/callback',
	],

];