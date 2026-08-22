<?php

use App\Models\Owner;
use App\Models\Payout;
use App\Models\TaxRate;
use App\Services\OwnerAttributionService;
use App\Services\OwnerPayoutService;
use App\Services\OwnerStatementService;
use App\Services\TaxRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave D · tax engine · Phase 3 — the WITHHOLDING engine. ⚠️ REAL money: a configured withholding rate reduces
// the owner's net payout, reconciling (#17). No rate → withholds nothing (genuine €0). Mirrors payment_fee.
// (ownUnit/postRoom/ownerNet from tests/Support/owner_helpers.php.)

/** A PT owner + a 20000 room stay attributed (pre-withholding net = 17000 at 15% commission). */
function withholdingOwner(array $s): Owner
{
    $owner = ownUnit($s['units'][0]);
    $owner->update(['tax_country' => 'PT']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

    return $owner->fresh();
}

// ── ⚠️ A configured withholding rate reduces the net + the payout, reconciling to the cent (#17) ──
it('withholds a configured rate from the payout, reconciling', function () {
    $s = seedScenario(1);
    $owner = withholdingOwner($s);
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_WITHHOLDING, 25.0, '2026-01-01');   // real 25%

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    // Pre-withholding net was 17000 (20000 − 3000 commission); 25% withheld = 4250; final net = 12750.
    expect($statement->withholding_minor)->toBe(-4250)
        ->and($statement->net_source_minor)->toBe(12750)
        ->and($statement->net_payable_minor)->toBe(12750);

    // The withholding is a REAL ledger line, so the ledger net reconciles too (#17).
    expect(ownerNet($owner->id))->toBe(12750);

    // A statement line surfaces it; component lines sum to net.
    $line = $statement->lines()->where('line_type', 'withholding')->first();
    expect($line)->not->toBeNull()->and($line->amount_minor)->toBe(-4250)
        ->and((int) $statement->lines()->where('line_type', '!=', 'net')->sum('amount_minor'))->toBe(12750);

    // ⚠️ THE MONEY EFFECT: the payout is the REDUCED net — the owner is paid 12750, not 17000.
    $payout = app(OwnerPayoutService::class)->createFromStatement($statement);
    expect($payout->amount_minor)->toBe(12750);
});

// ── No configured rate → withholds NOTHING (genuine €0, line omitted) — proven the OTHER state ────
it('withholds nothing when no rate is configured', function () {
    $s = seedScenario(1);
    $owner = withholdingOwner($s);   // PT, but NO withholding rate configured

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    expect($statement->withholding_minor)->toBe(0)
        ->and($statement->net_payable_minor)->toBe(17000)                       // full net, unchanged
        ->and($statement->lines()->where('line_type', 'withholding')->exists())->toBeFalse();  // no line

    expect(app(OwnerPayoutService::class)->createFromStatement($statement)->amount_minor)->toBe(17000);
});

// ── An EXAMPLE withholding rate still applies (test-mode-visible) but is the configured value ──────
it('applies whatever rate is configured (deterministic, from the config only)', function () {
    $s = seedScenario(1);
    $owner = withholdingOwner($s);
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_WITHHOLDING, 10.0, '2026-01-01');   // 10%

    $svc = app(OwnerStatementService::class);
    $statement = $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    // 17000 × 10% = 1700 withheld → net 15300. Exactly the configured rate × the real amount, nothing invented.
    expect($statement->withholding_minor)->toBe(-1700)
        ->and($statement->net_payable_minor)->toBe(15300);
});
