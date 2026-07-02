<?php

declare(strict_types=1);

namespace App\Payments\Contracts;

use App\Payments\GatewayResult;
use App\Support\Money\Money;

/**
 * The payment-gateway seam. Phase 1 ships ONLY a manual/offline implementation; real Stripe/
 * PayPal wiring is Phase 2 and slots in behind this interface without touching FolioService.
 *
 * Implementations receive only a gateway TOKEN (never raw card data).
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function charge(Money $amount, ?string $token, array $metadata = []): GatewayResult;

    public function refund(string $reference, Money $amount): GatewayResult;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function authorize(Money $amount, ?string $token, array $metadata = []): GatewayResult;

    public function capture(string $reference, Money $amount): GatewayResult;

    public function void(string $reference): GatewayResult;
}
