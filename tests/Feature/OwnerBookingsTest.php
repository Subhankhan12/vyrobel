<?php

use App\Livewire\Owner\OwnerBookings;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerMonthlyTarget;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Support\Money\MoneyFormatter;
use App\Support\Owner\OwnerContext;
use App\Support\Pdf\OwnerBookingConfirmationDocument;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Book the unit, post the room charge, run the certified attribution → owner_ledger rows. Uniquely named +
 * guarded: a sibling test file declares `attributedStay` UNGUARDED, so reusing that name would fatally
 * redeclare depending on file load order.
 */
if (! function_exists('bkStay')) {
    function bkStay(array $s, Unit $unit, int $roomMinor): Reservation
    {
        $reservation = book($s, ['unit_id' => $unit->id]);
        postRoom($reservation, $roomMinor);
        app(OwnerAttributionService::class)->attributeReservation($reservation);
        collectFolio($reservation);   // ⚠️ the guest PAID — a statement claims only COLLECTED revenue (②+accrual)

        return $reservation;
    }
}

/**
 * The bookings TABLE BODY only.
 *
 * ⚠️ THIS EXISTS BECAUSE A PAGE-WIDE ASSERTION STOPPED MEANING WHAT IT SAID. Since the owner gained a
 * `owner_new_booking` notification, the bell panel — which renders on EVERY owner page — legitimately shows
 * "unit · dates · REFERENCE" for a new booking. So `assertDontSee($reference)` on the whole page could no
 * longer distinguish "the filter correctly excluded this row" from "the bell mentioned it elsewhere", and it
 * failed while the filter was working perfectly (proven: the table rendered its real empty state).
 *
 * Scoping to <tbody> makes the assertion say what it always meant — this booking is not in the FILTERED SET —
 * and it is STRICTER, not weaker: it still anchors on the reference, and it still reds if the row wrongly
 * appears in the table.
 */
function bkTableBody(string $html): string
{
    preg_match_all('/<tbody[^>]*>.*?<\/tbody>/s', $html, $m);

    return implode('', $m[0]);
}

// OWNER BOOKINGS + BOOKING DETAIL (Phase 2 · Sub-gate 1) — owner-scoped reads over the owner's OWN units.
//   THE HEADLINE GUARD: reservations carry no owner_id (owner A + owner B share the operator's ORG), so the
//   owner boundary is applied THROUGH the units — the set comes from the owner-scoped units, and a {booking}
//   id is REJECTED (404) unless it touches a unit this owner owns. Both proven here (and proven to fail if the
//   guard is removed). Plus: "Net to you" reconciles to the owner ledger; guest NAME only (no contact);
//   real source enum (OTA, never a brand); stage via the shared ReservationStage.

/**
 * A linked owner-portal user who owns unit[$index], with a distinct name. Reuses the shared ownUnit() (owner +
 * 15% agreement + unit->owner_id) and links a portal user on top. Guarded so it can't collide across files.
 *
 * @return array{user: User, owner: Owner, unit: Unit}
 */
if (! function_exists('bookingsOwner')) {
    function bookingsOwner(array $s, int $index, string $name): array
    {
        /** @var Unit $unit */
        $unit = $s['units'][$index];
        $owner = ownUnit($unit);

        $user = User::factory()->create(['name' => $name]);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return ['user' => $user, 'owner' => $owner, 'unit' => $unit];
    }
}

// ── 1. LIST ISOLATION — an owner sees only bookings on their OWN units ────────────────────────────
it('lists only the owner’s own-unit bookings, never another owner’s', function () {
    $s = seedScenario(2);
    $s['guest']->update(['first_name' => 'Lars', 'last_name' => 'Andersen']);

    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $b = bookingsOwner($s, 1, 'Bruno Vale');

    $mine = bkStay($s, $a['unit'], 20000);     // A's booking
    $theirs = bkStay($s, $b['unit'], 50000);   // B's booking — must never appear for A

    $this->actingAs($a['user'])->get('/owner/bookings?tab=all')
        ->assertOk()
        ->assertSee('Lars Andersen')            // guest NAME (name-only)
        ->assertSee($mine->reference)           // A's booking is listed…
        ->assertDontSee($theirs->reference);    // …B's booking is NOT (through-units scoping)
});

