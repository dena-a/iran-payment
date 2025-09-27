<?php

namespace Dena\IranPayment\Gateways;

interface GatewayRefundableInterface
{
    public function refund(
        bool $limitRefund = false,
        int $limitRefundAmount = 0,
        array $refundItems = [],
        string $description = ''
    ): array;
}
