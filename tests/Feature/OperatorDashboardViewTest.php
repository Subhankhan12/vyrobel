<?php

use App\Models\Membership;
use App\Models\User;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Operator Screen 5 (Operator Dashboard, /app/dashboard) — completes the operator core.
// Locks its PRESENTATION guarantees: every KPI is a TRUE org-scoped figure (occupancy summed org-wide
// from the real Availability spine — not a mislabeled/cross-org number); TENANT isolation holds (org B's
// data never enters org A's KPIs); no fabricated metric/series; no guest PII.

/**
 * A real operator: an active member of the scenario's org holding a real back-office role — what
 * EnsureBackOfficeAccess allow-lists on /app. The role is team-scoped, so it grants nothing in any
 * other org, and the tenant-isolation tests below stay honest.
 */
function dashMember(array $scenario, string $name = 'Marisol Vandenberg'): User
{
    $user = User::factory()->create(['name' => $name]);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $scenario['org']);
}

// ── 1. Occupancy is a TRUE org figure (2 of 3 sold) + arrivals real + no PII + deferred flagged ──
it('renders true org-scoped KPIs (occupancy, arrivals) with no PII or fabricated series', function () {
    $s = seedScenario(3);   // 3 units in one room type; availability published 2026-09-01..10 (total = 3)
    $s['guest']->update(['first_name' => 'Zephyrina', 'last_name' => 'Quibblewick', 'email' => 'zephyrina-secret@example.test']);
    book($s, ['unit_id' => $s['units'][0]->id]);   // sell 2 of the 3 units for 2026-09-01..03
    book($s, ['unit_id' => $s['units'][1]->id]);

    $this->actingAs(dashMember($s))->get('/app/dashboard?date=2026-09-01')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('2 of 3 rooms sold')   // occupancy numerator/denominator are the TRUE org figures
        ->assertSee('67')                  // 2/3 → 67% (not a mislabeled per-room-type figure)
        ->assertSee('Zephyrina Quibblewick')   // real arrivals list (names only)
        ->assertSee('Not yet available')   // the revenue/ADR/RevPAR series is DEFERRED, not fabricated
        ->assertDontSee('zephyrina-secret@example.test');   // encrypted PII never rendered
});

// ── 2. Tenant isolation — org B's occupancy never contributes to org A's KPI ─────────────────────
it('isolates tenants — org B rooms never enter org A\'s occupancy', function () {
    $sA = seedScenario(3);
    book($sA, ['unit_id' => $sA['units'][0]->id]);
    book($sA, ['unit_id' => $sA['units'][1]->id]);   // A: 2 of 3
    $userA = dashMember($sA, 'Amara Okafor');

    $sB = seedScenario(3);
    book($sB, ['unit_id' => $sB['units'][0]->id]);
    book($sB, ['unit_id' => $sB['units'][1]->id]);
    book($sB, ['unit_id' => $sB['units'][2]->id]);   // B: 3 of 3
    $userB = dashMember($sB, 'Bao Nguyen');

    // A sees exactly A's figure; never B's, never a combined 5-of-6.
    $this->actingAs($userA)->get('/app/dashboard?date=2026-09-01')
        ->assertOk()
        ->assertSee('2 of 3 rooms sold')
        ->assertDontSee('3 of 3 rooms sold')
        ->assertDontSee('5 of 6 rooms sold');

    $this->actingAs($userB)->get('/app/dashboard?date=2026-09-01')
        ->assertOk()->assertSee('3 of 3 rooms sold');
});

// ── 3. Payments-today KPI is a real, tenant-scoped ledger figure ─────────────────────────────────
it('shows only the org\'s own payments received today', function () {
    $sA = seedScenario(1);
    $resA = book($sA, ['unit_id' => $sA['units'][0]->id]);
    $folios = app(FolioService::class);
    $folios->recordPayment($folios->openFolio($resA), Money::of(20000, 'EUR'));   // €200 today, org A
    $userA = dashMember($sA, 'Amara Okafor');

    $sB = seedScenario(1);
    $resB = book($sB, ['unit_id' => $sB['units'][0]->id]);
    $folios->recordPayment($folios->openFolio($resB), Money::of(99900, 'EUR'));   // €999 today, org B
    $userB = dashMember($sB, 'Bao Nguyen');

    // A's dashboard (today) shows A's €200.00 and never B's €999.00.
    $this->actingAs($userA)->get('/app/dashboard')
        ->assertOk()
        ->assertSee('€200.00')
        ->assertDontSee('€999.00');
});
