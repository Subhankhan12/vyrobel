<?php

use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Support\Schedule\RosterWeek;
use App\Support\Schedule\ShiftType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #102 GATE 2 — the Schedule PDF summary reconciles to the ONE RosterWeek builder; the Search filters are REAL.

// ── SCHEDULE — the PDF summary (team/shifts/hours) reconciles to the roster + the PDF streams ────────
it('reconciles the schedule PDF summary to the roster and streams a PDF', function () {
    $org = newDemoOrg();
    $property = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $s = ['org' => $org];
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');   // schedule.manage
    $desk = operatorUser($s, ['name' => 'Frank Desk'], 'Front Desk');           // rostered staff
    $today = CarbonImmutable::today()->toDateString();

    $this->actingAs($manager)->withSession(['current_property_id' => $property->id])
        ->post('/app/schedule/shift', ['user_id' => $desk->id, 'date' => $today, 'shift_type' => ShiftType::DAY, 'week' => 0, 'dept' => 'all'])
        ->assertRedirect();

    $roster = RosterWeek::build(Property::find($property->id), 0, 'all');
    $working = collect($roster->rows)->flatMap(fn (array $r): array => $r['cells'])->where('working', true)->count();

    // The summary methods derive from the SAME cells the grid renders — they reconcile (#43).
    expect($roster->shiftCount())->toBe($working)
        ->and($roster->hours())->toBe($working * 8)
        ->and($roster->teamSize())->toBe(count($roster->rows))
        ->and($working)->toBeGreaterThanOrEqual(1);

    $res = $this->actingAs($manager)->withSession(['current_property_id' => $property->id])->get('/app/schedule/pdf');
    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
});

// ── SEARCH — a real status + source filter narrows the results (not a client-side illusion) ──────────
it('narrows search results by a real status and source filter', function () {
    $s = seedScenario(1);
    $guest = Guest::create(['first_name' => 'Zed', 'last_name' => 'Searcher']);
    Reservation::create(['property_id' => $s['property']->id, 'reference' => 'VYR-AAA', 'status' => 'confirmed', 'lead_guest_id' => $guest->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'currency' => 'EUR', 'source' => 'direct']);
    Reservation::create(['property_id' => $s['property']->id, 'reference' => 'VYR-BBB', 'status' => 'cancelled', 'lead_guest_id' => $guest->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'currency' => 'EUR', 'source' => 'ota']);

    $manager = operatorUser($s, [], 'Hotel Manager');

    // Un-filtered by guest name → both.
    $this->actingAs($manager)->get('/app/search?q=Searcher')->assertOk()->assertSee('VYR-AAA')->assertSee('VYR-BBB');

    // status=confirmed → only AAA (a real WHERE, not a client hide).
    $this->actingAs($manager)->get('/app/search?q=Searcher&status=confirmed')->assertOk()->assertSee('VYR-AAA')->assertDontSee('VYR-BBB');

    // source=ota → only BBB.
    $this->actingAs($manager)->get('/app/search?q=Searcher&source=ota')->assertOk()->assertSee('VYR-BBB')->assertDontSee('VYR-AAA');
});
