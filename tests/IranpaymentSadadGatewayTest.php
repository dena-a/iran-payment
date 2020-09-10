<?php

use Dena\IranPayment\Gateways\Sadad\Sadad;
use Dena\IranPayment\Gateways\Sadad\SadadException;
use Dena\IranPayment\Gateways\Test\TestGateway;
use Dena\IranPayment\IranPayment;
use Dena\IranPayment\Models\IranPaymentTransaction;
use Orchestra\Testbench\TestCase;
use Tests\Models\ProductModel;

class IranpaymentSadadGatewayTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
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
            'iranpayment.sadad.merchant_id',
            app('config')->get('iranpayment.sadad.merchant_id', 1)
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

    public function testSuccess()
    {
        $sadad = Mockery::mock(Sadad::class)->makePartial();
        // $sadad->shouldReceive('getToken')->andReturn(1);
        $sadad->shouldAllowMockingProtectedMethods();
        $purchaseResponse = (object) [
            'Token' => 1,
            'ResCode' => 0,
            'Description' => ''
        ];
        $verifyResponse = (object) [
            'ResCode' => 0,
            'Amount' => 10000,
            'SystemTraceNo' => 1,
            'RetrivalRefNo' => 1,
            'Description' => ''
        ];
        $sadad->shouldReceive('httpRequest')->andReturn($purchaseResponse, $verifyResponse);
        // $sadad->shouldReceive('purchase')->andReturn(null);
        // $sadad->shouldReceive('verify')->andReturn(null);

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

    public function testError()
    {
        $sadad = Mockery::mock(Sadad::class)->makePartial();
        $sadad->shouldAllowMockingProtectedMethods();
        $purchaseResponse = (object) [
            'Token' => 1,
            'ResCode' => 1006,
            'Description' => ''
        ];
        $verifyResponse = (object) [
            'ResCode' => -1,
            'Amount' => 10000,
            'SystemTraceNo' => 1,
            'RetrivalRefNo' => 1,
            'Description' => ''
        ];
        $sadad->shouldReceive('httpRequest')->andReturn($purchaseResponse, $verifyResponse);

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

        try {
            $payment = $payment->ready();
        } catch (Throwable $e) {
            $this->assertInstanceOf(SadadException::class, $e);
            $this->assertEquals(SadadException::error(1006)->getMessage(), $e->getMessage());
        }
        
        $this->assertEquals(IranPaymentTransaction::T_FAILED, $payment->getTransaction()->status);
    }
}