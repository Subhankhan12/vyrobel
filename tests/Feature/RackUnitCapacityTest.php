<?php

/*
 * D2-1 — `units.max_occupancy` ON THE RESERVATION RACK ROW (2026-08-15).
 *
 * ⚠️ A ONE-COLUMN-AWAY WIN, and this time the ledger's "populated" HELD: 8 of 8 demo units carry a real
 * capacity (2–4). D1-3's field was 0-of-41 because an action the demo never performs writes it; this one is
 * set at unit creation, so the populated branch is the one the demo shows. ⚠️ **That makes ZERO the hidden
 * branch here** — the inverse of D1-3 — and it is forced below.
 *
 * ⚠️ NULL IS IMPOSSIBLE; ZERO IS THE REAL EDGE. `max_occupancy` is `int unsigned NOT NULL DEFAULT 2`, so the
 * column can never BE null — but 0 is storable. And 0 does not mean "sleeps nobody", it means nobody recorded
 * a capacity. Rendering "Sleeps 0" would assert a fact the data never claimed, so the payload maps 0 → null
 * and the view omits the element entirely. Absent is the honest rendering of an unrecorded fact.
 *
 * ⚠️ THE LABEL IS MATCHED, NOT INVENTED (§N.8f). The product already says "Sleeps N" in two places — the
 * operator Owner-detail unit list and the owner Unit-detail property row. The rack reuses that exact wording
 * and the row's existing muted meta style rather than introducing a third way to say capacity. A parity test
 * reads those sources so a rename in one place fails here.
 *
 * ⚠️ DISPLAY ONLY. No over-capacity comparison against a reservation's party size — that is a different field
 * on a different table and a different feature; logged as `D2-1a`, deliberately not built.
 */

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level function in a Pest file is GLOBAL and has collided in this suite before. */
function d21Operator(array $s)
{
    return operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');
}

/** Load the rack as the operator, for the property the scenario built. */
function d21Rack(array $s)
{
    return test()->actingAs(d21Operator($s))
        ->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/calendar');
}

it('⚠️ D2-1 — a POPULATED capacity renders on the rack row, with the product’s existing "Sleeps N" label', function () {
    $s = seedScenario(1);
    // ⚠️ NON-VACUITY: a distinctive value, so the assertion cannot pass on some other unit's default.
    Unit::withoutGlobalScopes()->whereKey($s['units'][0]->getKey())->update(['max_occupancy' => 6]);

    $res = d21Rack($s)->assertOk();

    $res->assertSee('Sleeps 6');
    assertNoRawBlade($res);
});

it('⚠️ D2-1 — ZERO is omitted entirely: never "Sleeps 0", never a bare 0 (the hidden branch)', function () {
    /*
     * ⚠️ THE BRANCH THE DEMO CANNOT SHOW. All 8 demo units carry 2–4, so a zero-capacity unit never appears
     * on a demo page — and "Sleeps 0" would be a false claim about a real unit, not a cosmetic slip.
     */
    $s = seedScenario(1);
    foreach ($s['units'] as $unit) {
        Unit::withoutGlobalScopes()->whereKey($unit->getKey())->update(['max_occupancy' => 0]);
    }

    // ⚠️ NON-VACUITY: the zeros really are stored, so an absent "Sleeps" means omitted, not un-set-up.
    expect((int) DB::table('units')->where('max_occupancy', 0)->count())->toBe(count($s['units']));

    $html = (string) d21Rack($s)->assertOk()->getContent();

    // The row itself renders — only the capacity fact is absent.
    expect($html)->toContain('vy-unit-row')
        ->and($html)->not->toContain('Sleeps 0')
        ->and($html)->not->toContain('Sleeps</span>');
});

