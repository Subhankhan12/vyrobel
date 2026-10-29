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
 * READ-ONLY in either of TWO real cases (both written by the Billing pause/cancel state machine, #85):
 *   • PAUSE — `organizations.paused_until` is a FUTURE timestamp: read-only NOW, resuming on that date.
 *   • CANCEL — `organizations.readonly_from` is a PAST-or-now timestamp: the subscription was canceled and
 *     the paid-through date has passed, so the workspace is read-only from then on (a future `readonly_from`
 *     is a SCHEDULED cancellation — still full access until it arrives).
 * The operator can view everything and check out in-house guests, but new bookings + edits are off. Pure +
 * read-only; it never sets the state. Orthogonal to the subscription paywall — a paused/canceled-but-in-app
 * org stays IN `/app` (read-only) rather than being redirected.
 *
 * Fail-safe: no tenant context, no org, or neither trigger active → NOT read-only (never a spurious lock).
 */
final class OperatorWorkspace
{
    public function __construct(private readonly TenantContext $tenant) {}

    /** The org, or null (no tenant / not found). Read explicitly by id — safe in any context. */
    private function organization(): ?Organization
    {
        $id = $this->tenant->id();

        return $id === null ? null : Organization::query()->find($id);
    }

    /** The active PAUSE window's resume date (future), or null. */
    public function pausedUntil(): ?Carbon
    {
        $until = $this->organization()?->paused_until;

        return ($until instanceof Carbon && $until->isFuture()) ? $until : null;
    }

    /** True when the workspace is genuinely read-only — a live pause, OR a cancellation whose date has passed. */
    public function isReadOnly(): bool
    {
        if ($this->pausedUntil() !== null) {
            return true;
        }

        $from = $this->organization()?->readonly_from;

        return $from instanceof Carbon && ! $from->isFuture();
    }

    /** "5 Oct" — the pause's resume date for the banner, or null when not paused (a cancel has no resume). */
    public function resumeLabel(): ?string
    {
        return $this->pausedUntil()?->translatedFormat('j M');
    }
}
