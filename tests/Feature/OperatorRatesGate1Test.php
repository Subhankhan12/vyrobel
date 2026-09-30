<?php

use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Services\RateAndRestrictionService;
use App\Services\RateDerivationService;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// #100 GATE 1 — operator Rates: bulk %-adjust/set-exact (skip booked), the REAL derived-plan engine
// (base × adjustment reconciles), and the read-only guard (was missing). All via the certified writer.

function rateOn(RatePlan $plan, string $date): ?int
{
    return RateCalendar::query()->where('rate_plan_id', $plan->id)->whereDate('date', $date)->value('price_minor');
}

// ── BULK %-INCREASE adjusts each night's existing rate (via the certified per-night writer) ─────────
it('bulk-increases rates by a percentage across the range', function () {
    $s = seedScenario(1);
    $plan = $s['ratePlan'];
    $start = CarbonImmutable::today()->addDays(2);
    app(RateAndRestrictionService::class)->setRate($plan, $start->toDateString(), $start->addDays(3)->toDateString(), Money::of(20000, 'EUR'));

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.rates.bulk'), [
        'room_type_id' => $s['roomType']->id,
        'start_date' => $start->toDateString(),
        'end_date' => $start->addDays(2)->toDateString(),   // inclusive 3 nights
        'mode' => 'increase_pct',
        'amount' => '10',
        'nights' => 'whole',
    ])->assertRedirect()->assertSessionHas('rates_notice');

    // €200 + 10% = €220 on every night.
    expect(rateOn($plan, $start->toDateString()))->toBe(22000)
        ->and(rateOn($plan, $start->addDays(2)->toDateString()))->toBe(22000);
});

// ── BULK set-exact on WEEKENDS only leaves midweek untouched ────────────────────────────────────────
it('bulk set-exact applies to weekends only when the nights filter is weekends', function () {
    $s = seedScenario(1);
    $plan = $s['ratePlan'];
    // A fixed 14-day window from the next Monday so we have known weekdays.
    $monday = CarbonImmutable::today()->next(Carbon::MONDAY);
    app(RateAndRestrictionService::class)->setRate($plan, $monday->toDateString(), $monday->addDays(7)->toDateString(), Money::of(10000, 'EUR'));

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.rates.bulk'), [
        'room_type_id' => $s['roomType']->id,
        'start_date' => $monday->toDateString(),
        'end_date' => $monday->addDays(6)->toDateString(),
        'mode' => 'set_exact',
        'amount' => '150',
        'nights' => 'weekends',
    ])->assertRedirect();

    // Friday (Mon+4) + Saturday (Mon+5) become €150; Monday stays €100.
    expect(rateOn($plan, $monday->addDays(4)->toDateString()))->toBe(15000)   // Fri
        ->and(rateOn($plan, $monday->addDays(5)->toDateString()))->toBe(15000)   // Sat
        ->and(rateOn($plan, $monday->toDateString()))->toBe(10000);             // Mon untouched
});

// ── ⚠️ DERIVED PLANS recompute from base (base × adjustment, reconciling #17) ───────────────────────
it('recomputes derived plans from the base rate — reconciling', function () {
    $s = seedScenario(1);
    $base = $s['ratePlan'];
    $nonRef = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'name' => 'Non-refundable', 'code' => 'NR', 'currency' => 'EUR', 'is_refundable' => false, 'derived_from_rate_plan_id' => $base->id, 'adjustment' => ['pct' => -10]]);
    $weekly = RatePlan::create(['property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id, 'name' => 'Weekly', 'code' => 'WK', 'currency' => 'EUR', 'is_refundable' => true, 'derived_from_rate_plan_id' => $base->id, 'adjustment' => ['pct' => -15]]);

    $start = CarbonImmutable::today()->addDays(2);
    // Write the base + recompute derived via the cell-edit path (update_derived).
    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.rates.update'), [
        'room_type_id' => $s['roomType']->id,
        'start_date' => $start->toDateString(),
        'end_date' => $start->addDays(2)->toDateString(),
        'set_rate' => '1',
        'rate' => '200',
        'update_derived' => '1',
    ])->assertRedirect();

    $d = $start->toDateString();
    expect(rateOn($base, $d))->toBe(20000)
        ->and(rateOn($nonRef, $d))->toBe(18000)   // −10%
        ->and(rateOn($weekly, $d))->toBe(17000);  // −15%

    // And a direct service recompute reconciles too.
    app(RateDerivationService::class)->recomputeDerived($base, $start, $start->addDays(3));
    expect(rateOn($nonRef, $d))->toBe(18000);
});

// ── READ-ONLY guard — a paused workspace refuses a rate write (was missing) ─────────────────────────
it('refuses a rate write when the workspace is read-only', function () {
    $s = seedScenario(1);
    $s['org']->update(['paused_until' => Carbon::now()->addDays(5)]);
    $plan = $s['ratePlan'];
    $start = CarbonImmutable::today()->addDays(2);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))->post(route('app.rates.update'), [
        'room_type_id' => $s['roomType']->id,
        'start_date' => $start->toDateString(),
        'end_date' => $start->toDateString(),
        'set_rate' => '1',
        'rate' => '999',
        // ⚠️ 2026-10-01 — a refusal is an ALERT (`rates_error`), no longer a notice.
    ])->assertRedirect()->assertSessionHas('rates_error', 'Your workspace is read-only right now — rate changes are paused.');

    expect(rateOn($plan, $start->toDateString()))->toBeNull();   // nothing written
});
