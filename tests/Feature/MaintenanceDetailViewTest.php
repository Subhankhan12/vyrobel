<?php

use App\Models\Membership;
use App\Models\OwnerExpense;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Maintenance ticket detail (MaintenanceTicketDetail, /app/maintenance/{ticket}). Locks its
// PRESENTATION guarantees: real facts + a real (audit-event) timeline; lifecycle actions persist through
// MaintenanceService and are state-gated + guarded; the OOS callout + manual-move count are REAL; the cost
// destination is read from the ACTUAL owner_expense; tenant isolation (cross-org → 404).

function mtDetailMember(array $scenario, string $name = 'Marcus Bellweather'): User
{
    $user = User::factory()->create(['name' => $name, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    // A Membership alone is NOT operator access — /app is an allow-list of back-office roles
    // (EnsureBackOfficeAccess). A real staff member holds one, as OwnerInviteService assigns.
    return grantBackOfficeRole($user, $scenario['org']);
}

// ── 1. Real facts + real activity timeline (no fabricated entries) ───────────────────────────────
it('renders real facts and a real activity timeline', function () {
    $s = seedScenario(1);
    $s['units'][0]->update(['code' => 'DTL1']);
    $tech = mtDetailMember($s, 'Rosa Delacroix');
    $svc = app(MaintenanceService::class);

    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Leaking mixer tap', 'category' => 'plumbing', 'priority' => 'high', 'description' => 'Drips overnight.']);
    $svc->assign($ticket->refresh(), $tech->id);
    $svc->start($ticket->refresh());

    $this->actingAs(mtDetailMember($s))->get("/app/maintenance/{$ticket->id}")
        ->assertOk()
        ->assertSee('Leaking mixer tap')   // real title
        ->assertSee('DTL1')                // real unit
        ->assertSee('Plumbing')            // real category
        ->assertSee('High')                // real priority
        ->assertSee('Drips overnight.')    // real description
        ->assertSee('In progress')         // real status
        ->assertSee('Reported')            // real timeline entries (audit events) — #108 exact-match label
        ->assertSee('Assigned')
        ->assertSee('Rosa Delacroix')      // real assignee, surfaced on the assigned entry
        ->assertSee('Work started')
        // ⚠️ The SLA card is REAL now (wireframe-parity WAVE 2 · #16). This asserted its ABSENCE and called
        // that "no fabricated SLA" — but the absence came from a bug, not from honesty: the board resolved
        // settings as `first() ?? new MaintenanceSetting`, and an unsaved model carries no schema defaults.
        // `sla_high_hours` is NOT NULL and defaults to 8h, so this High ticket genuinely HAS a same-day
        // target. Showing a real configured target is not fabrication — hiding it was the defect.
        ->assertSee('Same-day target')
        ->assertDontSee('Parts on order'); // still no fabricated panel — that one has no data source at all
});

// ── 2. Lifecycle actions are state-gated + persist through the service ───────────────────────────
it('offers only state-legal actions and persists them through the service', function () {
    $s = seedScenario(1);
    $user = mtDetailMember($s);
    $ticket = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Fix heater']);

    // OPEN → Start offered, Resolve NOT offered.
    $this->actingAs($user)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('Start work')
        ->assertDontSee('Mark resolved');

    // Start persists → in_progress.
    $this->actingAs($user)->post(route('app.maintenance.start', $ticket->id))->assertRedirect(route('app.maintenance.show', $ticket->id));
    $this->assertDatabaseHas('maintenance_tickets', ['id' => $ticket->id, 'status' => 'in_progress']);

    // IN PROGRESS → Resolve offered, Start NOT offered.
    $this->actingAs($user)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('Mark resolved')
        ->assertDontSee('Start work');

    // Resolve (no cost) persists → resolved.
    $this->actingAs($user)->post(route('app.maintenance.resolve', $ticket->id))->assertRedirect(route('app.maintenance.show', $ticket->id));
    $this->assertDatabaseHas('maintenance_tickets', ['id' => $ticket->id, 'status' => 'resolved']);
});

// ── 3. An illegal transition is guarded — clean flash, not a 500, nothing mutated ────────────────
it('guards an illegal transition with a clean error, not a 500', function () {
    $s = seedScenario(1);
    $user = mtDetailMember($s);
    $svc = app(MaintenanceService::class);
    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Open ticket']);
    $svc->resolve($ticket->refresh());   // now terminal (resolved)

    // Starting a RESOLVED ticket is illegal — surfaced cleanly (not a 500), ticket unchanged. (This would
    // only reach the server via a stale form, since the UI doesn't offer Start on a resolved ticket.)
    $this->actingAs($user)->post(route('app.maintenance.start', $ticket->id))
        ->assertRedirect(route('app.maintenance.show', $ticket->id))
        ->assertSessionHas('mt_error');
    $this->assertDatabaseHas('maintenance_tickets', ['id' => $ticket->id, 'status' => 'resolved']);
});

// ── 4. Resolving an OWNED unit → "charged to owner" AND a real owner_expense exists + reconciles ─
it('shows the real owner-expense destination when resolving an owned unit with a cost', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);   // owned unit + 15% agreement
    $user = mtDetailMember($s);
    $svc = app(MaintenanceService::class);

    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Rewire']);
    $svc->start($ticket->refresh());

    // Resolve with a real cost through the action.
    $this->actingAs($user)->post(route('app.maintenance.resolve', $ticket->id), ['cost' => '200.00'])
        ->assertRedirect(route('app.maintenance.show', $ticket->id));

    // A real owner_expense (source = this ticket) exists and reconciles to the cent.
    app(TenantContext::class)->set($s['org']->id);
    $expense = OwnerExpense::query()->where('source_type', 'maintenance_ticket')->where('source_id', $ticket->id)->firstOrFail();
    expect($expense->owner_id)->toBe($owner->id)->and($expense->amount_minor)->toBe(20000);

    // The detail names the real owner as the deductible destination.
    $this->actingAs($user)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('€200.00')
        ->assertSee('Charged to')
        ->assertSee($owner->name)
        ->assertSee('deductible')
        ->assertDontSee('Operator cost');
});

