<?php

use App\Models\AuditEvent;
use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Note;
use App\Models\OwnerExpense;
use App\Models\ReservationUnit;
use App\Notifications\Operator\MaintenanceReassignedNotification;
use App\Notifications\Operator\MaintenanceResolvedNotification;
use App\Services\MaintenanceService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// #108 — the Operator Work Order DETAIL exact-match rebuild (GATE 1). Locks the ADDED behaviour on top of the
// existing detail (MaintenanceDetailViewTest covers the base): the real Activity composer (a shared Note store),
// reassign from the detail, the real SLA surfacing (the ONE MaintenanceSla helper), real photo surfacing, the
// real "arrival tonight" narrowing — and the HONEST omissions (no fabricated Area / €-per-night / WO-#### / on-site).

function workOrder(array $s, array $overrides = []): MaintenanceTicket
{
    return app(MaintenanceService::class)->open(array_merge([
        'unit_id' => $s['units'][0]->id,
        'title' => 'AC not cooling',
        'category' => 'appliance',
        'priority' => 'urgent',
        'description' => 'Blowing warm air, not cooling.',
    ], $overrides));
}

// ── 1. Real facts + HONEST omissions (the wireframe's fabricated fields are never invented, #39) ─────
it('renders real facts and omits the wireframe’s fabricated fields', function () {
    $s = seedScenario(1);
    $s['units'][0]->update(['code' => 'WO1', 'name' => 'Villa Azure']);
    $manager = operatorUser($s, ['name' => 'Marco Reyes'], 'Hotel Manager');
    $ticket = workOrder($s);

    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")
        ->assertOk()
        ->assertSee('AC not cooling')          // real title
        ->assertSee('Villa Azure')             // real unit name
        ->assertSee('Appliance')               // real category
        ->assertSee('Urgent')                  // real priority
        ->assertSee('Open for')                // real elapsed
        ->assertSee('Reported')                // real timeline
        // 🪤 the prototype's fabricated fields are NOT modelled → omitted, never invented:
        ->assertDontSee('Living room')         // Area
        ->assertDontSee('Wall unit')
        ->assertDontSee('on hold while blocked')   // the "€X / night on hold" revenue-on-hold figure (refused)
        ->assertDontSee('WO-2041')             // fabricated sequential ref
        ->assertDontSee('On site');            // fabricated technician presence
});

// ── 2. The Activity composer posts a REAL note (shared Note store) into the timeline ─────────────────
it('posts a real activity update through the composer and shows it in the timeline', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, ['name' => 'Rosa Fernandes'], 'Hotel Manager');
    $ticket = workOrder($s);

    $this->actingAs($manager)->post(route('app.maintenance.notes.store', $ticket->id), [
        'body' => 'Ordered a replacement capacitor; will refit on arrival.',
        'kind' => 'progress',
    ])->assertRedirect(route('app.maintenance.show', $ticket->id))->assertSessionHas('mt_notice');

    // A REAL Note (kind) on the ticket — never a fabricated timeline row.
    $this->assertDatabaseHas('notes', [
        'notable_type' => $ticket->getMorphClass(),
        'notable_id' => $ticket->id,
        'kind' => 'progress',
        'body' => 'Ordered a replacement capacitor; will refit on arrival.',
    ]);

    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('Progress update')
        ->assertSee('Ordered a replacement capacitor');
});

// ── 3. The composer is manager-gated + read-only respected ───────────────────────────────────────────
it('gates the composer behind maintenance.manage and refuses when read-only', function () {
    $s = seedScenario(1);
    $ticket = workOrder($s);

    // Front Desk (no maintenance.manage) → 403.
    $frontDesk = operatorUser($s, [], 'Front Desk');
    $this->actingAs($frontDesk)->post(route('app.maintenance.notes.store', $ticket->id), ['body' => 'x'])->assertForbidden();

    // A paused workspace refuses the write (read-only), records nothing.
    $manager = operatorUser($s, [], 'Hotel Manager');
    $s['org']->update(['paused_until' => CarbonImmutable::now()->addDays(5)]);
    $this->actingAs($manager)->post(route('app.maintenance.notes.store', $ticket->id), ['body' => 'while paused'])
        ->assertRedirect(route('app.maintenance.show', $ticket->id))->assertSessionHas('mt_error');

    app(TenantContext::class)->set($s['org']->id);
    expect(Note::query()->where('notable_id', $ticket->id)->count())->toBe(0);
});

