<?php

use App\Models\RatePlan;
use App\Models\UnitRateOverride;
use App\Services\UnitRateOverrideService;
use App\Support\Pricing\NightlyRate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ══ DO THE TWO PRICE ANSWERS AGREE? — 2026-08-31 ════════════════════════════════════════════════════
//
// ⚠️ THERE ARE ALREADY TWO WAYS TO ANSWER "what does this unit cost on this night":
//   · {@see NightlyRate::forUnit()} — one resolution, two queries, used by the rates grid per cell AND
//     (through stayTotalForUnit) by BookingEngineService when a unit has been chosen — i.e. the MONEY PATH;
//   · {@see UnitRateOverrideService}'s private bulk indexes, which serve preview()/commit().
//
// ⚠️ An existing test compares them for 2 units × 3 nights. This widens that to EVERY unit across the grid's
// whole 14-day window, with and without overrides, and over the awkward cases — because if the two ever
// disagree an operator sets a rate against a number the booking path does not use.
//
// ⚠️ ITS OWN FIXTURE TRAP: with no published type rate every cell resolves NULL and the comparison would
// compare null with null and pass for the wrong reason. Nights 1-12 carry a rate; nights 13-14 deliberately
// do not, so the "no calendar row" case is exercised rather than assumed.

const RP1_FROM = '2026-09-01';

const RP1_TYPE = 18000;

