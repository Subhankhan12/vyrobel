<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Livewire\Operator\PayoutRun;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\PayoutRun as PayoutRunModel;
use App\Payouts\Rails\ManualPayoutRail;
use App\Payouts\Rails\StripeConnectPayoutRail;
use App\Services\OwnerPayoutService;
use App\Services\PayoutRunService;
use App\Support\Operator\OperatorWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The Payout Run's two REAL money writes (Wave 1 · Sub-gate 4) — POST → controller → the CERTIFIED
 * {@see OwnerPayoutService} → PRG redirect + flash. Driven from {@see PayoutRun}, which only renders.
 *
 * Why a controller and not a wire-action (invariant #20): a Livewire action re-enters through
 * /livewire/update, which does NOT run the route middleware — a scope-dependent write there would leak.
 * A normal POST re-runs the web group, so SetTenantForWebRequest has genuinely established tenant context
 * before anything is resolved, and every `findOrFail` below runs through BelongsToOrganization's global
 * scope → a cross-org id 404s. CSRF comes from the web group.
 *
 * NOTHING here reimplements payout logic. The service decides what may be created (finalized+locked only,
 * idempotent, null for a non-positive net) and what may be paid (pending only); this class resolves ids,
 * translates the service's real outcome into an honest sentence, and redirects.
 *
 * ⚠️ NEVER CATCH-AND-PRETEND. Both writes can legitimately fail and BOTH failures are reported as failures:
 *   • a guarded transition (a draft statement, a non-pending payout) → the service THROWS → clean flash,
 *     never a 500, and the row is untouched;
 *   • the bound rail cannot disburse — {@see StripeConnectPayoutRail::send()} THROWS by design (the
 *     deferred live checkpoint) — the exception surfaces as an honest flash and the payout stays pending.
 *     It is never swallowed into a fake "paid";
 *   • the rail returns a FAILURE result (no exception) → the service marks the payout genuinely `failed`
 *     with the real reason → an error flash carrying that reason, never a success message.
 * A success message is only ever emitted from a row the DB really changed: `wasRecentlyCreated` and the
 * post-write `status` are read back off the model rather than assumed.
 */
class PayoutRunActionController extends Controller
{
    /**
     * Create a payout from ONE finalized statement (`statement_id`), or from every ready statement when it
     * is absent (the "create all" batch).
     */
    /**
     * Run the CERTIFIED payout engine ({@see PayoutRunService::run}) for the tenant's owners — a REAL persisted
     * {@see PayoutRunModel} that reconciles (run total = Σ owner nets) and LOCKS (idempotent on its run_key). In
     * TEST MODE nothing moves real money (the ManualPayoutRail records / the fake Connect client simulates) — the
     * SAME payout-engine boundary as the CLI. Read-only-respecting (#20).
     */
    public function runNow(Request $request, PayoutRunService $runs): RedirectResponse
    {
        // ⚠️⚠️ THE FEATURE FLAG NOW GATES THE MANUAL RUN TOO. `PAYOUT_ENGINE_ENABLED` was read by the
        // scheduled command (`RunScheduledPayouts`) and by the owner-facing display — but NOT here, so this
        // route ran the engine with the flag off. ⚠️ A flag that gates the scheduled path and not the manual
        // one is worse than no flag: it reads as "the engine is off" on every surface while one button still
        // moves money. Same family as `MAIL_MAILER=log` — nothing errors, the harm just happens.
        if (! (bool) config('services.payouts.engine_enabled', false)) {
            return $this->back('payout_error',
                'The payout engine is disabled (PAYOUT_ENGINE_ENABLED). Enable it before running a payout.');
        }

        if ($this->paused()) {
            return $this->back('payout_error', $this->pausedMessage());
        }

        $run = $runs->run(PayoutRunModel::TRIGGER_MANUAL);
        $created = (int) ($run->payouts_created ?? 0);
        $paid = (int) ($run->payouts_paid ?? 0);
        $held = (int) ($run->payouts_held ?? 0);

        return $this->back('payout_notice', 'Payout run complete ('.$run->mode.' mode) — '.$created.' created · '.$paid.' paid · '.$held.' held.');
    }

    public function create(Request $request, OwnerPayoutService $payouts): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back('payout_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'statement_id' => ['nullable', 'string'],
        ]);

        $id = $validated['statement_id'] ?? null;

        if (! is_string($id) || $id === '') {
            return $this->createAllReady($payouts);
        }

        // Tenant-scoped, AFTER the middleware → a statement from another organization 404s.
        $statement = OwnerStatement::query()->findOrFail($id);

        try {
            $payout = $payouts->createFromStatement($statement);
        } catch (RuntimeException $e) {
            // e.g. the statement is still a draft — guarded by the service, reported, never a 500.
            return $this->back('payout_error', 'Couldn’t create the payout — '.$e->getMessage());
        }

        // A null return is NOT an error: nothing was payable. Report which real reason it was.
        if ($payout === null) {
            return $this->back('payout_notice', $this->nothingPayable($statement));
        }

        // The service is idempotent — an existing pending/paid payout comes back as-is. `wasRecentlyCreated`
        // is the DB's own answer to "did a row actually get inserted", so nothing is ever claimed twice.
        if (! $payout->wasRecentlyCreated) {
            return $this->back('payout_notice', 'That statement already has a payout — nothing was created.');
        }

        return $this->back('payout_notice', '1 payout created.');
    }

    /**
     * Pay ONE pending payout (`payout_id`), or every pending payout when it is absent (the "pay all" batch).
     * On the real, working {@see ManualPayoutRail} this RECORDS a bank transfer the operator has already
     * made outside Vyrobel — the rail does not move money, and the copy on the page says exactly that.
     */
    public function pay(Request $request, OwnerPayoutService $payouts): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back('payout_error', $this->pausedMessage());
        }

        $validated = $request->validate([
            'payout_id' => ['nullable', 'string'],
        ]);

        $id = $validated['payout_id'] ?? null;

        if (! is_string($id) || $id === '') {
            return $this->payAllPending($payouts);
        }

        // Tenant-scoped, AFTER the middleware → a payout from another organization 404s.
        $payout = Payout::query()->findOrFail($id);

        try {
            $paid = $payouts->pay($payout);
        } catch (RuntimeException $e) {
            // Two real causes, both honest, neither a 500: the payout is not pending, or the bound rail
            // threw (Stripe Connect — the deferred skeleton). The row is left exactly as it was.
            return $this->back('payout_error', 'Couldn’t pay — '.$e->getMessage());
        }

        if ($paid->status === 'paid') {
            return $this->back('payout_notice', '1 payout marked paid.');
        }

        // The rail returned a failure rather than throwing: the payout is genuinely `failed` and says why.
        return $this->back('payout_error', 'The payout failed — '.($paid->failure_reason ?? 'the rail gave no reason.'));
    }

    /**
     * The batch create. The ready predicate is the page's (finalized + locked + a POSITIVE net) — a
     * non-positive net is deliberately excluded so a bulk button can never trigger the service's
     * carry-forward ledger write on a negative statement as a side effect.
     *
     * The "already has a pending/paid payout" case is NOT re-tested here on purpose: createFromStatement()
     * is idempotent and returns the existing row, and `wasRecentlyCreated` reports what was really inserted.
     * So the count cannot be inflated and a paid statement cannot be double-paid.
     */
    private function createAllReady(OwnerPayoutService $payouts): RedirectResponse
    {
        $ready = OwnerStatement::query()   // tenant-scoped by BelongsToOrganization
            ->where('status', 'finalized')
            ->where('locked', true)
            ->where('net_payable_minor', '>', 0)
            ->get();

        $created = 0;
        $error = null;

        foreach ($ready as $statement) {
            try {
                $payout = $payouts->createFromStatement($statement);
            } catch (RuntimeException $e) {
                $error ??= $e->getMessage();

                continue;
            }

            if ($payout !== null && $payout->wasRecentlyCreated) {
                $created++;
            }
        }

        if ($created === 0) {
            if ($error !== null) {
                return $this->back('payout_error', 'Couldn’t create payouts — '.$error);
            }

            return $this->back('payout_notice', $ready->isEmpty()
                ? 'Nothing is ready to pay — no finalized statement has a positive net payable.'
                : 'No new payouts — every ready statement already has one.');
        }

        $message = $created.' '.Str::plural('payout', $created).' created.';

        if ($error !== null) {
            return $this->back('payout_error', $message.' Some could not be created — '.$error);
        }

        return $this->back('payout_notice', $message);
    }

    /** The batch pay. Every real outcome is counted separately and reported — paid, failed, and refused. */
    private function payAllPending(OwnerPayoutService $payouts): RedirectResponse
    {
        $pending = Payout::query()->where('status', 'pending')->get();   // tenant-scoped

        $paid = 0;
        $failed = 0;
        $error = null;

        foreach ($pending as $payout) {
            try {
                $result = $payouts->pay($payout);
            } catch (RuntimeException $e) {
                // The rail refused outright (e.g. the deferred Connect skeleton) — the payout stays pending.
                $error ??= $e->getMessage();

                continue;
            }

            if ($result->status === 'paid') {
                $paid++;
            } else {
                $failed++;
            }
        }

        $parts = [];

        if ($paid > 0) {
            $parts[] = $paid.' '.Str::plural('payout', $paid).' marked paid';
        }

        if ($failed > 0) {
            $parts[] = $failed.' failed on the rail';
        }

        if ($parts === []) {
            if ($error !== null) {
                return $this->back('payout_error', 'Couldn’t pay — '.$error);
            }

            return $this->back('payout_notice', 'No pending payouts to pay.');
        }

        $message = implode(' · ', $parts).'.';

        // A partial success is NOT a success flash — the failures lead the sentence they are part of.
        if ($failed > 0 || $error !== null) {
            return $this->back('payout_error', $error !== null ? $message.' Some could not be attempted — '.$error : $message);
        }

        return $this->back('payout_notice', $message);
    }

    /**
     * Why the service returned null — read from the REAL column, so the operator is told what actually
     * happened rather than a generic "nothing to do". A negative net is not merely skipped: the service
     * carries it forward as an adjustment into the next period, which is what this says.
     */
    private function nothingPayable(OwnerStatement $statement): string
    {
        return (int) $statement->net_payable_minor < 0
            ? 'Nothing payable — this statement’s net is negative, so the balance carries forward to offset the owner’s next statement.'
            : 'Nothing payable — this statement’s net is zero, so nothing is owed.';
    }

    private function paused(): bool
    {
        return app(OperatorWorkspace::class)->isReadOnly();
    }

    private function pausedMessage(): string
    {
        return 'Workspace paused — payouts are read-only until '.app(OperatorWorkspace::class)->resumeLabel().'.';
    }

    private function back(string $key, string $message): RedirectResponse
    {
        return redirect()->route('app.payouts.run')->with($key, $message);
    }
}
