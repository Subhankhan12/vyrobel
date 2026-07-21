<?php

use App\Models\Property;
use App\Models\Shift;
use App\Support\Schedule\ShiftType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 7d · SG1 — the weekly staff roster grid. The roster is over the org's REAL staff (never fabricated
// names), TENANT + PROPERTY scoped (a shift lands on the SELECTED property via PropertyContext, never
// cross-property/org), and MANAGING it is manager-gated (schedule.manage, NOT Front Desk); viewing is broad.

/** An org with two properties (A, B) + a manager + a front-desk clerk + an owner (a non-staff row). */
function scheduleScenario(): array
{
    $org = newDemoOrg();
    $propertyA = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $propertyB = Property::create(['name' => 'Bravo House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);

    $s = ['org' => $org];
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');   // schedule.manage
    $desk = operatorUser($s, ['name' => 'Frank Desk'], 'Front Desk');           // schedule.view only
    $owner = operatorUser($s, ['name' => 'Olivia Owner'], 'External Owner');    // NOT staff — never rostered

    return compact('org', 'propertyA', 'propertyB', 'manager', 'desk', 'owner');
}

// ── 1. 🪤 REAL STAFF ONLY — the grid rows are the org's actual team, never fabricated people ─────────
it('rosters the org’s real staff and excludes owners and prototype fixtures', function () {
    $s = scheduleScenario();

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->get('/app/schedule')
        ->assertOk()
        ->assertSee('Rosa Manager')      // real staff…
        ->assertSee('Frank Desk')
        ->assertDontSee('Olivia Owner')  // …but NOT the External Owner (not staff)…
        ->assertDontSee('Marco Reyes')   // …and NOT the design's prototype fixtures.
        ->assertDontSee('Lena Roth');

    // A department with no staff → an honest empty state, never fabricated people.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->get('/app/schedule?dept=housekeeping')
        ->assertOk()
        ->assertSee('No staff in Housekeeping');
});

// ── 2. 🔒 A SHIFT LANDS ON THE SELECTED PROPERTY (property + tenant scoped, never cross-property) ────
it('assigns a shift to the selected property and never to another', function () {
    $s = scheduleScenario();
    $today = CarbonImmutable::now()->toDateString();

    // Property B is selected → the shift must land on B, never the alphabetically-first A.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $s['desk']->id, 'date' => $today, 'shift_type' => ShiftType::LATE, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect();

    expect(Shift::where('property_id', $s['propertyB']->id)->where('user_id', $s['desk']->id)->whereDate('date', $today)->where('shift_type', 'late')->exists())->toBeTrue();
    expect(Shift::where('property_id', $s['propertyA']->id)->count())->toBe(0);   // NEVER property A

    // The roster READ follows the property: B shows the shift (a viewer sees the badge with its hours title),
    // A does not. `title="15:00–23:00"` is unique to a rendered Late badge (the legend is plain text).
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->get('/app/schedule')->assertOk()->assertSee('title="15:00–23:00"', false);
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->get('/app/schedule')->assertOk()->assertDontSee('title="15:00–23:00"', false);
});

// ── 3. 🪤/🔒 REAL STAFF GUARD — a non-staff / cross-org user can't be assigned a shift ───────────────
it('refuses to assign a shift to a non-staff or cross-org user', function () {
    $s = scheduleScenario();
    $today = CarbonImmutable::now()->toDateString();

    // A user from a DIFFERENT org — not in this org's staff list → rejected (validation), no shift written.
    $other = newDemoOrg();
    $foreign = operatorUser(['org' => $other], ['name' => 'Foreign Staff'], 'Hotel Manager');

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $foreign->id, 'date' => $today, 'shift_type' => ShiftType::DAY, 'week' => 0, 'dept' => 'all'])
        ->assertSessionHasErrors('user_id');
    expect(Shift::where('user_id', $foreign->id)->count())->toBe(0);

    // The org's OWN owner (a non-staff row) also can't be shifted.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $s['owner']->id, 'date' => $today, 'shift_type' => ShiftType::DAY, 'week' => 0, 'dept' => 'all'])
        ->assertSessionHasErrors('user_id');
});

// ── 4. 🔒 PERMISSION — VIEW is broad, MANAGE is manager-only (Front Desk is refused the write) ──────
it('lets any staff view but only a manager assign shifts', function () {
    $s = scheduleScenario();
    $today = CarbonImmutable::now()->toDateString();

    // Front Desk can VIEW the roster…
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->get('/app/schedule')->assertOk();

    // …but CANNOT assign a shift (schedule.manage is a manager-level gate).
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $s['desk']->id, 'date' => $today, 'shift_type' => ShiftType::DAY, 'week' => 0, 'dept' => 'all'])
        ->assertForbidden();
    expect(Shift::count())->toBe(0);

    // A manager can.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $s['desk']->id, 'date' => $today, 'shift_type' => ShiftType::DAY, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect();
    expect(Shift::count())->toBe(1);
});
