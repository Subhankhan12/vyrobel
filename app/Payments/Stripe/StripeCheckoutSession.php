<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

/**
 * A created Stripe Checkout Session (Phase 8 · SG1). The card is entered on Stripe's hosted page — it never
 * touches our server (PCI SAQ A). We keep only the id + the redirect `url`; fulfilment happens on the
 * WEBHOOK (checkout.session.completed), never on the redirect back.
 */
final class StripeCheckoutSession
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
    ) {}
}