// ── 4. Reassign from the detail (real assign path) ───────────────────────────────────────────────────
it('reassigns the work order to a real staff member from the detail', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $tech = operatorUser($s, ['name' => 'Diego Ruiz'], 'Maintenance');
    $ticket = workOrder($s);

    $this->actingAs($manager)->post(route('app.maintenance.assign', $ticket->id), ['user_id' => $tech->id, 'origin' => 'detail'])
        ->assertRedirect(route('app.maintenance.show', $ticket->id));

    $this->assertDatabaseHas('maintenance_tickets', ['id' => $ticket->id, 'assigned_to' => $tech->id]);

    // The reassign shows in the detail (assignee + a real audit-event timeline entry).
    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('Diego Ruiz')
        ->assertSee('Assigned to Diego Ruiz');
});

// ── 5. The SLA card is REAL — surfaced only when a target is configured (the ONE MaintenanceSla) ─────
it('surfaces the real SLA card from the org’s configured target, including the defaults', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $ticket = workOrder($s);   // urgent

    // ⚠️ REWRITTEN (wireframe-parity WAVE 2 · #16). This used to assert that a fresh org shows NO SLA card,
    // and called that honest. It was not: `maintenance_settings.sla_*_hours` are NOT NULL with real defaults
    // (4/8/48/168h), so an org ALWAYS has targets. "No target" was reachable only through the bug this fixed
    // — the board resolved settings as `first() ?? new MaintenanceSetting`, and an UNSAVED model carries no
    // schema defaults. So the test was pinning a bug-state as intended behaviour, and the SLA signal stayed
    // dead from install until someone happened to open Settings.
    //
    // What is worth asserting is what the card is FOR: that it reflects the org's real target.
    app(TenantContext::class)->set($s['org']->id);
    expect(MaintenanceSetting::query()->count())->toBe(0);   // genuinely fresh

    // Fresh org → the REAL default urgent target (4h) applies, and the card computes from it.
    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('Same-day target')
        ->assertSee('Due by');

    expect((int) MaintenanceSetting::query()->firstOrFail()->sla_urgent_hours)->toBe(4);

    // And a CHANGED target really moves the card — it is driven by the setting, not hardcoded.
    MaintenanceSetting::query()->firstOrFail()->update(['sla_urgent_hours' => 72]);

    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('Due by')
        ->assertDontSee('Same-day target');   // 72h is no longer a same-day promise
});

// ── 6. Real ticket photos are surfaced on the detail ─────────────────────────────────────────────────
it('surfaces real ticket photos on the detail', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $ticket = workOrder($s);

    app(TenantContext::class)->set($s['org']->id);
    $ticket->media()->create([
        'collection' => 'mt_photo',
        'disk' => 'public',
        'path' => 'maintenance/'.$ticket->id.'/shot.jpg',
        'mime' => 'image/jpeg',
        'size' => 1234,
        'alt' => 'Maintenance photo · AC',
    ]);

    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('Maintenance photo', false)
        ->assertSee('maintenance/'.$ticket->id.'/shot.jpg', false);
});

// ── 7. The affected-arrival is REAL — an arrival TONIGHT on the blocked unit (today-narrowed count) ──
it('shows a real arrival tonight affected by the out-of-order block', function () {
    $s = seedScenario(1);
    $s['units'][0]->update(['code' => 'BLK1']);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // A real booking physically assigned to the unit, arriving TODAY.
    book($s, ['unit_id' => $s['units'][0]->id]);
    app(TenantContext::class)->set($s['org']->id);
    ReservationUnit::query()->where('unit_id', $s['units'][0]->id)->update(['check_in' => CarbonImmutable::now()->toDateString()]);

    // Take the unit out of service via an OOS ticket (the certified path genuinely blocks it).
    $ticket = workOrder($s, ['title' => 'Flood', 'takes_out_of_service' => true]);
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service');

    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('arrival tonight needs a room')   // real today-narrowed count
        ->assertSee('blocked from sale')
        ->assertSee('Open the rack to reassign');
});

