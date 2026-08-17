<?php

use App\Exceptions\Booking\UnitUnavailableException;
use App\Livewire\FrontDesk;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\UnitRateOverride;
use App\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// CHANNEL-SYNC OPTION 1 — an OTA-sourced stay never sits on a unit that carries its own price.
//
// ⚠️ THIS IS A MONEY RULE IN AN ASSIGNMENT COSTUME. OTAs are structurally type-only (AriSnapshotBuilder has
// no unit concept), so a channel sold the ROOM TYPE at the TYPE rate. A unit with an override is priced for
// direct sale; seating an OTA stay on it means the property either eats the difference or dishonours what the
// guest was sold. Only a non-overridden unit honours the price the OTA actually quoted.
//
// ⚠️ IT GUARDS THE SELECTION, NEVER THE LOCK. The check runs before assignUnit()'s transaction and touches
// neither lockAvailability() nor assertUnitFree(); ConcurrentBookingTest is re-run untouched and green.
//
// ⚠️ A DIRECT BOOKING IS DELIBERATELY UNAFFECTED — it may take any unit, overridden or not. A guard that
// caught direct bookings too would be over-permissive in the wrong direction: it would block a sale the
// property is entitled to make.

const OTA_FROM = '2026-09-01';

const OTA_TO = '2026-09-03';

const OTA_UNIT_MINOR = 27000;

/** A room type with two units, one of which carries its own price for the stay. */
function otaSetup(): array
{
    $s = seedScenario(2);
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();
    $priced = (string) $s['units'][0]->id;
    $plain = (string) $s['units'][1]->id;

    foreach ([OTA_FROM, '2026-09-02'] as $d) {
        UnitRateOverride::create(['organization_id' => $s['org']->id, 'rate_plan_id' => $plan->id,
            'unit_id' => $priced, 'date' => $d, 'price_minor' => OTA_UNIT_MINOR, 'currency' => 'EUR']);
    }

    return ['s' => $s, 'plan' => $plan, 'priced' => $priced, 'plain' => $plain];
}

/** A reservation of the given source with one unassigned line — the shape an OTA import leaves behind. */
function otaLine(array $f, string $source): ReservationUnit
{
    $r = Reservation::create([
        'property_id' => $f['s']['property']->id,
        'reference' => 'VYR-OTA-'.strtoupper(substr(md5(uniqid()), 0, 6)),
        'status' => 'confirmed', 'source' => $source,
        'lead_guest_id' => $f['s']['guest']->id,
        'check_in' => OTA_FROM, 'check_out' => OTA_TO, 'currency' => 'EUR',
    ]);

    return ReservationUnit::create([
        'reservation_id' => $r->id, 'room_type_id' => $f['s']['roomType']->id,
        // ⚠️ unit_id NULL — this is exactly what BookingImportService leaves: OTA imports are TYPE-assigned
        // and a physical unit is chosen later, which is why the rule belongs at assignUnit() and nowhere else.
        'unit_id' => null, 'rate_plan_id' => $f['plan']->id,
        'check_in' => OTA_FROM, 'check_out' => OTA_TO,
        'adults' => 2, 'children' => 0, 'price_minor' => 18000, 'currency' => 'EUR', 'status' => 'active',
    ]);
}

// ── 1. ⚠️⚠️ AN OTA STAY IS REFUSED THE OVERRIDDEN UNIT ───────────────────────────────────────────
it('refuses to seat an OTA-sourced stay on a unit that carries its own price', function () {
    $f = otaSetup();
    $line = otaLine($f, 'ota');

    $refused = false;
    try {
        app(AssignmentService::class)->assignUnit($line, $f['priced']);
    } catch (UnitUnavailableException $e) {
        $refused = true;
    }

    // ⚠️ RAW: refused, and the line is still unassigned — nothing was half-written.
    expect($refused)->toBeTrue()
        ->and(DB::table('reservation_units')->where('id', $line->id)->value('unit_id'))->toBeNull();
});

// ── 2. ⚠️⚠️ …AND ACCEPTS THE NON-OVERRIDDEN ONE — the OTA's price is honourable ──────────────────
it('seats an OTA-sourced stay on a unit that follows the room-type rate', function () {
    $f = otaSetup();
    $line = otaLine($f, 'ota');

    app(AssignmentService::class)->assignUnit($line, $f['plain']);

    // ⚠️ RAW: the assignment really landed, on the unit whose price the channel actually sold.
    expect(DB::table('reservation_units')->where('id', $line->id)->value('unit_id'))->toBe($f['plain']);
});

