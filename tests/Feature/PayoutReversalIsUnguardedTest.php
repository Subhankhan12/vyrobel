<?php

use App\Exceptions\Owner\PayoutReversalQuarantinedException;
use App\Models\AuditEvent;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Services\OwnerPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ THIS FILE RECORDS DEFECTS THAT ARE CONTAINED, NOT FIXED — AND IT IS THE ONLY PLACE THE MEASURED
 * BEHAVIOUR SURVIVES.
 *
 * FIX 37 EXECUTED `OwnerPayoutService::reverse()` — the first pass ever to do so — and measured two defects:
 *
 *   · **F-53** one paid payout, two `reverse()` calls → 🔬 **TWO `reversed` rows, no refusal**. Its own
 *     `status !== 'paid'` check cannot stop a second press because `reverse()` NEVER CHANGES the source
 *     payout's status; and the unique index `payouts_one_live_per_statement` is on a VIRTUAL column that is
 *     NULL for a `reversed` row, and NULLs never collide.
 *   · **F-54** after one reversal, 🔬 `createFromStatement()` returned **the SAME original payout, still
 *     `paid`** — so the statement sits `finalized` (unpaid) while the payout says `paid`, permanently. The
 *     owner is owed money the system believes it has sent.
 *
 * ⚠️⚠️ R-REVERSE (founder, 2026-09-19) then ruled QUARANTINE, NOT REPAIR. So these cases can no longer prove
 * the defects by RUNNING them — the method refuses first. Each one below therefore keeps the figure it
 * measured in its name and its comment, and now asserts that the sequence CANNOT RUN AT ALL.
 *
 * ⚠️⚠️ THE DEFECTS ARE UNFIXED, NOT GONE. Remove the quarantine and every measurement above returns exactly
 * as recorded. Anyone lifting it must fix the CAUSE first: the source payout has to reach a TERMINAL status
 * on reversal, which is the single change that both releases the unique live key (F-54) and makes the
 * existing status check able to refuse a second press (F-53).
 */
// ⚠️ `purWorld()` now lives in tests/Support/payout_reversal_helpers.php — two suites need it.

