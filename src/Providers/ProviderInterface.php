<?php

namespace Dena\IranPayment\Providers;

interface ProviderInterface {
    function getName();

	function payRequest();

	function verifyRequest();

	function redirectView();

	function payBack();
}
