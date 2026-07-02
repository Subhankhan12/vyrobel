<?php

declare(strict_types=1);

namespace App\Payments;

/**
 * Immutable result of a payment-gateway operation.
 */
final class GatewayResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $reference,
        public readonly string $status,
        public readonly ?string $message = null,
    ) {}
}
