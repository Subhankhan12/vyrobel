<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 3d — the calendar/rack booking bars link to their reservation detail (the wireframe's clickable bar),
// and a real ?days= range switcher (7/14/30) drives the window.

it('links a booking bar to its reservation and offers the range switcher', function () {
    $s = seedScenario(1);
    $res = book($s);   // a real reservation with an active line (check_in 2026-09-01)
    $manager = operatorUser($s, [], 'Hotel Manager');
    $session = ['current_property_id' => $s['property']->id];

    // The window covering the stay renders the bar as a link to the reservation detail.
    $this->actingAs($manager)->withSession($session)->get(route('app.calendar', ['start' => '2026-09-01']))
        ->assertOk()
        ->assertSee(route('app.reservations.show', $res->id), false);

    // The range switcher renders and ?days=30 is honoured (30n selected).
    $this->actingAs($manager)->withSession($session)->get(route('app.calendar', ['days' => 30]))
        ->assertOk()
        ->assertSee('30n');
});
