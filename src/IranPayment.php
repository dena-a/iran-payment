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

	const PC_RIAL		= 1;
	const PC_TOMAN		= 2;

	protected $user;
	protected $gateway;
	protected $currency;
	protected $payment_gateway;

	public function __construct($gateway = null)
	{
		$this->currency		= self::PC_RIAL;
		if (is_null($gateway)) {
			$this->gateway	= Config::get('iranpayment.default');
		} else {
			$this->gateway	= $gateway;
		}

		if (!Config::has('hashids.connections.iranpayment')) {
			Config::set('hashids.connections.iranpayment', [
				'salt'		=> Config::get('iranpayment.hashids.salt' ,'your-salt-string'),
				'length'	=> Config::get('iranpayment.hashids.length' ,16),
				'alphabet'	=> Config::get('iranpayment.hashids.alphabet' ,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
			]);
		}
	}

	public function __call($name, $arguments)
	{
		if ($this->payment_gateway) {
			return call_user_func_array([$this->payment_gateway, $name], $arguments);
		}
		return false;
	}

	public function build()
	{
		switch ($this->gateway) {
			case self::PG_ZARINPAL:
			case self::PGN_ZARINPAL:
				$this->payment_gateway = new Zarinpal(self::PG_ZARINPAL);
				break;
			case self::PG_SAMAN:
			case self::PGN_SAMAN:
				$this->payment_gateway = new Saman(self::PG_SAMAN);
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

		$this->gateway = $transaction->gateway;
		$this->build();

		return $this->payment_gateway->verify($transaction);
	}

	public function getSupportedGateways()
	{
		return [
			self::PGN_ZARINPAL,
			self::PGN_SAMAN,
		];
	}

}