// ── 2. THE CRITICAL GUARD — a cross-owner {booking} id 404s (proven both ways + fail-closed) ──────
it('404s another owner’s booking detail and fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $b = bookingsOwner($s, 1, 'Bruno Vale');

    $mine = bkStay($s, $a['unit'], 20000);
    $theirs = bkStay($s, $b['unit'], 50000);

    // A's own booking resolves…
    $this->actingAs($a['user'])->get('/owner/bookings/'.$mine->id)->assertOk();

    // …owner B's booking does NOT: it touches no unit A owns → 404, not a leak (the #26 shape).
    $this->actingAs($a['user'])->get('/owner/bookings/'.$theirs->id)->assertNotFound();

    // A non-linked authenticated member → empty owner set → the owned-unit check is empty → 404 (fail closed).
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);
    // ⚠️ R-GATE (founder, 2026-09-19): a NON-LINKED user is now refused at the realm door by
    //    EnsureOwnerPortalAccess, BEFORE any owner resolution runs — so this is a 403 about the AREA, not a
    //    404 about the resource. ⚠️ Cross-OWNER 404s above are untouched: an owner passes the gate and the
    //    scope still withholds another owner's rows.
    $this->actingAs($stranger)->get('/owner/bookings/'.$mine->id)->assertForbidden();
});

// ── 3. NET RECONCILES to the owner ledger, to the cent (list + detail) ────────────────────────────
it('shows a “Net to you” that reconciles to the owner ledger', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');

    bkStay($s, $a['unit'], 20000);   // €200 room − 15% commission = €170 net

    // The exact figure the screen is claiming — Σ signedMinor over THIS booking's owner-scoped ledger rows.
    $ledgerNet = OwnerLedgerEntry::where('owner_id', $a['owner']->id)->get()
        ->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);
    expect($ledgerNet)->toBe(17000);   // €170.00

    $reservation = Reservation::query()->firstOrFail();

    // The list shows the net…
    $this->actingAs($a['user'])->get('/owner/bookings?tab=all')
        ->assertOk()->assertSee('€170.00');

    // …and the detail's gross→net breakdown reconciles (Credited + Deductions = Net), to the cent.
    $this->actingAs($a['user'])->get('/owner/bookings/'.$reservation->id)
        ->assertOk()
        ->assertSee('+€200.00')   // room_revenue credit
        ->assertSee('−€30.00')    // commission deduction (U+2212)
        ->assertSee('€170.00');   // net
});

// ── 4. GUEST NAME ONLY — never contact (invariant #28) ────────────────────────────────────────────
it('renders the guest name but never their contact details', function () {
    $s = seedScenario(1);
    $s['guest']->update([
        'first_name' => 'Lars', 'last_name' => 'Andersen',
        'email' => 'l.andersen@icloud.test', 'phone' => '+4520551804',
    ]);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $reservation = bkStay($s, $a['unit'], 20000);

    foreach (['/owner/bookings?tab=all', '/owner/bookings/'.$reservation->id] as $url) {
        $this->actingAs($a['user'])->get($url)
            ->assertOk()
            ->assertSee('Lars Andersen')             // NAME — allowed
            ->assertDontSee('l.andersen@icloud.test')// email — never
            ->assertDontSee('+4520551804');          // phone — never
    }
});

// ── 5. REAL SOURCE ENUM — an OTA stay is "OTA", never a brand ─────────────────────────────────────
it('renders the real source enum and never an OTA brand', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $reservation = bkStay($s, $a['unit'], 20000);
    $reservation->update(['source' => 'ota']);   // a channel booking — the brand is NOT modelled

    $response = $this->actingAs($a['user'])->get('/owner/bookings?tab=all')->assertOk();
    $response->assertSee('OTA')
        ->assertDontSee('Booking.com')
        ->assertDontSee('Airbnb');

    assertNoRawBlade($response);
});

