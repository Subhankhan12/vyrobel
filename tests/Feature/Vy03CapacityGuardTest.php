<?php

declare(strict_types=1);

use App\Events\DomainEventRecorded;
use App\Exceptions\Booking\InventoryOversoldException;
use App\Exceptions\Booking\UnitUnavailableException;
use App\Livewire\ReservationRack;
use App\Models\AuditEvent;
use App\Models\InventoryBlock;
use App\Models\MaintenanceTicket;
use App\Models\Reservation;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\MaintenanceService;
use App\Support\Inventory\RoomCapacity;
use App\Support\Property\PropertyContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

// ⚠️⚠️ VY-03 (audit of 2026-11-24, reproduced) — TAKING A UNIT AWAY CAN NO LONGER OVERSELL A ROOM TYPE IN SILENCE.
//
// Two units, two bookings with no room assigned yet. One unit was put out of service and the other blocked: no error, real capacity −2,
// the stored figure shown as 0 (it is clamped) and no record anywhere. Both paths now count rooms against bookings UNDER THE LOCK:
//   · a BLOCK (an owner's own stay, a hold) is refused when the bookings already taken need that unit;
//   · OUT OF ORDER is refused too — unless the operator, shown how many bookings would have no room, goes ahead anyway (a broken room
//     is a fact; the product must not argue with it, only make sure nobody is surprised), and then the audit trail records the gap;
//   · the calendar shows a night with more bookings than rooms as overbooked, instead of a quiet 0.
//
// ⚠️ "ROOMS" IS PHYSICAL CAPACITY (App\Support\Inventory\RoomCapacity), not the stored `total`: a PAUSED unit keeps the stays already on
//    it, and a paused property stores a total of 0 on purpose — neither is an overbooking.

uses(DatabaseTransactions::class);

/** Two units of one room type, and `$bookings` bookings on 2026-09-01 → 09-03 with NO room assigned. */
function vy03World(int $units = 2, int $bookings = 2): array
{
    $s = seedScenario($units);
    $s['bookings'] = [];
    for ($i = 0; $i < $bookings; $i++) {
        $s['bookings'][] = book($s);   // no unit_id → room-type level
    }

    return $s;
}

function vy03Open(array $s, Unit $unit, array $extra = []): MaintenanceTicket
{
    return app(MaintenanceService::class)->open(array_merge([
        'unit_id' => $unit->id, 'title' => 'Burst pipe', 'category' => 'plumbing', 'priority' => 'high', 'takes_out_of_service' => true,
    ], $extra));
}

function vy03Block(array $s, Unit $unit, string $from = '2026-09-01', string $to = '2026-09-03'): InventoryBlock
{
    return app(AssignmentService::class)->blockInventory([
        'property_id' => $s['property']->id, 'unit_id' => (string) $unit->id, 'room_type_id' => (string) $s['roomType']->id,
        'start_date' => $from, 'end_date' => $to, 'reason' => 'owner',
    ]);
}

function vy03Condition(Unit $unit): string
{
    return (string) DB::table('units')->where('id', $unit->id)->value('condition_status');
}

it('⚠️⚠️ VY-03 (as reproduced): out of order, then a block, on a fully booked room type — BOTH are refused, nothing is written', function () {
    $s = vy03World(units: 2, bookings: 2);
    [$a, $b] = $s['units'];
    $rt = (string) $s['roomType']->id;
    $rowsBefore = DB::table('availability')->where('room_type_id', $rt)->orderBy('date')->get()->map(fn ($r) => (array) $r)->all();

    // 1 · out of order on A: refused, with the numbers
    try {
        vy03Open($s, $a);
        $this->fail('taking the unit out of order should have been refused');
    } catch (InventoryOversoldException $e) {
        expect($e->nights)->toBe(2)                 // 1 and 2 September
            ->and($e->peak)->toBe(1)                // one booking too many each night
            ->and($e->firstNight)->toBe('2026-09-01');
    }
    expect(MaintenanceTicket::query()->where('unit_id', $a->id)->count())->toBe(0)   // no half-made work order
        ->and(vy03Condition($a))->toBe('in_service');

    // 2 · a block on B: refused the same way
    expect(fn () => vy03Block($s, $b))->toThrow(InventoryOversoldException::class);
    expect(InventoryBlock::query()->where('unit_id', $b->id)->count())->toBe(0)
        ->and(DB::table('availability')->where('room_type_id', $rt)->orderBy('date')->get()->map(fn ($r) => (array) $r)->all())->toBe($rowsBefore);

    // …and the refusal is one the existing callers already understand ("pick open dates"), so no screen shows a crash
    expect(new InventoryOversoldException('x', 1, 1, '2026-09-01'))->toBeInstanceOf(UnitUnavailableException::class);
});

