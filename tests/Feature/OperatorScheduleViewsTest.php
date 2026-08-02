<?php

use App\Models\Property;
use App\Support\Schedule\ShiftType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GATE 9.2 — the DAY view (agenda grouped by working shift + who's off) and the HONEST per-department coverage
// bars (days-with-cover / 7 — never the prototype's fabricated 14/16 target). Both derive from the ONE RosterWeek
// builder, so they reconcile with the grid. Uniquely-named local scenario so the file runs in isolation.

function viewsScenario(): array
{
    $org = newDemoOrg();
    $property = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);

    $s = ['org' => $org];
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');   // manager dept
    $desk = operatorUser($s, ['name' => 'Frank Desk'], 'Front Desk');           // frontdesk dept

    return compact('org', 'property', 'manager', 'desk');
}

function viewsAssign(array $s, $user, string $date, string $type): void
{
    test()->actingAs($s['manager'])->withSession(['current_property_id' => $s['property']->id])
        ->post('/app/schedule/shift', ['user_id' => $user->id, 'date' => $date, 'shift_type' => $type, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect();
}

function viewsWeek(): CarbonImmutable
{
    return CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);
}

// ── DAY VIEW — the focused day groups working staff by shift + lists who's off ───────────────────────
it('renders the day view: the focused day’s people grouped by shift, and who is not working', function () {
    $s = viewsScenario();
    $mon = viewsWeek()->toDateString();
    viewsAssign($s, $s['desk'], $mon, ShiftType::DAY);   // Frank works Monday; Rosa does not

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/schedule?view=day&day='.$mon)
        ->assertOk()
        ->assertSee('Frank Desk')             // grouped under a working shift
        ->assertSee('Day · 09:00–17:00')      // the group heading (label · hours)
        ->assertSee('1 working')              // exactly one working that day
        ->assertSee('Not working')
        ->assertSee('Rosa Manager');          // …and Rosa is listed as off
});

// ── DAY VIEW default focus = today when no ?day is given ─────────────────────────────────────────────
it('defaults the day view to today when no day is specified', function () {
    $s = viewsScenario();
    $today = CarbonImmutable::now()->format('l j M');

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/schedule?view=day')
        ->assertOk()
        ->assertSee($today);   // the focused-day heading is today's date
});

// ── COVERAGE BARS — real days-with-cover / 7, per department (no fabricated target) ─────────────────
it('shows honest per-department coverage: days with at least one person working, out of 7', function () {
    $s = viewsScenario();
    $mon = viewsWeek();

    // Front desk (Frank) works Mon/Tue/Wed → 3 of 7 days covered. Manager (Rosa) works Mon only → 1 of 7.
    viewsAssign($s, $s['desk'], $mon->toDateString(), ShiftType::DAY);
    viewsAssign($s, $s['desk'], $mon->addDay()->toDateString(), ShiftType::DAY);
    viewsAssign($s, $s['desk'], $mon->addDays(2)->toDateString(), ShiftType::DAY);
    viewsAssign($s, $s['manager'], $mon->toDateString(), ShiftType::LATE);

    $this->actingAs($s['manager'])->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/schedule')
        ->assertOk()
        ->assertSee('Coverage this week')
        ->assertSee('3/7 days')   // Front desk — real count, denominator is 7 (never a fabricated target)
        ->assertSee('1/7 days')   // Manager
        ->assertDontSee('14/16')  // the prototype's fabricated figure is never rendered
        ->assertDontSee('17/18');
});
