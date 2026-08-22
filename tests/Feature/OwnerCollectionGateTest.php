<?php

use App\Models\Folio;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Payment;
use App\Models\Payout;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Support\Money\Money;
use App\Support\Owner\OwnerCollection;
use App\Support\Owner\OwnerLedgerAttribution;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;

// ══ ②+ACCRUAL · THE COLLECTION GATE ═══════════════════════════════════════════════════════════════════
//
// ⚠️⚠️ THE SPINE OF THIS PASS. Two defects were CANCELLING: the owner ledger accrues on charges (correct —
// it is an accrual), and nothing finalises a statement (so nothing was ever paid). Nobody was paid, so nobody
// was overpaid. ⚠️ **Unpicking finalisation ALONE would have started paying owners against revenue the
// business never collected** — and with `PAYMENTS_GATEWAY=manual` every storefront booking is pay-on-arrival,
// so that is the DEFAULT path, not an edge case.
//
// ⚠️ THE ORDERING CONSTRAINT IS WHAT THIS FILE PROVES: a charged-but-unpaid booking may finalise a statement
// and must still pay NOTHING. Remove the gate and this file goes red — which is the point.

uses(RefreshDatabase::class);

/** An owned unit with a booking, room revenue posted and attributed — but NOT paid. */
function gateScenario(int $roomMinor = 20000): array
{
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);

    // ⚠️⚠️ DELIBERATELY NOT COLLECTED. A bulk fixture sweep added `collectFolio()` here and NEUTERED THIS
    // WHOLE FILE — every "pays nothing" assertion would have passed for the wrong reason. The scenario is
    // charged-and-UNPAID on purpose; the tests that need payment call collectFolio() themselves.
    return ['scenario' => $s, 'owner' => $owner, 'reservation' => $reservation];
}

// ══ ⚠️ A · THE SPINE — charged but unpaid finalises, and pays nothing ══════════════════════════════════

it('⚠️⚠️ finalises a statement for a CHARGED-BUT-UNPAID booking and pays the owner NOTHING', function () {
    $g = gateScenario(20000);

    // ⚠️ THE LEDGER STILL SAYS THE OWNER IS OWED — the accrual is untouched, and that is deliberate.
    $accrued = OwnerLedgerAttribution::netPerCurrency(
        OwnerLedgerEntry::where('owner_id', $g['owner']->id)->get()
    );
    Assert::assertGreaterThan(0, $accrued['EUR'] ?? 0,
        'the fixture accrued nothing — this test would prove nothing about the gate');

    // …and the statement, which is what a payout is made FROM, claims none of it.
    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($g['owner']->id, '2026-09-01', '2026-09-30'));

    expect($statement->net_payable_minor)->toBe(0);
    expect($statement->gross_minor)->toBe(0);

    // ⚠️ AND THE MONEY IS NOT LOST — the rows stay unclaimed, for a later statement once the guest pays.
    expect(OwnerLedgerEntry::where('owner_id', $g['owner']->id)->whereNull('statement_id')->count())
        ->toBeGreaterThan(0);
});

it('⚠️ PROVES the gate is not simply refusing everything — the SAME booking, once PAID, pays in full', function () {
    // ⚠️ THE VACUITY CHECK FOR THE SPINE. "Pays nothing" passes identically on a gate that never releases
    // anything, on a broken fixture, or on a statement service that silently errors to zero. This forces the
    // positive against the same scenario, so the only difference is the payment.
    $g = gateScenario(20000);
    collectFolio($g['reservation']);

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($g['owner']->id, '2026-09-01', '2026-09-30'));

    expect($statement->gross_minor)->toBe(20000);
    expect($statement->net_payable_minor)->toBeGreaterThan(0);

    // ⚠️ δ = 0, TWO INDEPENDENT WAYS. (1) the statement's net equals the ledger net of the rows it claimed…
    $claimed = OwnerLedgerEntry::where('statement_id', $statement->id)->get();
    $ledgerNet = OwnerLedgerAttribution::netPerCurrency($claimed)['EUR'] ?? 0;
    Assert::assertSame($ledgerNet, (int) $statement->net_source_minor, 'statement net ≠ claimed ledger net');

    // …and (2) its component lines sum to the same figure, derived by a different path entirely.
    $lines = (int) $statement->lines()->where('line_type', '!=', 'net')->sum('amount_minor');
    Assert::assertSame((int) $statement->net_source_minor, $lines, 'component lines ≠ net');
});

