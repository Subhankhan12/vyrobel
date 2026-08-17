<?php

use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Unit;
use App\Models\UnitRateOverride;
use App\Models\User;
use App\Services\UnitRateOverrideService;
use App\Support\Pricing\NightlyRate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// D18-5 — BULK PER-UNIT RATE OVERRIDES. ⚠️⚠️ MONEY AT SCALE.
//
// ⚠️ THE HAZARD IS NOT "A WRONG PRICE", IT IS "MANY WRONG PRICES, SILENTLY". A single-set mistake is one
// unit an operator can see and correct. A bulk mistake lands across a whole room type at once and looks
// exactly like a success. Every assertion below is aimed at that difference:
//
//   · PREVIEW WRITES NOTHING          — the approval gate is real, not decorative (test 1)
//   · COMMIT == PREVIEW               — the rows written are the tuples shown, raw-verified (tests 2, 3)
//   · A MOVED WORLD ABORTS            — figures the operator never saw are refused, not reconciled (test 4)
//   · ATOMIC                          — a mid-bulk failure leaves NOTHING, not a half-priced set (test 5)
//   · CLEAR LANDS ON THE TYPE RATE    — an absent override is not €0 (tests 6, 7)
//   · UNTOUCHED UNITS DO NOT MOVE     — byte-identical, raw-verified (test 8)
//   · ONE SOURCE OF PRICE             — the preview agrees with NightlyRate tuple-for-tuple (test 9)
//
// ⚠️ RAW QUERIES THROUGHOUT for the money assertions. A check must be independent of the thing it checks
// (§N.18.1): verifying the override rows through the same helper that wrote them would be vacuous.

const BUR_FROM = '2026-09-01';

const BUR_TO = '2026-09-03';          // inclusive — 3 nights with BUR_FROM

const BUR_TYPE_MINOR = 18000;         // the room-type rate these units resolve to today

const BUR_SET_MINOR = 25500;          // the per-unit figure a bulk set applies

/** An operator who may manage rates, on a room type with two units. */
function burSetup(): array
{
    $s = seedScenario(2);
    $user = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s, [], 'Revenue Manager');
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();

    // ⚠️ THE FIXTURE TRAP: seedScenario publishes no price for this window, and a preview over unpriced
    // nights resolves NULL everywhere — every "from → to" assertion would then be comparing nulls and would
    // pass for the wrong reason. Publish a real type rate so the fallback under test is a real figure.
    foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $d) {
        RateCalendar::query()->updateOrCreate(
            ['rate_plan_id' => $plan->id, 'date' => $d],
            ['organization_id' => $s['org']->id, 'price_minor' => BUR_TYPE_MINOR, 'currency' => 'EUR'],
        );
    }

    return [
        's' => $s, 'user' => $user, 'plan' => $plan,
        'session' => ['current_property_id' => $s['property']->id],
        'unitA' => (string) $s['units'][0]->id,
        'unitB' => (string) $s['units'][1]->id,
    ];
}

/** ⚠️ RAW — every override row, as the database actually holds it. */
function burRaw(?string $unitId = null): array
{
    return DB::table('unit_rate_overrides')
        ->when($unitId !== null, fn ($q) => $q->where('unit_id', $unitId))
        ->orderBy('unit_id')->orderBy('date')
        ->get(['unit_id', 'date', 'price_minor', 'currency'])
        ->map(fn ($r): string => $r->unit_id.'|'.substr((string) $r->date, 0, 10).'|'.$r->price_minor.'|'.$r->currency)
        ->all();
}

/** The POST body both steps share. */
function burPayload(array $f, string $mode, array $unitIds = [], string $amount = '255.00'): array
{
    return [
        'start_date' => BUR_FROM, 'end_date' => BUR_TO,
        'room_type_id' => (string) $f['s']['roomType']->id,
        'mode' => $mode, 'amount' => $amount,
        'unit_ids' => $unitIds,
    ];
}

