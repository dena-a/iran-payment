<?php

namespace Dena\IranPayment\Providers;

interface GatewayInterface
{
    function gatewayName();

	function gatewayPayPrepare();

	function gatewayPay();

	function gatewayVerifyPrepare();

	function gatewayVerify();

	function gatewayPayView();

	function gatewayPayRedirect();

	function gatewayPayBack();
}
