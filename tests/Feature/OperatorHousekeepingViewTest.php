<?php

use App\Models\HousekeepingTask;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Operator Screen 3 (Housekeeping → HousekeepingBoard, /app/housekeeping). Locks its
// PRESENTATION guarantees: real tasks render in the real status columns (no invented states); the
// status-advance ACTION persists through HousekeepingService (incl. the unit-cleanliness sync) and
// stays tenant-scoped (a cross-org status change 404s and mutates nothing).

/** A real housekeeping OPERATOR: an active member of the scenario's org holding a back-office role. */
function hkMember(array $scenario, string $name = 'Marisol Vandenberg'): User
{
    $user = User::factory()->create(['name' => $name]);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $scenario['org']);
}

/** Create a real housekeeping task on the scenario's first unit. */
function hkTask(array $scenario, array $overrides = []): HousekeepingTask
{
    return HousekeepingTask::create(array_merge([
        'organization_id' => $scenario['org']->id,
        'property_id' => $scenario['property']->id,
        'unit_id' => $scenario['units'][0]->id,
        'date' => '2026-09-03',
        'type' => 'departure',
        'status' => 'pending',
        'priority' => 'high',
    ], $overrides));
}

// ── 1. Real tasks render in the real status columns, with real fields ────────────────────────────
it('renders real housekeeping tasks with real type/priority/assignee', function () {
    $s = seedScenario(1);
    $s['units'][0]->update(['code' => 'HKUNIT']);
    $cleaner = User::factory()->create(['name' => 'Rosa Delacroix']);
    hkTask($s, ['assigned_to' => $cleaner->id]);

    $this->actingAs(hkMember($s))->get('/app/housekeeping')
        ->assertOk()
        ->assertSee('Housekeeping')
        ->assertSee('HKUNIT')             // real unit
        ->assertSee('Departure clean')    // real type (humanized, real enum)
        ->assertSee('Pending')            // real status column
        ->assertSee('High')               // real priority
        ->assertSee('Rosa Delacroix')     // real assigned housekeeper (staff, not guest PII)
        ->assertSee('Start')              // the real forward action for a pending task
        ->assertDontSee('Sparkling')      // no invented status
        ->assertDontSee('Overdue');
});

// ── 2. Status advance PERSISTS through the real action (and syncs the unit on clean) ─────────────
it('advances a task through the real housekeeping action and persists it', function () {
    $s = seedScenario(1);
    $task = hkTask($s);
    $user = hkMember($s);

    $this->actingAs($user)
        ->post(route('app.housekeeping.status', $task->id), ['status' => 'in_progress'])
        ->assertRedirect();
    $this->assertDatabaseHas('housekeeping_tasks', ['id' => $task->id, 'status' => 'in_progress']);

    // Marking clean must run the FULL real action — task clean + unit cleanliness synced.
    $this->actingAs($user)
        ->post(route('app.housekeeping.status', $task->id), ['status' => 'clean'])
        ->assertRedirect();
    $this->assertDatabaseHas('housekeeping_tasks', ['id' => $task->id, 'status' => 'clean']);
    $this->assertDatabaseHas('units', ['id' => $s['units'][0]->id, 'housekeeping_status' => 'clean']);
});

// ── 3. An out-of-enum target is rejected, never invented ─────────────────────────────────────────
it('rejects a status that is not in the real enum', function () {
    $s = seedScenario(1);
    $task = hkTask($s);

    $this->actingAs(hkMember($s))
        ->post(route('app.housekeeping.status', $task->id), ['status' => 'teleported'])
        ->assertStatus(422);

    $this->assertDatabaseHas('housekeeping_tasks', ['id' => $task->id, 'status' => 'pending']);   // unchanged
});

// ── 4. Tenant isolation — org B cannot see OR act on org A's task ────────────────────────────────
it('isolates tenants — org B cannot view or advance org A\'s task', function () {
    $sA = seedScenario(1);
    $sA['units'][0]->update(['code' => 'AUNIT']);
    $taskA = hkTask($sA);
    $userA = hkMember($sA, 'Amara Okafor');

    $sB = seedScenario(1);
    $userB = hkMember($sB, 'Bao Nguyen');

    $this->actingAs($userA)->get('/app/housekeeping')->assertOk()->assertSee('AUNIT');
    $this->actingAs($userB)->get('/app/housekeeping')->assertOk()->assertDontSee('AUNIT');

    // B cannot advance A's task — tenant-scoped resolve 404s, nothing mutates.
    $this->actingAs($userB)
        ->post(route('app.housekeeping.status', $taskA->id), ['status' => 'in_progress'])
        ->assertNotFound();
    $this->assertDatabaseHas('housekeeping_tasks', ['id' => $taskA->id, 'status' => 'pending']);
});

// ── Batch C · GATE 1 — REAL housekeeper assignment (housekeeping.manage; Housekeeping department only) ──
it('assigns a REAL housekeeper to a task and persists it', function () {
    $s = seedScenario(1);
    $manager = hkMember($s);                                                    // Hotel Manager · housekeeping.manage
    $cleaner = operatorUser($s, ['name' => 'Rosa Cleaner'], 'Housekeeping');    // a REAL housekeeper (dept)
    $task = hkTask($s);                                                         // unassigned

    $this->actingAs($manager)
        ->post(route('app.housekeeping.assign', $task->id), ['user_id' => $cleaner->id])
        ->assertRedirect(route('app.housekeeping'));

    $this->assertDatabaseHas('housekeeping_tasks', ['id' => $task->id, 'assigned_to' => $cleaner->id]);
});

it('refuses a non-housekeeper — only the Housekeeping department is assignable (no fabrication)', function () {
    $s = seedScenario(1);
    $frontDesk = operatorUser($s, ['name' => 'Front Deskington'], 'Front Desk');   // NOT a housekeeper
    $task = hkTask($s);

    $this->actingAs(hkMember($s))
        ->post(route('app.housekeeping.assign', $task->id), ['user_id' => $frontDesk->id])
        ->assertSessionHasErrors('user_id');   // Rule::in(housekeeperIds) rejects a non-housekeeper

    $this->assertDatabaseHas('housekeeping_tasks', ['id' => $task->id, 'assigned_to' => null]);
});

it('gates assign on housekeeping.manage — a role without it is refused (403)', function () {
    $s = seedScenario(1);
    $cleaner = operatorUser($s, [], 'Housekeeping');
    $task = hkTask($s);

    // A Reservation Agent runs the front desk but does NOT hold housekeeping.manage → the route gate 403s.
    $this->actingAs(operatorUser($s, [], 'Reservation Agent'))
        ->post(route('app.housekeeping.assign', $task->id), ['user_id' => $cleaner->id])
        ->assertForbidden();

    $this->assertDatabaseHas('housekeeping_tasks', ['id' => $task->id, 'assigned_to' => null]);
})->group('security');

it('refuses a cross-org user_id — the assignable set is THIS org\'s real housekeepers', function () {
    $s = seedScenario(1);
    $task = hkTask($s);

    $otherOrg = seedScenario(1);
    $foreign = operatorUser($otherOrg, [], 'Housekeeping');   // a housekeeper in ANOTHER org

    $this->actingAs(hkMember($s))
        ->post(route('app.housekeeping.assign', $task->id), ['user_id' => $foreign->id])
        ->assertSessionHasErrors('user_id');   // not in this org's housekeeperIds → rejected

    $this->assertDatabaseHas('housekeeping_tasks', ['id' => $task->id, 'assigned_to' => null]);
})->group('security');
