# IranPayment
a Laravel package to handle Internet Payment Gateways for Iran Banking System

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


### Extends
```php
use Dena\IranPayment\Providers\BaseProvider;
use Dena\IranPayment\Providers\ProviderInterface;

class TestGateway extends BaseProvider implements ProviderInterface {
    
    public function getName() {
        return 'test-gateway';
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
```

### New Payment:
```php
//default gateway
$payment = new IranPayment();
// OR
$payment = new IranPayment('zarinpal');
// Custom gateway
$payment->extends(TestGateway::class);
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
$payment->extends(TestGateway::class);
$payment->build()->verify($transaction);
$trackingCode = $payment->getTrackingCode();
$statusText = $payment->getTransactionStatusText();
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
