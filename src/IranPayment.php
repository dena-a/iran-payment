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

use Config;

class IranPayment
{
	const PG_ZARINPAL	= 1;
	const PG_SAMAN		= 2;

	const PGN_ZARINPAL	= 'zarinpal';
	const PGN_SAMAN		= 'saman';

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
			case self::PG_ZARINPAL:
			case self::PGN_ZARINPAL:
				$this->gateway = new Zarinpal;
				break;
			case self::PG_SAMAN:
			case self::PGN_SAMAN:
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

		$transaction	= request()->transaction;
		$transaction	= Hashids::connection('iranpayment')->decode($transaction);
		if (!isset($transaction[0])) {
			throw new InvalidRequestException;
		}
		$transaction_id	= $transaction[0];
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

		return $this->gateway->verify($transaction);
	}

	public function getSupportedGateways()
	{
		return [
			self::PGN_ZARINPAL,
			self::PGN_SAMAN,
		];
	}

	public static function gatewayCodeFromName($gateway_name)
	{
		switch ($gateway_name) {
			case self::PGN_SAMAN:
				return self::PG_SAMAN;
			case self::PGN_ZARINPAL:
				return self::PG_ZARINPAL;
		}
	}

}