/** Every row the reversal would have touched, read together so "nothing moved" is one comparison. */
function purState(array $c): array
{
    return [
        'payouts' => Payout::query()->count(),
        'reversals' => Payout::query()->where('status', 'reversed')->count(),
        'source_status' => (string) $c['paid']->fresh()->status,
        'statement_status' => (string) OwnerStatement::query()->whereKey($c['statement']->id)->value('status'),
        'ledger' => DB::table('owner_ledger')->count(),
        'audit_reversed' => AuditEvent::withoutGlobalScopes()->where('action', 'payout.reversed')->count(),
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// 1 · WAS: "the inverse itself is genuine — it APPENDS, and returns the statement to finalized"
//     NOW: that sequence cannot run, and NOTHING is written when it is attempted.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * ⚠️⚠️ THE ROW COUNTS ARE THE ASSERTION, NOT THE EXCEPTION. Catching the throw proves only THAT it refused;
 * it proves nothing about WHEN. A guard placed after the append would strand the statement and then complain,
 * and a test that only caught the exception would pass over exactly that.
 *
 * ⚠️ AND THE CALL IS PROVEN TO HAVE BEEN ATTEMPTED — "nothing moved" is also what you see when a test never
 * reached the call at all, so the refusal and the unmoved rows are asserted TOGETHER.
 */
it('⚠️⚠️ QUARANTINED — reverse() refuses BEFORE ANY WRITE: no append, no statement move, no audit row', function () {
    $c = purWorld();

    // 🔬 FIX 37 MEASURED, when this still ran: 1 payout row → 2, source still `paid`, statement → `finalized`.
    $before = purState($c);
    expect($before['payouts'])->toBe(1);
    expect($before['source_status'])->toBe('paid');
    expect((int) $c['paid']->amount_minor)->toBe(17000);   // €200.00 room less 15% commission

    $threw = false;
    try {
        $c['payouts']->reverse($c['paid']->fresh(), 'attempted');
    } catch (PayoutReversalQuarantinedException $e) {
        $threw = true;
        expect($e->getMessage())->toContain('F-53')->toContain('F-54');
    }

    expect($threw)->toBeTrue('the call must actually have been attempted — otherwise "nothing moved" is vacuous');
    expect(purState($c))->toEqual($before, 'the refusal must fire BEFORE any write, not after');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2 · WAS: "a SECOND reverse() is NOT refused: one payout, two reversals"
//     NOW: neither press runs — but the defect that made the second one land is UNFIXED.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * 🔬 THE MEASUREMENT THIS CASE CARRIES (FIX 37, when the method still ran): two calls produced **TWO**
 * `reversed` rows superseding ONE payout — an operator double-click reversing an owner's payout twice.
 *
 * ⚠️ THE CAUSE IS STILL IN THE CODE. `reverse()` still never changes the source payout's status, so its own
 * check still cannot refuse a second press. Only the quarantine stops the sequence.
 */
it('⚠️⚠️ CONTAINED (F-53) — the double-press that produced TWO reversals cannot run: still ZERO', function () {
    $c = purWorld();

    foreach (['first press', 'second press — an operator double-click'] as $attempt) {
        expect(fn () => $c['payouts']->reverse($c['paid']->fresh(), $attempt))
            ->toThrow(PayoutReversalQuarantinedException::class);
    }

    // ⚠️ FIX 37 measured 2 here. The defect is contained, so it is 0 — and the SOURCE is untouched, which is
    //    the very fact that made the second press possible.
    expect(Payout::query()->where('supersedes_id', $c['paid']->id)->where('status', 'reversed')->count())->toBe(0);
    expect((string) $c['paid']->fresh()->status)->toBe('paid');
});

// ─────────────────────────────────────────────────────────────────────────────
// 3 · WAS: "after a reversal the statement is stranded: finalized, but unpayable"
//     NOW: no reversal happens, so the statement stays payable — the stranding is prevented, not fixed.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * 🔬 THE MEASUREMENT THIS CASE CARRIES (FIX 37): after ONE reversal, `createFromStatement()` returned the
 * **SAME original payout id, still `paid`**, because that row keeps the unique live-statement key — so the
 * statement said `finalized` while the payout said `paid`, and no new payout could ever be made.
 *
 * ⚠️ THE ASYMMETRY THAT CAUSES IT IS UNCHANGED: the statement transition and the payout status are written by
 * different rules, and only one of them moves.
 */
it('⚠️⚠️ CONTAINED (F-54) — the stranding cannot happen, because the reversal that caused it cannot run', function () {
    $c = purWorld();
    $originalId = (string) $c['paid']->id;

    expect(fn () => $c['payouts']->reverse($c['paid']->fresh(), 'attempted'))
        ->toThrow(PayoutReversalQuarantinedException::class);

    // ⚠️ The statement is still `paid` — NOT the contradictory `finalized`/`paid` pair FIX 37 measured.
    expect((string) OwnerStatement::query()->whereKey($c['statement']->id)->value('status'))->toBe('paid');

    // ⚠️ AND THE IDEMPOTENT RETURN IS STILL THERE, unchanged — it is the mechanism, not the defect.
    expect((string) $c['payouts']->createFromStatement($c['statement']->fresh())?->id)->toBe($originalId);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4 · THE NO-CALLER PIN — the quarantine stops the damage; this stops it being wired
// ─────────────────────────────────────────────────────────────────────────────

/**
 * ⚠️⚠️ WITHOUT THIS, SOMEONE WIRES A ROUTE, MEETS THE EXCEPTION, AND DELETES THE GUARD BECAUSE IT IS "IN THE
 * WAY". The quarantine and this pin are two halves of one control.
 *
 * ⚠️ THE SCAN MUST NOT READ ITS OWN NEEDLE — a trap that has fired about ten times in this project. It
 * therefore EXCLUDES the defining service (whose own body contains the method) and the `tests/` tree (this
 * file names it repeatedly), and it matches a CALL shape (`->reverse(`) rather than the bare word.
 *
 * ⚠️ `Collection::reverse()` is a legitimate, unrelated call that appears in app code, so the match is
 * narrowed to receivers that are the payout service.
 */
it('⚠️ nothing in the application calls or routes the quarantined reversal', function () {
    $callers = [];

    foreach (File::allFiles(base_path('app')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        // ⚠️ THE DEFINING FILE IS EXCLUDED — it contains the method itself, which is not a caller.
        if (str_ends_with($file->getPathname(), 'Services/OwnerPayoutService.php')) {
            continue;
        }

        $source = File::get($file->getPathname());

        // A call ON the payout service, never the bare word and never Collection::reverse().
        if (preg_match('/(payouts?|payoutService|OwnerPayoutService::class\))\s*(->|\)->)reverse\s*\(/i', $source)) {
            $callers[] = $file->getRelativePathname();
        }
    }

    expect($callers)->toBe([], 'the quarantined reversal must have no callers: '.implode(', ', $callers));

    // ⚠️ AND NO ROUTE REACHES IT — asked of the FRAMEWORK, not of a parser.
    $routed = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($r) => str_contains(mb_strtolower((string) ($r->getActionName() ?? '')), 'payoutreversal'))
        ->map(fn ($r) => (string) $r->uri())
        ->values()->all();

    expect($routed)->toBe([]);
});
