<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Support\Events\AuditEventRecorder;
use App\Support\Operator\OperatorWorkspace;
use Illuminate\Support\Carbon;

/**
 * The operator subscription PAUSE / CANCEL state machine (Operator Billing exact-match #85) — the REAL
 * backend of the wireframe's retention flow, and the writer of the cross-cutting read-only mode that the
 * Dashboard/Calendar/certified-write already consume via {@see OperatorWorkspace}.
 *
 * ⚠️ It writes ONLY the org's read-only window (`paused_until` / `readonly_from`), NOT the subscription
 * `status` — so it is orthogonal to the paywall (`EnsureSubscriptionActive`): a paused/canceling org stays
 * IN `/app` read-only rather than being redirected, and no BillingAccessGate behaviour changes. No real
 * money moves (the subscription charge stays account-gated). Every transition is audited (#20).
 *
 *   • PAUSE (up to 3 months) → `paused_until = now + N months` → read-only NOW, auto-resumes on that date.
 *   • CANCEL → `readonly_from = the paid-through date (current_period_end)` → full access until then, then
 *     read-only indefinitely. (Undo = resume.)
 *   • RESUME → clears both → the workspace is live again.
 */
class SubscriptionLifecycleService
{
    public function __construct(private readonly AuditEventRecorder $audit) {}

    /** Pause the workspace for 1–3 months: read-only now, resuming on the returned date. */
    public function pause(Organization $organization, int $months): Carbon
    {
        $months = max(1, min(3, $months));
        $until = Carbon::now()->addMonthsNoOverflow($months);

        $organization->update(['paused_until' => $until, 'readonly_from' => null]);
        $this->audit->record('billing.workspace_paused', $organization, null, ['months' => $months, 'until' => $until->toDateString()]);

        return $until;
    }

    /** Cancel the subscription: full access until the paid-through date, then read-only. Returns that date. */
    public function cancel(Organization $organization): Carbon
    {
        $paidThrough = $organization->currentSubscription()?->current_period_end;
        $from = $paidThrough instanceof Carbon ? $paidThrough : Carbon::now();

        $organization->update(['readonly_from' => $from, 'paused_until' => null]);
        $this->audit->record('billing.subscription_canceled', $organization, null, ['read_only_from' => $from->toDateString()]);

        return $from;
    }

    /** Resume: clear any pause / scheduled cancellation → the workspace is live again. */
    public function resume(Organization $organization): void
    {
        $organization->update(['paused_until' => null, 'readonly_from' => null]);
        $this->audit->record('billing.workspace_resumed', $organization, null, null);
    }
}
