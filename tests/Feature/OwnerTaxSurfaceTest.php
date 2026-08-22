<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use App\Services\TaxRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave D · tax engine · Phase 4 — the surfaces (Reports VAT + Tax Center + VAT-return CSV), honestly labelled.

/** A VAT-registered PT owner (portal user) with a finalized Sept statement (gross 20000). */
function taxSurfaceOwner(array $s): array
{
    $user = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR', 'tax_country' => 'PT', 'vat_registered' => true]);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01']);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)
    $svc = app(OwnerStatementService::class);
    $svc->finalize($svc->generateDraft($owner->id, '2026-09-01', '2026-09-30'));

    return [$user, $owner];
}

// ── Reports shows the REAL computed VAT, honestly labelled; an example rate is flagged ────────────
it('renders the computed VAT on Reports, flagged when it rests on example rates', function () {
    $s = seedScenario(1);
    [$user] = taxSurfaceOwner($s);
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_ACCOMMODATION_VAT, 6.0, '2026-01-01', isExample: true);
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_SERVICE_VAT, 23.0, '2026-01-01', isExample: true);

    $this->actingAs($user)->get('/owner/reports?year=2026')
        ->assertOk()
        ->assertSee('Tax &amp; VAT summary', false)
        ->assertSee('Output VAT')
        ->assertSee('Based on EXAMPLE rates')                 // ⚠️ flagged
        ->assertSee('not for filing')
        ->assertSee('not tax advice');                        // the honest label
});

// ── Reports shows the honest not-configured state when no rate exists ─────────────────────────────
it('renders the honest not-configured VAT state when no rate is set', function () {
    $s = seedScenario(1);
    [$user] = taxSurfaceOwner($s);   // registered, but no rate configured

    $this->actingAs($user)->get('/owner/reports?year=2026')
        ->assertOk()
        ->assertSee('aren’t configured for your jurisdiction', false)
        ->assertDontSee('Output VAT');                        // no fabricated figure
});

// ── The VAT-return CSV downloads the real figures with the honest header ──────────────────────────
it('exports a VAT-return CSV with the honest header, or 404 when unavailable', function () {
    $s = seedScenario(1);
    [$user] = taxSurfaceOwner($s);

    // No rate → 404 (never a fabricated export).
    $this->actingAs($user)->get('/owner/tax/2026/vat-return.csv')->assertNotFound();

    // A real rate → the CSV, with the honest labels.
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_ACCOMMODATION_VAT, 6.0, '2026-01-01');
    $body = $this->actingAs($user)->get('/owner/tax/2026/vat-return.csv')->assertOk()->streamedContent();
    expect($body)->toContain('verify with your accountant')->toContain('Output VAT');
});

// ── The Tax Center withholding card shows the REAL withheld figure when configured ────────────────
it('shows the real withheld total on the Tax Center when withholding is configured', function () {
    $s = seedScenario(1);
    app(TaxRateService::class)->setRate('PT', TaxRate::TYPE_WITHHOLDING, 25.0, '2026-01-01');   // before finalize
    [$user, $owner] = taxSurfaceOwner($s);   // finalizes → 25% of 17000 = 4250 withheld

    $this->actingAs($user)->get('/owner/tax')
        ->assertOk()
        ->assertSee('Withholding tax')
        ->assertSee('42.50')                                  // €42.50 withheld (4250 minor)
        ->assertDontSee('No withholding is configured');
});