function burPost(array $f, string $route, array $body)
{
    return test()->actingAs($f['user'])->withSession($f['session'])->post(route($route), $body);
}

// ── 1. ⚠️⚠️ PREVIEW WRITES NOTHING — the approval gate is real ────────────────────────────────────
it('writes nothing at all when previewing a bulk set', function () {
    $f = burSetup();

    $response = burPost($f, 'app.rates.units.preview', burPayload($f, 'set'));
    $response->assertRedirect();

    // ⚠️ RAW: not one row exists, and the preview genuinely described work (so this is not vacuous).
    expect((int) DB::table('unit_rate_overrides')->count())->toBe(0);

    $preview = session('unit_rate_preview');
    expect($preview)->not->toBeNull()
        ->and(count($preview['units']))->toBe(2)
        ->and($preview['digest'])->not->toBeEmpty();
});

// ── 2. ⚠️⚠️ COMMIT == PREVIEW — what lands is exactly what was shown ─────────────────────────────
it('writes exactly the rows the preview promised, and no others', function () {
    $f = burSetup();

    burPost($f, 'app.rates.units.preview', burPayload($f, 'set'))->assertRedirect();
    $preview = session('unit_rate_preview');

    // Every unit was previewed as moving from the type rate to the new figure.
    foreach ($preview['units'] as $row) {
        expect($row['from_minor'])->toBe(BUR_TYPE_MINOR)
            ->and($row['to_minor'])->toBe(BUR_SET_MINOR)
            ->and($row['state'])->toBe('change');
    }

    burPost($f, 'app.rates.units.apply', burPayload($f, 'set') + ['digest' => $preview['digest']])->assertRedirect();

    // ⚠️ RAW: exactly 2 units × 3 nights, all at the previewed figure — nothing more, nothing else.
    $rows = burRaw();
    expect(count($rows))->toBe(6);
    foreach ($rows as $row) {
        expect($row)->toContain('|'.BUR_SET_MINOR.'|EUR');
    }

    // …and the resolver now answers with it, which is the only thing that actually charges money.
    expect(NightlyRate::forUnit((string) $f['plan']->id, $f['unitA'], BUR_FROM))->toBe(BUR_SET_MINOR);
});

// ── 3. ⚠️ A SELECTION APPLIES TO THE SELECTION — the other unit is untouched ─────────────────────
it('prices only the units that were selected', function () {
    $f = burSetup();

    burPost($f, 'app.rates.units.preview', burPayload($f, 'set', [$f['unitA']]))->assertRedirect();
    $preview = session('unit_rate_preview');
    expect(count($preview['units']))->toBe(1);

    burPost($f, 'app.rates.units.apply', burPayload($f, 'set', [$f['unitA']]) + ['digest' => $preview['digest']])->assertRedirect();

    // ⚠️ RAW: unit A has rows, unit B has NONE — and B still resolves the type rate, unmoved.
    expect(count(burRaw($f['unitA'])))->toBe(3)
        ->and(burRaw($f['unitB']))->toBe([])
        ->and(NightlyRate::forUnit((string) $f['plan']->id, $f['unitB'], BUR_FROM))->toBe(BUR_TYPE_MINOR);
});

// ── 4. ⚠️⚠️ THE WORLD MOVED — the commit REFUSES rather than applying unapproved figures ─────────
it('refuses to apply when the change set moved after the preview, and writes nothing', function () {
    $f = burSetup();

    burPost($f, 'app.rates.units.preview', burPayload($f, 'set'))->assertRedirect();
    $digest = session('unit_rate_preview')['digest'];

    // ⚠️ A colleague re-prices the room type between the preview and the approval. The operator approved
    // "from €180.00 to €255.00"; that sentence is now false.
    RateCalendar::query()->where('rate_plan_id', $f['plan']->id)->update(['price_minor' => 19500]);

    burPost($f, 'app.rates.units.apply', burPayload($f, 'set') + ['digest' => $digest])
        ->assertRedirect()
        ->assertSessionHas('rates_notice', fn (string $m): bool => str_contains($m, 'changed while you were reviewing'));

    // ⚠️ RAW: NOTHING was written. Not a partial set, not a "corrected" set — nothing.
    expect((int) DB::table('unit_rate_overrides')->count())->toBe(0);
});

