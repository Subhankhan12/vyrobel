<?php

use App\Models\Reservation;
use App\Models\ReservationUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 3c — the reservations list shows a REAL "Refunded" pill (payment_status='refunded') and its search
// now matches the assigned UNIT code (encrypted email/phone stay un-searchable — that deferral is unchanged).

it('shows a Refunded pill and searches by unit code', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');

    $res = Reservation::create([
        'property_id' => $s['property']->id,
        'reference' => 'VYR-REF1',
        'status' => 'checked_out',
        'source' => 'direct',
        'lead_guest_id' => $s['guest']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'currency' => 'EUR',
        'payment_status' => 'refunded',
    ]);
    ReservationUnit::create([
        'reservation_id' => $res->id,
        'room_type_id' => $s['roomType']->id,
        'unit_id' => $s['units'][0]->id,   // code "U1"
        'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'price_minor' => 20000,
        'currency' => 'EUR',
        'status' => 'active',
    ]);

    $session = ['current_property_id' => $s['property']->id];

    // The Refunded pill renders.
    $this->actingAs($manager)->withSession($session)->get(route('app.reservations'))
        ->assertOk()->assertSee('VYR-REF1')->assertSee('Refunded');

    // Searching the assigned unit code finds the reservation.
    $this->actingAs($manager)->withSession($session)->get(route('app.reservations', ['q' => $s['units'][0]->code]))
        ->assertOk()->assertSee('VYR-REF1');
});