it('VY-03 CONTROL: with a room to spare, out of order and a block both go through as before', function () {
    $s = vy03World(units: 3, bookings: 1);
    [$a, $b] = $s['units'];

    $ticket = vy03Open($s, $a);
    expect($ticket->exists)->toBeTrue()->and(vy03Condition($a))->toBe('out_of_service');

    $block = vy03Block($s, $b);
    expect($block->exists)->toBeTrue();

    // 3 units − 1 out of order − 1 blocked = 1 room for 1 booking: nothing is overbooked
    expect(RoomCapacity::overbooked((string) $s['roomType']->id, '2026-09-01', '2026-09-10'))->toBe([]);

    // …and the NEXT block is refused: it would take the last room that booking needs
    expect(fn () => vy03Block($s, $s['units'][2]))->toThrow(InventoryOversoldException::class);
});

it('⚠️ VY-03: a broken room is a fact — out of order goes through once ACKNOWLEDGED, and the audit records the gap', function () {
    $s = vy03World(units: 2, bookings: 2);
    $a = $s['units'][0];

    $ticket = vy03Open($s, $a, ['acknowledge_oversell' => true]);

    expect($ticket->exists)->toBeTrue()
        ->and(vy03Condition($a))->toBe('out_of_service');

    $audit = AuditEvent::query()->where('action', 'unit.condition_changed')->where('subject_id', $a->id)->latest('id')->firstOrFail();
    // (toEqual: the database's JSON column returns an object's keys in its own order)
    expect($audit->after['overbooked'] ?? null)->toEqual(['nights' => 2, 'peak' => 1, 'first_night' => '2026-09-01']);

    // the two nights now read as OVERBOOKED BY 1 — not a quiet 0
    expect(RoomCapacity::overbooked((string) $s['roomType']->id, '2026-09-01', '2026-09-10'))
        ->toBe(['2026-09-01' => 1, '2026-09-02' => 1]);
});

it('VY-03: an unacknowledged out-of-order with NO overbooking records no gap in the audit', function () {
    $s = vy03World(units: 2, bookings: 1);
    $a = $s['units'][0];

    vy03Open($s, $a);

    $audit = AuditEvent::query()->where('action', 'unit.condition_changed')->where('subject_id', $a->id)->latest('id')->firstOrFail();
    expect(array_key_exists('overbooked', (array) $audit->after))->toBeFalse();
});

it('⚠️ VY-03: only nights from TODAY on count for out of order — a past night that was full is not a reason to refuse', function () {
    $s = vy03World(units: 2, bookings: 2);   // full on 1–2 September
    $a = $s['units'][0];

    $this->travelTo('2026-09-05 10:00:00');   // those nights are over
    $ticket = vy03Open($s, $a);

    expect($ticket->exists)->toBeTrue()->and(vy03Condition($a))->toBe('out_of_service');
});

it('⚠️ VY-03: a PAUSED unit keeps the stays already on it — that is not an overbooking, and it does not block the guard', function () {
    $s = vy03World(units: 3, bookings: 0);
    [$a, $b, $c] = $s['units'];
    book($s, ['unit_id' => $c->id]);   // a stay ON unit C
    book($s);                          // and one with no room yet

    // the owner pauses C: it takes no NEW booking, the stay on it stays (the stored `total` drops to 2 with `sold` still 2)
    $c->update(['is_active' => false]);
    app(AvailabilityService::class)->recompute((string) $s['roomType']->id, '2026-09-01', '2026-09-10');

    expect(RoomCapacity::overbooked((string) $s['roomType']->id, '2026-09-01', '2026-09-10'))->toBe([]);

    // A goes out of order: B still takes the unassigned booking, C keeps its own → allowed, unacknowledged
    expect(vy03Open($s, $a)->exists)->toBeTrue();

    // B too would leave the unassigned booking with no room → refused
    expect(fn () => vy03Open($s, $b))->toThrow(InventoryOversoldException::class);
});

