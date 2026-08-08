<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\GatewayResult;
use App\Support\Money\Money;
use Illuminate\Support\Str;

/**
 * Offline/manual gateway — the ONLY implementation in Phase 1. It performs NO external calls:
 * it just records the intent and returns a synthetic reference, modelling cash / bank-transfer /
 * "mark as paid at the desk" and the authorize→capture lifecycle for deposits. Real gateways
 * (Stripe, …) arrive in Phase 2 behind {@see PaymentGateway}.
 */
class ManualPaymentGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function charge(Money $amount, ?string $token, array $metadata = []): GatewayResult
    {
        return new GatewayResult(true, $this->reference('pay'), 'succeeded');
    }

    public function refund(string $reference, Money $amount, ?string $idempotencyKey = null): GatewayResult
    {
        // Manual/offline settlement moves no money over a network, so there is nothing to de-duplicate
        // at this seam; the key is accepted to satisfy the contract. FolioService's row lock + the
        // cumulative bound are what actually prevent a double refund here.
        return new GatewayResult(true, $this->reference('ref'), 'succeeded');
    }

    public function authorize(Money $amount, ?string $token, array $metadata = []): GatewayResult
    {
        return new GatewayResult(true, $this->reference('auth'), 'authorized');
    }

    public function capture(string $reference, Money $amount): GatewayResult
    {
        return new GatewayResult(true, $this->reference('cap'), 'captured');
    }

    public function void(string $reference): GatewayResult
    {
        return new GatewayResult(true, $this->reference('void'), 'voided');
    }

    private function reference(string $prefix): string
    {
        return "manual_{$prefix}_".Str::lower(Str::ulid()->toBase32());
    }
}
