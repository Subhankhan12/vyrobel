<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

/**
 * A verified inbound Stripe webhook event (signature already validated by the client). `payload`
 * is the decoded event body; helpers pull the fields the webhook handler needs.
 */
final class StripeEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly array $payload,
    ) {}

    /**
     * The PaymentIntent id this event concerns (events we handle are payment_intent.* whose
     * data.object is the intent), or null if absent.
     */
    public function paymentIntentId(): ?string
    {
        $object = $this->payload['data']['object'] ?? [];

        if (! is_array($object)) {
            return null;
        }

        // payment_intent.* → object IS the intent; charge.* → object.payment_intent holds the id.
        $id = $object['id'] ?? null;
        if (isset($object['object']) && $object['object'] === 'payment_intent' && is_string($id)) {
            return $id;
        }

        $pi = $object['payment_intent'] ?? null;

        return is_string($pi) ? $pi : (is_string($id) ? $id : null);
    }
}