// ── 8. GATE 2 — Mark-resolved captures REAL cost (#17) + hours + notes, and RETURNS the unit to sale ─
it('resolves with a real cost that reconciles to the owner expense and returns the unit to sale', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0]);   // owner-owned unit + agreement
    $manager = operatorUser($s, [], 'Hotel Manager');
    $svc = app(MaintenanceService::class);

    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Burst pipe', 'takes_out_of_service' => true]);
    $svc->start($ticket->refresh());
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service');   // genuinely blocked

    $this->actingAs($manager)->post(route('app.maintenance.resolve', $ticket->id), [
        'parts' => '120.00',
        'time_spent' => '1.5',
        'resolution' => 'Replaced the trap and seal — tested, no leak.',
        'return_to_sale' => '1',
        'notify' => '0',
    ])->assertRedirect(route('app.maintenance.show', $ticket->id));

    app(TenantContext::class)->set($s['org']->id);
    $ticket->refresh();
    expect($ticket->status)->toBe('resolved')
        ->and($ticket->cost_minor)->toBe(12000)          // labor(0) + parts(12000) === cost (#17)
        ->and($ticket->parts_minor)->toBe(12000)
        ->and((float) $ticket->labor_hours)->toBe(1.5)   // hours logged (informational)
        ->and((string) $ticket->resolution_notes)->toContain('Replaced the trap');

    // The owner expense reconciles to the cent (#17) — the SAME integer-minor.
    $exp = OwnerExpense::query()->where('source_type', 'maintenance_ticket')->where('source_id', $ticket->id)->firstOrFail();
    expect($exp->amount_minor)->toBe(12000)->and($exp->owner_id)->toBe($owner->id);

    // 🪤 RETURN TO SALE — mutation-proven un-block: the unit is back in service.
    expect($s['units'][0]->refresh()->condition_status)->toBe('in_service');

    // The "what was done" note lands in the Activity timeline.
    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")->assertSee('Replaced the trap');
});

// ── 9. GATE 2 — return-to-sale OFF keeps the unit out of order (mutation-proven) ─────────────────────
it('resolves but keeps the unit out of order when return-to-sale is off', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $svc = app(MaintenanceService::class);

    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Flood', 'takes_out_of_service' => true]);
    $svc->start($ticket->refresh());
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service');

    $this->actingAs($manager)->post(route('app.maintenance.resolve', $ticket->id), ['return_to_sale' => '0'])
        ->assertRedirect(route('app.maintenance.show', $ticket->id));

    app(TenantContext::class)->set($s['org']->id);
    expect($ticket->refresh()->status)->toBe('resolved')
        ->and($s['units'][0]->refresh()->condition_status)->toBe('out_of_service');   // STILL blocked
});

// ── 10. GATE 2 — "Notify the front desk" is real + toggle-respecting (never a dead toggle, #39) ──────
it('notifies the reporter only when the notify toggle is on', function () {
    Notification::fake();
    $s = seedScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $reporter = operatorUser($s, ['name' => 'Front Deskington'], 'Front Desk');
    $svc = app(MaintenanceService::class);

    // notify OFF → nothing sent.
    $t1 = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'A', 'reported_by' => $reporter->id]);
    $svc->start($t1->refresh());
    $this->actingAs($manager)->post(route('app.maintenance.resolve', $t1->id), ['notify' => '0'])->assertRedirect();
    Notification::assertNothingSent();

    // notify ON → the reporter gets a real MaintenanceResolvedNotification.
    $t2 = $svc->open(['unit_id' => $s['units'][1]->id, 'title' => 'B', 'reported_by' => $reporter->id]);
    $svc->start($t2->refresh());
    $this->actingAs($manager)->post(route('app.maintenance.resolve', $t2->id), ['notify' => '1'])->assertRedirect();
    Notification::assertSentTo($reporter, MaintenanceResolvedNotification::class);
});

// ── 11. GATE 2 — reassign notifies BOTH technicians + records the reason (real, not a dead chip) ─────
it('notifies both technicians and records the reason on reassign', function () {
    Notification::fake();
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $oldTech = operatorUser($s, ['name' => 'Old Tech'], 'Maintenance');
    $newTech = operatorUser($s, ['name' => 'New Tech'], 'Maintenance');
    $svc = app(MaintenanceService::class);

    $ticket = $svc->open(['unit_id' => $s['units'][0]->id, 'title' => 'Reassign me', 'assigned_to' => $oldTech->id]);

    $this->actingAs($manager)->post(route('app.maintenance.assign', $ticket->id), [
        'user_id' => $newTech->id, 'reason' => 'Balancing load', 'notify' => '1', 'origin' => 'detail',
    ])->assertRedirect(route('app.maintenance.show', $ticket->id));

    $this->assertDatabaseHas('maintenance_tickets', ['id' => $ticket->id, 'assigned_to' => $newTech->id]);

    // Both technicians notified.
    Notification::assertSentTo($newTech, MaintenanceReassignedNotification::class);
    Notification::assertSentTo($oldTech, MaintenanceReassignedNotification::class);

    // The reason is a REAL recorded field on the assign audit event (never a chip that persists nothing).
    app(TenantContext::class)->set($s['org']->id);
    $event = AuditEvent::query()->where('subject_id', $ticket->id)->where('action', 'maintenance_ticket.assigned')->latest()->firstOrFail();
    expect($event->after['reason'] ?? null)->toBe('Balancing load');
});
