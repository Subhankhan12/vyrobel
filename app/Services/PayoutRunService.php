<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Owner\PayoutHeldException;
use App\Models\Owner;
use App\Models\OwnerPayoutPreference;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\PayoutRun;
use App\Payments\Stripe\StripeSafetyGuard;
use App\Payouts\Contracts\PayoutRail;
use App\Payouts\PayoutEngineMode;
use App\Payouts\PayoutSchedule;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * The automated PAYOUT ENGINE (payout engine · Phase 1) — the scheduled sweep that turns finalized owner
 * statements into disbursed payouts, ON A SCHEDULE, without an operator clicking each one. It ORCHESTRATES
 * the certified {@see OwnerPayoutService} + the config-bound {@see PayoutRail}; it
 * NEVER reimplements amount, transfer, or state logic (anti-drift with the money core).
 *
 * ⚠️ TEST-MODE ONLY in this build — {@see PayoutEngineMode} stamps `mode='test'` on the run and no real money
 * moves (the fake client, or Stripe TEST keys the {@see StripeSafetyGuard} permits).
 *
 * Cross-tenant by design: owner SELECTION runs under {@see TenantContext::runAsSuperAdmin} (every org), and
 * each owner's create/pay/hold runs INSIDE that owner's org context ({@see TenantContext::forOrganization}),
 * so the money writes stay tenant-scoped + audited exactly as the operator path.
 *
 * IDEMPOTENT on `run_key`: a same-key re-run resumes the same {@see PayoutRun} row and re-drives the certified
 * idempotent service (a statement already in flight is not re-created; a paid payout is not re-paid) — so a
 * run can never double-pay. Phase 1 processes an explicit owner set (or every owner with payable work);
 * Phase 2 supplies the preference-driven due set + a `scheduled:YYYY-MM-DD` key.
 */
class PayoutRunService
{
    public function __construct(
        private OwnerPayoutService $payouts,
        private TenantContext $tenant,
    ) {}

    /**
     * The SCHEDULED entry point (payout engine · Phase 2) — select the owners DUE on `$today` per their
     * {@see OwnerPayoutPreference} (frequency/business-day, not paused, meeting their minimum) and
     * run them under the day's idempotency key `scheduled:YYYY-MM-DD` (so a same-day re-run never double-pays).
     */
    public function runScheduled(CarbonImmutable $today, ?string $runKey = null): PayoutRun
    {
        $dueIds = $this->tenant->runAsSuperAdmin(fn (): array => $this->dueOwnerIds($today));

        return $this->run(PayoutRun::TRIGGER_SCHEDULED, $dueIds, $runKey ?? 'scheduled:'.$today->toDateString());
    }

    /**
     * Execute a run. Creates + disburses payouts for `$ownerIds` (or every owner with payable work when
     * null). ⚠️⚠️ NULL MEANS EVERY ORGANISATION — it runs under super-admin, for the daily automatic run. An operator's
     * request must name its own organisation's owners (A11, 2026-10-02: "Run payouts now" passed null, and one
     * organisation's click created and paid another's owners). `$runKey` is the idempotency anchor (default a fresh per-invocation key; scheduled runs pass
     * `scheduled:YYYY-MM-DD`). Returns the completed {@see PayoutRun}.
     *
     * @param  list<string>|null  $ownerIds
     */
    public function run(string $trigger = PayoutRun::TRIGGER_MANUAL, ?array $ownerIds = null, ?string $runKey = null): PayoutRun
    {
        $mode = PayoutEngineMode::current();
        $key = $runKey ?? $trigger.':'.(string) now()->format('U-u');

        return $this->tenant->runAsSuperAdmin(function () use ($trigger, $ownerIds, $key, $mode): PayoutRun {
            $run = PayoutRun::firstOrCreate(
                ['run_key' => $key],
                ['trigger' => $trigger, 'mode' => $mode, 'status' => PayoutRun::STATUS_RUNNING, 'started_at' => now()],
            );

            // Idempotent: a run already finished under this key is returned as-is — never re-paid.
            if (! $run->wasRecentlyCreated && $run->status !== PayoutRun::STATUS_RUNNING) {
                return $run;
            }

            Log::info('Payout engine run starting [TEST MODE — no real funds move].', [
                'run_key' => $key, 'trigger' => $trigger, 'mode' => $mode,
            ]);

            $owners = $this->owners($ownerIds);
            $created = $paid = $held = $failed = 0;
            $totals = [];
            $firstError = null;

            foreach ($owners as $owner) {
                try {
                    $this->tenant->forOrganization((string) $owner->organization_id, function () use ($owner, $run, &$created, &$paid, &$held, &$failed, &$totals): void {
                        $this->processOwner($owner, $run, $created, $paid, $held, $failed, $totals);
                    });
                } catch (Throwable $e) {
                    $failed++;
                    $firstError ??= $e->getMessage();
                }
            }

            $run->update([
                'status' => $firstError !== null && $created === 0 && $paid === 0 ? PayoutRun::STATUS_FAILED : PayoutRun::STATUS_COMPLETED,
                'owners_selected' => $owners->count(),
                'payouts_created' => $created,
                'payouts_paid' => $paid,
                'payouts_held' => $held,
                'payouts_failed' => $failed,
                'totals' => $totals,
                'notes' => $firstError,
                'finished_at' => now(),
            ]);

            Log::info('Payout engine run finished [TEST MODE].', [
                'run_key' => $key, 'created' => $created, 'paid' => $paid, 'held' => $held, 'failed' => $failed,
            ]);

            return $run->refresh();
        });
    }

