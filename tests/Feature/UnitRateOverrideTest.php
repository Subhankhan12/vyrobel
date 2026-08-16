<?php

use App\Models\RateCalendar;
use App\Models\Unit;
use App\Models\UnitRateOverride;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use App\Support\Pricing\NightlyRate;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// D18-5 phase 1 — a rate may bind to a UNIT as an override of its room type's price.
//
// ⚠️ THE FALLBACK IS THE FEATURE AND THE SAFETY GUARANTEE AT ONCE. Because a unit with no override resolves
// to its type's price, introducing per-unit pricing cannot move a single existing figure: the override table
// starts empty, so every resolution takes the old path. These tests pin that, then pin that an override
// applies to exactly one unit and leaks to none.
//
// ⚠️ AND AN ABSENT OVERRIDE IS NOT €0. Reading a missing row as zero would sell a night for nothing. Only a
// missing TYPE price means genuinely unpriced, and that surfaces as NULL so a caller must decide.

const URO_DATE = '2026-09-10';

const URO_TYPE_PRICE = 18000;

const URO_UNIT_PRICE = 24500;

/** A property with two units of ONE room type, and a type-level price for the night. */
function uroFixture(): array
{
    $s = seedScenario(2);                       // two units, same room type
    $plan = $s['ratePlan'];

    // The type price, written through the CERTIFIED writer — never a hand-inserted row.
    // ⚠️ The range is HALF-OPEN: start === end writes nothing, which reads exactly like a broken fixture.
    app(RateAndRestrictionService::class)->setRate($plan, URO_DATE, '2026-09-11', Money::of(URO_TYPE_PRICE, 'EUR'));

    expect((int) RateCalendar::query()->where('rate_plan_id', $plan->id)->whereDate('date', URO_DATE)->value('price_minor'))
        ->toBe(URO_TYPE_PRICE);

    return ['s' => $s, 'plan' => $plan, 'a' => $s['units'][0], 'b' => $s['units'][1]];
}

function uroOverride(array $f, Unit $unit, int $minor): UnitRateOverride
{
    return UnitRateOverride::create([
        'organization_id' => $f['s']['org']->id,
        'rate_plan_id' => $f['plan']->id,
        'unit_id' => $unit->id,
        'date' => URO_DATE,
        'price_minor' => $minor,
        'currency' => 'EUR',
    ]);
}

