<?php

namespace Dena\IranPayment\Providers;

interface GatewayInterface
{
    public function gatewayName();

	public function gatewayPayPrepare();

	public function gatewayPay();

	public function gatewayVerifyPrepare();

	public function gatewayVerify();

	public function gatewayPayView();

	public function gatewayPayRedirect();

	public function gatewayPayBack();

	public function gatewayPayUri();
}
