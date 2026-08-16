<?php

use App\Models\HousekeepingTask;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// D8-2 — the housekeeping board's READY column gains a real turnover deadline.
//
// ⚠️ THE DEADLINE IS A FACT OR IT IS NOTHING. It exists only when a real, non-cancelled reservation arrives at
// THIS room on THIS date, and it is that property's own published check-in time — the hour a guest may
// actually turn up. No arrival, or no published check-in time, means no deadline and the column keeps the
// honest "—" it already had. Nothing estimates, rounds or defaults an hour (#39; D1-3's coerced-time lesson).
//
// ⚠️ The demo hides the NULL-check_in_time branch (both demo properties publish 15:00), so test 3 forces it.

/** An operator, a property that publishes a check-in time, and a housekeeping task on a unit. */
function readyByFixture(): array
{
    $s = seedScenario(1);
    $operator = User::where('email', 'demo-staff@vyrobel-demo.test')->first() ?? operatorUser($s);

    // ⚠️ The scenario's property publishes NO check-in time (that is the DEMO seeder's doing, not this
    // fixture's) — so the published-time case has to be constructed, exactly the D1-3 inversion: "populated
    // on the demo" says nothing about the world a test runs in.
    Property::query()->whereKey($s['property']->id)->update(['check_in_time' => '15:00:00']);

    $task = HousekeepingTask::create([
        'property_id' => $s['property']->id,
        'unit_id' => $s['units'][0]->id,
        'date' => '2026-09-01',
        'type' => 'departure',
        'status' => 'pending',
    ]);

    return ['s' => $s, 'operator' => $operator, 'task' => $task];
}

// ── 1. ⚠️ NO ARRIVAL → NO DEADLINE. The column keeps its honest "—" ───────────────────────────────
it('shows no ready-by time when no guest is arriving at that room that day', function () {
    $f = readyByFixture();

    // The property publishes a check-in time …
    expect(DB::table('properties')->where('id', $f['s']['property']->id)->value('check_in_time'))->not->toBeNull();

    // … but nobody is arriving, so there is nothing to be ready FOR.
    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertDontSee('by 15:00');
});

// ── 2. A REAL ARRIVAL → the deadline is that property's published check-in time ──────────────────
it('shows the property’s published check-in time as the deadline when a guest arrives', function () {
    $f = readyByFixture();

    // A genuine booking arriving at this unit on the task's date.
    book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertSee('by 15:00');   // the REAL published time, not an assumed one
});

// ── 3. ⚠️ THE BRANCH THE DEMO HIDES — a property with NO published check-in time ─────────────────
// Both demo properties publish 15:00, so this case never renders on the demo. Forced here: an unpublished
// time must produce NO deadline, never a defaulted hour.
it('shows no ready-by time when the property publishes none', function () {
    $f = readyByFixture();
    book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);

    Property::query()->whereKey($f['s']['property']->id)->update(['check_in_time' => null]);

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertDontSee('by 15:00')
        ->assertDontSee('by 00:00')      // ⚠️ the shape a coerced null would take
        ->assertSee('—');                 // the honest holding state, unchanged
});

// ── 4. ⚠️ THE DEADLINE IS DATE-MATCHED — an arrival on another day is not this room's deadline ────
it('does not borrow a deadline from an arrival on a different date', function () {
    $f = readyByFixture();

    // A real arrival on 1 Sep …
    book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);

    // ⚠️ THE TASK is moved instead of the booking. `book()` HARDCODES the reservation header to 2026-09-01 —
    // a `check_in` override reaches only the line — so an attempt to move the ARRIVAL silently produces a
    // same-date arrival and the test passes for the wrong reason. (The canon records this exact helper trap
    // from D1-3; it caught this file too.) Moving the task is the mismatch that actually happens.
    DB::table('housekeeping_tasks')->where('id', $f['task']->id)->update(['date' => '2026-09-05']);

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertDontSee('by 15:00');
});

// ── 5. ⚠️ EACH ROOM TAKES ITS OWN PROPERTY'S TIME (the board can span properties) ─────────────────
it('uses each room’s own property check-in time, not one shared figure', function () {
    $f = readyByFixture();
    book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);

    // This property opens at 14:00, not the other demo property's 15:00.
    Property::query()->whereKey($f['s']['property']->id)->update(['check_in_time' => '14:00:00']);

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertSee('by 14:00')
        ->assertDontSee('by 15:00');
});

