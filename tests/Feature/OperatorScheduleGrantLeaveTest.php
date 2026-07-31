<?php

use App\Models\LeaveRequest;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #104 GATE 3d — the manager grant-leave route existed with no UI; the schedule now has the form + a "Coverage
// this week" strip (derived from the same RosterWeek builder, so it reconciles). Self-grant stays refused.

it('renders coverage + a working manager grant-leave form; refuses self-grant', function () {
    $org = newDemoOrg();
    $property = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $s = ['org' => $org, 'property' => $property];
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');   // schedule.manage
    $desk = operatorUser($s, ['name' => 'Frank Desk'], 'Front Desk');           // rostered staff
    $session = ['current_property_id' => $property->id];

    // The coverage strip + the grant form render.
    $this->actingAs($manager)->withSession($session)->get(route('app.schedule'))
        ->assertOk()
        ->assertSee('Team on rota')
        ->assertSee('Rostered hours')
        ->assertSee('Add leave for a team member');

    // Manager grants leave for Frank → a real approved leave exists.
    $this->actingAs($manager)->withSession($session)
        ->post(route('app.schedule.leave.grant'), ['user_id' => $desk->id, 'start_date' => '2026-08-10', 'end_date' => '2026-08-12'])
        ->assertRedirect();
    expect(LeaveRequest::query()->where('user_id', $desk->id)->where('status', 'approved')->exists())->toBeTrue();

    // A manager can't grant their OWN leave (self-approval separation) — refused, no approved self-leave.
    $this->actingAs($manager)->withSession($session)
        ->post(route('app.schedule.leave.grant'), ['user_id' => $manager->id, 'start_date' => '2026-08-10', 'end_date' => '2026-08-12'])
        ->assertRedirect();
    expect(LeaveRequest::query()->where('user_id', $manager->id)->where('status', 'approved')->exists())->toBeFalse();
});
