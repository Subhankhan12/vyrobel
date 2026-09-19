<?php

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ THIS FILE RECORDS A DEFECT. IT DOES NOT ENDORSE ONE.
 *
 * R-REVERSE (founder, 2026-09-19) ruled that `OwnerPayoutService::reverse()` should be ROUTED for operators.
 * FIX 37 did NOT route it, and these assertions are why: 🔬 measured, `reverse()` is an APPEND with NO
 * double-press guard, and it strands the statement it was meant to free. Routing it as it stands would ship
 * a button that reverses an owner's payout twice on a double-click.
 *
 * ⚠️ THE PROJECT'S OWN RULE NAMED THIS BEFORE IT WAS MEASURED: an APPEND needs a guard; a SET does not.
 * `reverse()` appends a row. It was never guarded.
 *
 * ⚠️⚠️ WHY THE EXISTING PROTECTIONS DO NOT REACH IT:
 *   · the `status !== 'paid'` check cannot stop a second press, because `reverse()` NEVER CHANGES the source
 *     payout's status — the row it guards on is still `paid` afterwards;
 *   · the unique index `payouts_one_live_per_statement` is on a VIRTUAL column that is
 *     `owner_statement_id` only while status ∈ (pending, processing, held, paid) and NULL otherwise —
 *     🔬 read from `information_schema`. A `reversed` row therefore keys NULL, and NULLs never collide in a
 *     MySQL unique index, so any number of reversals may be appended for one payout.
 *
 * ⚠️ THE CONCURRENT CASE IS MOOT: there is no guard to race. A second press a second later already succeeds.
 *
 * ⚠️⚠️ FIXING THIS MEANS CHANGING `reverse()`, WHICH FIX 37 WAS EXPLICITLY TOLD IS A FINDING TO REPORT AND
 * NOT A LIBERTY TO TAKE. These tests exist so the behaviour is RECORDED and cannot change silently while the
 * founder decides.
 */
function purWorld(): array
{
    $s = seedScenario(1);
    $owner = Owner::factory()->create(['name' => 'Reversal Probe', 'payout_currency' => 'EUR', 'status' => 'active']);
    $unit = $s['units'][0];
    $unit->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01',
    ]);

    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);

    $statements = app(OwnerStatementService::class);
    $statement = $statements->finalize($statements->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    $payouts = app(OwnerPayoutService::class);
    $paid = $payouts->pay($payouts->createFromStatement($statement));

    return ['statement' => $statement, 'paid' => $paid, 'payouts' => $payouts];
}

it('⚠️ the inverse itself is genuine — it APPENDS, and returns the statement to finalized', function () {
    $c = purWorld();

    expect((string) $c['paid']->fresh()->status)->toBe('paid');
    expect((int) $c['paid']->amount_minor)->toBe(17000);   // €200.00 room less 15% commission

    $c['payouts']->reverse($c['paid']->fresh(), 'measured');

    // ⚠️ APPENDED, NEVER EDITED — the paid row is still there, untouched (#7).
    expect(Payout::query()->count())->toBe(2);
    expect((string) $c['paid']->fresh()->status)->toBe('paid');
    expect((string) OwnerStatement::query()->whereKey($c['statement']->id)->value('status'))->toBe('finalized');
});

/**
 * ⚠️⚠️ THE FINDING. Asserted on the ROW COUNT, not a response status — a test that only checked the second
 * call "returned something sensible" would pin nothing at all.
 */
it('⚠️⚠️ RECORDS A DEFECT — a SECOND reverse() is NOT refused: one payout, two reversals', function () {
    $c = purWorld();

    $c['payouts']->reverse($c['paid']->fresh(), 'first press');
    $c['payouts']->reverse($c['paid']->fresh(), 'second press — an operator double-click');

    // ⚠️ TWO reversal rows superseding ONE payout. This is what a double-click produces today.
    expect(Payout::query()->where('supersedes_id', $c['paid']->id)->where('status', 'reversed')->count())
        ->toBe(2, 'RECORDED DEFECT: reverse() is an unguarded append');
});

/**
 * ⚠️⚠️ AND THE SECOND HALF, WHICH IS WORSE THAN THE FIRST. A reversal is meant to return the money to the
 * owner's balance so it can be paid again. 🔬 It cannot be: `createFromStatement()` hands back the ORIGINAL
 * payout — still `paid` — because that row keeps the unique live-statement key. So after a reversal the
 * statement says `finalized` (unpaid) while the payout says `paid`, and no new payout can be made.
 *
 * ⚠️ THE OWNER IS OWED MONEY THE SYSTEM BELIEVES IT HAS ALREADY SENT.
 */
it('⚠️⚠️ RECORDS A DEFECT — after a reversal the statement is stranded: finalized, but unpayable', function () {
    $c = purWorld();
    $originalId = (string) $c['paid']->id;

    $c['payouts']->reverse($c['paid']->fresh(), 'measured');

    $again = $c['payouts']->createFromStatement($c['statement']->fresh());

    // ⚠️ NOT a fresh payout — the SAME row, still marked paid.
    expect((string) $again?->id)->toBe($originalId, 'RECORDED DEFECT: the statement cannot be paid again');
    expect((string) $again?->status)->toBe('paid');

    // ⚠️ AND THE TWO RECORDS DISAGREE — the contradiction, stated as one assertion.
    expect((string) OwnerStatement::query()->whereKey($c['statement']->id)->value('status'))->toBe('finalized');
});
