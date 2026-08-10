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

// ═══════════════════════════════════════════════════════════════════════════════════════════════════
// P34 (design-parity) — the schedule PDF is a DOWNLOADABLE ARTIFACT, so a scope slip leaks another
// property's staff schedule as a FILE. And a printed rota has to say how old it is.
// ═══════════════════════════════════════════════════════════════════════════════════════════════════

it('scopes the PDF to the SELECTED property — another property\'s staff never reach the file (#38)', function () {
    $org = newDemoOrg();
    $alpha = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $beta = Property::create(['name' => 'Beta Lodge', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $s = ['org' => $org];
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');
    $alphaStaff = operatorUser($s, ['name' => 'Alpha Person'], 'Front Desk');
    $betaStaff = operatorUser($s, ['name' => 'Beta Person'], 'Front Desk');
    $today = CarbonImmutable::today()->toDateString();

    // One shift on each property.
    $this->actingAs($manager)->withSession(['current_property_id' => $alpha->id])
        ->post('/app/schedule/shift', ['user_id' => $alphaStaff->id, 'date' => $today, 'shift_type' => ShiftType::DAY, 'week' => 0, 'dept' => 'all'])->assertRedirect();
    $this->actingAs($manager)->withSession(['current_property_id' => $beta->id])
        ->post('/app/schedule/shift', ['user_id' => $betaStaff->id, 'date' => $today, 'shift_type' => ShiftType::LATE, 'week' => 0, 'dept' => 'all'])->assertRedirect();

    // ⚠️ The roster the ALPHA document is built from carries Alpha's shift and NOT Beta's. The property is
    // the SELECTED context — there is no property id in the PDF URL to point at another one.
    $alphaRoster = RosterWeek::build(Property::find($alpha->id), 0, 'all');
    $alphaWorking = collect($alphaRoster->rows)->flatMap(fn (array $r): array => $r['cells'])->where('working', true)->count();
    expect($alphaWorking)->toBe(1);

    $betaRoster = RosterWeek::build(Property::find($beta->id), 0, 'all');
    $betaWorking = collect($betaRoster->rows)->flatMap(fn (array $r): array => $r['cells'])->where('working', true)->count();
    expect($betaWorking)->toBe(1);

    // …and the two documents really are different files for the same week.
    $a = $this->actingAs($manager)->withSession(['current_property_id' => $alpha->id])->get('/app/schedule/pdf');
    $b = $this->actingAs($manager)->withSession(['current_property_id' => $beta->id])->get('/app/schedule/pdf');
    $a->assertOk();
    $b->assertOk();
    expect($a->headers->get('content-disposition'))->toContain($alpha->getKey())
        ->and($b->headers->get('content-disposition'))->toContain($beta->getKey());
});

it('refuses the PDF to a viewer without schedule.view (#30)', function () {
    $org = newDemoOrg();
    $property = Property::create(['name' => 'Alpha House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $s = ['org' => $org];

    // ⚠️ `schedule.view` is DELIBERATELY broad (#43 — anyone rostered should be able to read the rota):
    // 9 back-office roles hold it. The only operator roles that do NOT are Accountant and Marketing
    // Manager, so Accountant is the honest negative case — Housekeeping would have passed and made this
    // assertion meaningless.
    $accountant = operatorUser($s, ['name' => 'Alan Accountant'], 'Accountant');
    $this->actingAs($accountant)->withSession(['current_property_id' => $property->id])
        ->get('/app/schedule/pdf')->assertForbidden();

    // A manager does hold it.
    $manager = operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');
    $this->actingAs($manager)->withSession(['current_property_id' => $property->id])
        ->get('/app/schedule/pdf')->assertOk();
});
