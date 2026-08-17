<?php

use App\Models\RatePlan;
use App\Models\UnitRateOverride;
use App\Models\User;
use App\Support\Pricing\NightlyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// D18-6 · THE THIRD OPTION — read-only unit rows under each room type, until "price individually".
//
// ⚠️ THE SHAPE IS THE SAFETY. A fully editable per-unit grid (the "Yes" fork) would mint an override on
// every edit, detach every unit from its room type, and leave the rack's "N units affected" warning reading
// "0 of N" everywhere — useless BY SHAPE, not by bug. So the unit rows here are DISPLAY: they show what each
// unit resolves to and offer one deliberate action. The type row remains the price-mover, which is what keeps
// per-unit pricing the exception the founder ruled it to be.
//
// ⚠️ A DISPLAY RE-SHAPE MUST MOVE NO MONEY, and that is asserted on the WHOLE table, not a sample.
//
// ⚠️ AND EVERY FIGURE MUST BE THE RESOLVER'S. If the grid read `rate_calendar` or `unit_rate_overrides`
// itself, its rows could disagree with what the booking paths charge — the D4-1 hazard. Test 4 pins the two
// together on a unit whose override makes them genuinely different numbers.

const RGU_FROM = '2026-09-01';

const RGU_TYPE_MINOR = 18000;

const RGU_UNIT_MINOR = 26500;

