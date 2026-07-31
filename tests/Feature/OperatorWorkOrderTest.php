<?php

use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Note;
use App\Models\ReservationUnit;
use App\Services\MaintenanceService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// #105 — the Operator Work Order DETAIL exact-match rebuild (GATE 1). Locks the ADDED behaviour on top of the
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
it('surfaces the real SLA card only when a resolution target is configured', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $ticket = workOrder($s);   // urgent

    // No MaintenanceSetting → no target → no SLA card (honest, never fabricated).
    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")
        ->assertDontSee('Same-day target')
        ->assertDontSee('Due by');

    // Configure a real 4h urgent target → the card computes a real due-by + countdown.
    app(TenantContext::class)->set($s['org']->id);
    MaintenanceSetting::create(['organization_id' => $s['org']->id, 'sla_urgent_hours' => 4]);

    $this->actingAs($manager)->get("/app/maintenance/{$ticket->id}")
        ->assertSee('Same-day target')
        ->assertSee('Due by');
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
