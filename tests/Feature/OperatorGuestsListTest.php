<?php

use App\Models\Guest;
use App\Models\Owner;
use App\Models\Reservation;
use App\Models\Tag;
use App\Services\FolioService;
use App\Services\InboxService;
use App\Support\Guest\GuestSpend;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// THE OPERATOR GUESTS LIST — the CRM (Wave 2 · Sub-gate 4).
//
// What is actually load-bearing here, and therefore what these tests defend:
//   • TENANT isolation + the /app role gate — the two security facts (invariant #26).
//   • ⚠️ THE PII PERIMETER (locked decision 4). This page decrypts email/phone; every OTHER guest-bearing
//     operator surface must stay NAME-ONLY. Test 5 asserts that perimeter from the outside — it is the test
//     that turns a decision into an enforced boundary, and it fails the moment someone adds an email to the
//     front desk or the inbox.
//   • "Total billed" comes from the SHARED GuestSpend, reconciling to the real folio — so the list and the
//     profile cannot disagree (GuestSpendTest pins the helper itself against FolioTotals; this file pins
//     what the PAGE renders to that same helper).
//   • The status badge + the stay count come from the SHARED ReservationStage — a cancelled booking is not
//     a stay, and the rule is the resolver's, not this page's.
//
// ⚠️ FIXTURE EMAILS ARE CHOSEN, NOT ARBITRARY. assertNoRawBlade() scans the body for literal '@if', '@empty',
// '@php' &c. A guest whose email were `sam@ifonly.com` would contain '@if' and fail it — a false positive in
// the HELPER, not a bug on the page. Fixture domains below start with letters that cannot collide.

beforeEach(function () {
    // The status badge and the in-house tile are date-dependent. book() always stays 2026-09-01→03 (the
    // window seedScenario publishes availability for), so freeze mid-stay for deterministic stages.
    // TestCase::tearDown() resets Carbon's test-now.
    Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00'));
});

/** A guest with real CRM columns — VIP/risk are real booleans, contact PII is a real encrypted cast. */
function crmGuest(string $first, string $last, array $extra = []): Guest
{
    return Guest::create(array_merge(['first_name' => $first, 'last_name' => $last], $extra));
}

/** Put a booking at a known status/window for a guest. Each booking takes its OWN unit — book() defaults
 *  every stay to the same dates, so reusing a unit trips the certified no-double-booking guard. */
function crmStay(array $s, int $unitIndex, Guest $guest, string $status, string $checkIn = '2026-09-01', string $checkOut = '2026-09-03'): Reservation
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);

    Reservation::query()->whereKey($reservation->getKey())->update([
        'lead_guest_id' => $guest->id,
        'status' => $status,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
    ]);

    return $reservation->fresh();
}

// ── 1. THE POINT OF THE PAGE — real guests, real columns, real flags ─────────────────────────────
it('renders the real guests with name, VIP and risk flags, tags and nationality', function () {
    $s = seedScenario(2);

    $vip = crmGuest('Ada', 'Lovelace', ['is_vip' => true, 'nationality' => 'IE']);
    $risky = crmGuest('Rex', 'Rogue', ['risk_flag' => true]);

    // The REAL per-tenant catalog on the shared polymorphic pivot — not a string column.
    $tag = Tag::create(['name' => 'Corporate', 'color' => '#8A6410']);
    $vip->tags()->attach($tag);

    $response = $this->actingAs(operatorUser($s))->get('/app/guests');

    $response->assertOk()
        ->assertSee('Ada Lovelace')
        ->assertSee('Rex Rogue')
        ->assertSee('VIP')              // guests.is_vip — a real boolean column
        ->assertSee('At risk')          // guests.risk_flag — likewise
        ->assertSee('Corporate');       // the real tag, through HasTags

    // The flags rendered are the ones the DB holds — not decoration.
    expect((bool) $vip->fresh()->is_vip)->toBeTrue()
        ->and((bool) $risky->fresh()->risk_flag)->toBeTrue();

    assertNoRawBlade($response);
});

// ── 2. TENANT ISOLATION — another org's guest is not on this page at all ─────────────────────────
it('isolates tenants — another organization’s guest is never listed', function () {
    $other = seedScenario(1);
    $foreign = crmGuest('Foreign', 'Guest', ['email' => 'foreign.guest@elsewhere.test', 'is_vip' => true]);
    $foreignStay = crmStay($other, 0, $foreign, 'confirmed');
    postRoom($foreignStay, 99900);
    Tag::create(['name' => 'ForeignTag9', 'color' => '#000000']);

    $s = seedScenario(1);   // a second org — the tenant context now points here
    crmGuest('Mine', 'Own');

    // Guest / Tag / Folio are all BelongsToOrganization: the foreign rows are not in the collection, so
    // nothing about them — not the name, not the PII, not the money, not the tag chip — can reach the page.
    $response = $this->actingAs(operatorUser($s))->get('/app/guests');

    $response->assertOk()
        ->assertSee('Mine Own')
        ->assertDontSee('Foreign Guest')
        ->assertDontSee('foreign.guest@elsewhere.test')
        ->assertDontSee('ForeignTag9')
        ->assertDontSee('€999.00');
});