// ── 6. STAGE via the shared ReservationStage — an unattributed FUTURE booking still lists honestly ─
it('stages a booking via ReservationStage and shows “Not yet attributed” before attribution', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');

    // A confirmed FUTURE booking with NO attribution run yet — it belongs on the list (Upcoming) and its net
    // is honestly "Not yet attributed", never a fabricated €0.
    $reservation = book($s, ['unit_id' => $a['unit']->id]);   // 2026-09-01..03, confirmed, no ledger rows

    $this->actingAs($a['user'])->get('/owner/bookings')   // default tab = upcoming
        ->assertOk()
        ->assertSee($reservation->reference)
        ->assertSee('Upcoming')            // the active temporal tab (stage split)
        ->assertSee('Confirmed')           // the STATUS column — the real reservations.status
        ->assertSee('Not yet attributed'); // no ledger rows → honest, not €0.00
});

// ── 7. MONTHLY TARGET — a REAL owner-set goal (never fabricated), owner-scoped + idempotent upsert ──
it('lets a co-owner set a real monthly target that persists and is an idempotent upsert', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    bkStay($s, $a['unit'], 20000);

    // No target yet → the ring is an HONEST "no target" prompt, NEVER a fabricated % (#39).
    $this->actingAs($a['user'])->get('/owner/bookings')
        ->assertOk()
        ->assertSee('Set a monthly target')
        ->assertSee('no target')
        ->assertDontSee('of goal');

    // The co-owner (primary owner) sets it via the CONTROLLER route (#20 — not a Livewire action).
    $this->actingAs($a['user'])->post('/owner/bookings/target', ['currency' => 'EUR', 'target' => '6000'])
        ->assertRedirect();
    expect(OwnerMonthlyTarget::where('owner_id', $a['owner']->id)->where('currency', 'EUR')->value('target_minor'))->toBe(600000);

    // A second submit UPDATES the same row — an idempotent upsert, not a second row (mutation-proven).
    $this->actingAs($a['user'])->post('/owner/bookings/target', ['currency' => 'EUR', 'target' => '7500']);
    expect(OwnerMonthlyTarget::where('owner_id', $a['owner']->id)->count())->toBe(1);
    expect(OwnerMonthlyTarget::where('owner_id', $a['owner']->id)->value('target_minor'))->toBe(750000);

    // The ring now shows the real target + the chart shows a real goal line.
    $this->actingAs($a['user'])->get('/owner/bookings')
        ->assertOk()
        ->assertSee('of goal')
        ->assertSee('€7.5k');
});

// ── 8. TARGET is CAPABILITY-gated (co-owner only) + a bad amount is rejected ───────────────────────
it('refuses the target write to a view-only sub-user and rejects a non-positive amount', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $view = ownerSubUser($s, $a['owner']->id, 'view_only');

    // A view-only sub-user CAN see the page (calendar capability)…
    $this->actingAs($view)->get('/owner/bookings')->assertOk();
    // …but CANNOT set the shared target (actions capability — co-owner only) → 403, nothing written.
    $this->actingAs($view)->post('/owner/bookings/target', ['currency' => 'EUR', 'target' => '6000'])
        ->assertForbidden();
    expect(OwnerMonthlyTarget::count())->toBe(0);

    // The co-owner's own bad input (zero) is rejected with an error, not stored.
    $this->actingAs($a['user'])->post('/owner/bookings/target', ['currency' => 'EUR', 'target' => '0'])
        ->assertSessionHasErrors('target');
    expect(OwnerMonthlyTarget::count())->toBe(0);
});

