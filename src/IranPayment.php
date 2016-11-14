<?php

namespace Dena\IranPayment;

use Dena\IranPayment\Exceptions\RetryException;
use Dena\IranPayment\Exceptions\InvalidRequestException;
use Dena\IranPayment\Exceptions\GatewayNotFoundException;
use Dena\IranPayment\Exceptions\TransactionNotFoundException;

use Dena\IranPayment\Providers\Zarinpal\Zarinpal;

use Dena\IranPayment\GatewayAbstract;
use Dena\IranPayment\Models\IranPaymentTransaction;

use Config;

class IranPayment
{
	const PG_ZARINPAL	= 1;
	const PGN_ZARINPAL	= 'zarinpal';

	protected $user;
	protected $gateway;
	protected $payment_gateway;

	public function __construct($gateway = null)
	{
		if (is_null($gateway)) {
			$this->gateway	= Config::get('iranpayment.default');
		} else {
			$this->gateway	= $gateway;
		}
	}

	public function __call($name, $arguments)
	{
		return call_user_func_array([$this->payment_gateway, $name], $arguments);
	}

	public function build()
	{
		switch ($this->gateway) {
			case self::PG_ZARINPAL:
			case self::PGN_ZARINPAL:
				$this->payment_gateway = new Zarinpal(self::PG_ZARINPAL);
				break;
			default:
				throw new GatewayNotFoundException;
				break;
		}
		return $this;
	}

	public function verify()
	{
		if (!isset(request()->transaction_id)) {
			throw new InvalidRequestException;
		}

		$transaction_id	= intval(request()->transaction_id);
		$transaction	= IranPaymentTransaction::find($transaction_id);
		if (!$transaction) {
			throw new TransactionNotFoundException;
		}

		if ($transaction->status == GatewayAbstract::T_SUCCEED || $transaction->status == GatewayAbstract::T_FAILED) {
			throw new RetryException;
		}

		$this->build($transaction->gateway);

		return $this->payment_gateway->verify($transaction);
	}

	public function getSupportedGateways()
	{
		return [
			self::PGN_ZARINPAL,
		];
	}

}