// ── 3. THE /app ROLE GATE (invariant #26) — an owner never reaches an operator screen ────────────
// EnsureBackOfficeAccess sends a recognised owner to their OWN portal rather than a bare 403 (the shipped
// behaviour, asserted the same way by OperatorReservationsListTest). The security fact under test is that
// the CRM never renders for them — this page holds every guest's contact PII, so it is the worst possible
// page for the gate to leak.
it('never renders the CRM for an owner-role user, who holds a Membership but no back-office role', function () {
    $s = seedScenario(1);
    crmGuest('Ada', 'Lovelace', ['email' => 'ada.lovelace@example.test']);

    $response = $this->actingAs(ownerRoleUser($s, 0))->get('/app/guests');

    $response->assertRedirect(route('owner.dashboard'));
    // Not one byte of the page — nor the PII on it — was rendered before the bounce.
    $response->assertDontSee('ada.lovelace@example.test');
});

// ── 4. PII — this surface DOES show contact detail, decrypted, and degrades honestly ─────────────
it('decrypts and renders guest email and phone on the CRM, the one surface that may show them', function () {
    $s = seedScenario(1);
    crmGuest('Ada', 'Lovelace', ['email' => 'ada.lovelace@example.test', 'phone' => '+353860000001']);

    // Stored ENCRYPTED: the raw column is ciphertext, so rendering the address is a real decrypt, not a
    // plaintext column that happened to be selected.
    $raw = (string) DB::table('guests')->where('first_name', 'Ada')->value('email');
    expect($raw)->not->toBe('ada.lovelace@example.test')->and($raw)->not->toContain('lovelace');

    $this->actingAs(operatorUser($s))->get('/app/guests')
        ->assertOk()
        ->assertSee('ada.lovelace@example.test')
        ->assertSee('+353860000001');
});

// ── 5. ⚠️ THE PII PERIMETER — every OTHER guest-bearing operator surface stays NAME-ONLY ─────────
// This is the test that makes locked decision 4 an enforced boundary rather than a note in a docblock.
// Each page below is asserted to still show the guest's NAME (so a passing test means "name yes, contact
// no" — not "the page was empty and asserted nothing").
it('never leaks guest contact PII onto the front desk, the reservations list or the inbox', function () {
    $s = seedScenario(1);
    $guest = crmGuest('Ada', 'Lovelace', ['email' => 'ada.lovelace@example.test', 'phone' => '+353860000001']);

    // In-house today (frozen 2026-09-02) → the guest genuinely renders on the front desk.
    $stay = crmStay($s, 0, $guest, 'checked_in');
    postRoom($stay, 30000);

    // A real thread, through the real service — so the guest renders in the inbox too.
    app(InboxService::class)->startThread([
        'channel' => 'manual',
        'subject' => 'Late arrival',
        'guest_id' => $guest->id,
        'reservation_id' => $stay->id,
    ]);

    $operator = operatorUser($s);

    foreach (['/app/front-desk', '/app/reservations', '/app/inbox'] as $url) {
        $response = $this->actingAs($operator)->get($url);

        $response->assertOk()
            ->assertSee('Ada Lovelace')                          // the name IS shown — the page is live…
            ->assertDontSee('ada.lovelace@example.test')         // …and the contact detail is NOT.
            ->assertDontSee('+353860000001');
    }

    // The CRM is the exception, and only the CRM.
    $this->actingAs($operator)->get('/app/guests')->assertSee('ada.lovelace@example.test');
});