    /**
     * Process ONE owner inside their org context: release now-clear holds, create payouts for payable
     * statements, then disburse every pending payout. Counters + per-currency totals accumulate by reference.
     *
     * @param  array<string, int>  $totals
     */
    private function processOwner(Owner $owner, PayoutRun $run, int &$created, int &$paid, int &$held, int &$failed, array &$totals): void
    {
        // 1) Auto-release any of this owner's held payouts that are now tax-clear → back to pending.
        if ($owner->hasTaxDetails()) {
            Payout::query()->where('owner_id', $owner->getKey())->where('status', 'held')->get()
                ->each(fn (Payout $p) => $this->payouts->releaseHold($p));
        }

        // 2) Create a payout per payable statement (idempotent — an in-flight statement is not re-created).
        $statements = OwnerStatement::query()
            ->where('owner_id', $owner->getKey())
            ->where('status', 'finalized')
            ->where('locked', true)
            ->where('net_payable_minor', '>', 0)
            ->get();

        foreach ($statements as $statement) {
            $payout = $this->payouts->createFromStatement($statement, (string) $run->getKey());
            if ($payout !== null && $payout->wasRecentlyCreated) {
                $created++;
            }
        }

        // 3) Disburse every pending payout for this owner (new + released holds).
        $pending = Payout::query()->where('owner_id', $owner->getKey())->where('status', 'pending')->get();

        foreach ($pending as $payout) {
            try {
                $result = $this->payouts->pay($payout);
            } catch (PayoutHeldException) {
                // No tax details on file — persist the hold as a real, visible, auto-releasing state.
                $this->payouts->hold($payout);
                $held++;

                continue;
            } catch (RuntimeException) {
                // A guarded refusal (e.g. no longer pending under a race) — leave the row, move on.
                continue;
            }

            if (in_array($result->status, ['paid', 'processing'], true)) {
                $paid++;
                $currency = (string) $result->currency;
                $totals[$currency] = ($totals[$currency] ?? 0) + (int) $result->amount_minor;
            } elseif ($result->status === 'failed') {
                $failed++;
            }
        }
    }

    /**
     * The owners this run touches. An explicit `$ownerIds` set wins; otherwise every owner with payable
     * work — a finalized+locked+positive statement OR a held payout waiting to release. Cross-tenant
     * (called under super-admin), so it sees every org.
     *
     * @param  list<string>|null  $ownerIds
     * @return Collection<int, Owner>
     */
    private function owners(?array $ownerIds): Collection
    {
        // ⚠️⚠️ DEC-n (2026-10-02) — a PAUSED owner is never selected, named or not: a pause stops every payout. (The payout
        //    service refuses them too, so a pause set mid-run is still honoured — two layers, each tested on its own.)
        $paused = OwnerPayoutPreference::query()->where('paused', true)->pluck('owner_id')
            ->map(fn ($id): string => (string) $id)->all();

        if ($ownerIds !== null) {
            return Owner::query()->whereIn('id', $ownerIds)->whereNotIn('id', $paused)->get();
        }

        $payable = OwnerStatement::query()
            ->where('status', 'finalized')->where('locked', true)->where('net_payable_minor', '>', 0)
            ->distinct()->pluck('owner_id');

        $heldOwners = Payout::query()->where('status', 'held')->distinct()->pluck('owner_id');

        $ids = $payable->merge($heldOwners)->filter()->unique()
            ->reject(fn ($id): bool => in_array((string) $id, $paused, true))->values()->all();

        return $ids === [] ? new Collection : Owner::query()->whereIn('id', $ids)->get();
    }

    /**
     * The owners DUE on `$today` (payout engine · Phase 2) — from those with payable work (a finalized+locked+
     * positive statement) or a releasable hold, keep the ones whose {@see OwnerPayoutPreference}: is NOT paused,
     * fires today per {@see PayoutSchedule::isDue}, and (when they have new payable statements) meets their
     * `minimum_minor` — a below-minimum owner is SKIPPED and rolls forward to a later run (never a partial pay).
     * An owner with only a held payout waiting to release is due whenever their cadence fires. Must run under
     * super-admin (cross-tenant selection).
     *
     * @return list<string>
     */
    private function dueOwnerIds(CarbonImmutable $today): array
    {
        $payableByOwner = OwnerStatement::query()
            ->where('status', 'finalized')->where('locked', true)->where('net_payable_minor', '>', 0)
            ->get(['owner_id', 'net_payable_minor'])
            ->groupBy('owner_id');

        $heldOwnerIds = Payout::query()->where('status', 'held')->distinct()->pluck('owner_id');

        $candidateIds = $payableByOwner->keys()->merge($heldOwnerIds)->filter()->unique();

        $due = [];

        foreach ($candidateIds as $ownerId) {
            $ownerId = (string) $ownerId;
            $pref = OwnerPayoutPreference::query()->where('owner_id', $ownerId)->first();
            $prefs = $pref instanceof OwnerPayoutPreference
                ? ['frequency' => $pref->frequency, 'payout_day' => $pref->payout_day, 'minimum_minor' => $pref->minimum_minor, 'paused' => $pref->paused]
                : OwnerPayoutPreference::defaults();

            if ($prefs['paused'] || ! PayoutSchedule::isDue($prefs, $today)) {
                continue;
            }

            // Minimum threshold — Σ payable net (owner's single payout currency). A held-only owner has 0 new
            // payable and is never blocked by the minimum (their existing hold is simply released + paid).
            $payableSum = (int) ($payableByOwner->get($ownerId)?->sum('net_payable_minor') ?? 0);
            if ($payableSum > 0 && $payableSum < (int) $prefs['minimum_minor']) {
                continue;   // below minimum → roll forward
            }

            $due[] = $ownerId;
        }

        return $due;
    }
}
