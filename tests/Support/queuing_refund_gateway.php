<?php

declare(strict_types=1);

use App\Payments\Contracts\PaymentGateway;
use App\Payments\GatewayResult;
use App\Support\Money\Money;

// ⚠️ ONE definition, shared by the pending-refund suite and the money-switch suite — a second copy of a
//    test double that models Stripe's queuing behaviour would drift exactly like any other duplicate.
/** A gateway whose refunds queue, exactly as Stripe does on an insufficient balance. */
final class QueuingRefundGateway implements PaymentGateway
{
    public int $refundCalls = 0;

    /** @var list<string> every idempotency key the gateway was handed */
    public array $keys = [];

    public function name(): string
    {
        return 'stripe';
    }

    public function charge(Money $amount, ?string $token, array $metadata = []): GatewayResult
    {
        return new GatewayResult(true, 'pi_'.bin2hex(random_bytes(6)), 'succeeded');
    }

    /** @var list<?string> the connected account each refund named — proves it went to the RIGHT one. */
    public array $accounts = [];

    public function refund(string $reference, Money $amount, ?string $idempotencyKey = null, ?string $connectedAccountId = null): GatewayResult
    {
        $this->refundCalls++;
        $this->accounts[] = $connectedAccountId;
        $this->keys[] = (string) $idempotencyKey;

        // ⚠️ Stripe: "Stripe holds the refund as pending for card transactions … until your Stripe balance
        //    becomes sufficient." Accepted, not failed.
        return new GatewayResult(false, 're_'.bin2hex(random_bytes(6)), 'pending');
    }

    public function authorize(Money $amount, ?string $token, array $metadata = []): GatewayResult
    {
        return new GatewayResult(true, 'auth_x', 'authorized');
    }

    public function capture(string $reference, Money $amount): GatewayResult
    {
        return new GatewayResult(true, $reference, 'captured');
    }

    public function void(string $reference): GatewayResult
    {
        return new GatewayResult(true, $reference, 'voided');
    }
}
