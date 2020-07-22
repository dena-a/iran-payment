<?php

namespace Dena\IranPayment\Gateways;

interface GatewayInterface
{
    public function gatewayName(): string;

	public function gatewayPayPrepare(): void;

	public function gatewayPay(): void;

	public function gatewayVerifyPrepare(): void;

	public function gatewayVerify(): void;

	public function gatewayPayView();

	public function gatewayPayRedirect();

	public function gatewayPayBack(): void;

	public function gatewayPayUri(): string;
}
