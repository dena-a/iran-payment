<?php

namespace Dena\IranPayment;

interface IranPaymentInterface
{
	public function __construct($gateway);

	public function setAmount($amount);

	public function ready();

	public function referenceId();

	public function trackingCode();

	public function gateway();

	public function transactionId();

	public function cardNumber();

	public function redirect();

	public function verify($transaction);
}