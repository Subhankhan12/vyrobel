<?php

use App\Models\Membership;
use App\Models\Reservation;
use App\Models\User;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A seeded scenario with a real nightly rate over the window, so room types are genuinely bookable. */
function rackRated(int $units = 1, int $nightlyMinor = 12000): array
{
    $s = seedScenario($units);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of($nightlyMinor, 'EUR'));

    return $s;
}

/** A valid booking POST payload (new guest, no payment). */
function rackBooking(array $s): array
{
    return [
        'check_in' => '2026-09-02', 'check_out' => '2026-09-05',
        'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id,
        'guest_first_name' => 'Grace', 'guest_last_name' => 'Hopper',
        'adults' => '2', 'children' => '0', 'source' => 'direct',
    ];
}

// Design pass · Operator Screen 1 (Calendar → ReservationRack, /app/rack). Locks its PRESENTATION
// guarantees: real reservations render as booking bars on real availability; TENANT isolation holds
// (another org's booking is never visible); only REAL reservation statuses/labels are shown (nothing
// invented); guest NAMES are shown (operator surface) but encrypted PII (email) never is.

/**
 * A real operator: an active member of the scenario's org (so SetTenantForWebRequest resolves it) who
 * also holds a real back-office role — what EnsureBackOfficeAccess allow-lists on /app, and what a
 * genuine staff invite assigns. The role is team-scoped, so it grants nothing in any other org.
 */
function memberOf(array $scenario, string $name = 'Marisol Vandenberg'): User
{
    $user = User::factory()->create(['name' => $name]);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    return grantBackOfficeRole($user, $scenario['org']);
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

// ══ Operator Calendar exact-match · GATE 2 (rack new-reservation + read-only) ═══════════════════════

// ── The inline New-reservation panel shows REAL available units → a certified Confirm POST ─────────
it('renders the inline new-reservation panel with real availability and a certified confirm form', function () {
    $s = rackRated(1, 12000);                       // a real €120 nightly rate over 2026-09-01..10
    $operator = operatorUser($s, ['name' => 'Marco Reyes'], 'Front Desk');   // holds reservations.create

    $this->actingAs($operator)
        ->get('/app/calendar?new=1&ci=2026-09-02&co=2026-09-05&gf=Marco&gl=Reyes&adults=2&source=direct')
        ->assertOk()
        ->assertSee('New reservation')
        ->assertSee('Check rack')
        ->assertSee('€360.00')                          // the REAL stay total (€120 x 3 nights)
        ->assertSee('Confirm')                          // a real per-unit Confirm button
        ->assertSee(route('app.reservations.store'), false)   // posts to the CERTIFIED write path
        ->assertSee('Open the full form');
});

// ── Read-only: the calendar hides New reservation AND the certified write is refused when paused ──
it('respects read-only — hides new-reservation and refuses the write when the workspace is paused', function () {
    $s = rackRated(1, 12000);
    $operator = operatorUser($s, ['name' => 'Marco Reyes'], 'Front Desk');

    // Pause the workspace (a real future window).
    $s['org']->update(['paused_until' => now()->addDays(5)]);

    // The calendar shows the paused banner and NO "New reservation" affordance.
    $this->actingAs($operator)->get('/app/calendar')
        ->assertOk()
        ->assertSee('Workspace paused')
        ->assertDontSee('>New reservation<', false);

    // The certified write is refused at the single write path — nothing is created.
    $before = Reservation::query()->count();
    $this->actingAs($operator)->post('/app/reservations/create', rackBooking($s))
        ->assertRedirect();
    expect(Reservation::query()->count())->toBe($before);   // no booking while paused
});
