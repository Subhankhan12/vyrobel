<?php

declare(strict_types=1);

namespace App\Support\Operator;

use App\Models\Organization;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;

/**
 * The ONE read of the operator workspace's READ-ONLY state (Operator Dashboard/Calendar exact-match) — the
 * wireframe's "Workspace paused — read-only" gate, shared by the shell banner AND every operator write.
 *
 * READ-ONLY when the tenant org's `organizations.paused_until` is a future timestamp: the operator can view
 * everything and check out in-house guests, but new bookings + edits are off. Pure + read-only; it never
 * sets the state (the Billing subsystem / a platform admin does). Orthogonal to the subscription paywall —
 * a paused org is still trialing/active, so it stays IN `/app` (read-only) rather than being redirected.
 *
 * Fail-safe: no tenant context, no org, or an unset/past `paused_until` → NOT read-only (never a spurious
 * lock). Nothing is fabricated — the banner shows only a real future pause window.
 */
final class OperatorWorkspace
{
    public function __construct(private readonly TenantContext $tenant) {}

    /** The pausing org, or null (no tenant / not found). Read explicitly by id — safe in any context. */
    private function organization(): ?Organization
    {
        $id = $this->tenant->id();

        return $id === null ? null : Organization::query()->find($id);
    }

    public function pausedUntil(): ?Carbon
    {
        $until = $this->organization()?->paused_until;

        return ($until instanceof Carbon && $until->isFuture()) ? $until : null;
    }

    /** True when the workspace is genuinely paused (a real future window) — writes must be refused. */
    public function isReadOnly(): bool
    {
        return $this->pausedUntil() !== null;
    }

    /** "5 Oct" — the real resume date for the banner, or null when not paused. */
    public function resumeLabel(): ?string
    {
        return $this->pausedUntil()?->format('j M');
    }
}
