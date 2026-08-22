<?php

use App\Models\Owner;
use App\Models\TaxRate;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Services\OwnerVatService;
use App\Services\TaxRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave D · tax engine · Phase 2 — the VAT computation engine. ⚠️ INFORMATIONAL: computed from CONFIGURED rates ×
// REAL statement amounts, reconciling (#17); never invents a rate; never touches a payout. Honest states.
// (ownUnit/postRoom from tests/Support/owner_helpers.php.)

/** A VAT-registered PT owner with a finalized Sept statement (gross 20000, 15% commission → −3000). */
function vatOwner(array $s): Owner
{
    $owner = ownUnit($s['units'][0]);            // 15% commission
    $owner->update(['tax_country' => 'PT', 'vat_registered' => true]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    $svc = app(OwnerStatementService::class);
    $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return $owner->fresh();
}

// ── Computes output/input/net VAT from configured rates × real amounts, reconciling to the cent ────
it('computes VAT from configured rates and reconciles to the real amounts', function () {
    $s = seedScenario(1);
    $owner = vatOwner($s);
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_ACCOMMODATION_VAT, 6.0, '2026-01-01');   // real
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_SERVICE_VAT, 23.0, '2026-01-01');        // real

    $vat = app(OwnerVatService::class)->summary($owner, 2026);

    expect($vat['available'])->toBeTrue()
        ->and($vat['status'])->toBe('ok')
        ->and($vat['is_example'])->toBeFalse();

    // Sept is Q3. Output = 20000×6% = 1200; Input = 3000×23% = 690; Net = 510 — every figure = rate × real amount.
    $q3 = collect($vat['quarters'])->firstWhere('label', 'Q3');
    expect($q3['gross_minor'])->toBe(20000)
        ->and($q3['service_fee_minor'])->toBe(3000)
        ->and($q3['output_vat_minor'])->toBe(1200)
        ->and($q3['input_vat_minor'])->toBe(690)
        ->and($q3['net_vat_minor'])->toBe(510);

    // Annual reconciles to Σ quarters, to the cent.
    expect($vat['annual']['output_vat_minor'])->toBe(1200)
        ->and($vat['annual']['input_vat_minor'])->toBe(690)
        ->and($vat['annual']['net_vat_minor'])->toBe(510);
});

// ── An EXAMPLE rate computes but is FLAGGED (never silently authoritative / for filing) ────────────
it('flags a figure that rests on an example rate', function () {
    $s = seedScenario(1);
    $owner = vatOwner($s);
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_ACCOMMODATION_VAT, 6.0, '2026-01-01', isExample: true);

    $vat = app(OwnerVatService::class)->summary($owner, 2026);

    expect($vat['available'])->toBeTrue()
        ->and($vat['status'])->toBe('example')
        ->and($vat['is_example'])->toBeTrue()                       // ⚠️ the surface labels it "not for filing"
        ->and(collect($vat['quarters'])->firstWhere('label', 'Q3')['output_vat_minor'])->toBe(1200);
});

// ── No configured rate → honest "not configured" (NEVER a fabricated figure) ───────────────────────
it('returns an honest not-configured state when no rate exists', function () {
    $s = seedScenario(1);
    $owner = vatOwner($s);   // PT, VAT-registered, but NO rate configured

    $vat = app(OwnerVatService::class)->summary($owner, 2026);

    expect($vat['available'])->toBeFalse()
        ->and($vat['status'])->toBe('not_configured')
        ->and($vat['quarters'])->toBe([]);
});

// ── A non-VAT-registered owner → honest "not registered" (no figure) ──────────────────────────────
it('returns an honest not-registered state for a non-registered owner', function () {
    $s = seedScenario(1);
    $owner = vatOwner($s);
    $owner->update(['vat_registered' => false]);
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_ACCOMMODATION_VAT, 6.0, '2026-01-01');

    $vat = app(OwnerVatService::class)->summary($owner->fresh(), 2026);

    expect($vat['available'])->toBeFalse()->and($vat['status'])->toBe('not_registered');
});
