<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default gateway
	|--------------------------------------------------------------------------
	| [saman|zarinpal|payir|payping]
	*/
	'default' => env('IRANPAYMENT_DEFAULT', 'saman'),

	/*
	|--------------------------------------------------------------------------
	| Default currency
	|--------------------------------------------------------------------------
	| [IRR|IRT]
	*/
	'currency' => env('IRANPAYMENT_CURRENCY', 'IRR'),

	/*
	|--------------------------------------------------------------------------
	| Default callback url
	|--------------------------------------------------------------------------
	| You can use setCallbackUrl method to set a custom callback url for a payment.
	| You may set a specific callback url for each gateways in their config with callback-url parameter.
	*/
	// 'callback-url' => 'http://example.com/payments/callback',
	'callback-url' => '/payments/callback',

	/*
	|--------------------------------------------------------------------------
	| Settings
	|--------------------------------------------------------------------------
	*/
	'code_length' => 16,

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
		'add_fees'		=> false,
		// 'callback-url'	=> 'http://example.com/payments/zarinpal/callback',
	],

	/*
	|--------------------------------------------------------------------------
	| Saman Pay.ir gateway
	|--------------------------------------------------------------------------
	*/
	'payir' => [
		'merchant-id'	=> env('PAYIR_MERCHANT_ID', 'xxxxxxxx'), // api. set test for test
		// 'callback-url'	=> 'http://example.com/payments/payir/callback',
	],

	/*
	|--------------------------------------------------------------------------
	| PayPing gateway
	|--------------------------------------------------------------------------
	*/
	'payping' => [
		'merchant-id'	=> env('PAYPING_MERCHANT_ID', 'xxxxxxxx'),
		'add_fees'		=> false,
		// 'callback-url'	=> 'http://example.com/payments/payping/callback',
	],

	'transaction_query_param' => 'tc'

];