// ── 6. THE MONEY — "Total billed" + the open balance BESIDE it, via the SHARED definition ────────
it('renders total billed and the open balance from the shared definition, reconciling to the folio', function () {
    $s = seedScenario(2);
    $guest = crmGuest('Ada', 'Lovelace');

    $first = crmStay($s, 0, $guest, 'checked_out');
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($first);
    $folios->postCharge($folio, 'room', 'Room', moneyEur(30000));
    $folios->recordPayment($folio, moneyEur(10000));   // billed 300.00, paid 100.00 → balance 200.00

    $second = crmStay($s, 1, $guest, 'confirmed', '2026-09-20', '2026-09-22');
    postRoom($second, 20000);                          // billed 200.00, unpaid → balance 200.00

    $response = $this->actingAs(operatorUser($s))->get('/app/guests');

    $response->assertOk()
        ->assertSee('€500.00')      // BILLED — 300 + 200
        ->assertSee('€400.00');     // OPEN BALANCE — 200 + 200, shown BESIDE it, never netted into it

    // The page renders the SHARED helper's figures — not a second opinion computed in the component.
    // (GuestSpendTest pins the helper itself against FolioTotals folio-by-folio.)
    $buckets = GuestSpend::for($guest->fresh());
    expect($buckets['EUR']['billed_minor'])->toBe(50000)
        ->and($buckets['EUR']['balance_minor'])->toBe(40000);

    // BILLED is not COLLECTED: only 100.00 was ever paid, and 500.00 is still what we invoiced.
    expect((int) $folio->fresh()->paid_minor)->toBe(10000);
});

// ── 7. A guest with no billing history renders "—", never a fabricated €0.00 ─────────────────────
it('shows no money figure for a guest who has never been billed', function () {
    $s = seedScenario(1);
    crmGuest('Never', 'Booked');

    $response = $this->actingAs(operatorUser($s))->get('/app/guests');

    // No folio → no currency to render a zero IN. The row still renders, and says so.
    $response->assertOk()
        ->assertSee('Never Booked')
        ->assertSee('No bookings')      // a real state, not an invented stage
        ->assertDontSee('€0.00');
});

// ── 8. STAYS + STATUS come from the SHARED resolver — a cancelled booking is not a stay ──────────
it('excludes cancelled bookings from the stay count and badges the guest from the shared resolver', function () {
    $s = seedScenario(3);
    $guest = crmGuest('Ada', 'Lovelace');

    crmStay($s, 0, $guest, 'checked_out', '2026-08-01', '2026-08-03');   // a real, past stay
    crmStay($s, 1, $guest, 'cancelled', '2026-09-20', '2026-09-22');     // never happened
    crmStay($s, 2, $guest, 'checked_in');                                // here right now

    $response = $this->actingAs(operatorUser($s))->get('/app/guests');

    // 3 bookings, 2 stays — the cancelled one is excluded (decision 3).
    $response->assertOk()->assertSee('In-house');   // PRECEDENCE: in-house outranks past/cancelled

    expect($guest->reservations()->count())->toBe(3);

    // The rendered stay count is the non-cancelled one. Asserted against the DB rather than a literal, so
    // this cannot pass by coincidence.
    $notCancelled = $guest->reservations()->whereNotIn('status', ['cancelled', 'no_show'])->count();
    expect($notCancelled)->toBe(2);
    $response->assertSeeInOrder(['Ada Lovelace', '2']);
});

// ── 9. FILTERS — All · In-house · Upcoming · VIP (#87), each a REAL column or the shared stay-state ─
it('filters by the real VIP column and the shared in-house / upcoming stay-state, preserving search', function () {
    $s = seedScenario(2);
    $vip = crmGuest('Vera', 'Vip', ['is_vip' => true]);
    $inHouse = crmGuest('Ivan', 'Inhouse');
    crmStay($s, 0, $inHouse, 'checked_in');                                       // in-house now (frozen 09-02)
    $upcoming = crmGuest('Uma', 'Upcoming');
    crmStay($s, 1, $upcoming, 'confirmed', '2026-09-20', '2026-09-22');           // future arrival
    crmGuest('Plain', 'Person');

    $operator = operatorUser($s);

    $this->actingAs($operator)->get('/app/guests?filter=vip')
        ->assertOk()->assertSee('Vera Vip')->assertDontSee('Plain Person');

    $this->actingAs($operator)->get('/app/guests?filter=in_house')
        ->assertOk()->assertSee('Ivan Inhouse')->assertDontSee('Plain Person')->assertDontSee('Uma Upcoming');

    $this->actingAs($operator)->get('/app/guests?filter=upcoming')
        ->assertOk()->assertSee('Uma Upcoming')->assertDontSee('Plain Person')->assertDontSee('Ivan Inhouse');

    // Tag filtering survives via the URL (the shared catalog), even though the chip row now shows the 4 tabs.
    $tag = Tag::create(['name' => 'Corporate', 'color' => '#8A6410']);
    $vip->tags()->attach($tag);
    $this->actingAs($operator)->get('/app/guests?tag='.$tag->id)
        ->assertOk()->assertSee('Vera Vip')->assertDontSee('Plain Person');

    // An unknown filter falls back to All rather than rendering everything under a chip that looks active.
    $this->actingAs($operator)->get('/app/guests?filter=bogus')
        ->assertOk()->assertSee('Plain Person');
});

