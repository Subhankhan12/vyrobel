<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerMonthlyTarget;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerAttributionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

        return $reservation;
    }
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
    $this->actingAs($stranger)->get('/owner/bookings/'.$mine->id)->assertNotFound();
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
    $this->actingAs($a['user'])->get('/owner/bookings?tab=all&status=cancelled')
        ->assertOk()->assertDontSee($r->reference)->assertSee('No bookings match this view');
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
    $this->actingAs($a['user'])->get('/owner/bookings?tab=all&range=this_month')
        ->assertOk()->assertDontSee($r->reference);

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
