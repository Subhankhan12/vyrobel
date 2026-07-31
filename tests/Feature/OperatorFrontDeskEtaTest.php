<?php

use App\Models\Reservation;
use App\Models\ReservationUnit;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 3 — the front desk now SEES the guest's estimated arrival (real, captured at booking; was dropped).

it('shows the estimated arrival on the front-desk arrival row', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $today = CarbonImmutable::today()->toDateString();

    $res = Reservation::create([
        'property_id' => $s['property']->id,
        'reference' => 'VYR-ETA1',
        'status' => 'confirmed',
        'source' => 'direct',
        'expected_arrival' => '3–6 PM',
        'lead_guest_id' => $s['guest']->id,
        'check_in' => $today,
        'check_out' => CarbonImmutable::today()->addDays(2)->toDateString(),
        'currency' => 'EUR',
    ]);
    ReservationUnit::create([
        'reservation_id' => $res->id, 'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id,
        'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $today, 'check_out' => CarbonImmutable::today()->addDays(2)->toDateString(),
        'price_minor' => 20000, 'currency' => 'EUR', 'status' => 'active',
    ]);

    $this->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->get(route('app.front-desk', ['date' => $today]))
        ->assertOk()
        ->assertSee('Arrival · 3–6 PM');
});
