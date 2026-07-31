<?php

use App\Models\Tax;
use App\Support\Folio\StayEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 2c — the VAT Incl./Added toggle was rendered read-only though it drives folio math (an inclusive
// percentage tax is informational, an exclusive one is charged). It now PERSISTS, and flipping it flips the rule
// the folio uses (StayEstimator::taxApplies) — proven both ways.

it('persists the VAT Incl./Added toggle and it flips the folio-charging rule', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Property Owner');   // holds properties.manage
    $session = ['current_property_id' => $s['property']->id];

    $vat = Tax::create(['property_id' => $s['property']->id, 'name' => 'VAT', 'kind' => 'vat', 'mode' => 'percentage', 'rate' => 10.0, 'inclusive' => false]);
    expect(StayEstimator::taxApplies($vat, 'EUR'))->toBeTrue();   // exclusive → charged

    // Toggle to inclusive.
    $this->actingAs($manager)->withSession($session)
        ->post(route('app.settings.tax-fees'), ['taxes' => [$vat->id => 10], 'tax_inclusive' => [$vat->id => 1]])
        ->assertRedirect();

    $vat->refresh();
    expect((bool) $vat->inclusive)->toBeTrue()
        ->and(StayEstimator::taxApplies($vat, 'EUR'))->toBeFalse();   // inclusive → NOT charged (informational)

    // Toggle back to added.
    $this->actingAs($manager)->withSession($session)
        ->post(route('app.settings.tax-fees'), ['taxes' => [$vat->id => 10], 'tax_inclusive' => [$vat->id => 0]])
        ->assertRedirect();

    $vat->refresh();
    expect((bool) $vat->inclusive)->toBeFalse()
        ->and(StayEstimator::taxApplies($vat, 'EUR'))->toBeTrue();
});

it('leaves a non-percentage tax untouched by the inclusive toggle', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Property Owner');
    $city = Tax::create(['property_id' => $s['property']->id, 'name' => 'City tax', 'kind' => 'city_tax', 'mode' => 'per_person_night', 'amount_minor' => 200, 'currency' => 'EUR']);

    // Even if a tampered request sends tax_inclusive for a per-night tax, the service ignores it (no such concept).
    $this->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->post(route('app.settings.tax-fees'), ['taxes' => [$city->id => 3], 'tax_inclusive' => [$city->id => 1]])
        ->assertRedirect();

    expect((bool) $city->refresh()->inclusive)->toBeFalse()
        ->and((int) $city->amount_minor)->toBe(300);   // the amount DID update (3.00)
});
