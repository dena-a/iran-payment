<?php

namespace Dena\IranPayment\Gateways;

interface GatewayInterface
{
    public function getName(): string;

    public function initialize(array $parameters = []): self;

	public function purchase(): void;

	public function verify(): void;

	public function gatewayPayView();

	public function gatewayPayRedirect();

	public function gatewayPayUri(): string;
}
