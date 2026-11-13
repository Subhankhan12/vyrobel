<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Subscription;
use App\Support\I18n\LocalDate;
use Illuminate\Support\Carbon;

/**
 * THE ONE derivation of an org's billing/access state from its Subscription (Phase 7b). Pure + read-only —
 * every value is DERIVED from the real row (`status` + `trial_ends_at`), never a stored countdown or a faked
 * "active". The trial's remaining days come from the 7a provisioning timestamp (`trial_ends_at = created +
 * trial_days`), so a just-provisioned org is genuinely in-trial and an org past `trial_ends_at` is genuinely
 * expired.
 *
 * ACCESS (used by the SG3 gate): {@see hasFullAccess()} is true while trialing-not-expired OR active — the
 * gate bites ONLY on a lapsed org (an expired trial, or a canceled/past-due subscription), NEVER mid-trial.
 * An org with NO subscription row (legacy/pre-7b, or a data gap) FAILS OPEN to full access — a missing
 * billing record must never paywall an org; the gate is driven by a real lapsed state, not by absence.
 *
 * NOTE — no charge is ever computed here. `status === 'active'` is only ever reached through a REAL Stripe
 * charge (account-gated, deferred); nothing in the app flips an org to active off a payment that didn't
 * happen.
 */
final class SubscriptionStatus
{
    public function __construct(private ?Subscription $subscription) {}

    public function onTrial(): bool
    {
        return $this->subscription?->status === 'trialing' && ! $this->trialLapsed();
    }

    public function trialExpired(): bool
    {
        return $this->subscription?->status === 'trialing' && $this->trialLapsed();
    }

    /**
     * DEC-7 — a COMPLIMENTARY plan: a free workspace with an END DATE, granted from the console. It is a `trialing`
     * subscription whose `trial_ends_at` is that date (so every access gate already works and it lapses on the day),
     * marked by `complimentary_until` so the screens, the trial count and the revenue figures can tell it apart from a
     * trial waiting to convert. Still true on the day it has ended — see {@see complimentaryEnded()}.
     */
    public function isComplimentary(): bool
    {
        return $this->subscription?->status === 'trialing' && $this->subscription->complimentary_until !== null;
    }

    public function complimentaryEnded(): bool
    {
        return $this->isComplimentary() && $this->trialLapsed();
    }

    public function complimentaryUntil(): ?Carbon
    {
        return $this->isComplimentary() ? $this->subscription?->complimentary_until : null;
    }

    public function active(): bool
    {
        return $this->subscription?->status === 'active';
    }

    public function pastDue(): bool
    {
        return $this->subscription?->status === 'past_due';
    }

    public function canceled(): bool
    {
        return $this->subscription?->status === 'canceled';
    }

    /** Full app access: in an unexpired trial, or actively subscribed. Absence of a subscription fails OPEN. */
    public function hasFullAccess(): bool
    {
        if ($this->subscription === null) {
            return true;   // no billing record → never paywall (legacy/data-gap safety)
        }

        return $this->onTrial() || $this->active();
    }

    /** Lapsed = has a subscription but no full access (expired trial / canceled / past-due) → the paywall. */
    public function isLapsed(): bool
    {
        return $this->subscription !== null && ! $this->hasFullAccess();
    }

    /** Whole days left in the trial (ceiling), from the real timestamp — 0 once expired or not trialing. */
    public function trialDaysRemaining(): int
    {
        if (! $this->onTrial() || $this->subscription?->trial_ends_at === null) {
            return 0;
        }

        // Carbon 3 diffInDays returns a float; the trial end is guaranteed future here (onTrial()), so ceil
        // the absolute day-fraction — "14 days left" the moment the trial starts, counting down by real time.
        return max(0, (int) ceil(abs(Carbon::now()->diffInDays($this->subscription->trial_ends_at))));
    }

    /** A short human label for the billing state. */
    public function label(): string
    {
        return match (true) {
            $this->isComplimentary() && $this->onTrial() => __('php/support/billing/subscription_status.complimentary_until', ['date' => $this->subscription?->complimentary_until?->translatedFormat(LocalDate::pattern('j M Y'))]),
            $this->complimentaryEnded() => __('php/support/billing/subscription_status.complimentary_ended'),
            $this->onTrial() => trans_choice('php/support/billing/subscription_status.trial_days_left', $this->trialDaysRemaining(), ['count' => $this->trialDaysRemaining()]),
            $this->active() => __('php/support/billing/subscription_status.active'),
            $this->trialExpired() => __('php/support/billing/subscription_status.trial_ended'),
            $this->pastDue() => __('php/support/billing/subscription_status.past_due'),
            $this->canceled() => __('php/support/billing/subscription_status.canceled'),
            default => __('php/support/billing/subscription_status.none'),
        };
    }

    private function trialLapsed(): bool
    {
        $endsAt = $this->subscription?->trial_ends_at;

        return $endsAt !== null && $endsAt->isPast();
    }
}
