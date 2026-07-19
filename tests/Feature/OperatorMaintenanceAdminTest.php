<?php

use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Owner;
use App\Services\MaintenanceCostService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR MAINTENANCE COSTS + SETTINGS (Phase 1 · Sub-gate 4).
//   COSTS — every figure DERIVED from real resolved tickets (no fabricated Labor/Parts split; empty → "—").
//   SETTINGS — real TYPED persistence (SLA / category-assignee / toggles), not toast-only; assignees are
//   tenant-validated; notification dispatch is honestly deferred to Phase 3 (the preference is stored).

/** A resolved, costed maintenance ticket on the scenario's unit. */
function resolvedTicket(array $s, int $unitIndex, string $category, int $costMinor): MaintenanceTicket
{
    return MaintenanceTicket::create([
        'unit_id' => $s['units'][$unitIndex]->id,
        'title' => 'Test order',
        'category' => $category,
        'priority' => 'normal',
        'status' => 'resolved',
        'cost_minor' => $costMinor,
        'currency' => 'EUR',
        'opened_at' => now()->subDays(3),
        'resolved_at' => now()->subDay(),
    ]);
}

/** The org's maintenance settings row, read outside any tenant context. */
function maintenanceSettings(array $s): ?MaintenanceSetting
{
    return MaintenanceSetting::withoutGlobalScopes()->where('organization_id', $s['org']->id)->first();
}

// ── COSTS 1 — real aggregates that RECONCILE ─────────────────────────────────────────────────────
it('derives real maintenance cost aggregates that reconcile', function () {
    $s = seedScenario(3);
    $owner = Owner::factory()->create(['name' => 'Owner LLC']);
    $s['units'][1]->update(['owner_id' => $owner->id]);   // unit[1] is owner-owned → its spend is rechargeable

    resolvedTicket($s, 0, 'plumbing', 15000);    // operator unit
    resolvedTicket($s, 0, 'electrical', 5000);   // operator unit
    resolvedTicket($s, 1, 'plumbing', 10000);    // owner unit

    $report = app(MaintenanceCostService::class)->report();

    expect($report['total_minor'])->toBe(30000)
        ->and($report['orders_closed'])->toBe(3)
        ->and($report['avg_minor'])->toBe(10000)
        // by category: plumbing 25000 (2 orders), electrical 5000 (1)
        ->and(collect($report['by_category'])->firstWhere('category', 'plumbing')['total_minor'])->toBe(25000)
        ->and(collect($report['by_category'])->firstWhere('category', 'electrical')['count'])->toBe(1)
        // owner-unit + operator-unit spend reconciles to the total
        ->and($report['owner_units_minor'])->toBe(10000)
        ->and($report['operator_units_minor'])->toBe(20000)
        ->and($report['owner_units_minor'] + $report['operator_units_minor'])->toBe($report['total_minor']);
});

// ── COSTS 2 — empty shows "—", not a fabricated €0.00; real figures render ────────────────────────
it('shows an honest empty state and then real figures on the screen', function () {
    $s = seedScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');   // has maintenance.manage

    $this->actingAs($manager)->get('/app/maintenance/costs')
        ->assertOk()->assertSee('No resolved work orders')->assertSee('labour-vs-parts');   // the honest note

    resolvedTicket($s, 0, 'plumbing', 12345);

    $this->actingAs($manager)->get('/app/maintenance/costs')
        ->assertOk()->assertSee('123.45')->assertDontSee('No resolved work orders');
});

// ── COSTS 3 — tenant-scoped + gated on maintenance.manage ─────────────────────────────────────────
it('scopes costs to the org and gates them on maintenance.manage', function () {
    $a = seedScenario(2);
    resolvedTicket($a, 0, 'plumbing', 55500);
    $b = seedScenario(2);
    resolvedTicket($b, 0, 'electrical', 99900);   // another org's spend

    $manager = operatorUser($a, [], 'Hotel Manager');
    $this->actingAs($manager)->get('/app/maintenance/costs')
        ->assertOk()->assertSee('555.00')->assertDontSee('999.00');   // only org A's spend

    $frontDesk = operatorUser($a, [], 'Front Desk');   // no maintenance.manage
    $this->actingAs($frontDesk)->get('/app/maintenance/costs')->assertForbidden();
});

// ── SETTINGS 1 — real TYPED persistence (not toast-only) + category→assignee mapping ─────────────
it('persists maintenance settings as typed columns and maps a category to a technician', function () {
    $s = seedScenario(2);
    $manager = operatorUser($s, [], 'Hotel Manager');
    $tech = operatorUser($s, ['name' => 'Tina Tech'], 'Maintenance');

    $this->actingAs($manager)->post('/app/maintenance/settings', [
        'sla_urgent_hours' => 2,
        'sla_high_hours' => 6,
        'sla_normal_hours' => 24,
        'sla_low_hours' => 72,
        'require_photo_on_resolve' => '1',
        'notify_new_urgent' => '1',
        'assignee_plumbing_id' => $tech->id,
    ])->assertRedirect(route('app.maintenance.settings'))->assertSessionHas('maintenance_settings_notice');

    $settings = maintenanceSettings($s);
    expect((int) $settings->sla_urgent_hours)->toBe(2)
        ->and((int) $settings->sla_normal_hours)->toBe(24)
        ->and((bool) $settings->require_photo_on_resolve)->toBeTrue()
        ->and((bool) $settings->notify_new_urgent)->toBeTrue()
        ->and((string) $settings->assignee_plumbing_id)->toBe((string) $tech->id)   // category → technician mapped
        ->and((bool) $settings->notify_daily_digest)->toBeFalse();   // an unsubmitted checkbox → false, real state
});

// ── SETTINGS 2 — a cross-org technician is dropped (tenant validation) ────────────────────────────
it('drops a cross-org technician from the assignee map', function () {
    $a = seedScenario(1);
    $manager = operatorUser($a, [], 'Hotel Manager');
    $b = seedScenario(1);
    $outsider = operatorUser($b, ['name' => 'Ozan Outsider'], 'Maintenance');   // org B

    $this->actingAs($manager)->post('/app/maintenance/settings', ['assignee_plumbing_id' => $outsider->id]);

    expect(maintenanceSettings($a)?->assignee_plumbing_id)->toBeNull();   // a cross-org id is silently dropped
});

// ── SETTINGS 3 — gated on maintenance.manage (GET + POST) ─────────────────────────────────────────
it('gates maintenance settings on maintenance.manage', function () {
    $s = seedScenario(1);
    $frontDesk = operatorUser($s, [], 'Front Desk');   // no maintenance.manage

    $this->actingAs($frontDesk)->get('/app/maintenance/settings')->assertForbidden();
    $this->actingAs($frontDesk)->post('/app/maintenance/settings', ['sla_urgent_hours' => 1])->assertForbidden();

    expect(maintenanceSettings($s))->toBeNull();   // nothing was written
});
