<?php

declare(strict_types=1);

use App\Exceptions\Owner\PayoutReversalQuarantinedException;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Services\OwnerPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ F-53 AND F-54, PROVED **GONE** — NOT MERELY UNREACHABLE (REPAIR 01, 2026-09-23).
 *
 * ⚠️⚠️ WHY THIS FILE EXISTS BESIDE `PayoutReversalIsUnguardedTest`. That file proves the QUARANTINE: that
 * `reverse()` refuses before any write, and that nothing calls it. Both are still true and still pinned.
 * ⚠️ **But a test that only proves the throw proves the containment a second time, not the repair** — and
 * containment is a bet that nobody opens the door. This project shipped **two other reverse operations in
 * three days** (R3's entry point and R9), so the bet is not a safe one to leave standing.
 *
 * ⚠️ These tests therefore drive the REAL repaired code **past** the throw, through a test-only subclass.
 * The subclass adds no door to the application: `performReversal()` is `protected`, nothing in `app/` calls
 * it, and the no-caller pin in the sibling file still holds.
 *
 * 🔬 WHAT WAS WRONG (measured before the quarantine, by FIX 37):
 *   **F-53** — `reverse()` never changed the SOURCE payout's status, so its own `status !== 'paid'` check
 *              could never refuse a second press: two calls, two `reversed` rows superseding one payout.
 *   **F-54** — the statement returned to `finalized` while the source stayed **`paid`** — a LIVE status — so
 *              `createFromStatement()` handed back the same already-paid row and the statement was finalized
 *              but unpayable. The owner was owed money the system believed it had sent.
 *
 * ⚠️ **ONE CHANGE CLOSES BOTH:** the source payout moves to `reversed`, which {@see Payout::LIVE_STATUSES}
 * deliberately excludes — *"they claim nothing, which is what makes them retryable"*.
 */
final class ReversalDriver extends OwnerPayoutService
{
    /** ⚠️ TEST-ONLY. Drives the repaired implementation past the quarantine; adds nothing to `app/`. */
    public function drive(Payout $paid, string $reason): Payout
    {
        return $this->performReversal($paid, $reason);
    }
}

function repairDriver(): ReversalDriver
{
    return app()->make(ReversalDriver::class);
}

// ── F-53 · THE DOUBLE PRESS ─────────────────────────────────────────────────────────────────────────
it('⚠️⚠️ F-53 GONE — a second reversal REFUSES; exactly one reversal row, and the source is terminal', function () {
    $c = purWorld();
    $driver = repairDriver();

    // 1️⃣ The first reversal succeeds — the operation itself still works.
    $reversal = $driver->drive($c['paid'], 'probe: first press');

    expect((string) $reversal->status)->toBe('reversed')
        ->and((string) $reversal->supersedes_id)->toBe((string) $c['paid']->getKey());

    // ⚠️⚠️ THE FIX ITSELF: the SOURCE row left LIVE_STATUSES. Everything else follows from this.
    expect((string) $c['paid']->fresh()->status)->toBe('reversed',
        'the source payout must leave LIVE_STATUSES — that single move closes both F-53 and F-54')
        ->and(in_array((string) $c['paid']->fresh()->status, Payout::LIVE_STATUSES, true))->toBeFalse();

    // 2️⃣ ⚠️ THE SECOND PRESS — the defect was that this appended a SECOND reversal. It must now refuse.
    expect(fn () => $driver->drive($c['paid']->fresh(), 'probe: second press'))
        ->toThrow(RuntimeException::class);

    // 3️⃣ THE ROW COUNT IS THE ASSERTION, not the exception — a guard placed after the append would throw
    //    and still leave two reversal records, and an exception-only test would sail past exactly that.
    // ⚠️ COUNT BY `supersedes_id`, NOT BY STATUS. 🔬 The first draft counted `status = 'reversed'` and read
    //    **2** — because the REPAIR moves the SOURCE to `reversed` as well, so a status count now legitimately
    //    sees both rows. Counting the supersede links counts REVERSAL RECORDS, which is the thing F-53 was
    //    about: one press, one record.
    expect(Payout::query()->where('supersedes_id', $c['paid']->getKey())->count())
        ->toBe(1, 'exactly ONE reversal record may supersede a payout — two is the F-53 defect')
        ->and(Payout::query()->count())->toBe(2, 'the source and its single reversal, and nothing else');
});

// ── F-54 · THE STRANDED STATEMENT ───────────────────────────────────────────────────────────────────
it('⚠️⚠️ F-54 GONE — after a reversal the statement takes a FRESH payout, not the paid one back', function () {
    $c = purWorld();
    $originalId = (string) $c['paid']->getKey();

    repairDriver()->drive($c['paid'], 'probe: reversal');

    // The statement is back to finalized — that half always worked.
    expect((string) OwnerStatement::query()->whereKey($c['statement']->id)->value('status'))->toBe('finalized');

    // ⚠️⚠️ THE DEFECT: `createFromStatement()` used to hand back THE SAME ALREADY-PAID ROW, because the
    //    source still carried a LIVE status and so still claimed the statement. The owner was owed money the
    //    system believed it had sent.
    $fresh = app(OwnerPayoutService::class)->createFromStatement($c['statement']->fresh());

    expect((string) $fresh->getKey())->not->toBe($originalId,
        'the statement must get a NEW payout — handing back the paid one IS F-54')
        ->and((string) $fresh->status)->toBe('pending', 'and it must be payable, not already paid')
        ->and((int) $fresh->amount_minor)->toBe((int) $c['paid']->amount_minor);

    // ⚠️ AND THE DB TRIGGER AGREES — one live payout per statement, counted rather than assumed.
    expect(Payout::query()->where('owner_statement_id', $c['statement']->id)
        ->whereIn('status', Payout::LIVE_STATUSES)->count())
        ->toBe(1, 'exactly one LIVE payout may claim a statement');
});

// ── THE MONEY, READ BACK ────────────────────────────────────────────────────────────────────────────
it('⚠️ the repair moves no money — the ledger is untouched and the amounts still reconcile', function () {
    $c = purWorld();
    $ledgerBefore = DB::table('owner_ledger')->count();
    $sumBefore = (int) DB::table('owner_ledger')->sum('amount_minor');

    repairDriver()->drive($c['paid'], 'probe: money check');

    // ⚠️ A reversal records WHO owes what; it does not move money. Both halves are asserted, because a
    //    changed row count with an unchanged sum would also pass a sum-only check.
    expect(DB::table('owner_ledger')->count())->toBe($ledgerBefore, 'a reversal must append no ledger line')
        ->and((int) DB::table('owner_ledger')->sum('amount_minor'))->toBe($sumBefore, 'and move no money');

    // The two payout rows carry the SAME amount — the reversal mirrors, it does not re-price.
    $reversal = Payout::query()->where('supersedes_id', $c['paid']->getKey())->firstOrFail();
    expect((int) $reversal->amount_minor)->toBe((int) $c['paid']->amount_minor)
        ->and((string) $reversal->currency)->toBe((string) $c['paid']->currency);
});

// ── AND THE QUARANTINE IS UNTOUCHED ─────────────────────────────────────────────────────────────────
it('⚠️⚠️ the public reverse() STILL refuses — the repair did not lift R-REVERSE', function () {
    $c = purWorld();

    // ⚠️ R-REVERSE is an OPEN founder ruling. Repairing the defects behind the door is not opening it, and
    //    this assertion is what keeps those two facts separate.
    expect(fn () => app(OwnerPayoutService::class)->reverse($c['paid'], 'probe'))
        ->toThrow(PayoutReversalQuarantinedException::class);

    expect(Payout::query()->where('status', 'reversed')->count())->toBe(0)
        ->and(Payout::query()->whereNotNull('supersedes_id')->count())->toBe(0)
        ->and((string) $c['paid']->fresh()->status)->toBe('paid', 'the refused call wrote nothing');
});
