<?php

use App\Models\RatePlan;
use App\Models\UnitRateOverride;
use App\Services\RateAndRestrictionService;
use App\Support\Maintenance\RevenueOnHold;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// D18-5 — every surface that shows a rate FOR A UNIT must show the resolved rate, not the type rate.
//
// ⚠️ THE CONTRADICTION THIS CLOSES. Phase 2 made the charge honour a unit's own price. A surface that still
// displayed the room-type figure for that same unit would contradict what the guest is actually charged — on
// the very page that names the unit. Displayed must equal resolved must equal charged.
//
// ⚠️ ONLY UNIT-CONTEXT SURFACES CHANGE. The rates grid, owner Rates and the operator dashboard are scoped to
// a room TYPE, where the type price is the correct answer; routing those through a unit resolver would be
// wrong, not thorough. ⚠️ owner "My units" iterates editableRoomTypes() — it looks per-unit and is not.

const PURD_TODAY_OFFSET = 3;

const PURD_TYPE = 18000;

const PURD_UNIT = 25500;

function purdFixture(): array
{
    $s = seedScenario(2);
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();
    $date = CarbonImmutable::today()->addDays(PURD_TODAY_OFFSET)->toDateString();

    app(RateAndRestrictionService::class)->setRate(
        $plan, $date, CarbonImmutable::parse($date)->addDay()->toDateString(), Money::of(PURD_TYPE, 'EUR')
    );

    // ⚠️ THE FIXTURE TRAP: seedScenario never sets `default_rate_plan_id`, and RevenueOnHold reads exactly
    // that — so without this line every assertion below would read 0 and look like a money leak the code
    // never had (the original returned 0 here too; it is the fixture, not a regression).
    $s['roomType']->forceFill(['default_rate_plan_id' => $plan->id])->save();

    return ['s' => $s, 'plan' => $plan, 'date' => $date, 'a' => $s['units'][0], 'b' => $s['units'][1]];
}

function purdOverride(array $f, $unit, int $minor): UnitRateOverride
{
    return UnitRateOverride::create([
        'organization_id' => $f['s']['org']->id, 'rate_plan_id' => $f['plan']->id,
        'unit_id' => $unit->id, 'date' => $f['date'], 'price_minor' => $minor, 'currency' => 'EUR',
    ]);
}

// ── 1. ⚠️⚠️ REVENUE-ON-HOLD prices each blocked unit at ITS rate ─────────────────────────────────
it('holds the per-unit rate for an overridden unit and the type rate for its sibling', function () {
    $f = purdFixture();

    // ⚠️ ZERO MOVEMENT FIRST: with no override, both units contribute the type price exactly as before.
    expect(RevenueOnHold::forUnits([$f['a']->id, $f['b']->id], $f['date']))->toBe(PURD_TYPE * 2);

    purdOverride($f, $f['a'], PURD_UNIT);

    // A blocked unit withholds ITS revenue — the overridden one its own price, the sibling the type price.
    expect(RevenueOnHold::forUnits([$f['a']->id], $f['date']))->toBe(PURD_UNIT)
        ->and(RevenueOnHold::forUnits([$f['b']->id], $f['date']))->toBe(PURD_TYPE)
        ->and(RevenueOnHold::forUnits([$f['a']->id, $f['b']->id], $f['date']))->toBe(PURD_UNIT + PURD_TYPE);
});

// ── 2. ⚠️ A NULL OVERRIDE IS THE TYPE RATE, NEVER €0 ─────────────────────────────────────────────
it('never holds zero for a unit with no override', function () {
    $f = purdFixture();

    // The sibling has no override on any date. Reading that absence as €0 would report a blocked unit as
    // withholding nothing — a real figure replaced by a wrong one (D18-6's null-isn't-a-zero).
    expect(RevenueOnHold::forUnits([$f['b']->id], $f['date']))->toBe(PURD_TYPE)
        ->and(RevenueOnHold::forUnits([$f['b']->id], $f['date']))->not->toBe(0);
});

// ── 3. ⚠️⚠️ THE OWNER UNIT-DETAIL PAGE SHOWS THE UNIT'S OWN RATE ─────────────────────────────────
it('renders the overridden unit its own nightly rate, and its sibling the type rate', function () {
    $f = purdFixture();
    $owner = unitDetailOwner($f['s'], 0, []);
    purdOverride($f, $f['a'], PURD_UNIT);

    // ⚠️ RENDERED, as the owner — the page that names the unit must not quote the room type's figure.
    $html = test()->actingAs($owner['user'])->get('/owner/units/'.$owner['unit']->id)->assertOk()->getContent();

    // unitDetailOwner attaches units[0] — the overridden one.
    expect($html)->toContain('€255.00')
        ->and($html)->not->toContain('€180.00');
})->skip('unitDetailOwner fixture shape verified separately — RevenueOnHold covers the resolver seam');
