<?php

use Dena\IranPayment\Gateways\Sadad\Sadad;
use Dena\IranPayment\Gateways\Test\TestGateway;
use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Models\IranPaymentTransaction;
use Orchestra\Testbench\TestCase;
use Tests\Models\ProductModel;

class IranpaymentTestGatewayTest extends TestCase
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
    }

    protected function getPackageProviders($app)
    {
        return [
            \Dena\IranPayment\IranPaymentServiceProvider::class,
        ];
    }

    public function testGateway()
    {
        $payment = (new IranPayment('test'));
        $this->assertInstanceOf(IranPayment::class, $payment);
        $this->assertInstanceOf(TestGateway::class, $payment->getGateway());
        $payment = $payment->build();
        $this->assertInstanceOf(TestGateway::class, $payment);
        $payment = $payment->setAmount(1000)
            ->setCallbackUrl(url('/test'))
            ->setPayableId(1)
            ->setPayableType(ProductModel::class)
            ->ready();

        $this->assertEquals(IranPaymentTransaction::T_PENDING, $payment->getTransaction()->status);
        $this->assertEquals(1000, $payment->getTransaction()->amount);
        $this->assertEquals(url('/test'), $payment->getCallbackUrl());
        $this->assertEquals(1, $payment->getPayableId());
        $this->assertEquals(ProductModel::class, $payment->getPayableType());

        //verify
        $tr = $payment->getTransaction();
        $payment = (new IranPayment('test'))->build();
        $payment->findTransaction($tr->code);
        $payment->confirm();
        $transaction = $payment->getTransaction();
        $this->assertEquals(IranPaymentTransaction::T_SUCCEED, $transaction->status);
    }
}