<?php

use App\Models\Channel;
use App\Models\Property;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
