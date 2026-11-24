<?php

declare(strict_types=1);

namespace App\Support\Storefront;

use App\Http\Middleware\ResolvePublicOrganization;
use App\Http\Middleware\ResolvePublicProperty;
use App\Models\Organization;
use App\Support\Billing\SubscriptionStatus;

/**
 * ⚠️⚠️ VY-35 (audit of 2026-11-24, read in code) — THE ONE ANSWER TO "DOES THIS ORGANISATION'S STOREFRONT TAKE BOOKINGS?".
 *
 * A lapsed workspace (an expired trial, or a cancelled or unpaid subscription) has its back office closed by the paywall — and its
 * storefront stayed open: the public pages closed only for suspended and cancelled organisations, so bookings could arrive that nobody
 * at the hotel could see. A storefront is now closed when the organisation is gone, archived, suspended or cancelled (R-SUSPEND, F-51)
 * OR its workspace has lapsed ({@see SubscriptionStatus::isLapsed()} — the paywall's own rule, read here and nowhere
 * else, so the two can never disagree: in a trial or paid → open; lapsed → closed; no billing record at all → open, exactly as the
 * paywall fails open).
 *
 * ⚠️ READ BY ALL THREE PUBLIC SURFACES: the property storefront ({@see ResolvePublicProperty}), the organisation landing page
 * ({@see ResolvePublicOrganization}) and the storefront sitemap — a sitemap that listed a closed shop would send a
 * crawler to the closed sign.
 *
 * ⚠️ It closes NEW bookings only. A guest who already booked manages the stay from the guest portal, which this does not touch.
 * Call it with no tenant context needed: the subscription read is unscoped by its own definition (`Organization::currentSubscription()`).
 */
final class StorefrontAvailability
{
    public static function closed(?Organization $organization): bool
    {
        if ($organization === null || $organization->trashed() || in_array($organization->status, ResolvePublicProperty::CLOSED_FOR_BOOKING, true)) {
            return true;
        }

        return $organization->billingStatus()->isLapsed();
    }
}
