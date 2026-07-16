<?php

use App\Models\Guest;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\User;
use App\Support\Reservations\ReservationStage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// THE OPERATOR RESERVATIONS LIST (Wave 2 · Sub-gate 1).
//
// What is actually load-bearing here, and therefore what these tests defend:
//   • TENANT isolation + the /app role gate — the two security facts (invariant #26).
//   • The chips filter through the SHARED ReservationStage, so a chip can never disagree with the badge it
//     filters on. These tests assert the resolver's OWN classification is what renders — not a re-derivation.
//   • NO INVENTED VOCABULARY — "Completed" is not a status, and the OTA brand is not modelled at all.
//   • The stat tiles obey locked decision 3 (this calendar month · check_in · cancelled/no_show EXCLUDED ·
//     per currency), asserted against the DB rather than against a hard-coded figure.
//   • Money reconciles to total_minor / balance_minor, which FolioService::recompute() keeps in sync with
//     the append-only ledger.

beforeEach(function () {
    // Stage and "this month" are both date-dependent. book() always stays 2026-09-01→03 (that is the window
    // seedScenario publishes availability for), so freeze mid-stay: September 2026 is then "this month" and
    // the fixtures' stages are deterministic. TestCase::tearDown() resets Carbon's test-now.
    Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00'));
});

/**
 * Put a booking at a known status/window, optionally with its own guest.
 *
 * Each booking takes its OWN unit: book() defaults every stay to the same dates, so reusing a unit trips the
 * certified no-double-booking guard (correctly — it just isn't what this file tests). Status/dates are set
 * with a direct update because AssignmentService guards real transitions and this file is about how a row IS
 * classified and rendered, not how it got there. (Named distinctly from ReservationStageTest's
 * stagedReservation() — both files load into one process, so a shared name would fatal on redeclare.)
 */
function listReservation(array $s, int $unitIndex, string $status, string $checkIn, string $checkOut, array $extra = []): Reservation
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);

    Reservation::query()->whereKey($reservation->getKey())->update(array_merge([
        'status' => $status,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
    ], $extra));

    return $reservation->fresh();
}

/** A guest with a real name — and, when asked, real (encrypted) contact details the page must never leak. */
function listGuest(string $first, string $last, array $extra = []): Guest
{
    return Guest::create(array_merge(['first_name' => $first, 'last_name' => $last], $extra));
}

/** An OWNER-role user: a Membership (the only way their org resolves) and NO back-office role — as production. */
function listOwnerUser(array $s, int $unitIndex): User
{
    $user = User::factory()->create(['name' => 'Olive Owner', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][$unitIndex]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'commission_pct' => 15.00, 'effective_from' => '2026-01-01',
    ]);

    return $user;
}

// ── 1. THE POINT OF THE PAGE — real rows, real columns, tenant-scoped ────────────────────────────
it('renders the real reservations with guest, reference, unit, dates and money', function () {
    $s = seedScenario(2);
    $guest = listGuest('Ada', 'Lovelace');

    $reservation = listReservation($s, 0, 'confirmed', '2026-09-01', '2026-09-04', ['lead_guest_id' => $guest->id]);
    postRoom($reservation, 42500);   // the REAL ledger path — recompute() writes total_minor/balance_minor

    $response = $this->actingAs(operatorUser($s))->get('/app/reservations');

    $response->assertOk()
        ->assertSee('Ada Lovelace')                       // the guest NAME — the only PII this surface shows
        ->assertSee($reservation->reference)              // the real human code
        ->assertSee($s['units'][0]->code)                 // the real assignment
        ->assertSee('1 Sep 2026')                         // the real stay window…
        ->assertSee('4 Sep 2026')
        ->assertSee('3 nights')                           // …DERIVED, half-open [check_in, check_out)
        ->assertSee('€425.00');                           // the real money, formatted by money()

    // Every figure on the page traces to the row the ledger wrote.
    expect((int) $reservation->fresh()->total_minor)->toBe(42500);
});

