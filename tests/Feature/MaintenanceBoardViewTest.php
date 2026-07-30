<?php

use App\Models\Membership;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Maintenance board (MaintenanceList, /app/maintenance). Locks its PRESENTATION guarantees:
// real tickets render in the real active-status lanes (open/in_progress/resolved — no invented columns);
// the "Unit OOS" badge is REAL (genuine condition_status); "+ New ticket" (open) and Assign are REAL
// writes through MaintenanceService, tenant-scoped (a foreign unit / cross-org ticket 404s).

function boardMember(array $scenario, string $name = 'Marcus Bellweather'): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    // A Membership alone is NOT operator access — /app is an allow-list of back-office roles
    // (EnsureBackOfficeAccess). A real staff member holds one, as OwnerInviteService assigns.
    return grantBackOfficeRole($user, $scenario['org']);
}

// ── 1. Real tickets render in the real active-status lanes, with real fields ─────────────────────
it('renders real tickets grouped by the real active-status enum', function () {
    $s = seedScenario(2);
    $s['units'][0]->update(['code' => 'BOARD1']);
    $s['units'][1]->update(['code' => 'BOARD2']);
    $svc = app(MaintenanceService::class);

    $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Leaking mixer tap', 'category' => 'plumbing', 'priority' => 'high']);

    // A resolved ticket (with a cost) belongs in the Resolved lane.
    $done = $svc->open(['unit_id' => $s['units'][1]->id, 'title' => 'Broken blind', 'category' => 'other']);
    $svc->start($done);
    $svc->resolve($done->refresh(), Money::of(4500, 'EUR'));

    $this->actingAs(boardMember($s))->get('/app/maintenance')
        ->assertOk()
        ->assertSee('Maintenance')
        ->assertSee('Leaking mixer tap')   // real title
        ->assertSee('BOARD1')              // real unit
        ->assertSee('Plumbing')            // real category (humanized enum)
        ->assertSee('High')                // real priority
        ->assertSee('Open')                // the real Open KPI chip
        ->assertSee('Resolved')            // the real Resolved-7d KPI chip
        ->assertSee('Broken blind')        // the resolved ticket is in the 7-day window
        // #89 — "Awaiting parts" + "Parts on order" are now REAL (a real awaiting-parts state), not fabricated.
        ->assertSee('Awaiting parts')      // the real KPI chip
        ->assertSee('Parts on order')      // the real (empty here) parts panel
        ->assertSee('Technicians');        // the real technicians panel
});

// ── 2. The "Unit OOS" badge + rail are REAL (genuine condition_status) ───────────────────────────
it('shows the Unit OOS badge only for a genuinely out-of-service unit', function () {
    $s = seedScenario(2);
    $s['units'][0]->update(['code' => 'OOSUNIT']);
    $s['units'][1]->update(['code' => 'OKUNIT']);
    $svc = app(MaintenanceService::class);

    // OOS ticket → unit genuinely out_of_service.
    $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Flooded bathroom', 'takes_out_of_service' => true]);
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service');

    // A normal ticket on the other unit — no OOS.
    $svc->open(['unit_id' => $s['units'][1]->id, 'title' => 'Squeaky door']);

    $this->actingAs(boardMember($s))->get('/app/maintenance')
        ->assertOk()
        ->assertSee('Out of order')       // the real OOO badge on the row + the KPI chip
        ->assertSee('units blocked')      // the real out-of-order hero
        ->assertSee('OOSUNIT');           // the genuinely-blocked unit surfaces in the hero
});

