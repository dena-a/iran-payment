<?php

use Dena\IranPayment\Gateways\PayIr\PayIr;
use Dena\IranPayment\Gateways\PayPing\PayPing;
use Dena\IranPayment\Gateways\Zarinpal\Zarinpal;
use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Models\IranPaymentTransaction;
use Orchestra\Testbench\TestCase;
use Tests\Models\ProductModel;

class IranpaymentPaypingGatewayTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations', ['--database' => 'testing']);
        $this->loadMigrationsFrom(__DIR__ . '/migrations', ['--database' => 'testing']);
    }
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.env', 'testing');

        $app['config']->set(
            'iranpayment.payping.merchant-id',
            app('config')->get('iranpayment.payping.merchant-id', 1)
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            \Dena\IranPayment\IranPaymentServiceProvider::class,
        ];
    }

    public function testSuccess()
    {
        $gateway = Mockery::mock(PayPing::class)->makePartial();
        $gateway->shouldReceive('getCode')->andReturn(1);
        $gateway->shouldReceive('purchase')->andReturn(null);
        $gateway->shouldReceive('verify')->andReturn(null);
        $gateway->shouldReceive('getTrackingCode')->andReturn(1);
        $gateway->shouldReceive('getPayerCardNumber')->andReturn(1234123412341234);

        $product = (new ProductModel(['title' => 'product']));
        $product->save();
        $payment = (new IranPayment($gateway));

        $payment = $payment->build()
            ->setAmount(10000)
            ->setCallbackUrl(url('/test'))
            ->setPayable($product);
        $this->assertInstanceOf(PayPing::class, $payment);
        $this->assertEquals(10000, $payment->getAmount());
        $this->assertEquals(url('/test'), $payment->getCallbackUrl());
        $this->assertEquals(1, $payment->getPayable()->id);
        $this->assertEquals(ProductModel::class, get_class($payment->getPayable()));

        $payment = $payment->ready();
        $this->assertEquals(IranPaymentTransaction::T_PENDING, $payment->getTransaction()->status);
        $this->assertEquals(1, $payment->getCode());
        $this->assertEquals("https://api.payping.ir/v2/pay/gotoipg/1", $payment->purchaseUri());

        $tr = $payment->getTransaction();
        $payment = (new IranPayment($gateway))->build();
        $payment->findTransaction($tr->code);
        $payment->confirm();
        $transaction = $payment->getTransaction();
        $this->assertEquals(IranPaymentTransaction::T_SUCCEED, $transaction->status);
    }
}