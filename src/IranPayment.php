<?php

namespace Dena\IranPayment;

use Dena\IranPayment\Exceptions\RetryException;
use Dena\IranPayment\Exceptions\SucceedRetryException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\GatewayNotFoundException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;
use Dena\IranPayment\Exceptions\OnlyWorkOnDebugModeException;

use Dena\IranPayment\Providers\PayIr\PayIr;
use Dena\IranPayment\Providers\Saman\Saman;
use Dena\IranPayment\Providers\Zarinpal\Zarinpal;
use Dena\IranPayment\Providers\Test\TestGateway;

use Dena\IranPayment\Models\IranPaymentTransaction;
use Dena\IranPayment\Providers\ProviderInterface;

class IranPayment
{
	const ZARINPAL	= 'zarinpal';
	const SAMAN		= 'saman';
	const PAYIR		= 'payir';
	const PAYDOTIR	= 'pay.ir';
	const TEST		= 'test';

	const CURRENCY_IRR	= 'IRR';
	const CURRENCY_IRT	= 'IRT';

	protected $gateway;
	protected $extended;

	public function __construct($gateway = null)
	{
		$this->extended = false;
		$this->setDefaults();
		if ($gateway) {
			$this->setGateway($gateway);
		}
	}

	public function __call($name, $arguments)
	{
		// if ($this->gateway) {
		// 	return call_user_func_array([$this->gateway, $name], $arguments);
		// }
		// return false;
		if (
            !method_exists(__CLASS__, $name)
            && $this->gateway instanceof ProviderInterface
            && method_exists($this->gateway, $name) 
        ) {
			$res = call_user_func_array([$this->gateway, $name], $arguments);
			return $res ?? $this->gateway;
		}
	}

	private function setDefaults()
	{
		$this->setGateway(config('iranpayment.default'));
	}

	public function setGateway($gateway)
	{
		$this->gateway = strtolower($gateway);
	}

	public function extends($gateway)
	{
		$this->extended = true;
		$this->gateway = $gateway;
		return $this;
	}

	public function getGateway()
	{
		return $this->gateway;
	}

	public function build()
	{
		switch ($this->gateway) {
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
			case self::TEST:
				if(env('APP_DEBUG') == true) {
					$this->gateway = new TestGateway;
				} else {
					throw new OnlyWorkOnDebugModeException;
				}
				break;
			default:
				if($this->extended) {
					$this->gateway = new $this->gateway;
				} else {
					throw new GatewayNotFoundException;
				}
				break;
		}
		return $this;
	}
	
	/**
	 * Fetch all user transaction
	 *
	 * @param [integer] $userId
	 * @return IranPaymentTransaction object
	 */
	public static function userTransactions($userId)
	{
		return IranPaymentTransaction::where('user_id', $userId);
	}

	public function getSupportedGateways()
	{
		return [
			self::ZARINPAL,
			self::SAMAN,
			self::PAYIR,
			self::PAYDOTIR,
			self::TEST
		];
	}

}
