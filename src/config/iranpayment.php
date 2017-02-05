<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default gateway
	|--------------------------------------------------------------------------
	| [saman|zarinpal]
	*/
	'default'	=> 'saman',

	'timeout'	=> 30,

	'hashids'	=> [
		'salt'	=> 'your-salt-string',
	],

	/*
	|--------------------------------------------------------------------------
	| Saman gateway
	|--------------------------------------------------------------------------
	*/
	'saman' => [
		'merchant-id'	=> env('SAMAN_MERCHANT_ID', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
		'callback-url'	=> 'http://example.com/payments/saman/callback',
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
		'callback-url'	=> 'http://example.com/payments/zarinpal/callback',
		'server'		=> 'germany',
		'description'	=> 'description',
		'email'			=> 'email',
		'mobile'		=> 'mobile',
	],

];