// ── 2. TENANT ISOLATION — another org's booking is not on this page at all ───────────────────────
it('isolates tenants — another organization’s reservation is never listed', function () {
    $other = seedScenario(1);
    // seedScenario codes the first unit 'U1' in EVERY org, so a plain assertDontSee('U1') would fail on this
    // org's OWN legitimately-rendered unit and prove nothing. Give the foreign unit a code that could only
    // appear here by leaking.
    $other['units'][0]->update(['code' => 'FOREIGNUNIT9']);
    $foreignGuest = listGuest('Foreign', 'Guest');
    $foreign = listReservation($other, 0, 'confirmed', '2026-09-01', '2026-09-03', ['lead_guest_id' => $foreignGuest->id]);
    postRoom($foreign, 99900);

    $s = seedScenario(1);   // a second org — the tenant context now points here
    $mineGuest = listGuest('Mine', 'Own');
    $mine = listReservation($s, 0, 'confirmed', '2026-09-01', '2026-09-03', ['lead_guest_id' => $mineGuest->id]);
    postRoom($mine, 10000);

    // Reservation is BelongsToOrganization: the foreign row is not in the collection, so nothing about it —
    // not the guest, not the reference, not the unit, not the money — can reach the page.
    $response = $this->actingAs(operatorUser($s))->get('/app/reservations');

    $response->assertOk()
        ->assertSee('Mine Own')
        ->assertDontSee('Foreign Guest')
        ->assertDontSee($foreign->reference)
        ->assertDontSee('FOREIGNUNIT9')
        ->assertDontSee('€999.00');

    // …and the foreign row must not be counted either — the tiles and the footer are tenant-scoped reads,
    // so this org sees exactly its own one reservation.
    $response->assertSee('Showing 1 of 1 reservation');
});

// ── 3. THE /app ROLE GATE — an owner never reaches the operator's reservations ───────────────────
it('refuses an owner-role user and sends them to their own portal', function () {
    $s = seedScenario(1);
    $guest = listGuest('Ada', 'Lovelace');
    $reservation = listReservation($s, 0, 'confirmed', '2026-09-01', '2026-09-03', ['lead_guest_id' => $guest->id]);
    postRoom($reservation, 42500);

    $owner = listOwnerUser($s, 0);

    // A Membership is NOT staff access — EnsureBackOfficeAccess is an allow-list of real back-office roles.
    $response = $this->actingAs($owner)->get('/app/reservations');

    $response->assertRedirect(route('owner.dashboard'));

    // The rejection leaks nothing from the page it refused.
    expect($response->getContent())->not->toContain('Ada Lovelace')
        ->and($response->getContent())->not->toContain($reservation->reference);
});

// ── 4. THE CHIPS FILTER THROUGH THE SHARED RESOLVER ─────────────────────────────────────────────
it('filters by stage using the shared ReservationStage scope', function () {
    $s = seedScenario(3);

    $inHouse = listReservation($s, 0, 'checked_in', '2026-09-01', '2026-09-05', ['lead_guest_id' => listGuest('Ina', 'House')->id]);
    $upcoming = listReservation($s, 1, 'confirmed', '2026-09-20', '2026-09-23', ['lead_guest_id' => listGuest('Uma', 'Coming')->id]);
    $past = listReservation($s, 2, 'checked_out', '2026-08-01', '2026-08-04', ['lead_guest_id' => listGuest('Pat', 'Gone')->id]);

    $staff = operatorUser($s);

    // The fixtures are what the RESOLVER says they are — asserted through the shared class, not re-derived,
    // so this test cannot drift from the page's own definition.
    $today = Carbon::parse('2026-09-02');
    expect(ReservationStage::for($inHouse, $today))->toBe(ReservationStage::IN_HOUSE)
        ->and(ReservationStage::for($upcoming, $today))->toBe(ReservationStage::UPCOMING)
        ->and(ReservationStage::for($past, $today))->toBe(ReservationStage::PAST);

    $this->actingAs($staff)->get('/app/reservations?stage=in_house')
        ->assertOk()->assertSee('Ina House')->assertDontSee('Uma Coming')->assertDontSee('Pat Gone');

    $this->actingAs($staff)->get('/app/reservations?stage=upcoming')
        ->assertOk()->assertSee('Uma Coming')->assertDontSee('Ina House')->assertDontSee('Pat Gone');

    $this->actingAs($staff)->get('/app/reservations?stage=past')
        ->assertOk()->assertSee('Pat Gone')->assertDontSee('Ina House')->assertDontSee('Uma Coming');

    // "All" shows every stage…
    $this->actingAs($staff)->get('/app/reservations')
        ->assertOk()->assertSee('Ina House')->assertSee('Uma Coming')->assertSee('Pat Gone');

    // …and an unknown stage falls back to All rather than inventing a filter the app does not have.
    $this->actingAs($staff)->get('/app/reservations?stage=completed')
        ->assertOk()->assertSee('Ina House')->assertSee('Uma Coming')->assertSee('Pat Gone');
});