it('⚠️ D2-1 — the payload maps 0 to null rather than passing a falsy integer through', function () {
    // The mapping, asserted at the payload level: a view guarded on a raw 0 would work by accident today and
    // break the moment someone rendered it unguarded.
    // ⚠️ TWO units — the argument is the UNIT COUNT, and seedScenario(1) builds exactly one, so a second
    // index was simply undefined. The two capacities must render on ONE page for the contrast to mean anything.
    $s = seedScenario(2);
    Unit::withoutGlobalScopes()->whereKey($s['units'][0]->getKey())->update(['max_occupancy' => 0]);
    Unit::withoutGlobalScopes()->whereKey($s['units'][1]->getKey())->update(['max_occupancy' => 4]);

    $src = (string) i18nEnglishSource(app_path('Livewire/ReservationRack.php'));
    expect($src)->toContain("'sleeps' => (\$cap = (int) \$unit->max_occupancy) > 0 ? \$cap : null");

    $html = (string) d21Rack($s)->assertOk()->getContent();
    expect($html)->toContain('Sleeps 4')          // the populated unit
        ->and($html)->not->toContain('Sleeps 0'); // the zero one, on the same page
});

it('⚠️ D2-1 — the label MATCHES the product’s existing capacity idiom (§N.8f)', function () {
    /*
     * ⚠️ THE DRIFT GUARD. "Sleeps N" already exists on two surfaces; the rack reuses the wording rather than
     * inventing a third. This reads those sources, so renaming the label in one place fails here instead of
     * silently leaving the product saying capacity three different ways.
     */
    $ownerDetail = (string) i18nEnglishSource(resource_path('views/livewire/operator/owner-detail.blade.php'));
    $unitDetail = (string) i18nEnglishSource(resource_path('views/livewire/owner/unit-detail.blade.php'));
    $rack = (string) i18nEnglishSource(resource_path('views/livewire/reservation-rack.blade.php'));

    expect($ownerDetail)->toContain('Sleeps ')     // non-vacuity: the idiom really is established
        ->and($unitDetail)->toContain('Sleeps')
        ->and($rack)->toContain('Sleeps {{ $unit[\'sleeps\'] }}');

    // And the rack renders it in the row's EXISTING muted meta class, not a new one.
    expect($rack)->toContain('<span class="vy-unit-name">Sleeps');
});

it('⚠️ D2-1 — it is the UNIT’s capacity, never the reservation’s party size', function () {
    /*
     * ⚠️ THE CONFUSION THIS SCREEN INVITES. A rack row shows bookings; a booking has a party size. They are
     * different fields on different tables and would look identical on the row. Proven by making them
     * DIFFER: the unit sleeps 5, the stay is for 2 — the rack must show 5.
     */
    $s = seedScenario(1);
    Unit::withoutGlobalScopes()->whereKey($s['units'][0]->getKey())->update(['max_occupancy' => 5]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    DB::table('reservations')->where('id', $reservation->getKey())->update(['adults' => 2, 'children' => 0]);

    $html = (string) d21Rack($s)->assertOk()->getContent();

    expect($html)->toContain('Sleeps 5')
        ->and($html)->not->toContain('Sleeps 2');
});

it('⚠️ D2-1 — the other rack row fields render identically', function () {
    // A payload addition must not disturb what was already correct.
    $s = seedScenario(1);
    $unit = $s['units'][0];
    Unit::withoutGlobalScopes()->whereKey($unit->getKey())->update(['max_occupancy' => 4]);

    $html = (string) d21Rack($s)->assertOk()->getContent();

    expect($html)->toContain((string) $unit->code)          // the code
        ->toContain('vy-unit-label')                         // the label column
        ->toContain('vy-lane');                              // the lane the bars live in
});

it('⚠️ D2-1 — the unit data is untouched: this pass DISPLAYS a column, it does not write one', function () {
    // ⚠️ The seeded capacities must be exactly what the seeder set — a display item that mutated inventory
    // would be a far worse defect than the one it closed.
    $s = seedScenario(1);
    $before = DB::table('units')->orderBy('code')->pluck('max_occupancy')->all();

    d21Rack($s)->assertOk();

    expect(DB::table('units')->orderBy('code')->pluck('max_occupancy')->all())->toBe($before);
});