function rguSetup(): array
{
    $s = seedScenario(2);
    $user = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s, [], 'Revenue Manager');
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();

    // ⚠️ FIXTURE TRAP: without a published type rate every cell resolves NULL and every assertion below would
    // compare "—" with "—" and pass for the wrong reason.
    foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $d) {
        DB::table('rate_calendar')->updateOrInsert(
            ['rate_plan_id' => $plan->id, 'date' => $d],
            ['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id,
                'price_minor' => RGU_TYPE_MINOR, 'currency' => 'EUR', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    return ['s' => $s, 'user' => $user, 'plan' => $plan, 'session' => ['current_property_id' => $s['property']->id],
        'unitA' => (string) $s['units'][0]->id, 'unitB' => (string) $s['units'][1]->id];
}

function rguGrid(array $f): string
{
    return test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/rates?from='.RGU_FROM)->assertOk()->getContent();
}

/** ⚠️ The WHOLE rate table, as the database holds it — the zero-write baseline. */
function rguRateHash(): string
{
    return (string) DB::table('rate_calendar')->orderBy('id')
        ->get(['id', 'rate_plan_id', 'date', 'price_minor', 'currency'])->toJson();
}

// ── 1. ⚠️⚠️ A DISPLAY RE-SHAPE WRITES NOTHING — the whole table is byte-identical ─────────────────
it('renders the unit rows without moving a single rate', function () {
    $f = rguSetup();
    $before = rguRateHash();
    $overridesBefore = (int) DB::table('unit_rate_overrides')->count();

    $html = rguGrid($f);

    // The fixture is not vacuous — rows really rendered.
    expect($html)->toContain('vyr-unitrow');

    // ⚠️ RAW + WHOLE-TABLE: rendering the grid moved no price and minted no override.
    expect(rguRateHash())->toBe($before)
        ->and((int) DB::table('unit_rate_overrides')->count())->toBe($overridesBefore);
});

// ── 2. ⚠️⚠️ THE ROWS ARE READ-ONLY — there is no per-unit editor to mint an override with ─────────
it('offers no inline editor on a unit row, only the deliberate action', function () {
    $f = rguSetup();
    $html = rguGrid($f);

    // The type row keeps its editor — that is the default path and must be untouched.
    expect($html)->toContain('vyr-cellbtn');

    // ⚠️ …but every unit-row cell is static. Isolate the unit rows and prove no editor lives inside them:
    // an editable cell here IS the "Yes" fork, and it would hollow the safeguard silently.
    preg_match_all('/<tr class="vyr-unitrow">(.*?)<\/tr>/s', $html, $m);
    expect($m[1])->not->toBeEmpty();
    foreach ($m[1] as $rowHtml) {
        expect($rowHtml)->not->toContain('vyr-cellbtn')
            ->and($rowHtml)->not->toContain('<input')
            ->and($rowHtml)->toContain('vyr-cell-static');
    }

    // The one deliberate way in — the same single-set form the Units list opens.
    expect($html)->toContain('Price individually')
        ->and($html)->toContain('Price this unit individually')
        ->and($html)->toContain(route('app.rates.units.preview'));
});

// ── 3. ⚠️ NO OVERRIDE → THE ROOM-TYPE RATE, never €0 ─────────────────────────────────────────────
it('shows the room-type rate on a unit that has no price of its own', function () {
    $f = rguSetup();
    expect((int) DB::table('unit_rate_overrides')->count())->toBe(0);

    $html = rguGrid($f);

    // ⚠️ Both units resolve to the TYPE rate — an absent override is a fall-through, not a zero.
    expect($html)->toContain('€180.00')
        ->and($html)->not->toContain('€0.00');
});

// ── 4. ⚠️⚠️ THE FIGURES ARE THE RESOLVER'S — displayed = charged (D4-1) ──────────────────────────
it('shows each unit the rate NightlyRate resolves, not a rate read from the tables', function () {
    $f = rguSetup();
    foreach (['2026-09-01', '2026-09-02'] as $d) {
        UnitRateOverride::create(['organization_id' => $f['s']['org']->id, 'rate_plan_id' => $f['plan']->id,
            'unit_id' => $f['unitA'], 'date' => $d, 'price_minor' => RGU_UNIT_MINOR, 'currency' => 'EUR']);
    }

    // ⚠️ The two numbers are genuinely different, so a grid reading the type table would be visibly wrong.
    expect(NightlyRate::forUnit((string) $f['plan']->id, $f['unitA'], RGU_FROM))->toBe(RGU_UNIT_MINOR)
        ->and(NightlyRate::forUnit((string) $f['plan']->id, $f['unitB'], RGU_FROM))->toBe(RGU_TYPE_MINOR);

    $html = rguGrid($f);

    expect($html)->toContain('€265.00')      // the overridden unit shows ITS price…
        ->and($html)->toContain('€180.00')   // …its sibling still shows the type price
        ->and($html)->toContain('Priced individually');   // and the exception is marked as such
});

// ── 5. ⚠️⚠️ THE SAFEGUARD IS NOT HOLLOWED — the "Yes" failure, tested for ────────────────────────
it('keeps the blast-radius warning meaningful after the re-shape', function () {
    $f = rguSetup();

    // With nothing priced apart the warning is the plain count it has always been.
    expect(rguGrid($f))->toContain('2 units');

    UnitRateOverride::create(['organization_id' => $f['s']['org']->id, 'rate_plan_id' => $f['plan']->id,
        'unit_id' => $f['unitA'], 'date' => RGU_FROM, 'price_minor' => RGU_UNIT_MINOR, 'currency' => 'EUR']);

    // ⚠️ ONE unit priced apart → the warning EXCLUDES it and says so. On the "Yes" fork every unit would
    // carry an override and this would read "0 of 2" on every row — true, and useless. This assertion is
    // what makes the third option's whole justification checkable.
    expect(rguGrid($f))->toContain('1 of 2 units · 1 priced individually');
});

// ── 6. ⚠️ TYPE-ROW EDITING IS UNCHANGED — the default path still moves the type rate ─────────────
it('leaves the type-level rate write working exactly as before', function () {
    $f = rguSetup();

    test()->actingAs($f['user'])->withSession($f['session'])
        ->post(route('app.rates.update'), [
            'start_date' => RGU_FROM, 'end_date' => RGU_FROM,
            'room_type_id' => (string) $f['s']['roomType']->id,
            'set_rate' => '1', 'rate' => '199.00',
        ])->assertRedirect();

    // ⚠️ The TYPE price moved — and no override was minted doing it (that is the "Yes" hazard, absent here).
    expect((int) DB::table('rate_calendar')->where('rate_plan_id', $f['plan']->id)->where('date', RGU_FROM)->value('price_minor'))
        ->toBe(19900)
        ->and((int) DB::table('unit_rate_overrides')->count())->toBe(0);

    // …and both units follow it, because neither is priced apart.
    expect(NightlyRate::forUnit((string) $f['plan']->id, $f['unitA'], RGU_FROM))->toBe(19900)
        ->and(NightlyRate::forUnit((string) $f['plan']->id, $f['unitB'], RGU_FROM))->toBe(19900);
});