// ── 5. NO INVENTED VOCABULARY — not a status the enum lacks, not an OTA brand nothing models ────
it('renders only real vocabulary — no invented statuses and no OTA brand', function () {
    $s = seedScenario(2);

    $ota = listReservation($s, 0, 'confirmed', '2026-09-01', '2026-09-03', [
        'lead_guest_id' => listGuest('Otto', 'Agency')->id, 'source' => 'ota',
    ]);
    postRoom($ota, 20000);
    listReservation($s, 1, 'checked_out', '2026-08-01', '2026-08-03', ['lead_guest_id' => listGuest('Cee', 'Out')->id]);

    $response = $this->actingAs(operatorUser($s))->get('/app/reservations');

    $response->assertOk()
        // `source` is exactly direct/walk_in/ota/corporate/group/agent/manual and NO brand column exists on
        // any table — an OTA booking is "OTA", and nothing more specific can honestly be claimed.
        ->assertSee('OTA')
        ->assertDontSee('Booking.com')
        ->assertDontSee('Expedia')
        ->assertDontSee('Airbnb')
        // "Completed" is not in the status enum — a finished stay is `checked_out`, whose stage is "Past".
        ->assertDontSee('Completed')
        ->assertSee('Past');

    expect((string) $ota->fresh()->source)->toBe('ota');
});

// ── 6. THE STAT TILES — locked decision 3, asserted against the DB ──────────────────────────────
it('scopes the tiles to this month by check-in and excludes cancelled and no-show bookings', function () {
    $s = seedScenario(4);

    // Two live arrivals THIS month (September 2026).
    $a = listReservation($s, 0, 'confirmed', '2026-09-01', '2026-09-03', ['lead_guest_id' => listGuest('Ann', 'Arrival')->id]);
    postRoom($a, 30000);
    $b = listReservation($s, 1, 'checked_in', '2026-09-02', '2026-09-06', ['lead_guest_id' => listGuest('Ben', 'Arrival')->id]);
    postRoom($b, 20000);

    // A CANCELLED arrival this month. cancelReservation() → terminate() never zeroes total_minor, so a naive
    // SUM would silently bill it as booked value. It must be excluded from all three tiles.
    $cancelled = listReservation($s, 2, 'cancelled', '2026-09-04', '2026-09-07', ['lead_guest_id' => listGuest('Cass', 'Cancelled')->id]);
    postRoom($cancelled, 77700);

    // An arrival in ANOTHER month — the tiles key on check_in, so this is out of scope too.
    $next = listReservation($s, 3, 'confirmed', '2026-10-02', '2026-10-05', ['lead_guest_id' => listGuest('Nia', 'NextMonth')->id]);
    postRoom($next, 55500);

    // The expected figure, computed from the DB by the locked rule — never a hard-coded constant.
    $expected = (int) Reservation::query()
        ->whereBetween('check_in', ['2026-09-01', '2026-09-30'])
        ->whereNotIn('status', ['cancelled', 'no_show'])
        ->sum('total_minor');

    expect($expected)->toBe(50000)                       // 30000 + 20000 — the cancelled 77700 is NOT in it
        ->and((int) $cancelled->fresh()->total_minor)->toBe(77700);   // …and it was never zeroed, so the
    //    exclusion is doing the work

    $response = $this->actingAs(operatorUser($s))->get('/app/reservations');

    $response->assertOk()
        ->assertSee('Arrivals this month')
        ->assertSee('Booked value · arrivals this month', false)
        ->assertSee('€500.00')          // = $expected, per currency
        ->assertDontSee('€1,277.00')    // the cancelled row folded in
        ->assertDontSee('€1,832.00');   // …and next month's arrival folded in too

    // The cancelled booking is still a real row on the page — it is excluded from the TILES, not hidden.
    $response->assertSee('Cass Cancelled')->assertSee('Cancelled');
});