it('⚠️⚠️ VY-03 (the screen): the work order form shows HOW MANY bookings would have no room before anything is saved', function () {
    $s = vy03World(units: 2, bookings: 2);
    $a = $s['units'][0];
    $user = operatorUser($s, [], 'Property Owner');
    $body = ['unit_id' => $a->id, 'title' => 'Burst pipe', 'category' => 'plumbing', 'priority' => 'high', 'takes_out_of_service' => '1'];

    // first press: NOT saved — a page that names the nights and the bookings, and carries the same form back
    $page = $this->actingAs($user)->post(route('app.maintenance.store'), $body)->assertOk();
    $page->assertSee('2 nights')
        ->assertSee('1 Sep 2026')
        ->assertSee('1 booking would be left without a room')
        ->assertSee('Nothing has been saved')
        ->assertSee('name="acknowledge_oversell" value="1"', false)
        ->assertSee('name="title" value="Burst pipe"', false);
    assertNoRawBlade($page);
    expect(MaintenanceTicket::query()->where('unit_id', $a->id)->count())->toBe(0)
        ->and(vy03Condition($a))->toBe('in_service');

    // second press, acknowledged: saved, and the unit is out of order
    $this->actingAs($user)->post(route('app.maintenance.store'), $body + ['acknowledge_oversell' => '1'])->assertRedirect();
    expect(MaintenanceTicket::query()->where('unit_id', $a->id)->count())->toBe(1)
        ->and(vy03Condition($a))->toBe('out_of_service');
});

it('VY-03 (the screen): the same work order WITHOUT out of order is saved at once — the guard is about the room, not the work order', function () {
    $s = vy03World(units: 2, bookings: 2);
    $a = $s['units'][0];

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->post(route('app.maintenance.store'), ['unit_id' => $a->id, 'title' => 'Squeaky door', 'category' => 'other', 'priority' => 'low'])
        ->assertRedirect();

    expect(MaintenanceTicket::query()->where('unit_id', $a->id)->count())->toBe(1)
        ->and(vy03Condition($a))->toBe('in_service');
});

it('⚠️ VY-03 (no more silent clamp): the calendar says OVERBOOKED on a night with more bookings than rooms', function () {
    $s = vy03World(units: 2, bookings: 2);
    vy03Open($s, $s['units'][0], ['acknowledge_oversell' => true]);

    app(PropertyContext::class)->activate([(string) $s['property']->id], null);
    $this->travelTo('2026-09-01 09:00:00');

    $html = Livewire\Livewire::actingAs(operatorUser($s))->test(ReservationRack::class)->html();

    expect($html)->toContain('Overbooked by 1 on')
        ->and(substr_count($html, 'data-overbooked="1"'))->toBe(2);   // 1 and 2 September, and only those
});

it('VY-03: a type-level block is counted too, and a block on nights nobody booked is never refused', function () {
    $s = vy03World(units: 2, bookings: 2);

    // nights with no booking: free to block
    expect(vy03Block($s, $s['units'][0], '2026-09-05', '2026-09-07')->exists)->toBeTrue();

    // a block on the room TYPE (no unit) over the booked nights: refused
    expect(fn () => app(AssignmentService::class)->blockInventory([
        'property_id' => $s['property']->id, 'unit_id' => null, 'room_type_id' => (string) $s['roomType']->id,
        'start_date' => '2026-09-01', 'end_date' => '2026-09-02', 'reason' => 'manual',
    ]))->toThrow(InventoryOversoldException::class);

    expect(Reservation::query()->where('property_id', $s['property']->id)->count())->toBe(2);
});

it('⚠️ VY-03 (the lock): both paths read the nights FOR UPDATE — the rows a booking locks — before they count', function () {
    $s = vy03World(units: 3, bookings: 1);
    $locked = function (callable $act): bool {
        $seen = false;
        DB::listen(function ($query) use (&$seen): void {
            if (str_contains(strtolower($query->sql), 'from `availability`') && str_contains(strtolower($query->sql), 'for update')) {
                $seen = true;
            }
        });
        $act();

        return $seen;
    };

    expect($locked(fn () => vy03Block($s, $s['units'][0])))->toBeTrue()
        ->and($locked(fn () => vy03Open($s, $s['units'][1])))->toBeTrue();
});

it('⚠️ VY-03 (no gap): the stored figure is recomputed INSIDE the transaction — not only by the event after the commit', function () {
    $s = vy03World(units: 3, bookings: 1);
    $rt = (string) $s['roomType']->id;
    $row = fn (): object => DB::table('availability')->where('room_type_id', $rt)->whereDate('date', '2026-09-01')->first();

    // With the domain event silenced, nothing recomputes after the commit: the figure must ALREADY be right.
    Event::fake([DomainEventRecorded::class]);

    vy03Block($s, $s['units'][0]);
    expect((int) $row()->blocked)->toBe(1);

    vy03Open($s, $s['units'][1]);
    expect((int) $row()->out_of_order)->toBe(1);
});