// ── 9. THE RING IS REAL — % = current-month net ÷ the owner's target (no fabrication, #39) ─────────
it('drives the goal ring from the REAL current-month net and target', function () {
    // Book while "today" is BEFORE the stay (valid), then move "today" into the stay's month so it is the
    // current month for the ring. The net (€170 = €200 − 15%) is the ONLY current-month attributed revenue.
    $this->travelTo(CarbonImmutable::parse('2026-08-20 09:00'));
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    bkStay($s, $a['unit'], 20000);                       // check-in 2026-09-01 → month 2026-09

    $this->travelTo(CarbonImmutable::parse('2026-09-10 09:00'));   // now the current month IS 2026-09

    // With a €1,000 target the ring is EXACTLY round(170 / 1000 · 100) = 17% — a real division, not a mock.
    $this->actingAs($a['user'])->post('/owner/bookings/target', ['currency' => 'EUR', 'target' => '1000']);
    $this->actingAs($a['user'])->get('/owner/bookings')
        ->assertOk()
        ->assertSee('17%')
        ->assertSee('of goal');

    $this->travelBack();
});

// ── 10. CSV EXPORT — the SAME owner-scoped rows, name-only, no contact, cross-owner isolation ──────
it('exports the owner’s bookings as CSV — name only, never contact, never another owner’s', function () {
    $s = seedScenario(2);
    $s['guest']->update([
        'first_name' => 'Lars', 'last_name' => 'Andersen',
        'email' => 'l.andersen@icloud.test', 'phone' => '+4520551804',
    ]);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $b = bookingsOwner($s, 1, 'Bruno Vale');
    $mine = bkStay($s, $a['unit'], 20000);
    $theirs = bkStay($s, $b['unit'], 50000);

    $response = $this->actingAs($a['user'])->get('/owner/bookings/export')->assertOk();
    $csv = $response->streamedContent();

    expect($csv)->toContain('Lars Andersen')          // guest NAME — allowed
        ->toContain($mine->reference)                 // A's booking IS in the file…
        ->not->toContain($theirs->reference)          // …B's booking is NOT (through-units isolation)
        ->not->toContain('l.andersen@icloud.test')    // email — NEVER
        ->not->toContain('+4520551804');              // phone — NEVER
});

// ── 11. EXPORT is EXPORTS-gated — accountant may download but not view; view-only the reverse ──────
it('gates the export on the exports capability, independent of page access', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    bkStay($s, $a['unit'], 20000);

    // Accountant: NO calendar capability (can't see the page) but HAS exports (can download).
    $accountant = ownerSubUser($s, $a['owner']->id, 'accountant');
    $this->actingAs($accountant)->get('/owner/bookings')->assertForbidden();
    $this->actingAs($accountant)->get('/owner/bookings/export')->assertOk();

    // View-only: the reverse — sees the page, but no exports → the download 403s.
    $view = ownerSubUser($s, $a['owner']->id, 'view_only');
    $this->actingAs($view)->get('/owner/bookings')->assertOk();
    $this->actingAs($view)->get('/owner/bookings/export')->assertForbidden();
});

// ── 12. STATUS COLUMN — the REAL reservations.status (Confirmed / Pending), never fabricated ───────
it('shows the real reservation status in the STATUS column', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $r = bkStay($s, $a['unit'], 20000);

    $r->update(['status' => 'confirmed']);
    $this->actingAs($a['user'])->get('/owner/bookings?tab=all')->assertOk()->assertSee('Confirmed');

    $r->update(['status' => 'tentative']);
    $this->actingAs($a['user'])->get('/owner/bookings?tab=all')->assertOk()->assertSee('Pending');
});

// ── 13. FILTERS ride GET — status filter narrows the real set (owner scope middleware re-runs) ─────
it('filters the table by the real status via a GET parameter', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $r = bkStay($s, $a['unit'], 20000);
    $r->update(['status' => 'confirmed']);

    // Its own status shows it…
    $this->actingAs($a['user'])->get('/owner/bookings?tab=all&status=confirmed')
        ->assertOk()->assertSee($r->reference);
    // …a different status hides it (real filter, honest empty).
    $cancelled = $this->actingAs($a['user'])->get('/owner/bookings?tab=all&status=cancelled')
        ->assertOk()->assertSee('No bookings match this view');
    // ⚠️ TABLE-SCOPED (see bkTableBody): the bell panel legitimately names a new booking's reference on every
    // owner page, so only the table can answer "is it in the filtered set?".
    expect(bkTableBody($cancelled->getContent()))->not->toContain($r->reference);
});

