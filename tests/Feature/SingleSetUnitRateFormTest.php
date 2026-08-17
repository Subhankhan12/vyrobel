<?php

use App\Models\RatePlan;
use App\Models\User;
use App\Services\UnitRateOverrideService;
use App\Support\Pricing\NightlyRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// D18-5 — THE SINGLE-SET AUTHORING UI, the N=1 caller of the proven writer.
//
// ⚠️ THE MONEY IS ALREADY PROVEN AT THE SERVICE (item 21): preview→commit with a re-computed digest, atomic,
// clear→type-rate, folio-verified, five mutations. This file does NOT re-prove that. It proves the thing a
// new SURFACE can get wrong: that the form ROUTES to that service instead of becoming a second writer, and
// that a price set from this surface is the price that actually charges.
//
// ⚠️ SINGLE-SET IS N=1, NOT A SECOND FEATURE — one `unit_ids[]` value instead of several, through the same
// routes, the same service call and the same money path. A separate write path here would be exactly the
// two-disagreeing-sources hazard the service was built to prevent (D4-1).
//
// ⚠️ THE UNIT MUST BE NAMED ON THE FORM. The bulk pass shipped a money screen whose rows were blank because
// units carry a `code` and a NULL `name`, and thirteen green tests never noticed — it took rendering. So the
// label is asserted here, against the unit's REAL code.

const SSU_FROM = '2026-09-01';

const SSU_TO = '2026-09-03';            // inclusive → 3 nights

const SSU_TYPE_MINOR = 18000;

const SSU_SET_MINOR = 24500;

