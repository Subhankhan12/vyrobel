<?php

use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Operator Screen 1 (Calendar → ReservationRack, /app/rack). Locks its PRESENTATION
// guarantees: real reservations render as booking bars on real availability; TENANT isolation holds
// (another org's booking is never visible); only REAL reservation statuses/labels are shown (nothing
// invented); guest NAMES are shown (operator surface) but encrypted PII (email) never is.

/** A user who is an active member of the scenario's org (so SetTenantForWebRequest resolves it). */
function memberOf(array $scenario, string $name = 'Marisol Vandenberg'): User
{
    $user = User::factory()->create(['name' => $name]);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return $user;
}

// ── 1. A real reservation renders as a booking bar with real guest / status / dates ──────────────
it('renders real reservations as booking bars on the rack for a seeded org', function () {
    $s = seedScenario(1);
    $s['guest']->update([
        'first_name' => 'Zephyrina',
        'last_name' => 'Quibblewick',
        'email' => 'zephyrina-secret@example.test',   // encrypted PII tripwire
    ]);
    book($s, ['unit_id' => $s['units'][0]->id]);        // direct, confirmed, 2026-09-01..03

    $user = memberOf($s);

    $response = $this->actingAs($user)->get('/app/rack?start=2026-09-01')->assertOk();

    $response
        ->assertSee('Calendar')                 // styled operator shell rendered
        ->assertSee('Std')                      // real room-type row (StubViews 'Double Room' analogue)
        ->assertSee('Zephyrina Quibblewick')    // REAL guest name on the bar (operator surface)
        ->assertSee('Confirmed · Direct')       // REAL status + source (bar title; not in the legend)
        ->assertDontSee('zephyrina-secret@example.test');   // encrypted PII never rendered
});

// ── 2. No invented statuses/labels — only the real reservation status is shown ───────────────────
it('shows only real reservation statuses, never a fabricated one', function () {
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id]);   // status defaults to 'confirmed'

    $this->actingAs(memberOf($s))->get('/app/rack?start=2026-09-01')
        ->assertOk()
        ->assertSee('Confirmed · Direct')     // the real status the booking actually has
        // None of these are backed by any field on the reservation — they must never appear:
        ->assertDontSee('Balance due')
        ->assertDontSee('Awaiting deposit')
        ->assertDontSee('VIP guest')
        ->assertDontSee('Overbooked');        // active line, not an overbooked one
});

// ── 3. The rack window is real — a booking outside the visible nights is not drawn ───────────────
it('only draws bookings that fall inside the visible window', function () {
    $s = seedScenario(1);
    $s['guest']->update(['first_name' => 'Zephyrina', 'last_name' => 'Quibblewick']);
    book($s, ['unit_id' => $s['units'][0]->id]);   // 2026-09-01..03

    // A window a year later must not show the September booking (proves real date filtering).
    $this->actingAs(memberOf($s))->get('/app/rack?start=2027-09-01')
        ->assertOk()
        ->assertSee('Std')                       // room-type row still lists
        ->assertDontSee('Zephyrina Quibblewick');
});

// ── 4. Tenant isolation — org B's member never sees org A's booking ──────────────────────────────
it('isolates tenants — another org\'s reservations are not visible on the rack', function () {
    $sA = seedScenario(1);
    $sA['guest']->update(['first_name' => 'Aurelio', 'last_name' => 'Castellanos']);
    book($sA, ['unit_id' => $sA['units'][0]->id]);
    $userA = memberOf($sA, 'Amara Okafor');

    $sB = seedScenario(1);
    $userB = memberOf($sB, 'Bao Nguyen');

    // A sees A's guest.
    $this->actingAs($userA)->get('/app/rack?start=2026-09-01')
        ->assertOk()->assertSee('Aurelio Castellanos');

    // B (own org context) never sees A's guest.
    $this->actingAs($userB)->get('/app/rack?start=2026-09-01')
        ->assertOk()->assertDontSee('Aurelio Castellanos');
});
