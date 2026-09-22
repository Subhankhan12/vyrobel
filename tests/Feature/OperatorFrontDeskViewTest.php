<?php

use App\Models\Membership;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
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

// ── 2b. F-OP-1 — the board defaults to TODAY, never the furthest-future booking ──────────────────
it('opens the front-desk board on today by default, never the furthest-future booking (F-OP-1)', function () {
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id]);   // a FUTURE stay (arrives 2026-09-01) — the old `max(check_in)`

    $today = CarbonImmutable::today();

    $this->actingAs(deskMember($s))->get('/app/front-desk')   // NO ?date param
        ->assertOk()
        ->assertSee($today->format('l, j M Y'))                       // opens on TODAY
        ->assertSee('date='.$today->subDay()->toDateString());       // prev-day link proves today is the anchor
});

// ── 3. Check-in PERSISTS through the real staff action (PRG) ─────────────────────────────────────
it('checks a reservation in through the real action and persists it', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);   // confirmed
    $user = deskMember($s);

    // ⚠️⚠️ `confirm_early` WAS ADDED 2026-09-22, AND THE REASON IS THE POINT — the assertion below is
    // unchanged and still means what it always meant. The founder ruled an early check-in must raise a
    // confirmation, and 🔬 THIS FIXTURE IS 31 DAYS EARLY: the suite's clock is frozen at `VY_TEST_CLOCK`
    // (2026-08-01, tests/Pest.php) while `book()` arrives 2026-09-01. So this POST began returning the
    // confirmation screen — a 200 where the test asserts a redirect — which is the NEW behaviour working,
    // not a regression.
    //
    // ⚠️ THIS TEST IS NOT ABOUT THE WARNING. Its subject is "check-in PERSISTS through the real staff action
    // (PRG)", and that subject is untouched. Passing the confirmation is how this fixture now reaches the
    // action at all; it is the equivalent of the clerk pressing "Check in anyway". ⚠️ Deliberately NOT
    // re-dated to dodge the confirmation — moving the fixture would quietly change which day the front-desk
    // board is testing, and three other tests in this file depend on that date.
    $this->actingAs($user)
        ->post(route('app.front-desk.check-in', $res->id), ['date' => '2026-09-01', 'confirm_early' => 1])
        ->assertRedirect();

    // The mutation genuinely persisted (not a faked UI state).
    $this->assertDatabaseHas('reservations', ['id' => $res->id, 'status' => 'checked_in']);
});

// ── 3b. GATE 2 — the day timeline plots real SCHEDULED times (name-only), never a fabricated arrival ──
it('plots the day timeline from the real expected-arrival time, name-only', function () {
    $s = seedScenario(1);
    $s['guest']->update(['first_name' => 'Tam', 'last_name' => 'Rivera']);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $res->update(['expected_arrival' => 'arriving 16:30']);   // a parseable window → plots at 16:30

    $this->actingAs(deskMember($s))->get('/app/front-desk?date=2026-09-01')
        ->assertOk()
        ->assertSee('Today’s timeline')
        ->assertSee('16:30')                             // plotted at the REAL expected time (not a guess)
        ->assertSee('Tam Rivera · arrives 16:30');       // NAME-only dot (#28), honestly "scheduled"
});

// ── 3c. GATE 2 — on-desk COLLECT records a REAL folio payment and returns to the BOARD ───────────
it('records a real on-desk cash payment from the board and returns to the board', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);

    $this->actingAs(deskMember($s))
        ->post(route('app.reservations.payment', $res->id), [
            'amount' => '50', 'method' => 'cash', 'idempotency_key' => 'desk-'.$res->id,
            'return_to' => 'front-desk', 'return_date' => '2026-09-01',
        ])
        ->assertRedirect(route('app.front-desk').'?date=2026-09-01')   // back to the BOARD, not the detail
        ->assertSessionHas('desk_notice');

    // A REAL payment row landed (integer minor, cash) through the certified FolioService path.
    expect(Payment::query()->where('reservation_id', $res->id)->where('type', 'payment')->where('amount_minor', 5000)->exists())->toBeTrue();
});

// ── 3d. GATE 2 — the on-desk collect is idempotent (a double-submit records ONCE) ────────────────
it('records an on-desk collect once when the same idempotency key is submitted twice', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $payload = ['amount' => '50', 'method' => 'cash', 'idempotency_key' => 'dup-'.$res->id, 'return_to' => 'front-desk', 'return_date' => '2026-09-01'];

    $this->actingAs(deskMember($s))->post(route('app.reservations.payment', $res->id), $payload)->assertRedirect();
    $this->actingAs(deskMember($s))->post(route('app.reservations.payment', $res->id), $payload)->assertRedirect();

    expect(Payment::query()->where('reservation_id', $res->id)->where('type', 'payment')->count())->toBe(1);
});

// ── 3e. GATE 2 — an in-place MOVE reseats the line through the certified path and returns to the board ──
it('moves a reservation to a free same-type unit from the board and returns to the board', function () {
    $s = seedScenario(2);
    $s['units'][0]->update(['code' => 'ROOMA9']);
    $s['units'][1]->update(['code' => 'ROOMB9']);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = ReservationUnit::query()->where('reservation_id', $res->id)->where('status', 'active')->firstOrFail();

    $this->actingAs(deskMember($s))
        ->post(route('app.reservations.move', $res->id), [
            'reservation_unit_id' => $line->id, 'unit_id' => $s['units'][1]->id,
            'return_to' => 'front-desk', 'return_date' => '2026-09-01',
        ])
        ->assertRedirect(route('app.front-desk').'?date=2026-09-01')   // back to the BOARD, not the detail
        ->assertSessionHas('desk_notice');

    // The line genuinely moved through AssignmentService::assignUnit (the single guarded write path).
    expect((string) $line->fresh()->unit_id)->toBe((string) $s['units'][1]->id);
});

// ── 3f. GATE 2 — a WALK-IN creates a source=walk_in booking, checks it in, and records the first night ──
it('creates a walk-in through the certified path, checks it in, and records the first-night payment', function () {
    $s = seedScenario(1);
    $today = CarbonImmutable::today();
    // Publish availability + price the plan across today's window so the walk-in is genuinely bookable now
    // (seedScenario only publishes the Sept demo window).
    app(AvailabilityService::class)->recompute($s['roomType']->id, $today->toDateString(), $today->addDays(4)->toDateString());
    app(RateAndRestrictionService::class)->setRate(
        $s['ratePlan'], $today->toDateString(), $today->addDays(3)->toDateString(), Money::of(9000, 'EUR'),
    );

    $this->actingAs(deskMember($s))
        ->post(route('app.reservations.create'), [
            'source' => 'walk_in', 'walk_in' => '1',
            'check_in' => $today->toDateString(), 'check_out' => $today->addDay()->toDateString(),
            'room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id,
            'guest_first_name' => 'Wanda', 'guest_last_name' => 'Kingsley', 'adults' => '1',
            'payment_method' => 'cash', 'payment_amount' => '50',   // collect first night now
        ])
        ->assertRedirect(route('app.front-desk').'?date='.$today->toDateString())   // returns to the BOARD
        ->assertSessionHas('desk_notice');

    // Created AS a walk-in AND checked in (both via the certified createBooking + check-in path).
    $res = Reservation::query()->where('source', 'walk_in')->latest('created_at')->firstOrFail();
    expect((string) $res->status)->toBe('checked_in')
        // The first-night payment landed on the real folio (integer minor, cash).
        ->and(Payment::query()->where('reservation_id', $res->id)->where('type', 'payment')->where('amount_minor', 5000)->exists())->toBeTrue();
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
