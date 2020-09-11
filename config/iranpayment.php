<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default gateway
    |--------------------------------------------------------------------------
    | [saman|sadad|zarinpal|payir|payping]
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
    'transaction_query_param' => 'tc',

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
    | Sadad gateway
    |--------------------------------------------------------------------------
    */
    'sadad' => [
        'merchant_id'  => env('SADAD_MERCHANT_ID',  'xxxxxxxx'),
        'terminal_id'  => env('SADAD_TERMINAL_ID',  'xxxxxxxx'),
        'terminal_key' => env('SADAD_TERMINAL_KEY', 'xxxxxxxx'),
        'app_name'     => env('APP_NAME', ''),
        // 'callback-url' => 'http://example.com/payments/sadad/callback',
    ],

    /*
    |--------------------------------------------------------------------------
    | Saman Pay.ir gateway
    |--------------------------------------------------------------------------
    */
    'payir' => [
        'merchant-id' => env('PAYIR_MERCHANT_ID', 'xxxxxxxx'), // api. set test for test
        // 'callback-url' => 'http://example.com/payments/payir/callback',
    ],

    /*
    |--------------------------------------------------------------------------
    | Zarinpal gateway
    |--------------------------------------------------------------------------
    | Types: [normal | zaringate | sandbox (for test)]
    */
    'zarinpal' => [
        'merchant-id' => env('ZARINPAL_MERCHANT_ID', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
        'type' => 'normal',
        'add_fees' => false,
        'description' => 'تراكنش خرید',
        // 'callback-url' => 'http://example.com/payments/zarinpal/callback',
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPing gateway
    |--------------------------------------------------------------------------
    */
    'payping' => [
        'merchant-id' => env('PAYPING_MERCHANT_ID', 'xxxxxxxx'),
        'add_fees' => false,
        // 'callback-url' => 'http://example.com/payments/payping/callback',
    ],
];