it('⚠️ pays a LATER statement once the guest finally pays — held revenue is deferred, never lost', function () {
    $g = gateScenario(20000);
    $svc = app(OwnerStatementService::class);

    $sept = $svc->finalize($svc->generateDraft($g['owner']->id, '2026-09-01', '2026-09-30'));
    expect($sept->net_payable_minor)->toBe(0);

    collectFolio($g['reservation']);

    // ⚠️ Through the straggler path claimable() already had — no carry-forward mechanism was invented.
    $oct = $svc->finalize($svc->generateDraft($g['owner']->id, '2026-10-01', '2026-10-31'));

    // ⚠️ October claims the September rows as a PRIOR-PERIOD ADJUSTMENT, not as October gross — that is the
    // existing straggler behaviour (`buckets()` buckets anything before periodStart as an adjustment), and
    // asserting `gross_minor` here would have been asserting the wrong bucket.
    expect($oct->net_source_minor)->toBeGreaterThan(0);
    expect($oct->adjustments_minor)->toBeGreaterThan(0);
    expect(OwnerLedgerEntry::where('owner_id', $g['owner']->id)->whereNull('statement_id')->count())->toBe(0);
});

// ══ ⚠️ B · THE ASYMMETRY — hold the payment, never the clawback ════════════════════════════════════════

it('⚠️⚠️ never holds a group that REDUCES what we pay — a refund reverses even though the folio reads unpaid', function () {
    // ⚠️ THE BUG THIS PASS SHIPPED AND THEN CAUGHT. A refund drives the folio balance positive, so the
    // reservation reads "uncollected". A gate keyed on collection ALONE would hold the reversal — leaving the
    // owner credited for money handed back to the guest. Holding must apply only to what PAYS the owner.
    $g = gateScenario(20000);
    collectFolio($g['reservation']);

    $entries = OwnerLedgerEntry::where('owner_id', $g['owner']->id)->get();
    $split = OwnerCollection::split($entries);
    expect($split['uncollected'])->toBeEmpty();

    // ⚠️⚠️ ACTUALLY REFUND THE FOLIO, not just the ledger. The first draft reversed the ledger only, so the
    // folio stayed fully paid, the reservation still read "collected", and the mutation that removes the
    // asymmetry PASSED — the test never built the dangerous state it was written for (§N.4p).
    $folio = Folio::query()->where('reservation_id', $g['reservation']->getKey())->firstOrFail();
    $payment = Payment::query()->where('folio_id', $folio->id)->where('type', 'payment')->firstOrFail();
    app(FolioService::class)->refundPayment($payment, Money::of(20000, 'EUR'));

    // The folio must now read UNCOLLECTED — otherwise the assertions below prove nothing.
    Assert::assertFalse(OwnerCollection::reservationIsCollected((string) $g['reservation']->getKey()),
        'the folio still reads collected — this test cannot exercise the asymmetry');

    // …and the ledger reverses the booking wholesale, the mirror DEBIT rows a refund produces.
    app(OwnerAttributionService::class)->reverseReservation($g['reservation']->refresh());

    $after = OwnerLedgerEntry::where('owner_id', $g['owner']->id)->whereNull('statement_id')->get();
    $reversals = $after->filter(fn (OwnerLedgerEntry $e): bool => $e->reverses_id !== null);
    Assert::assertNotEmpty($reversals, 'no reversal rows — this test would prove nothing');

    // ⚠️ Every reversal must be RELEASABLE, whatever the folio now says.
    $splitAfter = OwnerCollection::split($after);
    $heldIds = array_map(fn (OwnerLedgerEntry $e): string => (string) $e->id, $splitAfter['uncollected']);

    foreach ($reversals as $reversal) {
        Assert::assertNotContains((string) $reversal->id, $heldIds,
            '⚠️ a REVERSAL is being held — the owner would keep money that was refunded to the guest');
    }
});

