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
	const PAYIR		= 'pay.ir';
	const TEST		= 'test';

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
		$this->setHashidsConfig();
	}

	private function setHashidsConfig()
	{
		if (!config('hashids.connections.iranpayment', false)) {
			config(['hashids.connections.iranpayment' => [
				'salt'		=> config('iranpayment.hashids.salt' ,'your-salt-string'),
				'length'	=> config('iranpayment.hashids.length' ,16),
				'alphabet'	=> config('iranpayment.hashids.alphabet' ,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
			]]);
		}
	}

	public function setGateway($gateway)
	{
		$this->gateway = $gateway;
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

	// public function verify()
	// {
	// 	$request = app('request');
	// 	if (!isset($request->transaction)) {
	// 		throw new InvalidRequestException;
	// 	}
	// 	$transaction_id	= $request->transaction;
	// 	$transaction_id	= app('hashids')->connection('iranpayment')->decode($transaction_id);
	// 	if (!isset($transaction_id[0])) {
	// 		throw new InvalidRequestException;
	// 	}
	// 	$transaction_id	= $transaction_id[0];
	// 	$transaction_id	= intval($transaction_id);
	// 	$transaction	= IranPaymentTransaction::find($transaction_id);
	// 	if (!$transaction) {
	// 		throw new TransactionNotFoundException;
	// 	}

	// 	$this->setGateway($transaction->gateway);
	// 	$this->build();
	// 	$this->gateway->setTransaction($transaction);

	// 	return $this->gateway->verify();
	// }

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
			self::TEST
		];
	}

}