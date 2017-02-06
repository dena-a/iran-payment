# IranPayment
a Laravel package to handle Internet Payment Gateways for Iran Banking System

<p align="center"><a href="https://github.com/dena-a/iran-payment" target="_blank"><img width="300" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/images/screen.png"></a></p>

## Install

You can install the package via composer:
``` bash
$ composer require dena-a/iran-payment
```

This service provider must be installed.
```php
// config/app.php
'providers' => [
    ...
    Dena\IranPayment\IranPaymentServiceProvider::class
];
```

Publish the config-file and migration with:
```bash
php artisan vendor:publish --provider="Dena\IranPayment\IranPaymentServiceProvider"
```
After the migration has been published you can create the transactions-tables by
running the migrations:
```bash
php artisan migrate
```

## Usage

First add aliases:
```php
// config/app.php
'aliases' => [
    ...
    'IranPayment' => Dena\IranPayment\IranPayment::class,
    'IranPaymentTransaction' => Dena\IranPayment\Models\IranPaymentTransaction::class,
];
```

New Payment:
```php
$payment = new IranPayment();
$payment = $payment->build()->setUserId($user_id)->setAmount(1000)->ready();
return $payment->redirect();
```

Verify Payment:
```php
$payment = new IranPayment();
$tracking_code = $payment->verify()->trackingCode();
return $tracking_code;
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