// ── 5. Resolving an OPERATOR-owned unit → "operator cost" + NO owner_expense ─────────────────────
it('shows operator cost and posts no owner expense on an operator-owned unit', function () {
    $s = seedScenario(1);   // unit has no owner_id
    $user = mtDetailMember($s);
    $svc = app(MaintenanceService::class);

    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Repaint']);
    $svc->start($ticket->refresh());

    $this->actingAs($user)->post(route('app.maintenance.resolve', $ticket->id), ['cost' => '80.00'])
        ->assertRedirect(route('app.maintenance.show', $ticket->id));

    // No owner_expense was created.
    app(TenantContext::class)->set($s['org']->id);
    expect(OwnerExpense::query()->where('source_type', 'maintenance_ticket')->where('source_id', $ticket->id)->exists())->toBeFalse();

    $this->actingAs($user)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('€80.00')
        ->assertSee('Operator cost')
        ->assertDontSee('Charged to');
});

// ── 6. OOS callout + the REAL manual-move count (absent when zero) ────────────────────────────────
it('surfaces the OOS callout and the real manual-move booking count', function () {
    $s = seedScenario(1);
    $s['units'][0]->update(['code' => 'OOSDTL']);
    $user = mtDetailMember($s);
    $svc = app(MaintenanceService::class);

    // A booking physically assigned to the unit (an active reservation_unit row).
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    // Now take the unit out of service via an OOS ticket.
    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Flooded', 'takes_out_of_service' => true]);
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service');

    $this->actingAs($user)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('blocked from sale')                  // the OOS callout — #108 exact-match copy
        ->assertSee('needs to be moved')                  // the real manual-move flag (real count = 1)
        ->assertSee('Open the rack to reassign');

    // A ticket on an OOS unit with NO bookings shows the callout but NOT the move note.
    $s2 = seedScenario(1);
    $s2['units'][0]->update(['code' => 'EMPTYOOS']);
    $t2 = app(MaintenanceService::class)->open(['unit_id' => $s2['units'][0]->id, 'title' => 'Empty OOS', 'takes_out_of_service' => true]);
    $this->actingAs(mtDetailMember($s2))->get("/app/maintenance/{$t2->id}")
        ->assertSee('blocked from sale')
        ->assertSee('No bookings affected')
        ->assertDontSee('to be moved');
});

// ── 7. Tenant isolation — cross-org detail 404s; cross-org action 404s and mutates nothing ───────
it('isolates tenants — cross-org detail and actions 404', function () {
    $other = seedScenario(1);
    $foreign = app(MaintenanceService::class)->open(['unit_id' => $other['units'][0]->id, 'title' => 'Foreign']);

    $s = seedScenario(1);
    $user = mtDetailMember($s);

    $this->actingAs($user)->get("/app/maintenance/{$foreign->id}")->assertNotFound();
    $this->actingAs($user)->post(route('app.maintenance.start', $foreign->id))->assertNotFound();
    $this->actingAs($user)->post(route('app.maintenance.cancel', $foreign->id))->assertNotFound();

    $this->assertDatabaseHas('maintenance_tickets', ['id' => $foreign->id, 'status' => 'open']);   // untouched
});
