<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Default gateway
	|--------------------------------------------------------------------------
	| [zarinpal|jahanpay|mellat|sadad|parsian|pasargad|saderat]
	*/
	'default' => 'zarinpal',

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

	/*
	|--------------------------------------------------------------------------
	| JahanPay gateway
	|--------------------------------------------------------------------------
	*/
	'jahanpay' => [
		'api'			=> env('JAHANPAY_API', 'xxxxxxxx'),
		'callback-url'	=> 'http://example.com/payments/jahanpay/callback',
	],

	/*
	|--------------------------------------------------------------------------
	| Mellat gateway
	|--------------------------------------------------------------------------
	*/
	'mellat' => [
		'username'		=> env('MELLAT_USERNAME', ''),
		'password'		=> env('MELLAT_PASSWORD', ''),
		'terminal-id'	=> env('MELLAT_TERMINAL_ID', 0000000),
		'callback-url'	=> 'http://example.com/payments/mellat/callback',
	],

	/*
	|--------------------------------------------------------------------------
	| Sadad gateway
	|--------------------------------------------------------------------------
	*/
	'sadad' => [
		'merchant'			=> env('SADAD_MERCHANT', ''),
		'transaction-key'	=> env('SADAD_TRANSACTION_KEY', ''),
		'terminal-id'		=> env('SADAD_TERMINAL_ID', 0000000),
		'callback-url'		=> 'http://example.com/payments/sadad/callback',
	],

	/*
	|--------------------------------------------------------------------------
	| Parsian gateway
	|--------------------------------------------------------------------------
	*/
	'parsian' => [
		'pin'			=> env('PARSIAN_PIN', 'xxxxxxxx'),
		'callback-url'	=> 'http://example.com/payments/parsian/callback',
	],

	/*
	|--------------------------------------------------------------------------
	| Pasargad gateway
	|--------------------------------------------------------------------------
	*/
	'pasargad' => [
		'merchant-code'	=> env('PASARGAD_MERCHANT_CODE', '99999999'),
		'terminal-code'	=> env('PASARGAD_TERMINAL_CODE', '99999999'),
		'callback-url'	=> 'http://example.com/payments/pasargad/callback',
	],

	/*
	|--------------------------------------------------------------------------
	| Saderat gateway
	|--------------------------------------------------------------------------
	| Public-key and Private-key must store in Storage directory.
	*/
	'saderat' => [
		'merchant-id'	=> env('SADERAT_MERCHANT_ID', '99999999'),
		'terminal-id'	=> env('SADERAT_TERMINAL_ID', '99999999'),
		'public-key'	=> '/saderat-public-key.pem',
		'private-key'	=> '/saderat-private-key.pem',
		'callback-url'	=> 'http://example.com/payments/saderat/callback',
	],

];