# IranPayment
**a Laravel package to handle Internet Payment Gateways for Iran Banking System**

[![Latest Stable Version](https://poser.pugx.org/dena-a/iran-payment/v)](https://packagist.org/packages/dena-a/iran-payment)
[![Total Downloads](https://poser.pugx.org/dena-a/iran-payment/downloads)](https://packagist.org/packages/dena-a/iran-payment)
[![Latest Unstable Version](https://poser.pugx.org/dena-a/iran-payment/v/unstable)](https://packagist.org/packages/dena-a/iran-payment)
[![License](https://poser.pugx.org/dena-a/iran-payment/license)](https://packagist.org/packages/dena-a/iran-payment)

<p align="center"><a href="https://github.com/dena-a/iran-payment" target="_blank"><img width="650" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/images/screen.png"></a></p>

## Installation

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

Add aliases:
```php
// config/app.php
'aliases' => [
    ...
    'IranPayment' => Dena\IranPayment\IranPayment::class,
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

### New Payment:
```php
//default gateway
$payment = new IranPayment();
// OR one of ['zarinpal', 'saman', 'payir']
$payment = new IranPayment('zarinpal');
// OR test gateway (Would not work on production environment)
$payment = new IranPayment('test');

$payment->build()
        ->setUserId($user->id)
        ->setAmount($data['amount'])
        ->setCallbackUrl(route('bank.callback'))
        ->ready();

return $payment->redirectView();
```

### Verify Payment:
```php
$payment = new IranPayment();
$payment->build()->verify($transaction);
$trackingCode = $payment->getTrackingCode();
$statusText = $payment->getTransactionStatusText();
```
### Extends
```php
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

class NewGateway extends AbstractGateway implements GatewayInterface {
    
    public function getName() {
        return 'new-gateway';
    }
    
    public function payRequest() {
        $code = rand(1, 10000);
        $this->setReferenceNumber($code);
        $this->transactionPending([
            'reference_number'	=> intval($code)
        ]);
    }
    
    public function verifyRequest() {
        $this->transactionVerifyPending();
        $code = rand(1, 10000);
        $this->setTrackingCode($code);
		$this->transactionSucceed(['tracking_code' => $code]);
    }
    
    public function redirectView() {
        return view('welcome')->with([
            'transaction' => $this->getTransaction()
        ]);
    }
    
    public function payBack() {
    }
}

$payment = new IranPayment();
$payment->extends(NewGateway::class);
```

## Payment Gateways

Gateway | Available | Tested | Last Update
--- | --- | --- | ---
[Saman (Sep)](https://www.sep.ir/) | ✓ | ✓ | 2020/08/03
[Pay.ir](https://pay.ir/) | ✓ | ✓ | 2020/08/03
[Zarinpal](https://zarinpal.com/) | ✓ | ✓ | 2020/08/03
[Payping](https://github.com/queueup-dev/omnipay-acapture) | ✓ | ✓ | 2020/08/03
[Melli (Sadad)](https://sadadpsp.ir/) | - | - | -
[Mellat (Behpardakht)](http://www.behpardakht.com/) | - | - | -
[Parsian (Pec)](https://www.pec.ir/) | - | - | -
[Pasargad (Pep)](https://www.pep.co.ir/) | - | - | -
[Zibal](https://zibal.ir/) | - | - | -

## Upgrade from v1 to v2

## Contribute

Contributions are always welcome!

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/dena-a/iran-payment/issues),
or better yet, fork the library and submit a pull request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
