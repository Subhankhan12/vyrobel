<?php

declare(strict_types=1);

namespace App\Support\Booking;

use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Services\OperatorConnectService;
use Throwable;

/**
 * ⚠️⚠️ VY-23 (audit of 2026-11-24, read in code) — WHAT THE STOREFRONT ASKS A GUEST TO PAY WHEN THEY BOOK. ONE place.
 *
 * A property could tick "Deposit at booking" in Settings → Policies and its booking terms then told every guest "A deposit is required
 * when you book" — while the storefront took the booking with nothing paid. The setting is now REAL:
 *
 *   · {@see depositPercent()} — the share of the stay a guest pays by card to book online (null when the property asks for none). A
 *     property that ticked the box before the share existed reads {@see DEFAULT_PERCENT} (shown to the operator, who can change it).
 *   · {@see depositMinor()} — that share of an amount, in whole minor units, rounded UP (a deposit never falls a cent short) and never
 *     more than the amount.
 *   · {@see canTakeCards()} — can THIS organisation's storefront take a card right now? The Stripe gateway, AND its own connected account
 *     ready for charges (direct charges only — read from Stripe, never a stored flag). When a property asks for a deposit and the answer is
 *     no, the storefront TAKES NO BOOKING and says so — the terms promise a deposit, so a booking without one would break the promise
 *     and leave the room held by nobody's money.
 */
final class StorefrontPayment
{
    /** The share a property that ticked "Deposit at booking" before the percentage existed asks for. */
    public const DEFAULT_PERCENT = 30;

    public static function depositPercent(?Property $property): ?int
    {
        /** @var PropertyPolicy|null $policy */
        $policy = $property?->policies()->first();
        $deposit = is_array($policy?->deposit) ? $policy->deposit : [];

        if (! (bool) ($deposit['required'] ?? false)) {
            return null;
        }

        $percent = $deposit['percent'] ?? null;

        return is_numeric($percent) ? max(1, min(100, (int) $percent)) : self::DEFAULT_PERCENT;
    }

    public static function depositMinor(int $amountMinor, int $percent): int
    {
        if ($amountMinor <= 0) {
            return 0;
        }

        return min($amountMinor, intdiv($amountMinor * max(1, min(100, $percent)) + 99, 100));
    }

    public static function canTakeCards(?Organization $organization): bool
    {
        if ($organization === null || (string) config('services.payments.gateway') !== 'stripe') {
            return false;
        }

        try {
            return app(OperatorConnectService::class)->readiness($organization)['ready'];
        } catch (Throwable) {
            return false;   // Stripe could not be asked: no card is taken on a guess
        }
    }
}