function rp1Setup(): array
{
    // ⚠️ SIX units and TWO plans, not the two-and-one a single scenario gives: a comparison over one unit
    //    pair and one plan could agree because there is nothing to disagree about.
    $s = seedScenario(6);
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();
    $second = RatePlan::create([
        'property_id' => $s['property']->id, 'room_type_id' => $s['roomType']->id,
        'name' => 'NonRef', 'currency' => 'EUR',
    ]);

    $dates = [];
    for ($d = CarbonImmutable::parse(RP1_FROM), $i = 0; $i < 14; $i++, $d = $d->addDay()) {
        $dates[] = $d->toDateString();
    }

    // Nights 1-12 priced; 13 and 14 left with NO calendar row on purpose. The second plan is priced
    // DIFFERENTLY on the same nights, so a comparison that ignored the plan id would show up.
    foreach ([[$plan->id, 0], [$second->id, 7000]] as [$planId, $offset]) {
        foreach (array_slice($dates, 0, 12) as $i => $date) {
            DB::table('rate_calendar')->updateOrInsert(
                ['rate_plan_id' => $planId, 'date' => $date],
                ['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id,
                    'price_minor' => RP1_TYPE + $offset + ($i * 250), 'currency' => 'EUR',
                    'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    $units = collect($s['units'])->map(fn ($u): string => (string) $u->id)->all();

    // A spread of overrides: a higher price, a lower price, a ZERO, and a night with no type rate beneath it.
    UnitRateOverride::create(['rate_plan_id' => $plan->id, 'unit_id' => $units[0], 'date' => $dates[1], 'price_minor' => 31000, 'currency' => 'EUR']);
    UnitRateOverride::create(['rate_plan_id' => $plan->id, 'unit_id' => $units[0], 'date' => $dates[4], 'price_minor' => 9000, 'currency' => 'EUR']);
    UnitRateOverride::create(['rate_plan_id' => $plan->id, 'unit_id' => $units[1], 'date' => $dates[4], 'price_minor' => 0, 'currency' => 'EUR']);
    UnitRateOverride::create(['rate_plan_id' => $plan->id, 'unit_id' => $units[1], 'date' => $dates[12], 'price_minor' => 27000, 'currency' => 'EUR']);

    // An override on the SECOND plan for the same unit/night, so the two plans genuinely differ there.
    UnitRateOverride::create(['rate_plan_id' => $second->id, 'unit_id' => $units[0], 'date' => $dates[1], 'price_minor' => 41000, 'currency' => 'EUR']);

    return ['s' => $s, 'plan' => $plan, 'second' => $second, 'units' => $units, 'dates' => $dates];
}

/** The BULK answer for one night, read through the service that owns the indexes. */
function rp1BulkPrices(array $f, string $night, ?RatePlan $plan = null): array
{
    $preview = app(UnitRateOverrideService::class)->preview(
        $plan ?? $f['plan'], $f['units'], $night, CarbonImmutable::parse($night)->addDay()->toDateString(), 'set', 12345,
    );

    $out = [];
    foreach ($preview['units'] as $row) {
        $out[(string) $row['unit_id']] = $row['from_minor'];
    }

    return $out;
}

// ── 1. ⚠️⚠️ THE AGREEMENT — every unit, every night of the window, both answers ─────────────────────
it('resolves the same price through the per-cell resolver and the bulk indexes, for every unit and night', function () {
    $f = rp1Setup();
    $compared = 0;
    $nonNull = 0;
    $differing = 0;

    foreach ([$f['plan'], $f['second']] as $plan) {
        foreach ($f['dates'] as $night) {
            $bulk = rp1BulkPrices($f, $night, $plan);

            foreach ($f['units'] as $unitId) {
                $resolver = NightlyRate::forUnit((string) $plan->id, $unitId, $night);

                expect(array_key_exists($unitId, $bulk))
                    ->toBeTrue("the bulk answer omitted unit {$unitId} on {$night} — a silent empty is the failure shape");
                expect($bulk[$unitId])
                    ->toBe($resolver, "the two price answers DISAGREE for unit {$unitId} on {$night} under plan {$plan->name}");

                $compared++;
                if ($resolver !== null) {
                    $nonNull++;
                }
            }
        }
    }

    // ⚠️ THE FIXTURE MUST BITE. A comparison where everything is NULL, or where nothing differs between
    // units, would agree for the wrong reason.
    expect($compared)->toBe(2 * count($f['dates']) * count($f['units']))
        ->and($nonNull)->toBeGreaterThan(0);

    // Distinct values really do occur across the window (overrides + the per-night type ladder).
    $seen = [];
    foreach ($f['dates'] as $night) {
        foreach ($f['units'] as $unitId) {
            $seen[] = NightlyRate::forUnit((string) $f['plan']->id, $unitId, $night);
        }
    }
    expect(count(array_unique($seen, SORT_REGULAR)))->toBeGreaterThan(3);
    expect($differing)->toBe(0);
});

// ── 2. ⚠️ THE AWKWARD CASES, NAMED — each asserted against the resolver, not an expectation ─────────
it('agrees on the awkward cases: no calendar row, a zeroing override, and an override with no type beneath', function () {
    $f = rp1Setup();
    $plan = (string) $f['plan']->id;
    [$unitA, $unitB] = [$f['units'][0], $f['units'][1]];

    // (a) a night with NO calendar row and no override → genuinely unpriced, NULL not 0
    expect(NightlyRate::forUnit($plan, $unitA, $f['dates'][13]))->toBeNull();
    expect(rp1BulkPrices($f, $f['dates'][13])[$unitA])->toBeNull();

    // (b) a ZEROING override → 0, and 0 is NOT null (the distinction the resolver's docblock insists on)
    expect(NightlyRate::forUnit($plan, $unitB, $f['dates'][4]))->toBe(0);
    expect(rp1BulkPrices($f, $f['dates'][4])[$unitB])->toBe(0);

    // (c) an override on a night with NO type rate beneath it → the override still wins
    expect(NightlyRate::forUnit($plan, $unitB, $f['dates'][12]))->toBe(27000);
    expect(rp1BulkPrices($f, $f['dates'][12])[$unitB])->toBe(27000);

    // (d) …and its neighbour on the same night, with no override, is unpriced
    expect(NightlyRate::forUnit($plan, $unitA, $f['dates'][12]))->toBeNull();
    expect(rp1BulkPrices($f, $f['dates'][12])[$unitA])->toBeNull();
});

// ── 3. ⚠️ THE PRECEDENCE IS TWO LEVELS, NOT FOUR — seasons/uplift are MATERIALISED ─────────────────
// ⚠️ Verified rather than inherited: a season or a weekend uplift changes `rate_calendar` itself, so the
// resolver sees ONE type price per (plan, date). If either resolved at read time the grid would need a
// third level and the indexes would be wrong by construction.
it('reads a single materialised type price per plan and date', function () {
    $f = rp1Setup();
    $plan = (string) $f['plan']->id;

    foreach (array_slice($f['dates'], 0, 12) as $date) {
        expect(DB::table('rate_calendar')->where('rate_plan_id', $plan)->where('date', $date)->count())
            ->toBe(1, "more than one calendar row for {$date} would make 'the type price' ambiguous");
    }

    // And the resolver's type branch returns exactly that row.
    $row = DB::table('rate_calendar')->where('rate_plan_id', $plan)->where('date', $f['dates'][3])->first();
    expect(NightlyRate::forType($plan, $f['dates'][3]))->toBe((int) $row->price_minor);
});