/** An operator who may price, on a room type with two units and a real published type rate. */
function ssuSetup(): array
{
    $s = seedScenario(2);
    $user = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s, [], 'Revenue Manager');
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();

    // ⚠️ FIXTURE TRAP: seedScenario publishes no price for this window, and every "from → to" figure would be
    // NULL — the assertions would compare nulls and pass for the wrong reason.
    foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $d) {
        DB::table('rate_calendar')->updateOrInsert(
            ['rate_plan_id' => $plan->id, 'date' => $d],
            ['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id,
                'price_minor' => SSU_TYPE_MINOR, 'currency' => 'EUR', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    return ['s' => $s, 'user' => $user, 'plan' => $plan, 'session' => ['current_property_id' => $s['property']->id],
        'unitA' => (string) $s['units'][0]->id, 'unitB' => (string) $s['units'][1]->id];
}

/** Drive the FORM exactly as the rendered markup posts it — one unit, the N=1 call. */
function ssuSubmit(array $f, string $unitId, string $mode, string $amount = '245.00'): array
{
    $body = [
        'start_date' => SSU_FROM, 'end_date' => SSU_TO,
        'room_type_id' => (string) $f['s']['roomType']->id,
        'unit_ids' => [$unitId], 'mode' => $mode, 'amount' => $amount,
    ];

    test()->actingAs($f['user'])->withSession($f['session'])
        ->post(route('app.rates.units.preview'), $body)->assertRedirect();

    $preview = session('unit_rate_preview');

    if ($preview !== null) {
        test()->actingAs($f['user'])->withSession($f['session'])
            ->post(route('app.rates.units.apply'), $body + ['digest' => $preview['digest']])->assertRedirect();
    }

    return $preview ?? [];
}

// ── 1. ⚠️⚠️ A SET FROM THE FORM REACHES THE MONEY — and only that unit ────────────────────────────
it('prices one unit from the form, and the resolver charges that price', function () {
    $f = ssuSetup();

    $preview = ssuSubmit($f, $f['unitA'], 'set');

    // The preview named exactly one unit and the real before/after figures (the approval gate, reused).
    expect(count($preview['units']))->toBe(1)
        ->and($preview['units'][0]['from_minor'])->toBe(SSU_TYPE_MINOR)
        ->and($preview['units'][0]['to_minor'])->toBe(SSU_SET_MINOR);

    // ⚠️ RAW: three nights written for THIS unit and nothing for its sibling.
    expect((int) DB::table('unit_rate_overrides')->where('unit_id', $f['unitA'])->count())->toBe(3)
        ->and((int) DB::table('unit_rate_overrides')->where('unit_id', $f['unitB'])->count())->toBe(0);

    // ⚠️ AND THE MONEY: the resolver — the only thing that charges — now answers with the set price for A,
    // and is byte-identical to today for B. Zero movement is a fact about the untouched unit, not a hope.
    expect(NightlyRate::forUnit((string) $f['plan']->id, $f['unitA'], SSU_FROM))->toBe(SSU_SET_MINOR)
        ->and(NightlyRate::forUnit((string) $f['plan']->id, $f['unitB'], SSU_FROM))->toBe(SSU_TYPE_MINOR);
});

// ── 2. ⚠️⚠️ CLEAR FROM THE FORM → THE TYPE RATE, NEVER €0 ─────────────────────────────────────────
it('clears one unit from the form back to the room-type rate', function () {
    $f = ssuSetup();
    ssuSubmit($f, $f['unitA'], 'set');
    expect((int) DB::table('unit_rate_overrides')->where('unit_id', $f['unitA'])->count())->toBe(3);

    $preview = ssuSubmit($f, $f['unitA'], 'clear');

    // ⚠️ The preview shows the REAL landing figure — the type rate — not the word "cleared" and not €0.
    expect($preview['units'][0]['to_minor'])->toBe(SSU_TYPE_MINOR);

    // ⚠️ RAW: the rows are GONE, not zeroed — a €0 row would sell the night for nothing.
    expect((int) DB::table('unit_rate_overrides')->where('unit_id', $f['unitA'])->count())->toBe(0)
        ->and((int) DB::table('unit_rate_overrides')->where('price_minor', 0)->count())->toBe(0)
        ->and(NightlyRate::forUnit((string) $f['plan']->id, $f['unitA'], SSU_FROM))->toBe(SSU_TYPE_MINOR);
});

// ── 3. ⚠️⚠️ THE FORM ROUTES TO THE SERVICE — it is not a second writer ────────────────────────────
it('writes only through UnitRateOverrideService, never directly', function () {
    $f = ssuSetup();

    // ⚠️ THE SERVICE IS `final`, SO IT CANNOT BE SUBCLASSED OR MOCKED — the first draft of this test tried and
    // died with no output at all (a fatal, not a failure). So the proof is inverted instead: MAKE THE SERVICE
    // UNRESOLVABLE. If the surface writes `unit_rate_overrides` itself, the request sails through and rows
    // appear; if it genuinely delegates, it cannot proceed and writes nothing. That distinction is exactly
    // "no second writer" (D4-1), and it needs no test double.
    app()->bind(UnitRateOverrideService::class, function (): never {
        throw new RuntimeException('the surface must go through the service');
    });

    $body = [
        'start_date' => SSU_FROM, 'end_date' => SSU_TO,
        'room_type_id' => (string) $f['s']['roomType']->id,
        'unit_ids' => [$f['unitA']], 'mode' => 'set', 'amount' => '245.00',
        'digest' => str_repeat('a', 64),
    ];

    $reached = false;
    try {
        test()->withoutExceptionHandling()->actingAs($f['user'])->withSession($f['session'])
            ->post(route('app.rates.units.apply'), $body);
    } catch (RuntimeException $e) {
        $reached = $e->getMessage() === 'the surface must go through the service';
    }

    // ⚠️ The surface DID ask for the service (so it delegates)…
    expect($reached)->toBeTrue()
        // …and with the service unavailable NOTHING was written — there is no other path to the table.
        ->and((int) DB::table('unit_rate_overrides')->count())->toBe(0);
});

// ── 4. ⚠️ RENDERED — the form exists on the Units list and NAMES the unit it prices ────────────────
it('renders the single-set form on the units list with the unit named', function () {
    $f = ssuSetup();

    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/units')->assertOk()->getContent();

    // ⚠️ DERIVED FROM THE REAL ROW, never hand-typed — the unit's own code, which is what it is labelled by.
    $code = (string) DB::table('units')->where('id', $f['unitA'])->value('code');
    expect($code)->not->toBeEmpty();

    expect($html)->toContain('Price individually')
        ->toContain('Price this unit individually')
        // the N=1 payload: this unit, pre-selected
        ->toContain('name="unit_ids[]" value="'.$f['unitA'].'"')
        // ⚠️ THE LABEL — the blank-rows lesson. A money form must say which unit it is about.
        ->toContain('<strong>'.$code.'</strong>')
        // …and it posts to the SAME proven route the grid uses — no second endpoint.
        ->toContain(route('app.rates.units.preview'));
});

// ── 5. ⚠️ THE SAFEGUARD FOLLOWS — a single set changes what a type-level save reaches ──────────────
it('updates the rack blast-radius label after a single-unit set', function () {
    $f = ssuSetup();

    $before = test()->actingAs($f['user'])->withSession($f['session'])->get('/app/rates?from='.SSU_FROM)->getContent();
    expect($before)->toContain('2 units');

    ssuSubmit($f, $f['unitA'], 'set');

    // Reads the same table the form wrote — so it cannot drift, and nobody maintains a second count.
    expect(test()->actingAs($f['user'])->withSession($f['session'])->get('/app/rates?from='.SSU_FROM)->getContent())
        ->toContain('1 of 2 units · 1 priced individually');
});

// ── 6. ⚠️ RBAC — pricing is rates.manage, and the control is hidden from those without it (#30) ────
it('hides the control and refuses the route without rates.manage', function () {
    $f = ssuSetup();
    $desk = operatorUser($f['s'], [], 'Front Desk');

    $html = test()->actingAs($desk)->withSession($f['session'])->get('/app/units')->assertOk()->getContent();
    expect($html)->not->toContain('Price individually');

    test()->actingAs($desk)->withSession($f['session'])
        ->post(route('app.rates.units.preview'), ['start_date' => SSU_FROM, 'end_date' => SSU_TO,
            'room_type_id' => (string) $f['s']['roomType']->id, 'unit_ids' => [$f['unitA']], 'mode' => 'set', 'amount' => '245.00'])
        ->assertForbidden();

    expect((int) DB::table('unit_rate_overrides')->count())->toBe(0);
});