// ── 7. BALANCES RECONCILE TO balance_minor ──────────────────────────────────────────────────────
it('reconciles the rendered balance to the reservation’s balance_minor', function () {
    $s = seedScenario(2);

    $owing = listReservation($s, 0, 'confirmed', '2026-09-01', '2026-09-03', ['lead_guest_id' => listGuest('Owen', 'Owing')->id]);
    postRoom($owing, 26000);          // charged, nothing paid → the whole charge is due

    $free = listReservation($s, 1, 'confirmed', '2026-09-01', '2026-09-03', ['lead_guest_id' => listGuest('Zoe', 'Zero')->id]);

    // recompute() writes balance_minor from the append-only ledger — the page reads that column, so the row,
    // the tile and the folio agree by construction.
    expect((int) $owing->fresh()->balance_minor)->toBe(26000)
        ->and((int) $free->fresh()->balance_minor)->toBe(0);

    $response = $this->actingAs(operatorUser($s))->get('/app/reservations');

    $response->assertOk()
        ->assertSee('€260.00')                                   // the row's balance…
        ->assertSee('Balances due · arrivals this month', false) // …and the tile that sums it
        ->assertSee('1 folio still owing');
});

// ── 8. NO RAW BLADE — a directive after a word character ships as visible source text ───────────
it('renders no unrendered Blade directives', function () {
    $s = seedScenario(1);
    $reservation = listReservation($s, 0, 'confirmed', '2026-09-01', '2026-09-03', ['lead_guest_id' => listGuest('Ada', 'Lovelace')->id]);
    postRoom($reservation, 42500);

    assertNoRawBlade($this->actingAs(operatorUser($s))->get('/app/reservations')->assertOk());

    // The empty states are separate branches — they must compile too.
    assertNoRawBlade($this->actingAs(operatorUser($s))->get('/app/reservations?q=nothingmatchesthis')->assertOk());
});

// ── 9. SEARCH — the real plaintext columns only, and never the encrypted ones ───────────────────
it('searches by reference and guest name, and never renders or queries guest contact details', function () {
    $s = seedScenario(2);

    // A guest WITH real contact details — encrypted casts on TEXT columns.
    $guest = listGuest('Ada', 'Lovelace', ['email' => 'ada@analytical.example', 'phone' => '+353871234567']);
    $found = listReservation($s, 0, 'confirmed', '2026-09-01', '2026-09-03', ['lead_guest_id' => $guest->id]);
    postRoom($found, 42500);

    $other = listReservation($s, 1, 'confirmed', '2026-09-01', '2026-09-03', ['lead_guest_id' => listGuest('Bob', 'Bystander')->id]);

    $staff = operatorUser($s);

    // By REFERENCE…
    $this->actingAs($staff)->get('/app/reservations?q='.$found->reference)
        ->assertOk()->assertSee('Ada Lovelace')->assertDontSee('Bob Bystander')->assertDontSee($other->reference);

    // …and by guest name, including the full "First Last" a human actually types.
    $this->actingAs($staff)->get('/app/reservations?q=Lovelace')
        ->assertOk()->assertSee('Ada Lovelace')->assertDontSee('Bob Bystander');
    $this->actingAs($staff)->get('/app/reservations?q=Ada+Lovelace')
        ->assertOk()->assertSee('Ada Lovelace')->assertDontSee('Bob Bystander');

    // THIS IS NOT THE PII SURFACE: email/phone are never rendered…
    $this->actingAs($staff)->get('/app/reservations')
        ->assertOk()
        ->assertDontSee('ada@analytical.example')
        ->assertDontSee('+353871234567');

    // …and never searched. The ciphertext is not indexed by value, so a LIKE over it can only ever match
    // nothing — a search box that appeared to accept an email would be lying. It matches no row, exactly as
    // an unrelated term does.
    $this->actingAs($staff)->get('/app/reservations?q=ada@analytical.example')
        ->assertOk()->assertDontSee('Ada Lovelace')->assertSee('No reservations match');
});