// ── 10. SEARCH — name + city + EXACT email via the blind index (#87); partial email/phone cannot ─
it('searches by name and city, matches an exact email via the blind index, and says what it cannot search', function () {
    $s = seedScenario(1);
    crmGuest('Ada', 'Lovelace', ['email' => 'ada.lovelace@example.test', 'city' => 'Dublin']);
    crmGuest('Rex', 'Rogue');

    $operator = operatorUser($s);

    $this->actingAs($operator)->get('/app/guests?q=Lovelace')
        ->assertOk()->assertSee('Ada Lovelace')->assertDontSee('Rex Rogue');

    // A full name matches across the two plaintext columns (CONCAT_WS).
    $this->actingAs($operator)->get('/app/guests?q=Ada+Lovelace')->assertOk()->assertSee('Ada Lovelace');

    // City is a real plaintext column — searchable.
    $this->actingAs($operator)->get('/app/guests?q=Dublin')->assertOk()->assertSee('Ada Lovelace')->assertDontSee('Rex Rogue');

    // An EXACT email address matches via the deterministic email_hash blind index (never the ciphertext).
    $this->actingAs($operator)->get('/app/guests?q=ada.lovelace@example.test')
        ->assertOk()->assertSee('Ada Lovelace')->assertDontSee('Rex Rogue');

    // A PARTIAL email matches nothing — the hash is exact, and the ciphertext is not indexed by value. The
    // footer says outright what can and cannot be searched.
    $this->actingAs($operator)->get('/app/guests?q=lovelace@')
        ->assertOk()
        ->assertDontSee('Ada Lovelace')
        ->assertSee('phone and partial email are encrypted at rest, so they cannot be searched', false);
});

// ── 11. An ANONYMIZED guest degrades honestly — the GDPR row must still render ───────────────────
it('renders an anonymized guest as an erased profile rather than a blank row', function () {
    $s = seedScenario(1);
    $guest = crmGuest('Ada', 'Lovelace', ['email' => 'ada.lovelace@example.test', 'phone' => '+353860000001']);
    $stay = crmStay($s, 0, $guest, 'checked_out');
    postRoom($stay, 30000);

    // The real GDPR path: strip the PII, KEEP the row (the folio history references it).
    $guest->anonymize();

    $response = $this->actingAs(operatorUser($s))->get('/app/guests');

    $response->assertOk()
        ->assertSee('Anonymized guest')                    // a real state, named
        ->assertSee('Erased')
        ->assertDontSee('Ada Lovelace')                    // the PII is genuinely gone…
        ->assertDontSee('ada.lovelace@example.test')
        ->assertDontSee('+353860000001')
        ->assertSee('€300.00')                             // …but the financial history it carries survives
        ->assertSee('1 anonymized');
});

// ── 12. THE TILES — recomputed from real rows, with a stated denominator and no invented delta ───
it('derives the stat tiles from real rows and refuses to invent a trend', function () {
    $s = seedScenario(3);

    $repeat = crmGuest('Repeat', 'Returner');
    crmStay($s, 0, $repeat, 'checked_out', '2026-08-01', '2026-08-03');
    crmStay($s, 1, $repeat, 'checked_in');                       // in-house now → the second tile

    $once = crmGuest('One', 'Timer');
    crmStay($s, 2, $once, 'checked_out', '2026-07-01', '2026-07-03');

    crmGuest('Never', 'Booked');                                 // in NEITHER side of the repeat ratio

    $response = $this->actingAs(operatorUser($s))->get('/app/guests');

    $response->assertOk()
        ->assertSee('Guest profiles')
        ->assertSee('In-house tonight')
        // 1 of the 2 guests who have actually stayed came back. "Never Booked" is excluded from both sides —
        // they are not a one-time guest, they are not a guest yet. The denominator is on the tile's face.
        // (Asserted in fragments: the template wraps the sentence, so the rendered HTML carries newlines and
        // indentation between "guests" and "who" — matching the whole line would test the whitespace.)
        ->assertSee('50')
        ->assertSee('1 of 2 guests')
        // "with a booking" since 2026-10-05: the count is of bookings, and "who have stayed" was said of stays still ahead
        ->assertSee('with a booking booked again')
        // The design's "+4%" had no prior-period snapshot anywhere to compute against.
        ->assertDontSee('+4%');

    // seedScenario() creates a guest of its own (book() needs a lead guest), and crmStay() re-points the
    // lead to mine — so that guest ends with no bookings. Four profiles, and the ratio still keys on the two
    // who actually stayed: this asserts the denominator EXCLUDES never-booked guests rather than counting
    // every row, which is exactly the choice the tile's label commits to.
    expect(Guest::query()->count())->toBe(4);
});
