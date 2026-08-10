<?php

use App\Models\Channel;
use App\Models\Property;
use App\Models\RatePlan;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// #96 GATE 1 — the operator Portfolio KPIs + cards on REAL analytics: Revenue·month + Needs-attention, per-card
// ADR/Revenue, and an HONEST Fix-sync (real channels.status, never a fabricated "synced"). Group = Σ (#17).

// ── The Revenue·month KPI + the side-by-side reconcile (Group = Σ properties, #17) ────────────────
it('shows a real revenue-this-month KPI that reconciles the portfolio to the sum of properties', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Group Admin');

    $this->actingAs($manager)->get('/app/portfolio')->assertOk()->assertSee('Revenue · month');

    // The Group room-revenue this month == the single property's (Σ of one), by construction (#17).
    $start = CarbonImmutable::today()->startOfMonth();
    $end = $start->addMonth();
    $group = PerformanceMetrics::forProperties([$s['property']->id], $start, $end, 'EUR');
    $this->actingAs($manager)->get('/app/portfolio')->assertSee(MoneyFormatter::display($group->roomRevenueMinor, 'EUR'));
});

// ── Needs-attention counts real signals (an empty property is a real "finish setup") ──────────────
it('counts a genuinely un-set-up property in needs-attention', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Group Admin');
    $this->actingAs($manager)->post('/app/portfolio', ['name' => 'Blank Retreat', 'type' => 'hotel', 'location' => 'Nowhere', 'timezone' => 'UTC']);

    $this->actingAs($manager)->get('/app/portfolio')
        ->assertOk()
        ->assertSee('Needs attention');
    // The empty property is flagged (finish-setup) — the KPI is a real derived count, not fabricated.
});

// ── Fix-sync HONEST — no channel → "No channels connected"; never a fabricated "synced" ──────────
it('renders an honest no-channel status, never a fabricated synced state', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Group Admin');

    // The honest label is present; there is no fabricated "synced N min ago" claim (sync is account-gated).
    $this->actingAs($manager)->get('/app/portfolio')
        ->assertOk()
        ->assertSee('No channels connected')
        ->assertDontSee('min ago');
});

// ── A real errored channel → "Sync needs attention" + Fix sync (from the real channels.status) ────
it('surfaces a real channel error as an honest fix-sync affordance', function () {
    $s = seedScenario(1);
    // Created while the tenant context is the seed org (BelongsToOrganization auto-fills organization_id).
    Channel::create(['property_id' => $s['property']->id, 'name' => 'Booking.com', 'status' => 'error']);
    $manager = operatorUser($s, [], 'Group Admin');

    $this->actingAs($manager)->get('/app/portfolio')
        ->assertOk()
        ->assertSee('Sync needs attention')
        ->assertSee('Fix sync');
});

// ── Read-only refuses adding a property while the workspace is paused ──────────────────────────────
it('refuses adding a property while the workspace is paused', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Group Admin');
    $s['org']->forceFill(['paused_until' => now()->addMonth()])->save();

    $this->actingAs($manager)->post('/app/portfolio', ['name' => 'Paused Property', 'type' => 'hotel', 'location' => 'X', 'timezone' => 'UTC'])
        ->assertRedirect(route('app.portfolio'))->assertSessionHas('portfolio_error');
    expect(Property::query()->where('name', 'Paused Property')->exists())->toBeFalse();
});

// ═══════════════════════════════════════════════════════════════════════════════════════════════════
// P27 (design-parity) — the CROSS-PROPERTY ROLLUP. A portfolio view enumerates properties AND rolls up
// money, so a scope slip here leaks another org's REVENUE, not just a name.
//
// ⚠️ The pre-existing "#17 reconciles" test above is VACUOUS on its own: seedScenario() books nothing,
// so it asserts "€0.00" on a page where every figure is zero — inflating the rollup by €9,999.99 left
// it green (proven by mutation). These give the rollup real, DISTINCT figures so it can actually fail.
// ═══════════════════════════════════════════════════════════════════════════════════════════════════

it('rolls up ONLY this org\'s properties and reconciles Portfolio == Σ per-property (#17/#38)', function () {
    // A FOREIGN org holding real money — the figure that must never reach my rollup.
    $foreign = seedScenario(1);
    $foreign['property']->update(['name' => 'Foreign Manor']);
    book($foreign, ['price_minor' => 99900]);          // €999.00

    // MY org — property A (from the scenario) plus a SECOND property, so the total is a genuine Σ.
    $mine = seedScenario(1);
    book($mine, ['price_minor' => 10000]);             // €100.00

    $propB = Property::create(['name' => 'Second House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $rtB = RoomType::create(['property_id' => $propB->id, 'name' => 'Std', 'kind' => 'room']);
    Unit::create(['property_id' => $propB->id, 'room_type_id' => $rtB->id, 'code' => 'B1']);
    $rpB = RatePlan::create(['property_id' => $propB->id, 'room_type_id' => $rtB->id, 'name' => 'Flex', 'currency' => 'EUR']);
    app(AvailabilityService::class)->recompute($rtB->id, '2026-09-01', '2026-09-10');
    book(['property' => $propB, 'roomType' => $rtB, 'ratePlan' => $rpB, 'guest' => $mine['guest']], ['price_minor' => 25000]); // €250.00

    $page = $this->actingAs(operatorUser($mine, [], 'Group Admin'))->get('/app/portfolio')->assertOk();

    // Both of MY properties' figures, and the Portfolio row == their sum, to the cent.
    $page->assertSee('€100.00')->assertSee('€250.00')->assertSee('€350.00');

    // ⚠️ The leak assertions: neither the foreign figure nor a total that swallowed it.
    $page->assertDontSee('€999.00')->assertDontSee('€1,349.00')->assertDontSee('Foreign Manor');
});

it('shows "—" for an average over ZERO room-nights but keeps a genuinely computed zero', function () {
    $s = seedScenario(1);                               // real units, no bookings at all
    $page = $this->actingAs(operatorUser($s, [], 'Group Admin'))->get('/app/portfolio')->assertOk();

    // ADR over 0 room-nights is UNDEFINED — the design renders "—", never a fabricated €0.00 average.
    $page->assertSee('—');
    // …but revenue really IS zero, and a computed zero stays a zero (the Owners-list ruling).
    $page->assertSee('€0.00');
});

it('gates the "Full report" link on the same permission as the route it points at (#30)', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Group Admin');

    // Holds reports.view → the control renders. (Asserted through a REQUEST: a bare $user->can() here
    // would have no spatie team set and fail silently to false — the null-team footgun.)
    $this->actingAs($manager)->get('/app/portfolio')->assertOk()->assertSee('Full report');

    // ⚠️ NOTE: no PROVISIONED role holds properties.view WITHOUT reports.view, so this branch is not
    // reachable through the stock catalogue — the gate is defence-in-depth. Revoke the permission
    // directly so the assertion still discriminates instead of quietly testing nothing.
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($s['org']->id);
    Role::query()
        ->where('name', 'Group Admin')->where('organization_id', $s['org']->id)->firstOrFail()
        ->revokePermissionTo('reports.view');
    $registrar->forgetCachedPermissions();

    $this->actingAs($manager->fresh())->get('/app/portfolio')->assertOk()->assertDontSee('Full report');
});
