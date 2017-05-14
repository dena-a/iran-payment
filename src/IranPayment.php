<?php

namespace Dena\IranPayment;

use Dena\IranPayment\Exceptions\RetryException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\GatewayNotFoundException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;

use Dena\IranPayment\Providers\Saman\Saman;
use Dena\IranPayment\Providers\Zarinpal\Zarinpal;

use Dena\IranPayment\Models\IranPaymentTransaction;

use Vinkla\Hashids\Facades\Hashids;

class IranPayment
{
	const ZARINPAL	= 'zarinpal';
	const SAMAN		= 'saman';

	protected $gateway;

	public function __construct($gateway = null)
	{
		$this->setDefaults();
		if (!is_null($gateway)) {
			$this->setGateway($gateway);
		}
	}

	public function __call($name, $arguments)
	{
		if ($this->gateway) {
			return call_user_func_array([$this->gateway, $name], $arguments);
		}
		return false;
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
			default:
				throw new GatewayNotFoundException;
				break;
		}
		return $this;
	}

	public function verify()
	{
		if (!isset(request()->transaction)) {
			throw new InvalidRequestException;
		}
		$transaction_id	= request()->transaction;
		$transaction_id	= Hashids::connection('iranpayment')->decode($transaction_id);
		if (!isset($transaction_id[0])) {
			throw new InvalidRequestException;
		}
		$transaction_id	= $transaction_id[0];
		$transaction_id	= intval($transaction_id);
		$transaction	= IranPaymentTransaction::find($transaction_id);
		if (!$transaction) {
			throw new TransactionNotFoundException;
		}
		if ($transaction->status != IranPaymentTransaction::T_PENDING) {
			throw new RetryException;
		}

		$this->setGateway($transaction->gateway);
		$this->build();
		$this->gateway->setTransaction($transaction);
		return $this->gateway->verify();
	}

	public function getSupportedGateways()
	{
		return [
			self::ZARINPAL,
			self::SAMAN,
		];
	}

}