<p align="center">
  <img src="https://raw.githubusercontent.com/dena-a/iran-payment/master/images/logo.png" height="128"/>
</p>
<h1 align="center">IranPayment for Laravel</h1>

<p align="center"><a href="https://github.com/dena-a/iran-payment" target="_blank"><img width="650" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/images/screen.png"></a></p>

**a Laravel package to handle Internet Payment Gateways (IPGs) for Iran Banking System**

Accepting [Sadad (Melli)](https://sadadpsp.ir/), [Saman (Sep)](https://www.sep.ir/), [Zarinpal](https://zarinpal.com/) and more iranian payment gateways. Just use the IranPayment to receive payments directly on your website.

[![Latest Stable Version](https://poser.pugx.org/dena-a/iran-payment/v)](https://packagist.org/packages/dena-a/iran-payment)
[![Total Downloads](https://poser.pugx.org/dena-a/iran-payment/downloads)](https://packagist.org/packages/dena-a/iran-payment)
[![Latest Unstable Version](https://poser.pugx.org/dena-a/iran-payment/v/unstable)](https://packagist.org/packages/dena-a/iran-payment)
[![License](https://poser.pugx.org/dena-a/iran-payment/license)](https://packagist.org/packages/dena-a/iran-payment)

## Gateways

## Gateways

| Logo                                                                                                                   | Gateway                                             | Description                         | Available        | Tested | Last Update |
|------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------|-------------------------------------|------------------|--------|-------------|
| <img width="50" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/sadad.png">     | [Sadad (Melli)](https://sadadpsp.ir/)               | بانک ملی (سداد)                     | ✓                | ✓      | 2020/09/10  |
| <img width="50" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/sep.png">       | [Saman (Sep)](https://www.sep.ir/)                  | (سپ) بانک سامان                     | ✓                | ✓      | 2020/08/08  |
| <img width="50" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/pay.png">       | [Pay.ir](https://pay.ir/)                           | پرداخت پی                           | ✓                | ✓      | 2020/08/03  |
| <img width="50" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/zp.png">        | [Zarinpal](https://zarinpal.com/)                   | زرین پال                            | ✓                | ✓      | 2020/08/03  |
| <img width="50" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/payping.png">   | [Payping](https://www.payping.ir/)                  | پی پینگ                             | ✓                | ✓      | 2020/08/04  |
| <img width="50" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/novinopay.png"> | [Novinopay](https://www.novinopay.com/)             | نوینو پرداخت                        | ✓                | ✓      | 2022/03/23  |
| <img width="50" src="https://raw.githubusercontent.com/dena-a/iran-payment/master/resources/assets/img/digipay.png">   | [Digipay](https://www.mydigipay.com/)               | دیجی پی                             | CPG ✓ BPG/BNPL ✓ | ✓      | 2024/06/24  |
| ---                                                                                                                    | [Qeroun](https://qeroun.com/)                       | قرون - خرید امن با ایجاد توافق‌نامه | -                | -      | -           |
| ---                                                                                                                    | [Mellat (Behpardakht)](http://www.behpardakht.com/) | (به پرداخت) بانک ملت                | -                | -      | -           |
| ---                                                                                                                    | [Parsian (Pec)](https://www.pec.ir/)                | (پک) بانک پارسیان                   | -                | -      | -           |
| ---                                                                                                                    | [Pasargad (Pep)](https://www.pep.co.ir/)            | (پپ) بانک پاسارگاد                  | -                | -      | -           |
| ---                                                                                                                    | [Zibal](https://zibal.ir/)                          | زیبال                               | -                | -      | -           |
| ---                                                                                                                    | [IDPay](https://idpay.ir/)                          | آیدی پی                             | -                | -      | -           |

## Requirements

* PHP >= 7.4
* PHP ext-curl
* PHP ext-json
* PHP ext-soap
* [Laravel](https://www.laravel.com) (or [Lumen](https://lumen.laravel.com)) >= 5.7

## Installation
1. Add the package to your composer file via the `composer require` command:
   
   ```bash
   $ composer require dena-a/iran-payment:^2.0
   ```
   
   Or add it to `composer.json` manually:
   
   ```json
   "require": {
       "dena-a/iran-payment": "^2.0"
   }
   ```

2. IranPayment's service providers will be automatically registered using Laravel's auto-discovery feature.

    > Note: For Lumen you have to add the IranPayment service provider manually to: `bootstrap/app.php` :

    ```php
   $app->register( Dena\IranPayment\IranPaymentServiceProvider::class);
    ```

3. Publish the config-file and migration with:
    ```bash
    php artisan vendor:publish --provider="Dena\IranPayment\IranPaymentServiceProvider"
    ```
4. After the migration has been published you can create the transactions-tables by running the migrations:
    ```bash
    php artisan migrate
    ```

## Usage

### New Payment:
```php
use Dena\IranPayment\IranPayment;

// Default gateway
$payment = IranPayment::create();
// Select one of available gateways
$payment = IranPayment::create('sadad');
// Test gateway (Would not work on production environment)
$payment = IranPayment::create('test');
// Or use your own gateway
$payment = IranPayment::create(NewGateway::class);

$payment->setUserId($user->id)
        ->setAmount($data['amount'])
        ->setCallbackUrl(route('bank.callback'))
        ->ready();

return $payment->redirect();
```

### Verify Payment:

```php
use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Exceptions\IranPaymentException;

try {
    $payment = IranPayment::detect()->confirm();
    $trackingCode = $payment->getTrackingCode();
    $statusText = $payment->getTransactionStatusText();
} catch (Dena\IranPayment\Exceptions\IranPaymentException $ex) {
    throw $ex;
}
```
### Create your own payment gateway class
```php
use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

class NewGateway extends AbstractGateway implements GatewayInterface
{
    public function getName(): string
    {
        return 'new-gateway';
    }

    public function initialize(array $parameters = []): self
    {
        parent::initialize($parameters);
    
        return $this;
    }
    
    public function purchase(): void
    {
        // Send Purchase Request

        $reference_number = 'xxxx';

        $this->transactionUpdate([
            'reference_number' => $reference_number,
        ]);
    }

    
    public function purchaseUri(): string
    {
        return 'http://new-gateway.com/token/xxxx';
    }
    
    public function verify(): void
    {
        $this->transactionVerifyPending();
            
        // Send Payment Verify Request

        $tracking_code = 'yyyy';

        $this->transactionSucceed([
            'tracking_code' => $tracking_code
        ]);
    }
}
```

## Contribute

Contributions are always welcome!

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/dena-a/iran-payment/issues),
or better yet, fork the library and submit a pull request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
