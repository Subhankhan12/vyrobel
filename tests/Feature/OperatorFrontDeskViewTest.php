<?php

use App\Models\Membership;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Operator Screen 2 (Front Desk → FrontDesk, /app/front-desk). Locks its PRESENTATION
// guarantees: real arrivals/departures/in-house render with real folio balances (terracotta-flagged);
// TENANT isolation holds; only REAL reservation statuses are shown; guest names shown but encrypted
// PII (email) never is; and the check-in ACTION genuinely persists through the real staff action and
// stays tenant-scoped (a cross-org check-in 404s and mutates nothing).

/** A real front-desk OPERATOR: an active member of the scenario's org holding a back-office role. */
function deskMember(array $scenario, string $name = 'Marisol Vandenberg'): User
{
    $user = User::factory()->create(['name' => $name]);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $scenario['org']);
}

// ── 1. Real arrivals render with real guest / status / balance; PII not leaked ───────────────────
it('renders real arrivals with the real folio balance flagged, no PII', function () {
    $s = seedScenario(1);
    $s['guest']->update([
        'first_name' => 'Zephyrina',
        'last_name' => 'Quibblewick',
        'email' => 'zephyrina-secret@example.test',   // encrypted PII tripwire
    ]);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);   // direct, confirmed, arrives 2026-09-01
    $res->update(['balance_minor' => 15000]);              // real folio balance column (FolioService-maintained)

    $this->actingAs(deskMember($s))->get('/app/front-desk?date=2026-09-01')
        ->assertOk()
        ->assertSee('Front desk')
        ->assertSee('Zephyrina Quibblewick')     // REAL guest name (front-desk surface)
        ->assertSee('Confirmed')                 // REAL status
        ->assertSee('€150.00 due')               // REAL folio balance, terracotta-flagged
        ->assertSee('Check in')                  // the real action affordance for a confirmed arrival
        ->assertDontSee('zephyrina-secret@example.test');   // encrypted PII never rendered
});

// ── 2. Only real statuses — no fabricated labels ────────────────────────────────────────────────
it('shows only real reservation statuses on the board, never a fabricated one', function () {
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id]);

    $this->actingAs(deskMember($s))->get('/app/front-desk?date=2026-09-01')
        ->assertOk()
        ->assertSee('Confirmed')
        ->assertDontSee('Awaiting deposit')
        ->assertDontSee('VIP guest')
        ->assertDontSee('Partial payment');
});

// ── 3. Check-in PERSISTS through the real staff action (PRG) ─────────────────────────────────────
it('checks a reservation in through the real action and persists it', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);   // confirmed
    $user = deskMember($s);

    $this->actingAs($user)
        ->post(route('app.front-desk.check-in', $res->id), ['date' => '2026-09-01'])
        ->assertRedirect();

    // The mutation genuinely persisted (not a faked UI state).
    $this->assertDatabaseHas('reservations', ['id' => $res->id, 'status' => 'checked_in']);
});

// ── 4. Tenant isolation — org B cannot see OR act on org A's reservation ─────────────────────────
it('isolates tenants — org B cannot view or check in org A\'s reservation', function () {
    $sA = seedScenario(1);
    $sA['guest']->update(['first_name' => 'Aurelio', 'last_name' => 'Castellanos']);
    $resA = book($sA, ['unit_id' => $sA['units'][0]->id]);
    $userA = deskMember($sA, 'Amara Okafor');

    $sB = seedScenario(1);
    $userB = deskMember($sB, 'Bao Nguyen');

    // A sees A's guest; B (own org context) never does.
    $this->actingAs($userA)->get('/app/front-desk?date=2026-09-01')->assertOk()->assertSee('Aurelio Castellanos');
    $this->actingAs($userB)->get('/app/front-desk?date=2026-09-01')->assertOk()->assertDontSee('Aurelio Castellanos');

    // B cannot check in A's reservation — the tenant-scoped resolve 404s and nothing mutates.
    $this->actingAs($userB)
        ->post(route('app.front-desk.check-in', $resA->id), ['date' => '2026-09-01'])
        ->assertNotFound();

    $this->assertDatabaseHas('reservations', ['id' => $resA->id, 'status' => 'confirmed']);
});