it('⚠️ never holds a non-reservation entry — holding a DEDUCTION would pay the owner MORE', function () {
    $g = gateScenario(20000);

    $expense = new OwnerLedgerEntry([
        'organization_id' => $g['owner']->organization_id,
        'owner_id' => $g['owner']->id,
        'entry_type' => 'expense', 'direction' => 'debit',
        'amount_minor' => 5000, 'currency' => 'EUR',
        'description' => 'Standalone expense', 'service_date' => '2026-09-05',
    ]);
    $expense->save();

    $split = OwnerCollection::split([$expense]);

    expect($split['collected'])->toHaveCount(1);
    expect($split['uncollected'])->toBeEmpty();
});

// ══ ⚠️ C · THE TWO PAYOUT DEFECTS ══════════════════════════════════════════════════════════════════════

it('⚠️ refuses a MANUAL payout run while PAYOUT_ENGINE_ENABLED is off', function () {
    // ⚠️ The flag gated the SCHEDULED command and the owner-facing display, but not this route — so the
    // engine read as "off" everywhere while one button still ran it.
    config(['services.payouts.engine_enabled' => false]);

    $g = gateScenario(20000);
    $operator = operatorUser($g['scenario'], role: 'Accountant');   // ⚠️ Hotel Manager does NOT hold payouts.manage; Accountant does

    $this->actingAs($operator)->post(route('app.payouts.run-now'))
        ->assertRedirect();

    expect(session('payout_error'))->toContain('PAYOUT_ENGINE_ENABLED');
    expect(Payout::query()->count())->toBe(0);
})->group('payout-gate');