// ── 3. ⚠️⚠️ A DIRECT BOOKING IS UNAFFECTED — it may take the overridden unit ─────────────────────
it('lets a direct booking take an overridden unit', function () {
    $f = otaSetup();
    $line = otaLine($f, 'direct');

    app(AssignmentService::class)->assignUnit($line, $f['priced']);

    // ⚠️ The property set that price for direct sale — blocking it here would forfeit a legitimate booking.
    expect(DB::table('reservation_units')->where('id', $line->id)->value('unit_id'))->toBe($f['priced']);

    // …and the override genuinely exists, so this is not passing for want of data.
    expect((int) DB::table('unit_rate_overrides')->where('unit_id', $f['priced'])->count())->toBe(2);
});

// ── 4. ⚠️ ANY OVERRIDDEN NIGHT DISQUALIFIES — not merely the first ───────────────────────────────
it('refuses the unit when only a later night of the stay is overridden', function () {
    $s = seedScenario(2);
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();
    $unit = (string) $s['units'][0]->id;

    // ⚠️ ONLY the SECOND night carries a price. A guard that peeked at the first night alone would pass this,
    // and the stay would still be sold on a unit the OTA never priced.
    UnitRateOverride::create(['organization_id' => $s['org']->id, 'rate_plan_id' => $plan->id,
        'unit_id' => $unit, 'date' => '2026-09-02', 'price_minor' => OTA_UNIT_MINOR, 'currency' => 'EUR']);

    $f = ['s' => $s, 'plan' => $plan, 'priced' => $unit, 'plain' => (string) $s['units'][1]->id];
    $line = otaLine($f, 'ota');

    expect(fn () => app(AssignmentService::class)->assignUnit($line, $unit))
        ->toThrow(UnitUnavailableException::class);
});

// ── 5. ⚠️ SHOWN = ENFORCED — the picker's candidate list drops what the write path refuses ───────
it('excludes an overridden unit from the OTA candidate list but keeps it for a direct stay', function () {
    // ⚠️ TESTED ON THE FILTER ITSELF, AND TWO EARLIER DRAFTS EARNED THAT. The first searched the front-desk
    // HTML for `value="<unit id>"` — the page emits no such attribute, so NEITHER unit was found and a
    // `not->toContain` passed proving nothing (a probe confirmed it). The second asserted on the component's
    // assembled view data, which depends on the stay falling in the day window, the status, and the move
    // permission all lining up — too much unrelated machinery for a filter test. This calls the filter
    // directly and asserts BOTH directions, which is the only shape that cannot be vacuous.
    $f = otaSetup();

    $m = new ReflectionMethod(FrontDesk::class, 'freeUnitsFor');
    $m->setAccessible(true);
    $fd = (new ReflectionClass(FrontDesk::class))->newInstanceWithoutConstructor();

    $ids = fn (bool $isOta): array => array_map(
        fn (array $u): string => (string) $u['id'],
        $m->invoke($fd, (string) $f['s']['property']->id, (string) $f['s']['roomType']->id,
            OTA_FROM, OTA_TO, 'no-such-reservation', $isOta),
    );

    // ⚠️ THE PREMISE: unfiltered, BOTH units are offered. Without this the OTA assertion below could pass
    // simply because the list is empty.
    $direct = $ids(false);
    expect($direct)->toContain($f['plain'])
        ->and($direct)->toContain($f['priced']);

    // ⚠️ Filtered for an OTA stay: the overridden unit is gone, the plain one remains — exactly the set
    // assignUnit() will accept, so the operator is never offered a move the write path would reject.
    $ota = $ids(true);
    expect($ota)->toContain($f['plain'])
        ->and($ota)->not->toContain($f['priced']);
});

// ── 6. ⚠️⚠️ THE CERTIFIED CORE IS UNWEAKENED ────────────────────────────────────────────────────
it('leaves the no-double-booking guarantee exactly as it was', function () {
    $f = otaSetup();
    $line = otaLine($f, 'direct');

    app(AssignmentService::class)->assignUnit($line, $f['plain']);

    // ⚠️ A SECOND stay cannot take the same unit for the same nights — the lock still refuses, and the new
    // guard sits entirely outside it (it runs before the transaction and reads a different table).
    $second = otaLine($f, 'direct');
    expect(fn () => app(AssignmentService::class)->assignUnit($second, $f['plain']))
        ->toThrow(UnitUnavailableException::class);

    expect(DB::table('reservation_units')->where('id', $second->id)->value('unit_id'))->toBeNull();
});
