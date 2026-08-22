<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\OwnerPayoutPreference;
use App\Models\Unit;
use App\Services\OwnerStatementService;
use App\Support\Events\AuditEventRecorder;
use App\Support\Money\MoneyFormatter;
use App\Support\Operator\OperatorWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Operator OWNER management writes (#93) — Edit owner (commission / portal), Pause payouts, Remove owner (a careful
 * ARCHIVE-not-destroy), and Add note. All PRG (POST → controller → redirect), tenant-scoped (`{owner}` resolves via
 * the global scope → cross-org 404), gated `can:users.manage` (the same owner-management privilege as the invite),
 * read-only-respecting (#20), and audited. NEVER a destructive cascade — the financial + booking records are kept.
 */
class OwnerManageController extends Controller
{
    public function __construct(private AuditEventRecorder $audit) {}

    /**
     * Edit owner: the management COMMISSION (a NEW effective-dated {@see ManagementAgreement} that starts next
     * month — "applies from the next statement", never a silent rewrite of history) + portal access. The real 18%
     * changes only forward; past statements keep the rate they were finalized at.
     */
    public function update(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        $model = Owner::query()->findOrFail($owner);
        $validated = $request->validate([
            'commission_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'portal_active' => ['nullable', 'boolean'],
        ]);

        $changed = [];

        if (array_key_exists('commission_pct', $validated) && $validated['commission_pct'] !== null) {
            $newPct = round((float) $validated['commission_pct'], 2);
            $current = ManagementAgreement::query()->where('owner_id', $model->id)->effectiveOn(CarbonImmutable::today())->whereNull('unit_id')->first();

            if ($current === null || (float) $current->commission_pct !== $newPct) {
                $nextMonth = CarbonImmutable::today()->startOfMonth()->addMonth();
                DB::transaction(function () use ($model, $current, $newPct, $nextMonth): void {
                    // Close the current portfolio agreement at the end of this month; open the new one next month.
                    if ($current !== null) {
                        $current->forceFill(['effective_to' => $nextMonth->subDay()->toDateString()])->save();
                    }
                    ManagementAgreement::create([
                        'owner_id' => $model->id,
                        'unit_id' => null,
                        'commission_pct' => $newPct,
                        'payout_schedule' => $current->payout_schedule ?? 'monthly',
                        'payout_currency' => $current->payout_currency ?? $model->payout_currency,
                        'effective_from' => $nextMonth->toDateString(),
                        'effective_to' => null,
                        'status' => 'active',
                    ]);
                });
                $changed[] = 'commission_pct';
            }
        }

        if (array_key_exists('portal_active', $validated)) {
            $active = (bool) $validated['portal_active'];
            $model->user?->forceFill(['status' => $active ? 'active' : 'inactive'])->save();
            $changed[] = 'portal_active';
        }

        $this->audit->record('owner.updated', $model, null, ['fields' => $changed]);

        return $this->back($owner, 'owners_notice', 'Owner updated. Commission changes apply from the next statement.');
    }

    /**
     * Pause / resume payouts — REAL via {@see OwnerPayoutPreference::paused}, which the certified payout engine
     * already respects (a paused owner is skipped; the amount stays in the ledger and carries via the statement).
     */
    public function pausePayouts(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        $model = Owner::query()->findOrFail($owner);
        $paused = $request->boolean('paused');

        OwnerPayoutPreference::query()->updateOrCreate(['owner_id' => $model->id], ['paused' => $paused]);
        $this->audit->record('owner.payouts_paused', $model, null, ['paused' => $paused]);

        return $this->back($owner, 'owners_notice', $paused ? 'Payouts paused — amounts accumulate and carry forward until resumed.' : 'Payouts resumed.');
    }

    /**
     * Add a note to the owner's operator-internal timeline (#93) — a real authored {@see Note}, never shown in the
     * owner portal.
     */
    /**
     * ⚠️⚠️ CLOSE A PERIOD — the FIRST REAL CALLER `OwnerStatementService::finalize()` has ever had.
     *
     * ⚠️ `generateDraft()` and `finalize()` were reachable only from seeders and tests: the twelve-entity
     * seeder-only class again, and a decisive one, because **the payout engine selects ONLY finalized
     * statements**. A real deployment therefore had nothing to pay, ever. The demo looked complete because
     * `DemoSeeder` finalises statements the application could not.
     *
     * ⚠️ SAFE ONLY BECAUSE THE COLLECTION GATE LANDED WITH IT. Before {@see OwnerCollection}, giving
     * finalisation a caller would have started paying owners against revenue never collected — the two
     * defects were cancelling. That ordering is proven by `OwnerCollectionGateTest`.
     *
     * ⛔ **THE TRIGGER IS A PRODUCT DECISION AND IS SURFACED, NOT PICKED.** This ships the MECHANISM as an
     * explicit operator action, which is the only option that needs no ruling — a human closes a period when
     * they judge it closed. The founder still has to decide:
     *   (a) operator action, as built here — deliberate, auditable, and someone must remember;
     *   (b) a scheduled period close (a monthly command) — reliable, but it closes over late arrivals;
     *   (c) automatic on period end — no forgetting, and no chance to review before money moves.
     * ⚠️ And two questions no code here answers: a booking that lands AFTER its period closes (today it
     * flows to the next statement as a prior-period adjustment — the existing straggler path), and whether a
     * finalised statement may ever be REOPENED, and by whom. Nothing reopens one today; `finalize()` locks.
     */
    public function closePeriod(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $model = Owner::query()->findOrFail($owner);
        $statements = app(OwnerStatementService::class);

        $statement = $statements->finalize(
            $statements->generateDraft($model->id, $data['period_start'], $data['period_end'])
        );

        // ⚠️ Recorded as a field list, never the figures — an audit row is not a second copy of the money.
        $this->audit->record('owner.statement_finalized', $model, null, [
            'fields' => ['statement_id', 'period_start', 'period_end'],
            'statement_id' => (string) $statement->id,
        ]);

        // ⚠️ `currency` is the PAYOUT currency and `net_payable_minor` the converted figure — there is no
        // `payout_currency` column and no `reference` column on owner_statements. Both were assumed in a
        // first draft and corrected by reading the live schema.
        $net = MoneyFormatter::display((int) $statement->net_payable_minor, (string) $statement->currency);

        return $this->back($owner, 'owners_notice',
            'Period closed — statement #'.$statement->sequence_no.' finalised at '.$net.' payable. '
            .'Revenue not yet collected from guests stays unclaimed and will appear on a later statement.');
    }

    public function addNote(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        $model = Owner::query()->findOrFail($owner);
        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $model->noteEntries()->create([
            'body' => $validated['body'],
            'visibility' => 'internal',
            'author_id' => $request->user()?->getAuthIdentifier(),
        ]);
        $this->audit->record('owner.note_added', $model);

        return $this->back($owner, 'owners_notice', 'Note added.');
    }

    /**
     * REMOVE OWNER — a careful ARCHIVE-not-destroy. Confirmed by typing the owner's exact name. Units become
     * UNASSIGNED (bookings stay intact — `units.owner_id` → null); the active agreement is ENDED; portal access
     * ends (the linked user is deactivated + pending invites revoked); the owner is SOFT-DELETED so all statements
     * / payouts / ledger / bookings are RETAINED for accounting (the FK cascades fire only on a hard delete). NEVER
     * a destructive cascade.
     */
    public function remove(Request $request, string $owner): RedirectResponse
    {
        if ($this->paused()) {
            return $this->back($owner, 'owners_error', $this->pausedMessage());
        }

        $model = Owner::query()->findOrFail($owner);
        $validated = $request->validate(['confirm_name' => ['required', 'string']]);

        if (trim((string) $validated['confirm_name']) !== trim((string) $model->name)) {
            return $this->back($owner, 'owners_error', 'The name you typed doesn’t match — the owner was not removed.');
        }

        DB::transaction(function () use ($model): void {
            // Units → unassigned (bookings untouched).
            Unit::query()->where('owner_id', $model->id)->update(['owner_id' => null]);
            // End the active management agreements.
            ManagementAgreement::query()->where('owner_id', $model->id)->where('status', 'active')
                ->update(['status' => 'ended', 'effective_to' => CarbonImmutable::today()->toDateString()]);
            // Portal access ends: deactivate the linked user + revoke pending invites.
            $model->user?->forceFill(['status' => 'inactive'])->save();
            OwnerInvite::query()->where('owner_id', $model->id)->where('status', 'pending')->update(['status' => 'revoked']);
            // Archive the owner (soft-delete) + mark inactive. Statements/payouts/ledger are RETAINED (no cascade).
            $model->forceFill(['status' => 'inactive'])->save();
            $model->delete();
        });

        $this->audit->record('owner.archived', $model, null, ['archived' => true]);

        return redirect()->route('app.owners')->with('owners_notice', $model->name.' removed — units unassigned, all statements & payouts kept for accounting.');
    }

    private function paused(): bool
    {
        return app(OperatorWorkspace::class)->isReadOnly();
    }

    private function pausedMessage(): string
    {
        return 'Workspace paused — owner changes are read-only until '.app(OperatorWorkspace::class)->resumeLabel().'.';
    }

    private function back(string $owner, string $key, string $message): RedirectResponse
    {
        return redirect()->route('app.owners.show', $owner)->with($key, $message);
    }
}
