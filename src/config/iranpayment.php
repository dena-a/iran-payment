<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default gateway
	|--------------------------------------------------------------------------
	| [saman|zarinpal]
	*/
	'default'		=> 'saman',

	/*
	|--------------------------------------------------------------------------
	| Default currency
	|--------------------------------------------------------------------------
	| [IRR|IRT]
	*/
	'currency'		=> 'IRR',

	/*
	|--------------------------------------------------------------------------
	| Default callback url
	|--------------------------------------------------------------------------
	| You can use setCallbackUrl method to set a custom callback url for a payment.
	| You may set a specific callback url for each gateways in the gateway config with callback-url parameter.
	*/
	'callback-url'	=> 'http://example.com/payments/callback',

	/*
	|--------------------------------------------------------------------------
	| Hashid
	|--------------------------------------------------------------------------
	| Salt: use your own random string for hash-id.
	*/
	'hashids' => [
		'use'		=> true,
		'salt'		=> 'your-salt-string',
	],

	/*
	|--------------------------------------------------------------------------
	| Store data
	|--------------------------------------------------------------------------
	*/
	'store' => [
		'use_iranpayment_table' => true,
	],

	/*
	|--------------------------------------------------------------------------
	| Saman gateway
	|--------------------------------------------------------------------------
	*/
	'saman' => [
		'merchant-id'	=> env('SAMAN_MERCHANT_ID', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
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
	],

];