// ── 4b. ⚠️ NO DIGEST, NO WRITE — an apply that skipped the gate is refused ───────────────────────
it('refuses a bulk apply that arrives without a preview digest', function () {
    $f = burSetup();

    burPost($f, 'app.rates.units.apply', burPayload($f, 'set'))->assertRedirect();

    expect((int) DB::table('unit_rate_overrides')->count())->toBe(0);
});

// ── 5. ⚠️⚠️ ATOMIC — a mid-bulk failure leaves NOTHING, not a half-priced set ────────────────────
it('rolls the whole bulk back when a write fails partway through', function () {
    $f = burSetup();
    $service = app(UnitRateOverrideService::class);

    // ⚠️ A REAL FAILURE, NOT A SIMULATED ONE. A unit deleted between preview and approval is a genuine race:
    // the prices resolve fine (they never join `units`), so the digest still matches, and the failure comes
    // from the database's own foreign key on the FIRST write of the second unit. That is precisely the
    // mid-bulk failure this guarantee exists for — and unit A is ordered first, so it has already been
    // written by the time it happens.
    $ghost = (string) Str::ulid();
    $unitIds = [$f['unitA'], $ghost];

    $preview = $service->preview($f['plan'], $unitIds, BUR_FROM, '2026-09-04', 'set', BUR_SET_MINOR);
    expect(count($preview['units']))->toBe(2);   // the fixture really does describe a two-unit bulk

    try {
        $service->commit($f['plan'], $unitIds, BUR_FROM, '2026-09-04', 'set', BUR_SET_MINOR, $preview['digest']);
        $failed = false;
    } catch (Throwable $e) {
        $failed = true;
    }

    // ⚠️ RAW: the write genuinely failed, and unit A — which succeeded — was rolled back with it.
    expect($failed)->toBeTrue()
        ->and((int) DB::table('unit_rate_overrides')->count())->toBe(0)
        ->and(burRaw($f['unitA']))->toBe([]);
});

// ── 6. ⚠️⚠️ CLEAR REMOVES THE OVERRIDE AND LANDS ON THE TYPE RATE — NOT €0 ──────────────────────
it('clears per-unit prices back to the room-type rate, never to zero', function () {
    $f = burSetup();

    // Price both units individually first.
    burPost($f, 'app.rates.units.preview', burPayload($f, 'set'))->assertRedirect();
    burPost($f, 'app.rates.units.apply', burPayload($f, 'set') + ['digest' => session('unit_rate_preview')['digest']])->assertRedirect();
    expect((int) DB::table('unit_rate_overrides')->count())->toBe(6);

    // Now clear them.
    burPost($f, 'app.rates.units.preview', burPayload($f, 'clear'))->assertRedirect();
    $preview = session('unit_rate_preview');

    // ⚠️ THE PREVIEW SHOWS THE REAL LANDING FIGURE — the type rate, not the word "cleared" and not €0.
    foreach ($preview['units'] as $row) {
        expect($row['from_minor'])->toBe(BUR_SET_MINOR)
            ->and($row['to_minor'])->toBe(BUR_TYPE_MINOR)
            ->and($row['state'])->toBe('cleared');
    }

    burPost($f, 'app.rates.units.apply', burPayload($f, 'clear') + ['digest' => $preview['digest']])->assertRedirect();

    // ⚠️ RAW: the rows are GONE — not zeroed. A €0 row would resolve as a free night.
    expect((int) DB::table('unit_rate_overrides')->count())->toBe(0)
        ->and((int) DB::table('unit_rate_overrides')->where('price_minor', 0)->count())->toBe(0);

    // …and the resolver hands both units back to the room-type price.
    expect(NightlyRate::forUnit((string) $f['plan']->id, $f['unitA'], BUR_FROM))->toBe(BUR_TYPE_MINOR)
        ->and(NightlyRate::forUnit((string) $f['plan']->id, $f['unitB'], BUR_FROM))->toBe(BUR_TYPE_MINOR);
});