// ── 14. "Last month" resolves the PREVIOUS month even on a 31st (Carbon subMonth overflow guard) ───
it('does not collapse “Last month” into “This month” on a 31st', function () {
    // On a 31st a naive $today->subMonth() overflows INTO the current month; "Last month" would then hide
    // genuine last-month bookings. Anchor "today" to 2026-07-31 and place a real June (last-month) booking.
    $this->travelTo(CarbonImmutable::parse('2026-07-31 12:00'));
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $r = bkStay($s, $a['unit'], 20000);
    $r->update(['check_in' => '2026-06-10', 'check_out' => '2026-06-12']);   // check-in month = June

    // Last month (June) → the booking shows. With the overflow bug this range would be JULY and hide it.
    $this->actingAs($a['user'])->get('/owner/bookings?tab=all&range=last_month')
        ->assertOk()->assertSee($r->reference);
    // This month (July) → it does NOT (June ≠ July), proving the two ranges are distinct.
    $thisMonth = $this->actingAs($a['user'])->get('/owner/bookings?tab=all&range=this_month')->assertOk();
    // ⚠️ TABLE-SCOPED for the same reason as test 13 — the bell is not the filtered set.
    expect(bkTableBody($thisMonth->getContent()))->not->toContain($r->reference);

    $this->travelBack();
});

// ── 15. A tab-DEFAULT sort direction never leaks onto the other tab's link (per-tab order holds) ───
it('keeps tab links clean of a defaulted sort direction', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    bkStay($s, $a['unit'], 20000);

    // Fresh (upcoming) view: the user chose NO explicit sort, so the "Past" tab link must be a clean
    // ?tab=past — the upcoming tab's DEFAULT 'asc' must not be dragged along (past re-defaults to desc).
    $this->actingAs($a['user'])->get('/owner/bookings')
        ->assertOk()
        ->assertSee('?tab=past"', false)         // a clean Past tab link…
        ->assertDontSee('tab=past&amp;', false); // …with nothing (no sort/dir) dragged into it
});

// ── 16. COMMISSION RATE — the REAL agreement rate on the detail page, NEVER the wireframe's hardcoded 15% ──
it('shows the real agreement commission rate on the booking detail, never a hardcoded 15%', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    // The REAL agreement is 18% (the demo rate) — deliberately NOT the wireframe's 15%. Set BEFORE attribution
    // so the ledger commission AND the rate label agree.
    ManagementAgreement::where('owner_id', $a['owner']->id)->update(['commission_pct' => 18.00]);
    $r = bkStay($s, $a['unit'], 50000);   // €500 room → 18% = €90 commission → net €410

    // The LEDGER commission is the real 18%.
    $commission = OwnerLedgerEntry::where('reservation_id', $r->id)->where('entry_type', 'commission')->get()
        ->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + abs($e->signedMinor()), 0);
    expect($commission)->toBe(9000);

    $this->actingAs($a['user'])->get('/owner/bookings/'.$r->id)
        ->assertOk()
        ->assertSee('18%')       // the REAL rate label (OwnerCommissionRate)…
        ->assertDontSee('15%');  // …NEVER the wireframe's hardcode
});

// ── 17. CONFIRMATION PDF — owner-scoped + exports-gated (the wireframe's "Download confirmation") ──
it('serves the per-booking confirmation PDF owner-scoped and exports-gated', function () {
    $s = seedScenario(2);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $b = bookingsOwner($s, 1, 'Bruno Vale');
    $mine = bkStay($s, $a['unit'], 50000);
    $theirs = bkStay($s, $b['unit'], 50000);

    $res = $this->actingAs($a['user'])->get('/owner/bookings/'.$mine->id.'/confirmation')->assertOk();
    expect($res->headers->get('content-type'))->toContain('pdf');

    // Owner boundary: B's booking 404s for A (the SAME through-units guard as the page).
    $this->actingAs($a['user'])->get('/owner/bookings/'.$theirs->id.'/confirmation')->assertNotFound();

    // EXPORTS gate: an accountant may download; a view-only sub-user is refused.
    $accountant = ownerSubUser($s, $a['owner']->id, 'accountant');
    $this->actingAs($accountant)->get('/owner/bookings/'.$mine->id.'/confirmation')->assertOk();
    $view = ownerSubUser($s, $a['owner']->id, 'view_only');
    $this->actingAs($view)->get('/owner/bookings/'.$mine->id.'/confirmation')->assertForbidden();
});

