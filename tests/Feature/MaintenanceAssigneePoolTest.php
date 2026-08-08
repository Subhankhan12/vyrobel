<?php

use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Membership;
use App\Models\User;
use App\Services\MaintenanceSettingsService;
use App\Support\Schedule\StaffRoster;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * THE TECHNICIAN POOL IS STAFF-ONLY (audit 2026-08-07 — the parity matrix's systemic MEDIUM, 3 surfaces).
 *
 * The Maintenance board, the Work Order reassign picker and Maintenance Settings each built their own pool
 * by selecting users with an ACTIVE MEMBERSHIP — and a Membership is NOT operator access: External Owners
 * hold one, because it is how their organisation resolves. So all three offered "Demo Owner",
 * "Demo Owner Accountant" and "Demo Owner Viewer" as assignable technicians.
 *
 * ⚠️ Most consequential on Settings, where category AUTO-ASSIGNMENT routes new work orders to the chosen
 * user: an owner could have been handed operational work and the guest-adjacent detail that comes with it.
 * That is a #28 perimeter breach, not a cosmetic list bug.
 *
 * ⚠️ AND THE PICKER IS ONLY HALF OF IT. The server-side write guards ALSO checked membership only, so a
 * POSTed owner id was still ACCEPTED after the pickers stopped offering it. Fixing the list without the
 * guard would have been theatre. Both halves are pinned below.
 */

/** @return array{0: array<string,mixed>, 1: User, 2: User} */
function poolScenario(): array
{
    $s = seedScenario(1);
    $staff = operatorUser($s, ['name' => 'Mia Manager'], 'Hotel Manager');
    $owner = ownerRoleUser($s);   // holds a Membership + the External Owner role — the exact shape of the bug

    return [$s, $staff, $owner];
}

it('excludes owner-realm users from the shared assignable pool', function () {
    [$s, $staff, $owner] = poolScenario();

    $pool = app(StaffRoster::class)->assignableStaff();
    $ids = array_column($pool, 'id');

    expect($ids)->toContain((string) $staff->getKey());
    expect($ids)->not->toContain((string) $owner->getKey(), 'an External Owner is offered as a technician');

    // The owner DOES hold an active membership — proving the exclusion is by ROLE, not by a missing
    // membership, which is the distinction the original code got wrong.
    expect(Membership::query()
        ->where('organization_id', $s['org']->id)
        ->where('user_id', $owner->getKey())
        ->where('status', 'active')->exists())->toBeTrue();
});

it('excludes owners from the Maintenance Settings technician list that drives AUTO-ASSIGNMENT', function () {
    [, $staff, $owner] = poolScenario();

    $ids = array_column(app(MaintenanceSettingsService::class)->technicians(), 'id');

    expect($ids)->toContain((string) $staff->getKey());
    expect($ids)->not->toContain((string) $owner->getKey());
});

it('REFUSES a crafted assign to an owner — the guard, not just the list', function () {
    [$s, $staff, $owner] = poolScenario();

    $ticket = MaintenanceTicket::factory()->create([
        'organization_id' => $s['org']->id,
        'unit_id' => $s['units'][0]->id,
        'status' => 'open',
    ]);

    // The picker no longer offers the owner — but a crafted POST must be refused too.
    $this->actingAs($staff)
        ->post("/app/maintenance/{$ticket->id}/assign", ['user_id' => (string) $owner->getKey()])
        ->assertStatus(422);

    expect($ticket->fresh()->assigned_to)->not->toBe((string) $owner->getKey());

    // …and a real staff member still assigns successfully, so the guard is not simply refusing everything.
    $this->actingAs($staff)
        ->post("/app/maintenance/{$ticket->id}/assign", ['user_id' => (string) $staff->getKey()])
        ->assertRedirect();

    expect((string) $ticket->fresh()->assigned_to)->toBe((string) $staff->getKey());
});

it('REFUSES an owner as a category auto-assignee in settings', function () {
    [$s, $staff, $owner] = poolScenario();

    // Saving an owner as the plumbing assignee must not stick — every new plumbing work order would
    // otherwise be routed to them.
    $this->actingAs($staff);
    app(MaintenanceSettingsService::class)->update(['assignee_plumbing_id' => (string) $owner->getKey()]);

    $setting = MaintenanceSetting::query()->where('organization_id', $s['org']->id)->first();

    expect($setting?->assignee_plumbing_id)->not->toBe((string) $owner->getKey());
});