// ── 7. ⚠️ CLEARING A UNIT THAT HAS NO OVERRIDE IS A NO-OP, not a €0 write ───────────────────────
it('leaves a unit alone when there is nothing to clear', function () {
    $f = burSetup();

    burPost($f, 'app.rates.units.preview', burPayload($f, 'clear'))->assertRedirect();

    // Nothing to show, nothing to write — and it says so rather than offering an empty approval.
    expect(session('unit_rate_preview'))->toBeNull()
        ->and((int) DB::table('unit_rate_overrides')->count())->toBe(0);
});

// ── 8. ⚠️⚠️ ZERO MOVEMENT — nothing outside the operation moves, byte-identical ─────────────────
it('moves no type-level price and no unit outside the selection', function () {
    $f = burSetup();

    $rateCalendarBefore = DB::table('rate_calendar')->orderBy('id')
        ->get(['id', 'rate_plan_id', 'date', 'price_minor', 'currency'])->toJson();

    burPost($f, 'app.rates.units.preview', burPayload($f, 'set', [$f['unitA']]))->assertRedirect();
    burPost($f, 'app.rates.units.apply', burPayload($f, 'set', [$f['unitA']]) + ['digest' => session('unit_rate_preview')['digest']])->assertRedirect();

    // ⚠️ RAW + BYTE-IDENTICAL: per-unit pricing writes to its OWN table, so no type-level price can move.
    expect(DB::table('rate_calendar')->orderBy('id')->get(['id', 'rate_plan_id', 'date', 'price_minor', 'currency'])->toJson())
        ->toBe($rateCalendarBefore);

    // …and a night outside the range is untouched on the very unit that WAS priced.
    expect(NightlyRate::forUnit((string) $f['plan']->id, $f['unitA'], '2026-09-04'))
        ->toBe(NightlyRate::forType((string) $f['plan']->id, '2026-09-04'));
});

// ── 9. ⚠️⚠️ ONE SOURCE OF PRICE — the preview agrees with the resolver, tuple for tuple ─────────
it('previews exactly what NightlyRate resolves, for every unit and night', function () {
    $f = burSetup();

    // A partially-overridden starting point, so the two implementations have something to disagree about.
    UnitRateOverride::create([
        'organization_id' => $f['s']['org']->id, 'rate_plan_id' => $f['plan']->id,
        'unit_id' => $f['unitB'], 'date' => '2026-09-02', 'price_minor' => 31000, 'currency' => 'EUR',
    ]);

    $preview = app(UnitRateOverrideService::class)
        ->preview($f['plan'], [$f['unitA'], $f['unitB']], BUR_FROM, '2026-09-04', 'set', BUR_SET_MINOR);

    // ⚠️ TUPLE FOR TUPLE, EVERY NIGHT — not just the first. A per-unit row carries only its opening figure,
    // and an earlier version of this test compared that alone: the override below sits on the SECOND night,
    // so a flipped precedence agreed on night one and the test stayed green through a mutation that had
    // genuinely broken the resolver's rule. Previewing each night on its own is what makes the comparison
    // real (§N.4p — a guard that cannot see the defect is decoration).
    foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $night) {
        $nightly = app(UnitRateOverrideService::class)
            ->preview($f['plan'], [$f['unitA'], $f['unitB']], $night, CarbonImmutable::parse($night)->addDay()->toDateString(), 'set', BUR_SET_MINOR);

        foreach ($nightly['units'] as $row) {
            expect($row['from_minor'])
                ->toBe(NightlyRate::forUnit((string) $f['plan']->id, (string) $row['unit_id'], $night));
        }
    }

    // The whole-range preview still opens on the real first-night figure.
    foreach ($preview['units'] as $row) {
        expect($row['from_minor'])->toBe(NightlyRate::forUnit((string) $f['plan']->id, (string) $row['unit_id'], BUR_FROM));
    }

    // And the overridden night really is the override, not the type rate (the fixture bites).
    expect(NightlyRate::forUnit((string) $f['plan']->id, $f['unitB'], '2026-09-02'))->toBe(31000);
});

