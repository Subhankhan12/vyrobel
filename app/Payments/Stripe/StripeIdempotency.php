<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

/**
 * Deterministic Stripe Idempotency-Key generator (Phase 8 · live Stripe · SG0) — the SENDING-side half of
 * the two-layer idempotency that eradicates double-charges STRUCTURALLY. Every Stripe WRITE (create a
 * PaymentIntent / Checkout Session / subscription / Connect transfer) carries a key derived from a STABLE
 * ref for the logical operation, so a retry (a double-click, a network retry, a job re-run) returns the
 * ORIGINAL object instead of creating a second charge/transfer.
 *
 * PURE + deterministic: same (operation, ref) → same key, no side effects — unit-tested without Stripe. The
 * caller supplies a ref that is STABLE for one logical operation and DISTINCT across separate ones (a
 * pre-created pending Payment/Payout id, a Checkout attempt id) — NOT something like folio+amount, which
 * would wrongly dedupe two legitimate payments. Stripe keys are ≤ 255 chars; ours are short + readable.
 */
final class StripeIdempotency
{
    /**
     * The idempotency key for a logical Stripe write. `$operation` names the write (e.g. 'folio_charge',
     * 'subscription_checkout', 'owner_payout'); `$ref` is the stable id for THIS operation.
     */
    public static function keyFor(string $operation, string $ref): string
    {
        return $operation.':'.$ref;
    }
}
