<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Models\Organization;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Builder;

/**
 * ⚠️ ADMIN 01 · PHASE 4 — THE SIGNUP FUNNEL, BY SOURCE, FROM WHAT THE DATABASE ALREADY RECORDS. No new tracking.
 *
 * Measured stages (each an organisation-level fact, customers only unless asked):
 *   1. Signed up          — the organisation exists (`organizations`).
 *   2. Verified an email  — an active member has `email_verified_at`: the gate every /app page sits behind.
 *   3. First reservation  — the organisation has any reservation.
 *   4. First check-in     — a reservation reached `checked_in` (or `checked_out`, which only follows it).
 *   5. Paid               — Stripe charged it: a processed `invoice.paid` event (see PlatformRevenue).
 *
 * ⚠️ UNMEASURED, and named on screen rather than dropped: visiting the site (no analytics are recorded) · starting
 * the sign-up form (nothing is written until it is submitted) · reaching /app itself (no page view is recorded;
 * stage 2 is the nearest recorded step). ⚠️ "Added a property" is NOT a stage: sign-up creates the first property
 * in the same transaction — {@see self::orgsWithoutProperty()} measures that rather than asserting it.
 *
 * ⚠️ By source: email · Google · X · Unknown (organisations created before the source was recorded — never guessed).
 */
final class PlatformFunnel
{
    public const STAGES = [
        'signed_up' => 'Signed up',
        'verified' => 'Verified an email (the gate to /app)',
        'first_reservation' => 'First reservation',
        'first_check_in' => 'First check-in',
        'paid' => 'Paid (charged by Stripe)',
    ];

    public const UNMEASURED = [
        'Visited the site' => 'No analytics or visit log is recorded, by design.',
        'Started the sign-up form' => 'Nothing is written until the form is submitted.',
        'Reached /app' => 'No page view is recorded. “Verified an email” is the nearest recorded step — every /app page requires it.',
    ];

    /**
     * @return array{sources: array<string, array<string, int>>, total: array<string, int>, without_property: int, include_internal: bool}
     */
    public function compute(bool $includeInternal = false): array
    {
        $sources = [];
        foreach ([...array_keys(TenantLifecycle::SIGNUP_SOURCES), 'unknown'] as $source) {
            $sources[$source] = $this->stagesFor(fn (Builder $q) => $source === 'unknown' ? $q->whereNull('signup_source') : $q->where('signup_source', $source), $includeInternal);
        }

        return [
            'sources' => $sources,
            'total' => $this->stagesFor(fn (Builder $q) => $q, $includeInternal),
            'without_property' => $this->orgsWithoutProperty($includeInternal),
            'include_internal' => $includeInternal,
        ];
    }

    /** @return array<string, int> */
    private function stagesFor(callable $source, bool $includeInternal): array
    {
        $base = function () use ($source, $includeInternal): Builder {
            $q = Organization::query();
            if (! $includeInternal) {
                $q->customers();
            }

            return $source($q);
        };

        return [
            'signed_up' => $base()->count(),
            'verified' => $base()->whereExists(fn ($q) => $q->selectRaw('1')->from('organization_user')
                ->join('users', 'users.id', '=', 'organization_user.user_id')
                ->whereColumn('organization_user.organization_id', 'organizations.id')
                ->where('organization_user.status', 'active')->whereNotNull('users.email_verified_at'))->count(),
            'first_reservation' => $base()->whereExists(fn ($q) => $q->selectRaw('1')->from('reservations')
                ->whereColumn('reservations.organization_id', 'organizations.id'))->count(),
            'first_check_in' => $base()->whereExists(fn ($q) => $q->selectRaw('1')->from('reservations')
                ->whereColumn('reservations.organization_id', 'organizations.id')->whereIn('reservations.status', ['checked_in', 'checked_out']))->count(),
            'paid' => $base()->whereExists(fn ($q) => $q->selectRaw('1')->from('webhook_events')
                ->whereColumn('webhook_events.organization_id', 'organizations.id')->where('webhook_events.source', 'stripe')
                ->where('webhook_events.event_type', 'invoice.paid')->where('webhook_events.status', WebhookEvent::STATUS_PROCESSED))->count(),
        ];
    }

    /** Organisations with no property at all — expected to be zero, because sign-up creates one. Measured, not assumed. */
    private function orgsWithoutProperty(bool $includeInternal): int
    {
        $q = Organization::query();
        if (! $includeInternal) {
            $q->customers();
        }

        return $q->whereNotExists(fn ($s) => $s->selectRaw('1')->from('properties')->whereColumn('properties.organization_id', 'organizations.id'))->count();
    }
}
