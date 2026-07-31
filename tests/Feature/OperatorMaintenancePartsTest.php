<?php

use App\Models\MaintenanceSetting;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 1d — the awaiting-parts writer (setAwaitingParts) existed but was wired to NO screen. The ticket
// detail now has the editor; and the SAME real SLA clock the board shows renders here (shared slaLabel).

it('marks a ticket awaiting parts from the detail, then clears it (real persistence)', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');           // holds maintenance.manage
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Leaky tap', 'category' => 'plumbing']);
    $session = ['current_property_id' => $s['property']->id];

    // Mark awaiting parts (name + ETA) — the first UI for a real backend.
    $this->actingAs($manager)->withSession($session)
        ->post(route('app.maintenance.parts', $ticket->id), ['awaiting_parts' => '1', 'part_name' => 'Thermostat', 'part_eta' => '2026-09-15'])
        ->assertRedirect(route('app.maintenance.show', $ticket->id));

    $ticket->refresh();
    expect((bool) $ticket->awaiting_parts)->toBeTrue()
        ->and($ticket->part_name)->toBe('Thermostat');

    // The detail renders the awaiting state.
    $this->actingAs($manager)->withSession($session)->get(route('app.maintenance.show', $ticket->id))
        ->assertOk()->assertSee('Waiting on: Thermostat');

    // Clear when parts arrive.
    $this->actingAs($manager)->withSession($session)
        ->post(route('app.maintenance.parts', $ticket->id), ['awaiting_parts' => '0'])
        ->assertRedirect();
    expect((bool) $ticket->refresh()->awaiting_parts)->toBeFalse()
        ->and($ticket->part_name)->toBeNull();
});

it('renders the real SLA target on the detail when the property configures one', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    MaintenanceSetting::create(['property_id' => $s['property']->id, 'sla_high_hours' => 24]);
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Fix heater', 'priority' => 'high']);

    $this->actingAs($manager)->withSession(['current_property_id' => $s['property']->id])
        ->get(route('app.maintenance.show', $ticket->id))
        ->assertOk()
        ->assertSee('Resolution target')
        ->assertSee('to target');   // the shared slaLabel wording
});
