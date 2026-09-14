<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Http\Controllers\Operator\InventoryActionController;
use App\Jobs\SendBookingConfirmationEmail;
use App\Models\Organization;
use App\Models\Unit;
use App\Services\BillingService;
use App\Services\OwnerUnitRequestService;
use App\Support\Log\LogSafe;
use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * THE MISSING CALLER (F3, 2026-09-01) — re-quantify an org's live per-unit subscription after its billable
 * unit count changed. {@see BillingService::syncSubscriptionQuantity()} was complete and correct and had
 * NOBODY invoking it, so a per-unit subscription would have drifted from the org's real rack silently. This
 * job is the only caller, and {@see Unit::booted()} is the only dispatcher.
 *
 * ⚠️⚠️ A JOB, NOT AN INLINE CALL — FOUR SEPARATE REASONS, EVERY ONE OF THEM LOAD-BEARING:
 *
 *  1. ⚠️ THE ROLLBACK. Both unit-creation writers run inside `DB::transaction`
 *     ({@see InventoryActionController::storeUnit()} and
 *     {@see OwnerUnitRequestService::approve()}). An inline sync would tell Stripe about a
 *     unit whose transaction then rolled back — a real charge for inventory that never existed. `$afterCommit`
 *     defers the dispatch to a successful commit, so a rollback sends nothing at all. This is the reverse of
 *     the orphan-row shape the residual-access pass found, and here it costs money rather than tidiness.
 *
 *  2. ⚠️ STRIPE BEING DOWN MUST NOT STOP AN OPERATOR ADDING A UNIT. The unit change is committed before this
 *     job is even enqueued, so an unreachable Stripe cannot undo an operational action — it retries
 *     ($tries/$backoff) and the count catches up. A billing call that could fail a unit creation would be
 *     strictly worse than a count that lags by a minute.
 *
 *  3. ⚠️ THE WORKER HAS NO TENANT CONTEXT, AND THE COUNT DEPENDS ON ONE. `BillableUnits::count()` reads
 *     `Unit` under the `BelongsToOrganization` global scope, which THROWS without a context. So the payload
 *     carries a SCALAR org id and the body runs inside `TenantContext::forOrganization()` — the same shape
 *     {@see SendBookingConfirmationEmail} was rewritten into after every confirmation email died on
 *     exactly this. No tenant-scoped model crosses the queue boundary.
 *
 *  4. ⚠️ BULK. `ShouldBeUniqueUntilProcessing` keyed on the org collapses a burst (a seeder, or any future
 *     import) into ONE sync carrying the FINAL count, instead of one Stripe call per unit. **Until
 *     PROCESSING, deliberately**: plain `ShouldBeUnique` holds the lock through execution, so a unit added
 *     while the job ran would be DROPPED and the count would drift silently — the precise failure this whole
 *     pass exists to prevent. ⚠️ Honest limit: the collapse is a property of a real queue. On the `sync`
 *     driver (the test suite) the lock is released before the next dispatch, so N creates run N syncs; they
 *     are idempotent and the last carries the truth, but no collapse is claimed there.
 *
 * ⚠️ NO SHAPE DECISION LIVES HERE. Whether a sync is owed at all — flat plan, or no live Stripe subscription
 * — is decided by the ONE existing set of guards inside `syncSubscriptionQuantity()` (§N.8f). Re-testing the
 * plan shape at dispatch time would be a second definition of "is this org billed per unit", and two
 * definitions of one concept is how surfaces drift. This job always runs; the sync decides and no-ops.
 */
class SyncSubscriptionQuantityJob implements ShouldBeUniqueUntilProcessing, ShouldQueueAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ⚠️ THE ROLLBACK GUARD (reason 1) is the `ShouldQueueAfterCommit` interface above — never dispatch from
    // inside a transaction that may still rewind. ⚠️ IT IS AN INTERFACE AND NOT A `public bool $afterCommit`
    // PROPERTY FOR A HARD REASON: `Illuminate\Bus\Queueable` already declares `$afterCommit` UNTYPED, so a
    // typed redeclaration is a PHP composition fatal — which kills the whole test process before this repo's
    // JSON reporter can print, giving a zero-byte, zero-diagnostic run that looks exactly like a segfault.

    /** A transient Stripe/network failure deserves a retry; the unit change already stands regardless. */
    public int $tries = 3;

    /** @var list<int> seconds between attempts */
    public array $backoff = [30, 120];

    /** Short: a stuck lock must never outlive a legitimate later change by long. */
    public int $uniqueFor = 60;

    public function __construct(public readonly string $organizationId) {}

    public function uniqueId(): string
    {
        return $this->organizationId;
    }

    public function handle(TenantContext $tenant, BillingService $billing): void
    {
        $organization = Organization::query()->find($this->organizationId);

        if (! $organization instanceof Organization) {
            return;   // the org was removed between the unit change and the sync — nothing to bill.
        }

        // ⚠️ The count inside the context, never outside it (reason 3).
        $tenant->forOrganization($this->organizationId, function () use ($billing, $organization): void {
            $billing->syncSubscriptionQuantity($organization);
        });
    }

    /**
     * ⚠️ REPORTED, NOT SOLVED. A sync that exhausts its retries leaves the subscription quantity behind the
     * real rack, and this log line is the ONLY thing that says so — nothing alerts on it. That is a known
     * gap, recorded here rather than papered over; building monitoring is out of scope for this pass.
     */
    public function failed(Throwable $e): void
    {
        Log::warning('Subscription quantity sync failed after retries — the billed quantity may lag the unit count', [
            'organization_id' => $this->organizationId,
            'error' => LogSafe::message($e),
        ]);
    }
}
