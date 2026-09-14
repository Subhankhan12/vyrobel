<?php

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\TaxRate;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

/**
 * ⚠️⚠️ STATEMENT FINALIZATION — the third irreversible path, pinned after being executed for the first
 * time (FIX 21, 2026-09-14, in tenant two). Phase 7 established it by reading and refused: it throws if
 * already locked, has NO REOPEN, and calls `WithholdingService::apply` before claiming ledger rows.
 *
 * ⚠️⚠️ THE WRONG-FIX MUTATION IS WHY THIS ASSERTS THE LEDGER. `locked = true` is set unconditionally after
 * the projection, so "the statement was finalized" passes while NOT ONE ledger row moved — the identical
 * shape FIX 20 proved on `applied_at`. Every assertion below reads `owner_ledger`.
 */
function f21Owner(array $s): Owner
{
    $owner = Owner::create([
        'type' => 'company', 'name' => 'Statement Probe Ltd',
        'email' => 'owner+'.uniqid().'@probe.test',
        'payout_currency' => 'EUR', 'tax_country' => 'PT', 'status' => 'active',
    ]);
    ManagementAgreement::create([
        'owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 20.00,
        'payout_currency' => 'EUR', 'payout_schedule' => 'monthly', 'term_months' => 12,
        'notice_days' => 60, 'auto_renews' => true, 'effective_from' => '2026-01-01', 'status' => 'active',
    ]);
    $s['units'][0]->forceFill(['owner_id' => $owner->id])->save();

    return $owner;
}

/** A period with real money in it: 10000 revenue less 20% commission = 8000 net. */
function f21Ledger(Owner $owner, array $s): void
{
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);
    $folios->postCharge($folio, 'room', 'Room', Money::of(10000, 'EUR'));
    $folio->refresh();
    $folios->recordPayment($folio, Money::of(10000, 'EUR'), 'tok');
    app(OwnerAttributionService::class)->attributeReservation($res);
}

it('⚠️⚠️ finalizing CLAIMS the ledger and posts withholding — not merely sets a lock', function () {
    $s = seedScenario(1);
    $owner = f21Owner($s);
    f21Ledger($owner, $s);

    // A configured withholding rate for the owner's jurisdiction. 🔬 FIX 21 measured that the rate is
    // CONFIGURED, never hardcoded, and that the currency is taken from the claimable lines.
    TaxRate::create([
        'jurisdiction' => 'PT', 'rate_type' => TaxRate::TYPE_WITHHOLDING, 'percent' => 20.000,
        'effective_from' => '2026-01-01', 'is_example' => true, 'note' => 'probe',
    ]);

    $svc = app(OwnerStatementService::class);
    $stmt = $svc->generateDraft((string) $owner->id, '2026-09-01', '2026-09-30');

    // CONTROL: the period really has money in it, or "nothing was claimed" proves nothing.
    $preNet = (int) OwnerLedgerEntry::query()->where('owner_id', $owner->id)
        ->get()->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
    expect($preNet)->toBe(8000);
    expect(OwnerLedgerEntry::query()->where('owner_id', $owner->id)->whereNotNull('statement_id')->count())->toBe(0);

    $svc->finalize($stmt, null);
    $stmt->refresh();

    // ── ① THE WITHHOLDING ROW — a DEBIT of exactly 20% of the pre-withholding net.
    $wh = OwnerLedgerEntry::query()->where('owner_id', $owner->id)
        ->where('entry_type', TaxRate::TYPE_WITHHOLDING)->get();
    expect($wh)->toHaveCount(1);
    expect((string) $wh->first()->direction)->toBe('debit')
        ->and((int) $wh->first()->amount_minor)->toBe(1600);   // round(8000 x 20%)

    // ── ② THE CLAIM — every projected line stamped to exactly this statement.
    expect(OwnerLedgerEntry::query()->where('owner_id', $owner->id)->whereNull('statement_id')->count())
        ->toBe(0, 'a line was left unclaimed by a finalized statement');

    // ── ③ THE PARTS EQUAL THE WHOLE.
    $claimed = (int) OwnerLedgerEntry::query()->where('statement_id', $stmt->id)
        ->get()->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
    expect($claimed)->toBe((int) $stmt->net_payable_minor)
        ->and($claimed)->toBe(6400);                            // 8000 - 1600
});

it('⚠️ a second finalize THROWS — there is no reopen', function () {
    $s = seedScenario(1);
    $owner = f21Owner($s);
    f21Ledger($owner, $s);

    $svc = app(OwnerStatementService::class);
    $stmt = $svc->generateDraft((string) $owner->id, '2026-09-01', '2026-09-30');
    $svc->finalize($stmt, null);

    expect(fn () => $svc->finalize($stmt->refresh(), null))->toThrow(RuntimeException::class);
});

it('⚠️ no configured rate means NO withholding row — not a zero row', function () {
    $s = seedScenario(1);
    $owner = f21Owner($s);       // tax_country PT, but no TaxRate configured in this test
    f21Ledger($owner, $s);

    $svc = app(OwnerStatementService::class);
    $stmt = $svc->generateDraft((string) $owner->id, '2026-09-01', '2026-09-30');
    $svc->finalize($stmt, null);

    // FIX 18 found the absorbed-refund branch wrote NO row rather than a zero row; withholding matches it.
    expect(OwnerLedgerEntry::query()->where('owner_id', $owner->id)
        ->where('entry_type', TaxRate::TYPE_WITHHOLDING)->count())->toBe(0);
    expect((int) $stmt->refresh()->net_payable_minor)->toBe(8000);
});
