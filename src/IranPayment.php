<?php

namespace Dena\IranPayment;

use Dena\IranPayment\Gateways\GatewayInterface;

use Dena\IranPayment\Gateways\PayIr\PayIr;
use Dena\IranPayment\Gateways\Saman\Saman;
use Dena\IranPayment\Gateways\PayPing\PayPing;
use Dena\IranPayment\Gateways\Test\TestGateway;
use Dena\IranPayment\Gateways\Zarinpal\Zarinpal;

use Dena\IranPayment\Exceptions\GatewayNotFoundException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Illuminate\Http\Request;

class IranPayment
{
	/**
	 * Gateways classes constant names
	 */
	const ZARINPAL	= 'zarinpal';
	const SAMAN		= 'saman';
	const PAYIR		= 'payir';
	const PAYDOTIR	= 'pay.ir';
	const PAYPING	= 'payping';
	const TEST		= 'test';

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
	 */
	public function __construct($gateway)
	{
        $this->setGateway($gateway);
	}

	/**
	 * set Gateway function
	 *
	 * @param GatewayInterface|string $gateway
	 * @return $this
	 */
	public function setGateway($gateway): self
	{
	    if ($gateway instanceof GatewayInterface) {
            $this->gateway = $gateway;

            return $this;
        }

        switch ($gateway) {
            case self::ZARINPAL:
            case Zarinpal::class:
                $this->gateway = new Zarinpal;
                break;
            case self::SAMAN:
            case Saman::class:
                $this->gateway = new Saman;
                break;
            case self::PAYIR:
            case self::PAYDOTIR:
            case PayIr::class:
                $this->gateway = new PayIr;
                break;
            case self::PAYPING:
            case PayPing::class:
                $this->gateway = new PayPing;
                break;
            case self::TEST:
            case TestGateway::class:
                if (config('app.env', 'production') !== 'production')
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
		return $this->gateway;
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
			self::PAYIR,
			self::PAYDOTIR,
			self::PAYPING,
		];

		if (config('app.env', 'production') !== 'production') {
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
            $gateway = config('iranpayment.default', Saman::class);
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
        $transaction_query_param = config('iranpayment.transaction_query_param', 'tc');

        if (is_null($data)) {
            $request = app('request');
            $transaction_code = $request->get($transaction_query_param);
        } elseif ($data instanceof Request) {
            $transaction_code = $data->get($transaction_query_param);
        } elseif ($data instanceof IranPaymentTransaction) {
            $transaction = $data;
            $gateway = $transaction->gateway;
        }

        if (isset($transaction_code)) {
            $transaction = IranPaymentTransaction::where('code', $transaction_code)->first();
            if (isset($transaction))
                $gateway = $transaction->gateway;
        }

        if (!isset($gateway)) {
            throw new GatewayNotFoundException;
        }

        return !isset($transaction)
            ? (new self($gateway))->build()
            : (new self($gateway))->build()->setTransaction($transaction);
    }

    public function __call($name, $arguments)
    {
        if (
            !method_exists(__CLASS__, $name)
            && method_exists($this->gateway, $name)
        ) {
            $this->build();

            $res = call_user_func_array([$this->gateway, $name], $arguments);

            return $res ?? $this->gateway;
        }
    }
}
