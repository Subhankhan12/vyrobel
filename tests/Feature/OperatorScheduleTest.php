<?php

use App\Models\LeaveRequest;
use App\Models\Property;
use App\Models\Shift;
use App\Support\Schedule\RosterWeek;
use App\Support\Schedule\ShiftType;
use App\Support\Tenancy\TenantContext;
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

    // The roster READ follows the property: B shows the shift (a viewer sees the rendered chip), A does not.
    // `data-shift-type="late"` marks a rendered Late shift chip and is unique to it (the legend is plain text).
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->get('/app/schedule')->assertOk()->assertSee('data-shift-type="late"', false);
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->get('/app/schedule')->assertOk()->assertDontSee('data-shift-type="late"', false);
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

// ── 5. 🔒 REQUEST → MANAGER APPROVES → approved leave BLOCKS a shift on those days ───────────────────
it('turns a request into approved leave that blocks a shift on those days', function () {
    $s = scheduleScenario();
    $today = CarbonImmutable::now()->toDateString();

    // The clerk requests THEIR OWN time off → pending (user_id is the requester, never someone else's).
    $this->actingAs($s['desk'])->post('/app/schedule/time-off', ['start_date' => $today, 'end_date' => $today, 'reason' => 'Annual leave'])
        ->assertRedirect();
    $leave = LeaveRequest::where('user_id', $s['desk']->id)->firstOrFail();
    expect($leave->status)->toBe('pending')->and((string) $leave->requested_by)->toBe((string) $s['desk']->id);

    // A manager approves → approved leave.
    $this->actingAs($s['manager'])->post("/app/schedule/leave/{$leave->id}/approve", ['week' => 0, 'dept' => 'all'])->assertRedirect();
    expect($leave->fresh()->status)->toBe('approved');

    // 🪤 Assigning a WORKING shift on the leave day is REFUSED (a real conflict, not a fabricated warning).
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $s['desk']->id, 'date' => $today, 'shift_type' => ShiftType::LATE, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect()->assertSessionHas('schedule_error');
    expect(Shift::where('user_id', $s['desk']->id)->count())->toBe(0);
});

// ── 6. 🔒 SELF-APPROVAL is impossible — a manager can't approve/grant their OWN leave ────────────────
it('refuses to let a manager approve or grant their own time-off', function () {
    $s = scheduleScenario();
    $today = CarbonImmutable::now()->toDateString();

    // The manager requests their OWN time off, then tries to approve it → refused, stays pending.
    $this->actingAs($s['manager'])->post('/app/schedule/time-off', ['start_date' => $today, 'end_date' => $today])->assertRedirect();
    $own = LeaveRequest::where('user_id', $s['manager']->id)->firstOrFail();
    $this->actingAs($s['manager'])->post("/app/schedule/leave/{$own->id}/approve", ['week' => 0, 'dept' => 'all'])
        ->assertRedirect()->assertSessionHas('schedule_error');
    expect($own->fresh()->status)->toBe('pending');

    // A DIFFERENT manager can approve it.
    $mgr2 = operatorUser(['org' => $s['org']], ['name' => 'Max Manager'], 'Hotel Manager');
    $this->actingAs($mgr2)->post("/app/schedule/leave/{$own->id}/approve", ['week' => 0, 'dept' => 'all'])->assertRedirect();
    expect($own->fresh()->status)->toBe('approved');

    // A manager also can't GRANT their own leave directly — the self-grant adds NO new approved leave (the
    // only approved leave for the manager remains the one ANOTHER manager approved above).
    $this->actingAs($s['manager'])->post('/app/schedule/leave', ['user_id' => $s['manager']->id, 'start_date' => $today, 'end_date' => $today])
        ->assertRedirect()->assertSessionHas('schedule_error');
    expect(LeaveRequest::where('user_id', $s['manager']->id)->where('status', 'approved')->count())->toBe(1);
});

// ── 7. 🪤 THE CONFLICT IS REAL — a shift + approved leave on the same day is flagged; no overlap, none ─
it('flags a real shift-on-leave conflict and nothing when there is no overlap', function () {
    $s = scheduleScenario();
    $today = CarbonImmutable::now()->toDateString();

    // A shift with NO leave → the grid shows no conflict.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $s['desk']->id, 'date' => $today, 'shift_type' => ShiftType::LATE, 'week' => 0, 'dept' => 'all'])->assertRedirect();
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->get('/app/schedule')->assertOk()->assertDontSee('shifted');   // "on leave & shifted" only shows on a real conflict

    // A manager GRANTS the clerk approved leave covering that same day → a REAL computed conflict now shows.
    $this->actingAs($s['manager'])->post('/app/schedule/leave', ['user_id' => $s['desk']->id, 'start_date' => $today, 'end_date' => $today])->assertRedirect();
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->get('/app/schedule')->assertOk()->assertSee('shifted');
});

// ── 8. 🔒 PERMISSION — requesting OWN time off is broad; grant/approve are manager-only ──────────────
it('lets any staff request time off but only a manager approves or grants it', function () {
    $s = scheduleScenario();
    $today = CarbonImmutable::now()->toDateString();

    // Front Desk can request their OWN time off…
    $this->actingAs($s['desk'])->post('/app/schedule/time-off', ['start_date' => $today, 'end_date' => $today])->assertRedirect();
    $req = LeaveRequest::where('user_id', $s['desk']->id)->firstOrFail();

    // …but CANNOT approve or grant (manager-level).
    $this->actingAs($s['desk'])->post("/app/schedule/leave/{$req->id}/approve", ['week' => 0, 'dept' => 'all'])->assertForbidden();
    $this->actingAs($s['desk'])->post('/app/schedule/leave', ['user_id' => $s['manager']->id, 'start_date' => $today, 'end_date' => $today])->assertForbidden();
    expect($req->fresh()->status)->toBe('pending');
});

// ── 9. 📄 THE SCHEDULE PDF — real, property-scoped, reconciles to the grid ──────────────────────────
it('streams a property-scoped schedule PDF that reconciles to the grid', function () {
    $s = scheduleScenario();
    $today = CarbonImmutable::now()->toDateString();

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $s['desk']->id, 'date' => $today, 'shift_type' => ShiftType::LATE, 'week' => 0, 'dept' => 'all'])->assertRedirect();

    // The PDF streams for the SELECTED property; a viewer (schedule.view) may download it too.
    $res = $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyB']->id])->get('/app/schedule/pdf');
    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');

    // The PDF renders from the SAME RosterWeek as the grid, so it reconciles + is property-scoped: B has the
    // shift, A does not (the builder the PDF uses is property-scoped).
    app(TenantContext::class)->set($s['org']->id);
    $rosterB = RosterWeek::build(Property::find($s['propertyB']->id), 0, 'all');
    $rosterA = RosterWeek::build(Property::find($s['propertyA']->id), 0, 'all');
    $working = fn (RosterWeek $r): int => collect($r->rows)->flatMap(fn ($row) => $row['cells'])->where('working', true)->count();
    expect($working($rosterB))->toBe(1)->and($working($rosterA))->toBe(0);
});