it('⚠️⚠️ enforces one live payout per statement AT THE DATABASE, with the application check BYPASSED', function () {
    // ⚠️⚠️ THIS IS THE WHOLE POINT: a constraint the application enforces is not a constraint. The service
    // takes a row lock and re-checks — but any other writer (a job, a command, a backfill, a second entry
    // point) could still insert a second live payout for one statement, which is a DOUBLE PAYMENT.
    // So this writes RAW SQL, going around OwnerPayoutService entirely.
    $g = gateScenario(20000);
    collectFolio($g['reservation']);

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($g['owner']->id, '2026-09-01', '2026-09-30'));

    $payout = app(OwnerPayoutService::class)->createFromStatement($statement);
    Assert::assertNotNull($payout, 'no payout was created — the constraint test would be vacuous');
    Assert::assertContains($payout->status, Payout::LIVE_STATUSES);

    $raw = fn (): int => DB::table('payouts')->insertGetId([
        'id' => (string) Str::ulid(),
        'organization_id' => $payout->organization_id,
        'owner_id' => $payout->owner_id,
        'owner_statement_id' => $payout->owner_statement_id,
        'amount_minor' => $payout->amount_minor,
        'currency' => $payout->currency,
        'rail' => $payout->rail,
        'status' => 'pending',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    expect($raw)->toThrow(QueryException::class);

    expect(Payout::query()->where('owner_statement_id', $statement->id)
        ->whereIn('status', Payout::LIVE_STATUSES)->count())->toBe(1);
})->group('payout-gate');

it('⚠️ still allows many NON-live payouts per statement — the retry history must survive the constraint', function () {
    // ⚠️ THE VACUITY CHECK FOR THE CONSTRAINT. A unique index that refused every second row would pass the
    // test above while breaking `OwnerPayoutService::retry()` (DO3-3, which IS built). Failed rows must all
    // be permitted, so this asserts the constraint is narrow rather than merely present.
    $g = gateScenario(20000);
    collectFolio($g['reservation']);

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($g['owner']->id, '2026-09-01', '2026-09-30'));
    $payout = app(OwnerPayoutService::class)->createFromStatement($statement);

    foreach (range(1, 3) as $i) {
        DB::table('payouts')->insert([
            'id' => (string) Str::ulid(),
            'organization_id' => $payout->organization_id,
            'owner_id' => $payout->owner_id,
            'owner_statement_id' => $statement->id,
            'amount_minor' => $payout->amount_minor,
            'currency' => $payout->currency,
            'rail' => $payout->rail,
            'status' => 'failed',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    expect(DB::table('payouts')->where('owner_statement_id', $statement->id)->count())->toBe(4);
})->group('payout-gate');

it('⚠️ keeps the DB constraint and Payout::LIVE_STATUSES in agreement', function () {
    // ⚠️ The generated column spells LIVE_STATUSES as a SQL literal because it cannot call PHP — a second
    // expression of one rule (§N.8f). If the PHP constant changes without the migration, this reds.
    $sql = (string) DB::selectOne('SHOW CREATE TABLE payouts')->{'Create Table'};

    foreach (Payout::LIVE_STATUSES as $status) {
        Assert::assertStringContainsString("'".$status."'", $sql,
            "[{$status}] is a LIVE status in PHP but is not in the payouts generated-column constraint");
    }

    Assert::assertStringContainsString('payouts_one_live_per_statement', $sql,
        'the one-live-payout unique index is missing from the payouts table');
});

// ══ ⚠️ D · FINALISATION HAS A REAL CALLER ══════════════════════════════════════════════════════════════

it('⚠️ closes a period through the APP — finalisation was seeder-and-test-only until now', function () {
    // ⚠️ `finalize()`/`generateDraft()` had ZERO callers in app/ or routes/, and the payout engine pays ONLY
    // finalized statements — so a real deployment had nothing to pay. The demo looked complete because
    // DemoSeeder finalised statements the application itself could not.
    $g = gateScenario(20000);
    collectFolio($g['reservation']);
    $operator = operatorUser($g['scenario'], role: 'Accountant');

    $this->actingAs($operator)
        ->post(route('app.owners.close-period', ['owner' => $g['owner']->id]), [
            'period_start' => '2026-09-01', 'period_end' => '2026-09-30',
        ])->assertRedirect();

    $statement = OwnerStatement::query()->where('owner_id', $g['owner']->id)->firstOrFail();

    expect($statement->status)->toBe('finalized');
    expect((bool) $statement->locked)->toBeTrue();
    expect($statement->net_payable_minor)->toBeGreaterThan(0);

    // ⚠️ A finalised statement is a CLAIM ABOUT MONEY OWED, so it must reproduce from its own ledger rows.
    $claimed = OwnerLedgerEntry::where('statement_id', $statement->id)->get();
    Assert::assertSame(
        OwnerLedgerAttribution::netPerCurrency($claimed)['EUR'] ?? 0,
        (int) $statement->net_source_minor,
        'the finalised statement does not reconcile to the ledger rows it claimed (δ ≠ 0)',
    );
});

it('⚠️ closing a period pays NOTHING for an uncollected booking — the ordering, end to end through HTTP', function () {
    $g = gateScenario(20000);   // charged, NOT paid
    $operator = operatorUser($g['scenario'], role: 'Accountant');

    $this->actingAs($operator)
        ->post(route('app.owners.close-period', ['owner' => $g['owner']->id]), [
            'period_start' => '2026-09-01', 'period_end' => '2026-09-30',
        ])->assertRedirect();

    $statement = OwnerStatement::query()->where('owner_id', $g['owner']->id)->firstOrFail();

    expect($statement->status)->toBe('finalized');
    expect($statement->net_payable_minor)->toBe(0);
});
