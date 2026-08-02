<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GATE 5.3 — the drag-to-reschedule ROUTE (POST /app/reservations/{reservation}/reschedule → the certified
// AssignmentService::reschedule). Locks the ACTOR gate (can:reservations.assign), the READ-ONLY guard, the
// tenant/line scoping, and the server-side stay-length preservation (a MOVE, no client date math).

function reschedulePayload(array $s, string $lineId, string $newCheckIn, string $unitId): array
{
    return [
        'reservation_unit_id' => $lineId,
        'new_check_in' => $newCheckIn,
        'unit_id' => $unitId,
        'return_start' => '2026-09-01',
        'return_days' => '14',
    ];
}

it('reschedules a booking via the route, preserving the stay length (server-derived check_out)', function () {
    $s = seedScenario(2);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);   // U1, 2026-09-01..03 (2 nights)
    $line = $res->reservationUnits()->first();
    $operator = operatorUser($s, ['name' => 'Move Master'], 'Front Desk');   // holds reservations.assign

    $this->actingAs($operator)
        ->post(route('app.reservations.reschedule', $res->id), reschedulePayload($s, $line->id, '2026-09-05', $s['units'][1]->id))
        ->assertRedirect();

    $line->refresh();
    expect((string) $line->unit_id)->toBe((string) $s['units'][1]->id)
        ->and($line->check_in->toDateString())->toBe('2026-09-05')
        ->and($line->check_out->toDateString())->toBe('2026-09-07');   // 2 nights preserved by the server
});

it('refuses the reschedule when the workspace is read-only (paused) — nothing moves', function () {
    $s = seedScenario(2);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $res->reservationUnits()->first();
    $operator = operatorUser($s, ['name' => 'Move Master'], 'Front Desk');

    $s['org']->update(['paused_until' => now()->addDays(5)]);   // read-only workspace

    $this->actingAs($operator)
        ->post(route('app.reservations.reschedule', $res->id), reschedulePayload($s, $line->id, '2026-09-05', $s['units'][1]->id))
        ->assertRedirect();

    $line->refresh();
    expect((string) $line->unit_id)->toBe((string) $s['units'][0]->id)   // unchanged
        ->and($line->check_in->toDateString())->toBe('2026-09-01');
});

it('forbids an operator without reservations.assign from rescheduling', function () {
    $s = seedScenario(2);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $res->reservationUnits()->first();
    $operator = operatorUser($s, ['name' => 'No Move'], 'Accountant');   // does NOT hold reservations.assign

    $this->actingAs($operator)
        ->post(route('app.reservations.reschedule', $res->id), reschedulePayload($s, $line->id, '2026-09-05', $s['units'][1]->id))
        ->assertForbidden();

    expect((string) $line->refresh()->unit_id)->toBe((string) $s['units'][0]->id);
});