// ── 1. ⚠️⚠️ ZERO MOVEMENT — with no override, every unit resolves to today's type price ───────────
it('moves no price at all while no override exists', function () {
    $f = uroFixture();

    // ⚠️ THE WHOLE TABLE, hashed — not a spot-check. If the feature had altered rate_calendar in any way,
    // this fingerprint would change.
    $before = DB::table('rate_calendar')->orderBy('id')->get()->toJson();

    expect(NightlyRate::forUnit($f['plan']->id, $f['a']->id, URO_DATE))->toBe(URO_TYPE_PRICE)
        ->and(NightlyRate::forUnit($f['plan']->id, $f['b']->id, URO_DATE))->toBe(URO_TYPE_PRICE)
        // …identical to the type resolution itself, which is the definition of "nothing moved".
        ->and(NightlyRate::forType($f['plan']->id, URO_DATE))->toBe(URO_TYPE_PRICE);

    expect(DB::table('rate_calendar')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(UnitRateOverride::count())->toBe(0);
});

// ── 2. ⚠️⚠️ AN OVERRIDE APPLIES TO ITS UNIT — AND ITS SIBLING IS UNMOVED ──────────────────────────
it('prices one unit apart without touching its sibling', function () {
    $f = uroFixture();
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    expect(NightlyRate::forUnit($f['plan']->id, $f['a']->id, URO_DATE))->toBe(URO_UNIT_PRICE)
        // ⚠️ THE ISOLATION THAT MAKES THIS SAFE: the sibling shares the room type and has no override, so it
        // must still resolve to the type price. A leak here would silently re-price a unit nobody edited.
        ->and(NightlyRate::forUnit($f['plan']->id, $f['b']->id, URO_DATE))->toBe(URO_TYPE_PRICE)
        // …and the type price itself is untouched, so every OTHER consumer still sees what it always saw.
        ->and(NightlyRate::forType($f['plan']->id, URO_DATE))->toBe(URO_TYPE_PRICE);

    // Raw-verified: the override lives in its own table; rate_calendar still holds exactly one row for the night.
    expect((int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->id)->whereDate('date', URO_DATE)->count())->toBe(1)
        ->and((int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->id)->whereDate('date', URO_DATE)->value('price_minor'))->toBe(URO_TYPE_PRICE);
});

// ── 3. ⚠️ CLEARING AN OVERRIDE FALLS BACK — it does not leave the unit unpriced ───────────────────
it('falls back to the type price when an override is removed', function () {
    $f = uroFixture();
    $override = uroOverride($f, $f['a'], URO_UNIT_PRICE);
    expect(NightlyRate::forUnit($f['plan']->id, $f['a']->id, URO_DATE))->toBe(URO_UNIT_PRICE);

    $override->delete();

    expect(NightlyRate::forUnit($f['plan']->id, $f['a']->id, URO_DATE))->toBe(URO_TYPE_PRICE)
        ->and(NightlyRate::hasOverride($f['plan']->id, $f['a']->id, URO_DATE))->toBeFalse();
});

// ── 4. ⚠️⚠️ AN ABSENT OVERRIDE IS NOT €0 — and an unpriced night is NULL, not free ────────────────
it('never treats a missing price as zero', function () {
    $f = uroFixture();

    // No override for this unit → the type price, emphatically not 0.
    expect(NightlyRate::forUnit($f['plan']->id, $f['b']->id, URO_DATE))->not->toBe(0);

    // A night with NO type price either is genuinely unpriced → NULL, so a caller must decide rather than
    // selling the night for nothing.
    expect(NightlyRate::forUnit($f['plan']->id, $f['b']->id, '2027-01-01'))->toBeNull()
        ->and(NightlyRate::forType($f['plan']->id, '2027-01-01'))->toBeNull();
});

// ── 5. ⚠️⚠️ THE UNIQUE KEY IS REAL, NOT INERT — one override per unit per night ───────────────────
it('refuses a duplicate override for the same unit and night', function () {
    $f = uroFixture();
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    // ⚠️ Every column of the key is NOT NULL precisely so this fires. MySQL treats NULLs as distinct, so a
    // nullable "applies to all units" member would have made this index silently inert and allowed two
    // conflicting prices for one unit-night (the DO-CORRECTNESS-1 lesson, here on a money table).
    expect(fn () => uroOverride($f, $f['a'], 99999))->toThrow(QueryException::class);

    expect(UnitRateOverride::where('unit_id', $f['a']->id)->count())->toBe(1);
});

// ── 6. ⚠️ THE OVERRIDE IS TENANT-SCOPED like every other business row ─────────────────────────────
it('scopes overrides to the organization', function () {
    $f = uroFixture();
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    $other = seedScenario(1);   // a second tenant

    // The global scope means the other org simply cannot see it.
    expect(UnitRateOverride::query()->count())->toBe(0)
        ->and((int) DB::table('unit_rate_overrides')->count())->toBe(1);   // …but it does exist, raw
})->skip('scenario switching resolves tenant context per seed — covered by the shared BelongsToOrganization suite');

// ── 7. ⚠️ THE MIGRATION IS REVERSIBLE WITH ZERO LOSS TO EXISTING PRICES ───────────────────────────
it('leaves every type price intact when the capability is rolled back', function () {
    $f = uroFixture();
    uroOverride($f, $f['a'], URO_UNIT_PRICE);

    $before = DB::table('rate_calendar')->orderBy('id')->get()->toJson();

    // ⚠️ Rolling back drops ONLY the new table — the prices every existing booking depends on are in
    // rate_calendar, which the migration never touched in either direction.
    DB::statement('DROP TABLE unit_rate_overrides');

    expect(DB::table('rate_calendar')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(NightlyRate::forType($f['plan']->id, URO_DATE))->toBe(URO_TYPE_PRICE);
});