// ── 10. ⚠️ THE RACK SAFEGUARD FOLLOWS — a bulk set changes what a type-level save reaches ───────
it('updates the blast-radius label once a bulk set has created overrides', function () {
    $f = burSetup();

    $before = test()->actingAs($f['user'])->withSession($f['session'])->get('/app/rates?from='.BUR_FROM)->getContent();
    expect($before)->toContain('2 units');

    burPost($f, 'app.rates.units.preview', burPayload($f, 'set', [$f['unitA']]))->assertRedirect();
    burPost($f, 'app.rates.units.apply', burPayload($f, 'set', [$f['unitA']]) + ['digest' => session('unit_rate_preview')['digest']])->assertRedirect();

    // ⚠️ The safeguard reads the SAME table this wrote, so it cannot drift — a type-level save now reaches
    // one unit, and the label says so without anyone maintaining a second count.
    expect(test()->actingAs($f['user'])->withSession($f['session'])->get('/app/rates?from='.BUR_FROM)->getContent())
        ->toContain('1 of 2 units · 1 priced individually');
});

// ── 11. ⚠️ RENDERED — the preview panel shows real figures and offers the approval ──────────────
it('renders the preview with real money and an apply control', function () {
    $f = burSetup();

    burPost($f, 'app.rates.units.preview', burPayload($f, 'set'))->assertRedirect();
    $digest = session('unit_rate_preview')['digest'];

    $html = test()->actingAs($f['user'])->withSession($f['session'])
        ->withSession(['unit_rate_preview' => session('unit_rate_preview')])
        ->get('/app/rates?from='.BUR_FROM)->assertOk()->getContent();

    // ⚠️ THE ROWS SAY WHICH UNIT. Found by opening the page, not by a test: every real unit carries a `code`
    // and a NULL `name`, so labelling by name alone rendered a bulk MONEY screen whose rows were blank — an
    // operator approving prices for units they cannot identify. Static checks cannot see this (§N.21.6).
    $codes = DB::table('units')->where('room_type_id', $f['s']['roomType']->id)->pluck('code')->all();
    expect($codes)->not->toBeEmpty();
    foreach ($codes as $code) {
        expect($html)->toContain('<td>'.$code.'</td>');
    }

    expect($html)->toContain('Review before applying')
        ->and($html)->toContain('nothing has been changed yet')
        // ⚠️ THE REAL FIGURES, formatted the way every other money surface formats them (§G.1 untouched).
        ->and($html)->toContain('€180.00')
        ->and($html)->toContain('€255.00')
        // …and the approval carries the digest, so the panel IS the gate.
        ->and($html)->toContain($digest)
        ->and($html)->toContain('Apply to 2 units');
});

// ── 12. ⚠️ RBAC — a bulk money write is rates.manage, not rates.view (#30) ──────────────────────
it('refuses both bulk routes to an operator who may only view rates', function () {
    $f = burSetup();
    $desk = operatorUser($f['s'], [], 'Front Desk');

    foreach (['app.rates.units.preview', 'app.rates.units.apply'] as $route) {
        test()->actingAs($desk)->withSession($f['session'])
            ->post(route($route), burPayload($f, 'set') + ['digest' => 'x'])
            ->assertForbidden();
    }

    expect((int) DB::table('unit_rate_overrides')->count())->toBe(0);
});
