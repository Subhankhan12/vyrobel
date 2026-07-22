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

    /**
     * The event's `data.object` (a checkout session / subscription / invoice for billing events).
     *
     * @return array<string, mixed>
     */
    public function object(): array
    {
        $object = $this->payload['data']['object'] ?? [];

        return is_array($object) ? $object : [];
    }

    /** A string field of `data.object` (e.g. 'customer', 'subscription', 'status'), or null. */
    public function objectField(string $key): ?string
    {
        $value = $this->object()[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /** An int field of `data.object` (e.g. 'current_period_end', a unix timestamp), or null. */
    public function objectInt(string $key): ?int
    {
        $value = $this->object()[$key] ?? null;

        return is_int($value) ? $value : null;
    }

    /** A bool field of `data.object` (e.g. a Connect account's 'payouts_enabled'). */
    public function objectBool(string $key): bool
    {
        return (bool) ($this->object()[$key] ?? false);
    }

    /** The `organization_id` stamped into `data.object.metadata` at Checkout create — resolves the tenant. */
    public function metadataOrganizationId(): ?string
    {
        $metadata = $this->object()['metadata'] ?? [];
        $id = is_array($metadata) ? ($metadata['organization_id'] ?? null) : null;

        return is_string($id) ? $id : null;
    }
}
