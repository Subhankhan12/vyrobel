<?php

use App\Models\OpenShift;
use App\Models\Property;
use App\Models\SchedulePublication;
use App\Models\Shift;
use App\Notifications\Operator\SchedulePublishedNotification;
use App\Support\Schedule\ShiftType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// #104b GATE 3 — the interactive-rota additions: PUBLISH WEEK (a real finalize stamp + a notify to the rostered
// team; edits stay live) and OPEN SHIFTS (an unassigned slot a manager posts, then FILLS into a real Shift via
// the certified ShiftRosterService). Plus the read-only guard the audit found missing on the schedule writes.
// Reuses the same scenario shape as OperatorScheduleTest (real staff, property-scoped, manager-gated writes).

/** An org with two properties + a manager (schedule.manage) + a front-desk clerk (schedule.view only). */
function interactiveScheduleScenario(): array
{
    $org = newDemoOrg();
    $propertyA = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $propertyB = Property::create(['name' => 'Bravo House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);

    $s = ['org' => $org];
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');
    $desk = operatorUser($s, ['name' => 'Frank Desk'], 'Front Desk');

    return compact('org', 'propertyA', 'propertyB', 'manager', 'desk');
}

// ── 1. PUBLISH WEEK — a real SchedulePublication stamp + the rostered team is notified ───────────────
it('publishes a week, stamping a publication and notifying the rostered staff', function () {
    Notification::fake();
    $s = interactiveScheduleScenario();
    $today = CarbonImmutable::now()->toDateString();

    // Put the desk clerk on the roster so there is a real staffer to notify.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $s['desk']->id, 'date' => $today, 'shift_type' => ShiftType::DAY, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect();

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/publish', ['week' => 0, 'dept' => 'all'])
        ->assertRedirect()->assertSessionHas('schedule_status');

    // A real publication stamp for THIS property + week (never fabricated).
    $pub = SchedulePublication::where('property_id', $s['propertyB']->id)->first();
    expect($pub)->not->toBeNull()
        ->and($pub->published_at)->not->toBeNull()
        ->and((string) $pub->published_by)->toBe((string) $s['manager']->id);

    // The rostered staffer (Frank) was notified; edits stay live afterwards (the roster is unchanged).
    Notification::assertSentTo($s['desk'], SchedulePublishedNotification::class);
    expect(Shift::where('user_id', $s['desk']->id)->count())->toBe(1);

    // Re-publishing updates the same row (idempotent per property+week), never a duplicate.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/publish', ['week' => 0, 'dept' => 'all'])->assertRedirect();
    expect(SchedulePublication::where('property_id', $s['propertyB']->id)->count())->toBe(1);
});

// ── 2. OPEN SHIFT — post an unassigned slot, then FILL it into a real Shift (the open row is consumed) ─
it('posts an open shift and fills it into a real Shift on the selected property', function () {
    $s = interactiveScheduleScenario();
    $date = CarbonImmutable::now()->toDateString();

    // Post an open (unassigned) slot on property B.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/open-shifts', ['date' => $date, 'shift_type' => 'late', 'department' => 'Front Desk', 'week' => 0, 'dept' => 'all'])
        ->assertRedirect()->assertSessionHas('schedule_status');

    $open = OpenShift::where('property_id', $s['propertyB']->id)->first();
    expect($open)->not->toBeNull()->and($open->shift_type)->toBe('late');

    // No Shift exists yet — an open shift is genuinely unassigned (never a fabricated assignment).
    expect(Shift::count())->toBe(0);

    // Fill it → a REAL Shift via the certified service, on B, and the open row is deleted.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post("/app/schedule/open-shifts/{$open->id}/fill", ['user_id' => $s['desk']->id, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect()->assertSessionHas('schedule_status');

    expect(Shift::where('property_id', $s['propertyB']->id)->where('user_id', $s['desk']->id)->whereDate('date', $date)->where('shift_type', 'late')->exists())->toBeTrue();
    expect(OpenShift::whereKey($open->id)->exists())->toBeFalse();   // the open slot is consumed
    expect(Shift::where('property_id', $s['propertyA']->id)->count())->toBe(0);   // never property A
});

// ── 3. OPEN SHIFT — remove an unassigned slot (no Shift ever created) ────────────────────────────────
it('removes an open shift without creating any Shift', function () {
    $s = interactiveScheduleScenario();
    $date = CarbonImmutable::now()->toDateString();

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/open-shifts', ['date' => $date, 'shift_type' => 'day', 'week' => 0, 'dept' => 'all'])->assertRedirect();
    $open = OpenShift::where('property_id', $s['propertyB']->id)->firstOrFail();

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->delete("/app/schedule/open-shifts/{$open->id}", ['week' => 0, 'dept' => 'all'])
        ->assertRedirect()->assertSessionHas('schedule_status');

    expect(OpenShift::count())->toBe(0)->and(Shift::count())->toBe(0);
});

// ── 4. READ-ONLY GUARD — a paused workspace refuses every schedule write (the audit gap, now closed) ──
it('refuses schedule writes while the workspace is read-only', function () {
    Notification::fake();
    $s = interactiveScheduleScenario();
    $today = CarbonImmutable::now()->toDateString();
    $s['org']->update(['paused_until' => CarbonImmutable::now()->addDays(5)]);

    // publish
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/publish', ['week' => 0, 'dept' => 'all'])
        ->assertRedirect()->assertSessionHas('schedule_error');
    expect(SchedulePublication::count())->toBe(0);
    Notification::assertNothingSent();

    // create open shift
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/open-shifts', ['date' => $today, 'shift_type' => 'day', 'week' => 0, 'dept' => 'all'])
        ->assertRedirect()->assertSessionHas('schedule_error');
    expect(OpenShift::count())->toBe(0);

    // assign a plain shift (the existing write, now guarded)
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/shift', ['user_id' => $s['desk']->id, 'date' => $today, 'shift_type' => ShiftType::DAY, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect()->assertSessionHas('schedule_error');
    expect(Shift::count())->toBe(0);
});

// ── 5. PERMISSION — the new writes are manager-gated (Front Desk / schedule.view is refused) ─────────
it('gates publish and open-shift writes behind schedule.manage', function () {
    $s = interactiveScheduleScenario();
    $date = CarbonImmutable::now()->toDateString();

    // A manager posts an open shift so there is a real row for the clerk to (fail to) act on.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/open-shifts', ['date' => $date, 'shift_type' => 'day', 'week' => 0, 'dept' => 'all'])->assertRedirect();
    $open = OpenShift::firstOrFail();

    // Front Desk (schedule.view only) is refused on every new write.
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/publish', ['week' => 0, 'dept' => 'all'])->assertForbidden();
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post('/app/schedule/open-shifts', ['date' => $date, 'shift_type' => 'day', 'week' => 0, 'dept' => 'all'])->assertForbidden();
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post("/app/schedule/open-shifts/{$open->id}/fill", ['user_id' => $s['desk']->id, 'week' => 0, 'dept' => 'all'])->assertForbidden();
    $this->actingAs($s['desk'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->delete("/app/schedule/open-shifts/{$open->id}", ['week' => 0, 'dept' => 'all'])->assertForbidden();

    // Nothing changed: no publication, the open row survives, no shift.
    expect(SchedulePublication::count())->toBe(0)
        ->and(OpenShift::count())->toBe(1)
        ->and(Shift::count())->toBe(0);
});

// ── 6. PROPERTY SCOPE — an open shift can only be filled/removed under ITS OWN property (never cross-property) ─
it('refuses to fill or remove an open shift while a different property is selected', function () {
    $s = interactiveScheduleScenario();
    $date = CarbonImmutable::now()->toDateString();

    // Post an open shift on property A.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post('/app/schedule/open-shifts', ['date' => $date, 'shift_type' => 'day', 'week' => 0, 'dept' => 'all'])->assertRedirect();
    $open = OpenShift::where('property_id', $s['propertyA']->id)->firstOrFail();

    // Now property B is selected — filling A's open shift must 404 (never create a shift on B off an A slot).
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->post("/app/schedule/open-shifts/{$open->id}/fill", ['user_id' => $s['desk']->id, 'week' => 0, 'dept' => 'all'])
        ->assertNotFound();
    // …and removing it under the wrong property also 404s.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyB']->id])
        ->delete("/app/schedule/open-shifts/{$open->id}", ['week' => 0, 'dept' => 'all'])
        ->assertNotFound();

    // The A open row is untouched; no shift landed on either property.
    expect(OpenShift::whereKey($open->id)->exists())->toBeTrue()
        ->and(Shift::count())->toBe(0);

    // Under the CORRECT property (A) it fills into a real shift on A.
    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['propertyA']->id])
        ->post("/app/schedule/open-shifts/{$open->id}/fill", ['user_id' => $s['desk']->id, 'week' => 0, 'dept' => 'all'])->assertRedirect();
    expect(Shift::where('property_id', $s['propertyA']->id)->where('user_id', $s['desk']->id)->exists())->toBeTrue()
        ->and(OpenShift::whereKey($open->id)->exists())->toBeFalse();
});
