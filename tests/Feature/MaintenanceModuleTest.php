<?php

use App\Models\Membership;
use App\Models\OwnerExpense;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\MaintenanceService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Maintenance module · Step 4 — integrity: the unstyled operator stub renders REAL tenant-scoped data
// (cross-org detail 404s), and a full end-to-end flow ties BOTH integrations together in one ticket.
// (Lifecycle, out-of-service↔availability + concurrency, and cost→owner-expense are locked by
// MaintenanceTicketTest / MaintenanceOutOfServiceTest / MaintenanceCostExpenseTest.)

function maintMember(array $s): User
{
    $user = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    // A Membership alone is NOT operator access — /app is an allow-list of back-office roles
    // (EnsureBackOfficeAccess). A real staff member holds one, as OwnerInviteService assigns.
    return grantBackOfficeRole($user, $s['org']);
}

// ── The unstyled stub renders real tenant-scoped tickets; org B's ticket is not shown ────────────
it('renders the maintenance list stub tenant-scoped', function () {
    $s = seedScenario(1);
    $s['units'][0]->update(['code' => 'MTUNIT']);
    app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Leaky roof']);
    $member = maintMember($s);

    // Another org's ticket must NOT appear.
    $other = seedScenario(1);
    $other['units'][0]->update(['code' => 'FOREIGN']);
    app(MaintenanceService::class)->open(['unit_id' => $other['units'][0]->id, 'title' => 'Foreign ticket']);

    $this->actingAs($member)->get('/app/maintenance')
        ->assertOk()
        ->assertSee('Leaky roof')
        ->assertSee('MTUNIT')
        ->assertDontSee('Foreign ticket')
        ->assertDontSee('FOREIGN');
});

// ── The detail stub is tenant-scoped: a cross-org ticket id 404s ─────────────────────────────────
it('404s a cross-org maintenance ticket in the detail stub', function () {
    $other = seedScenario(1);
    $foreign = app(MaintenanceService::class)->open(['unit_id' => $other['units'][0]->id, 'title' => 'Foreign']);

    $s = seedScenario(1);   // org A operator
    $member = maintMember($s);
    $own = app(MaintenanceService::class)->open(['unit_id' => $s['units'][0]->id, 'title' => 'Own ticket']);

    $this->actingAs($member)->get("/app/maintenance/{$foreign->id}")->assertNotFound();   // cross-org → 404
    $this->actingAs($member)->get("/app/maintenance/{$own->id}")->assertOk()->assertSee('Own ticket');
});

// ── End-to-end: OOS + cost tie together on ONE ticket over an OWNED unit ─────────────────────────
it('ties out-of-service and cost-to-owner-expense together across one ticket lifecycle', function () {
    $s = seedScenario(3);
    $owner = ownUnit($s['units'][0]);   // owned unit + 15% agreement
    $av = app(AvailabilityService::class);
    $svc = app(MaintenanceService::class);

    expect($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(3);

    // Open an OOS ticket → unit out of service → excluded from availability.
    $ticket = $svc->open([
        'unit_id' => $s['units'][0]->id, 'title' => 'Rewire', 'category' => 'electrical', 'takes_out_of_service' => true,
    ]);
    expect($s['units'][0]->refresh()->condition_status)->toBe('out_of_service')
        ->and($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(2);

    // Resolve with a cost → unit back in service + a deductible owner expense posted via the certified path.
    $svc->start($ticket);
    $svc->resolve($ticket->refresh(), Money::of(20000, 'EUR'));

    expect($s['units'][0]->refresh()->condition_status)->toBe('in_service')
        ->and($av->availableOn($s['roomType']->id, '2026-09-05'))->toBe(3);   // sellable again

    app(TenantContext::class)->set($s['org']->id);
    $expense = OwnerExpense::query()->where('source_type', 'maintenance_ticket')->where('source_id', $ticket->id)->firstOrFail();
    expect($expense->owner_id)->toBe($owner->id)
        ->and($expense->amount_minor)->toBe(20000)
        ->and(ownerNet($owner->id))->toBe(-20000);   // flowed to owner_ledger, to the cent
});
