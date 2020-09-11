<?php

namespace Dena\IranPayment;

use Dena\IranPayment\Gateways\AbstractGateway;
use Dena\IranPayment\Gateways\GatewayInterface;

use Dena\IranPayment\Gateways\PayIr\PayIr;
use Dena\IranPayment\Gateways\Saman\Saman;
use Dena\IranPayment\Gateways\Sadad\Sadad;
use Dena\IranPayment\Gateways\PayPing\PayPing;
use Dena\IranPayment\Gateways\Test\TestGateway;
use Dena\IranPayment\Gateways\Zarinpal\Zarinpal;

use Dena\IranPayment\Exceptions\GatewayNotFoundException;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Illuminate\Http\Request;

class IranPayment
{
    /**
     * Gateways classes constant names
     */
    const SAMAN	    = 'saman';
    const SADAD     = 'sadad';
    const PAYIR	    = 'payir';
    const PAYDOTIR  = 'pay.ir';
    const ZARINPAL  = 'zarinpal';
    const PAYPING   = 'payping';
    const TEST      = 'test';

    /**
     * Gateway variable
     *
     * @var GatewayInterface
     */
    protected GatewayInterface $gateway;

    /**
     * Constructor function
     *
     * @param GatewayInterface|string $gateway
     * @throws GatewayNotFoundException
     */
    public function __construct($gateway)
    {
        $this->setGateway($gateway);
    }

    /**
     * Set Gateway function
     *
     * @param GatewayInterface|string $gateway
     * @return $this
     * @throws GatewayNotFoundException
     */
    public function setGateway($gateway): self
    {
        if ($gateway instanceof GatewayInterface) {
            $this->gateway = $gateway;

            return $this;
        }

        switch ($gateway) {
            case self::SAMAN:
            case Saman::class:
                $this->gateway = new Saman;
                break;
            case self::SADAD:
            case Sadad::class:
                $this->gateway = new Sadad;
                break;
            case self::PAYIR:
            case self::PAYDOTIR:
            case PayIr::class:
                $this->gateway = new PayIr;
                break;
            case self::ZARINPAL:
            case Zarinpal::class:
                $this->gateway = new Zarinpal;
                break;
            case self::PAYPING:
            case PayPing::class:
                $this->gateway = new PayPing;
                break;
            case self::TEST:
            case TestGateway::class:
                if (app('config')->get('app.env', 'production') == 'production')
                    throw GatewayNotFoundException::productionUnavailableGateway();

                $this->gateway = new TestGateway;
                break;
            default:
                throw new GatewayNotFoundException;
        }

        return $this;
    }

    /**
     * Get Gateway function
     *
     * @return GatewayInterface
     */
    public function getGateway(): GatewayInterface
    {
        return $this->gateway;
    }

    /**
     * Build Gateway function
     *
     * @return GatewayInterface
     */
    public function build(): GatewayInterface
    {
        return $this->gateway->initialize();
    }

    /**
     * Get Supported Gateways function
     *
     * @return array
     */
    public function getSupportedGateways(): array
    {
        $gateways = [
            self::ZARINPAL,
            self::SAMAN,
            self::SADAD,
            self::PAYIR,
            self::PAYPING,
        ];

        if (app('config')->get('app.env', 'production') !== 'production') {
            $gateways[] = self::TEST;
        }

        return $gateways;
    }

    /**
     * Create new Instance of IranPayment
     *
     * @param GatewayInterface|string|null $gateway
     * @return GatewayInterface
     * @throws GatewayNotFoundException
     */
    public static function create($gateway = null): GatewayInterface
    {
        if (is_null($gateway)) {
            $gateway = app('config')->get('iranpayment.default', Saman::class);
        }

        return (new self($gateway))->build();
    }

    /**
     * Detect Gateway and Create new Instance of IranPayment
     *
     * @param IranPaymentTransaction|Request|null $data
     * @return GatewayInterface
     * @throws GatewayNotFoundException
     */
    public static function detect($data = null): GatewayInterface
    {
        $transaction_query_param = app('config')->get('iranpayment.transaction_query_param', 'tc');

        $data ??= app('request');

        if ($data instanceof Request) {
            $transaction_code = $data->get($transaction_query_param);
        } elseif ($data instanceof IranPaymentTransaction) {
            $transaction = $data;
            $gateway = $transaction->gateway;
        }

        if (isset($transaction_code)) {
            $transaction = IranPaymentTransaction::where('code', $transaction_code)->first();
            if (isset($transaction)) $gateway = $transaction->gateway;
        }

        if (!isset($gateway)) throw new GatewayNotFoundException;

        $gateway = self::create($gateway);

        if (isset($transaction) && $gateway instanceof AbstractGateway) {
            $gateway->setTransaction($transaction);
        }

        return $gateway;
    }
}
