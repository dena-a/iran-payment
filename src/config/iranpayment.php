<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default gateway
	|--------------------------------------------------------------------------
	| [saman|zarinpal]
	*/
	'default'		=> 'saman',

	'callback-url'	=> 'http://example.com/payments/callback',

	'hashids' => [
		'salt'		=> 'your-salt-string',
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
		'description'	=> 'description',
		'email'			=> 'email',
		'mobile'		=> 'mobile',
	],

];