// ── 3. "+ New ticket" opens a REAL ticket; a foreign unit is rejected ────────────────────────────
it('opens a real ticket through the create action', function () {
    $s = seedScenario(1);
    $user = boardMember($s);

    $this->actingAs($user)->post(route('app.maintenance.store'), [
        'unit_id' => $s['units'][0]->id,
        'title' => 'Replace smoke alarm',
        'category' => 'electrical',
        'priority' => 'urgent',
        'takes_out_of_service' => '1',
    ])->assertRedirect(route('app.maintenance'));

    $this->assertDatabaseHas('maintenance_tickets', [
        'unit_id' => $s['units'][0]->id,
        'title' => 'Replace smoke alarm',
        'category' => 'electrical',
        'priority' => 'urgent',
        'status' => 'open',
        'takes_out_of_service' => true,
    ]);
    // The real availability side-effect fired: the unit is out of service.
    $this->assertDatabaseHas('units', ['id' => $s['units'][0]->id, 'condition_status' => 'out_of_service']);
});

it('rejects opening a ticket on another org\'s unit (404)', function () {
    $other = seedScenario(1);
    $foreignUnit = $other['units'][0];

    $s = seedScenario(1);   // active org
    $this->actingAs(boardMember($s))->post(route('app.maintenance.store'), [
        'unit_id' => $foreignUnit->id,
        'title' => 'Cross-org attempt',
        'category' => 'other',
        'priority' => 'normal',
    ])->assertNotFound();

    $this->assertDatabaseMissing('maintenance_tickets', ['title' => 'Cross-org attempt']);
});

// ── 4. Assign persists through the real action; guards apply ─────────────────────────────────────
it('assigns a ticket to an org member through the real action', function () {
    $s = seedScenario(1);
    $tech = boardMember($s, 'Rosa Delacroix');
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Fix heater']);

    $this->actingAs(boardMember($s))
        ->post(route('app.maintenance.assign', $ticket->id), ['user_id' => $tech->id])
        ->assertRedirect(route('app.maintenance'));

    $this->assertDatabaseHas('maintenance_tickets', ['id' => $ticket->id, 'assigned_to' => $tech->id]);
});

it('rejects assigning to a non-member (422) and cross-org tickets (404)', function () {
    $s = seedScenario(1);
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Own ticket']);
    $stranger = User::factory()->create();   // not a member of this org

    $this->actingAs(boardMember($s))
        ->post(route('app.maintenance.assign', $ticket->id), ['user_id' => $stranger->id])
        ->assertStatus(422);
    $this->assertDatabaseHas('maintenance_tickets', ['id' => $ticket->id, 'assigned_to' => null]);

    // Cross-org ticket id → 404, nothing mutates.
    $other = seedScenario(1);
    $foreign = app(MaintenanceService::class)->open(['unit_id' => $other['units'][0]->id, 'title' => 'Foreign']);
    $this->actingAs(boardMember($s))
        ->post(route('app.maintenance.assign', $foreign->id), ['user_id' => null])
        ->assertNotFound();
});

// ── 5. Tenant isolation + no invented statuses (cancelled falls off the board) ───────────────────
it('isolates tenants and drops cancelled tickets from the work board', function () {
    $sA = seedScenario(1);
    $sA['units'][0]->update(['code' => 'AUNIT']);
    $svc = app(MaintenanceService::class);
    $svc->open(['unit_id' => $sA['units'][0]->id, 'title' => 'Visible ticket']);

    // A cancelled ticket is terminal — never shown on the board (no invented lane, no fake row).
    $cancelled = $svc->open(['unit_id' => $sA['units'][0]->id, 'title' => 'Cancelled ticket']);
    $svc->cancel($cancelled);

    $sB = seedScenario(1);
    $sB['units'][0]->update(['code' => 'BUNIT']);
    app(MaintenanceService::class)->open(['unit_id' => $sB['units'][0]->id, 'title' => 'Org B ticket']);

    $this->actingAs(boardMember($sA))->get('/app/maintenance')
        ->assertOk()
        ->assertSee('Visible ticket')
        ->assertDontSee('Cancelled ticket')   // terminal → off the board
        ->assertDontSee('Org B ticket')       // tenant isolation
        ->assertDontSee('BUNIT');
});