// ── 18. CONFIRMATION PDF document — GUEST NAME ONLY, the real rate, reconciles (#28 / #17) ─────────
it('builds the confirmation PDF with the guest NAME only — never contact — at the real rate, reconciling', function () {
    $s = seedScenario(1);
    $s['guest']->update([
        'first_name' => 'Lars', 'last_name' => 'Andersen',
        'email' => 'l.andersen@icloud.test', 'phone' => '+4520551804',
    ]);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    ManagementAgreement::where('owner_id', $a['owner']->id)->update(['commission_pct' => 18.00]);
    $r = bkStay($s, $a['unit'], 50000)->load('leadGuest');

    // The document runs inside the owner context (as it does behind the gated route).
    app(OwnerContext::class)->activate([(string) $a['owner']->id], 'co_owner', true);
    $data = OwnerBookingConfirmationDocument::data($r, [['id' => (string) $a['unit']->id, 'label' => (string) ($a['unit']->name ?? $a['unit']->code)]]);

    // #28 — the guest NAME is present; the contact NEVER appears anywhere in the document data.
    expect($data['guest'])->toBe('Lars Andersen')
        ->and($data['commission_rate'])->toBe('18');
    $encoded = (string) json_encode($data);
    expect($encoded)->not->toContain('l.andersen@icloud.test')
        ->and($encoded)->not->toContain('+4520551804');

    // #17 — gross − commission == net (no cleaning in the ledger): 50000 − 9000 = 41000.
    expect($data['earnings'][0]['net_display'])->toBe(MoneyFormatter::display(41000, 'EUR'));
});

// ── The chart's x-axis is a CALENDAR, not a list of months that happen to have bookings ───────────
// Grouping only the months that HAVE bookings left gaps in the axis: with November empty, the net-revenue
// LINE stepped from October straight to December as though they were consecutive, compressing a two-month
// gap into one and misreporting the trend. An empty month must occupy its slot as a genuine zero — and draw
// NOTHING (the min-bar floor is the recurring bug this pass keeps finding, here doubled as `max(2, pct)`
// AND a CSS `min-height`).
it('keeps the bookings chart on a contiguous month axis, with an empty month drawing nothing', function () {
    $s = seedScenario(2);   // two units, so the second stay avoids the first's availability
    $a = bookingsOwner($s, 0, 'Amara Okafor');

    // Two stays on two of the owner's OWN units (a second unit avoids the availability clash), with the
    // second pushed two months out so the month BETWEEN them is genuinely empty — the case the axis must keep.
    $second = $s['units'][1];
    $second->update(['owner_id' => $a['owner']->id]);

    bkStay($s, $a['unit'], 20000);
    $late = bkStay($s, $second, 30000);
    $gapStart = Carbon::parse($late->check_in)->addMonths(2)->startOfMonth();
    $late->forceFill([
        'check_in' => $gapStart->toDateString(),
        'check_out' => $gapStart->copy()->addDays(2)->toDateString(),
    ])->save();

    $months = Livewire::actingAs($a['user'])->test(OwnerBookings::class)->viewData('chart')['months'];
    $keys = array_column($months, 'month');
    expect(count($keys))->toBeGreaterThan(2);   // the gap month really is in range

    // Every step between the first and last month is exactly one month — no month is skipped.
    $cursor = Carbon::parse($keys[0].'-01');
    foreach ($keys as $key) {
        expect($key)->toBe($cursor->format('Y-m'));
        $cursor->addMonth();
    }

    // …and the gap month is present with a genuine zero rather than being absent.
    $empty = array_values(array_filter($months, fn (array $m): bool => $m['total_count'] === 0));
    expect($empty)->not->toBeEmpty()
        ->and($empty[0]['net_minor'])->toBe(0);
});