// ── 5b. ⚠️ TWO PROPERTIES, TWO DEADLINES — the mutation test 5 could not reach ────────────────────
// ⚠️ Test 5 changes ONE property's time and cannot tell a keyed lookup from "just take the first one": with
// a single property in the read set the two are identical, and a mutation replacing the lookup with
// `reset($checkInTimes)` passed the whole file. A board spanning properties is the case that distinguishes
// them, and it is the reason the deadline is keyed by property at all.
it('gives each property’s rooms its own deadline', function () {
    $f = readyByFixture();
    book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);

    // A SECOND property in the same org, opening at a different hour.
    $b = Property::create(['name' => 'B', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC', 'check_in_time' => '11:00:00']);
    $bType = RoomType::create(['property_id' => $b->id, 'name' => 'StdB', 'kind' => 'room']);
    $bPlan = RatePlan::create(['property_id' => $b->id, 'room_type_id' => $bType->id, 'name' => 'FlexB', 'currency' => 'EUR']);
    $bUnit = Unit::create(['property_id' => $b->id, 'room_type_id' => $bType->id, 'code' => 'B1']);
    app(AvailabilityService::class)->recompute($bType->id, '2026-09-01', '2026-09-10');

    HousekeepingTask::create([
        'property_id' => $b->id, 'unit_id' => $bUnit->id,
        'date' => '2026-09-01', 'type' => 'departure', 'status' => 'pending',
    ]);
    book(['property' => $b, 'roomType' => $bType, 'ratePlan' => $bPlan, 'guest' => $f['s']['guest'], 'units' => [$bUnit]],
        ['unit_id' => $bUnit->id]);

    // Each room answers to its OWN property's published hour.
    // ⚠️ The board defaults to ONE selected property, so both rows only appear once the context is switched
    // to "all" — through the REAL switcher route, not by poking the context. Without this the second
    // property's rooms are simply absent and the test proves nothing about per-property deadlines.
    $this->actingAs($f['operator'])->post(route('app.property.switch'), ['property_id' => 'all'])->assertRedirect();

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertSee('by 15:00')
        ->assertSee('by 11:00');
});

// ── 6. A cancelled booking is not an arrival, so it sets no deadline ─────────────────────────────
it('ignores a cancelled reservation when deriving the deadline', function () {
    $f = readyByFixture();
    $reservation = book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);

    DB::table('reservations')->where('id', $reservation->id)->update(['status' => 'cancelled']);

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertDontSee('by 15:00');
});

// ── 7. An inspected room shows Ready — the deadline is moot once the work is done ────────────────
it('shows Ready rather than a deadline once the room is inspected', function () {
    $f = readyByFixture();
    book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);
    DB::table('housekeeping_tasks')->where('id', $f['task']->id)->update(['status' => 'inspected']);

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertDontSee('by 15:00');
});

// ── 8. ⚠️ The time is FORMATTED THROUGH THE SHARED HELPER, in the board's own 24h idiom ──────────
it('renders the deadline through the shared StayTime helper', function () {
    $f = readyByFixture();
    book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);

    // A stored HH:MM:SS renders as the board's HH:MM, never the raw column value.
    Property::query()->whereKey($f['s']['property']->id)->update(['check_in_time' => '09:30:00']);

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertSee('by 09:30')
        ->assertDontSee('09:30:00');   // the raw stored value never reaches the page

    // ⚠️ Read the SOURCE, not a copy of the format: the board must go through the one shared helper.
    $source = file_get_contents(app_path('Livewire/HousekeepingBoard.php'));
    expect($source)->toContain('StayTime::display');
});

// ── 9. 🔒 Tenant isolation — another org's property time never reaches this board ─────────────────
it('never reads another org’s check-in time', function () {
    $f = readyByFixture();
    book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);

    $other = seedScenario(1);
    Property::query()->whereKey($other['property']->id)->update(['check_in_time' => '04:00:00']);

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertSee('by 15:00')
        ->assertDontSee('by 04:00');
});

// ── 10. The guest's prefs/VIP still work — the arrival map now carries reservations, not guests ──
// The map this deadline reads was widened from "guests arriving" to "reservations arriving" so the two
// readers cannot disagree. This pins that the ORIGINAL reader is unaffected (§N.8f, no regression).
it('still surfaces the arriving guest’s VIP flag', function () {
    $f = readyByFixture();
    $reservation = book($f['s'], ['unit_id' => $f['s']['units'][0]->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-04']);

    DB::table('guests')->where('id', $reservation->lead_guest_id)->update(['is_vip' => true]);

    $this->actingAs($f['operator'])->get('/app/housekeeping')
        ->assertOk()
        ->assertSee('VIP')
        ->assertSee('by 15:00');
});
