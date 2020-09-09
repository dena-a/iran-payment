<?php

use Dena\IranPayment\Gateways\Sadad\Sadad;
use Dena\IranPayment\Gateways\Sadad\SadadException;
use Dena\IranPayment\Gateways\Test\TestGateway;
use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Models\IranPaymentTransaction;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use Tests\Models\ProductModel;

class IranpaymentSadadGatewayTest extends TestCase
{
    private bool $isMock = false;
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

        $merchantId = app('config')->get('iranpayment.sadad.merchant_id');
        if (!$merchantId) $this->isMock = true;

        $app['config']->set(
            'iranpayment.sadad.merchant_id',
            $merchantId ?? 1
        );
        $app['config']->set(
            'iranpayment.sadad.terminal_id',
            app('config')->get('iranpayment.sadad.terminal_id', "1")
        );
        $app['config']->set(
            'iranpayment.sadad.terminal_key',
            app('config')->get('iranpayment.sadad.terminal_key', "1")
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            \Dena\IranPayment\IranPaymentServiceProvider::class,
        ];
    }

    public function testSadadGateway()
    {
        $sadad = Mockery::mock(Sadad::class)->makePartial();
        $sadad->shouldReceive('purchase')->andReturn(null);
        $sadad->shouldReceive('getToken')->andReturn(1);
        $sadad->shouldReceive('verify')->andReturn(null);

        $payment = (new IranPayment($sadad));

        $payment = $payment->build()
            ->setAmount(10000)
            ->setCallbackUrl(url('/test'))
            ->setPayableId(1)
            ->setPayableType(ProductModel::class);
        $this->assertInstanceOf(Sadad::class, $payment);
        $this->assertEquals(10000, $payment->getAmount());
        $this->assertEquals(url('/test'), $payment->getCallbackUrl());
        $this->assertEquals(1, $payment->getPayableId());
        $this->assertEquals(ProductModel::class, $payment->getPayableType());

        $payment = $payment->ready();
        $this->assertEquals(IranPaymentTransaction::T_PENDING, $payment->getTransaction()->status);
        $this->assertEquals(1, $payment->getToken());
        $this->assertEquals("https://sadad.shaparak.ir/VPG/Purchase?Token=1", $payment->purchaseUri());

        $tr = $payment->getTransaction();
        $payment = (new IranPayment($sadad))->build();
        $payment->findTransaction($tr->code);
        $payment->confirm();
        $transaction = $payment->getTransaction();
        $this->assertEquals(IranPaymentTransaction::T_SUCCEED, $transaction->status);
    }
}