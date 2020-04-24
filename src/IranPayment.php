<?php

namespace Dena\IranPayment;

use Dena\IranPayment\Exceptions\GatewayNotFoundException;
use Dena\IranPayment\Exceptions\OnlyWorkOnDebugModeException;

use Dena\IranPayment\Providers\BaseProvider;
use Dena\IranPayment\Providers\PayIr\PayIr;
use Dena\IranPayment\Providers\Saman\Saman;
use Dena\IranPayment\Providers\PayPing\PayPing;
use Dena\IranPayment\Providers\Zarinpal\Zarinpal;
use Dena\IranPayment\Providers\Test\TestGateway;

use Dena\IranPayment\Providers\GatewayInterface;

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
	protected $gateway;

	/**
	 * Constructor function
	 *
	 * @param mixed $gateway
	 */
	public function __construct($gateway = null)
	{
		$this->setDefaults();

		if (!is_null($gateway)) {
			$this->setGateway($gateway);
		} elseif ($gateway = BaseProvider::detectGateway()) {
			$this->setGateway($gateway);
		}
	}

	/**
	 * Set Defaults function
	 *
	 * @return void
	 */
	private function setDefaults()
	{
		$this->setGateway(config('iranpayment.default', Saman::class));
	}

	/**
	 * set Gateway function
	 *
	 * @param $gateway
	 * @return void
	 */
	public function setGateway($gateway)
	{
		if (is_string($gateway)) {
			switch (strtolower($gateway)) {
				case self::ZARINPAL:
					$this->gateway = new Zarinpal;
					break;
				case self::SAMAN:
					$this->gateway = new Saman;
					break;
				case self::PAYIR:
				case self::PAYDOTIR:
					$this->gateway = new PayIr;
					break;
				case self::PAYPING:
					$this->gateway = new PayPing;
					break;
				case self::TEST:
					if(env('APP_DEBUG') !== true)
						throw new OnlyWorkOnDebugModeException;
					$this->gateway = new TestGateway;
					break;
			}
		} else {
			$this->gateway  = $gateway;
		}
	}

	/**
	 * get Gateway function
	 *
	 * @return void
	 */
	public function getGateway()
	{
		return $this->gateway;
	}

	/**
	 * Build Gateway function
	 *
	 * @return GatewayInterface
	 */
	public function build()
	{
		if (!$this->gateway instanceof GatewayInterface) {
			throw new GatewayNotFoundException;
		}

		return $this->gateway;
	}

	/**
	 * get Supported Gateways function
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

		if (env('APP_DEBUG') === true) {
			$gateways[] = self::TEST;
		}

		return $gateways;
	}

    public function __call($name, $arguments)
    {
        if (
            !method_exists(__CLASS__, $name)
            && method_exists($this->gateway, $name)
        ) {
            if (!$this->gateway instanceof GatewayInterface) {
                $this->build();
            }

            $res = call_user_func_array([$this->gateway, $name], $arguments);
            return $res ?? $this->gateway;
        }
    }
}
