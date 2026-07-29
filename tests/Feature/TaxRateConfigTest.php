<?php

use App\Models\TaxRate;
use App\Services\TaxRateService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Wave D · tax engine · Phase 1 — the rate-CONFIGURATION mechanism. ⚠️ Rates are CONFIGURED, never invented:
// the engine reads a configured value or gets null (honest "not configured"); a real rate wins over an example.

// ── An unconfigured jurisdiction returns NULL (the honest "not configured" state — never a fabricated rate) ──
it('returns null for a jurisdiction/type with no configured rate', function () {
    expect(app(TaxRateService::class)->rateFor('PT', TaxRate::TYPE_ACCOMMODATION_VAT))->toBeNull();
});

// ── A configured rate is returned; effective-dating is respected ──────────────────────────────────
it('returns the effective configured rate and respects the date window', function () {
    $svc = app(TaxRateService::class);
    $svc->setRate('PT', TaxRate::TYPE_ACCOMMODATION_VAT, 6.0, '2026-01-01', effectiveTo: '2026-12-31');

    expect($svc->rateFor('PT', TaxRate::TYPE_ACCOMMODATION_VAT, CarbonImmutable::parse('2026-06-01'))?->percentFloat())->toBe(6.0)
        ->and($svc->rateFor('PT', TaxRate::TYPE_ACCOMMODATION_VAT, CarbonImmutable::parse('2025-06-01')))->toBeNull()   // before effective_from
        ->and($svc->rateFor('PT', TaxRate::TYPE_ACCOMMODATION_VAT, CarbonImmutable::parse('2027-06-01')))->toBeNull();  // after effective_to
});

// ── A REAL rate (is_example=false) wins over an EXAMPLE for the same jurisdiction/date ─────────────
it('prefers a real configured rate over a seeded example', function () {
    $svc = app(TaxRateService::class);
    $svc->setRate('PT', TaxRate::TYPE_SERVICE_VAT, 23.0, '2026-01-01', isExample: true, note: 'EXAMPLE');   // example
    $svc->setRate('PT', TaxRate::TYPE_SERVICE_VAT, 22.0, '2026-01-01', isExample: false);                   // real (founder-set)

    $rate = $svc->rateFor('PT', TaxRate::TYPE_SERVICE_VAT, CarbonImmutable::parse('2026-06-01'));
    expect($rate?->percentFloat())->toBe(22.0)          // the REAL rate wins
        ->and($rate?->is_example)->toBeFalse();
});

// ── An example rate is returned but FLAGGED (never silently authoritative) ─────────────────────────
it('returns an example rate flagged as example when no real rate exists', function () {
    $svc = app(TaxRateService::class);
    $svc->setRate('PT', TaxRate::TYPE_SERVICE_VAT, 23.0, '2026-01-01', isExample: true);

    $rate = $svc->rateFor('PT', TaxRate::TYPE_SERVICE_VAT, CarbonImmutable::parse('2026-06-01'));
    expect($rate?->percentFloat())->toBe(23.0)
        ->and($rate?->is_example)->toBeTrue();          // ⚠️ flagged — the surface must label it "not for filing"
});

// ── The admin command configures a REAL rate (the founder's mechanism) ─────────────────────────────
it('configures a real rate through the tax:set-rate command', function () {
    $this->artisan('tax:set-rate', ['jurisdiction' => 'ES', 'type' => 'withholding', 'percent' => '19', '--from' => '2026-01-01'])
        ->assertExitCode(0);

    $rate = app(TaxRateService::class)->rateFor('ES', TaxRate::TYPE_WITHHOLDING, CarbonImmutable::parse('2026-06-01'));
    expect($rate?->percentFloat())->toBe(19.0)
        ->and($rate?->is_example)->toBeFalse();          // the command sets a REAL (authoritative) rate

    // An invalid type is refused (never silently stored).
    $this->artisan('tax:set-rate', ['jurisdiction' => 'ES', 'type' => 'made_up', 'percent' => '10'])->assertExitCode(1);
});
