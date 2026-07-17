<?php

use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Services\RateAndRestrictionService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// OPERATOR NEW RESERVATION (Wave 4 · Sub-gate 2) — a single direct booking through the CERTIFIED
// createBooking path, plus the three guards the audit says the screen needs (it is "SAFE as drawn: ❌"):
//   GUARD 1 — honest availability: an occupied/blocked/unpriced unit is never offered, and a forced booking
//             of one is rejected (createBooking's row lock is the actual no-double-booking guarantee).
//   GUARD 2 — PII: the guest search returns NAME only; email/phone are never rendered.
//   GUARD 3 — NO raw PAN (PCI): no card-number field exists; payment is the manual folio path, gated.

/** seedScenario + a real nightly rate over the seeded window, so room types are genuinely bookable. */
function ratedScenario(int $units = 1, int $nightlyMinor = 10000): array
{
    $s = seedScenario($units);
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of($nightlyMinor, 'EUR'));

    return $s;
}

/** The base fields for a valid booking POST (new guest, no payment). */
function bookingPayload(array $s, array $overrides = []): array
{
    return array_merge([
        'check_in' => '2026-09-02',
        'check_out' => '2026-09-05',        // 3 nights
        'room_type_id' => $s['roomType']->id,
        'unit_id' => $s['units'][0]->id,
        'guest_first_name' => 'Grace',
        'guest_last_name' => 'Hopper',
        'adults' => '2',
        'children' => '0',
        'source' => 'direct',
    ], $overrides);
}

// ── 1. THE WRITE IS REAL — a booking is created through createBooking, with a folio room charge ───
it('creates a real reservation through the certified createBooking path', function () {
    $s = ratedScenario(1, 10000);
    $frontDesk = operatorUser($s, ['name' => 'Fred Front-Desk'], 'Front Desk');

    $response = $this->actingAs($frontDesk)->post('/app/reservations/create', bookingPayload($s));

    // A guest was created via the real path, and the reservation exists — confirmed, direct, its unit seated.
    $reservation = Reservation::query()->latest('created_at')->firstOrFail();
    $response->assertRedirect(route('app.reservations.show', $reservation->id))->assertSessionHas('folio_notice');

    expect((string) $reservation->status)->toBe('confirmed')
        ->and((string) $reservation->source)->toBe('direct')
        // The folio room charge landed → total kept in sync by FolioService::recompute (3 × €100.00).
        ->and((int) $reservation->total_minor)->toBe(30000)
        ->and((int) $reservation->balance_minor)->toBe(30000);   // no payment taken → balance due

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    expect((string) $line->unit_id)->toBe((string) $s['units'][0]->id)
        ->and((int) $line->price_minor)->toBe(30000);

    expect(Guest::query()->where('first_name', 'Grace')->where('last_name', 'Hopper')->exists())->toBeTrue();
    // The seated night really consumed inventory — the no-double-booking machinery ran.
    expect(soldOn($s['roomType']->id, '2026-09-02'))->toBe(1);
});

// ── 2. GUARD 2 — the guest search shows NAME only, never the encrypted email/phone ───────────────
it('searches existing guests by name and never renders their email or phone', function () {
    $s = ratedScenario(1);
    Guest::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada.secret@example.test', 'phone' => '+441234567890']);

    $response = $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get('/app/reservations/create?check_in=2026-09-02&check_out=2026-09-05&q=Lovelace');

    $response->assertOk()
        ->assertSee('Ada Lovelace')                 // the NAME is the pick affordance…
        ->assertDontSee('ada.secret@example.test')  // …the encrypted email is never rendered (GUARD 2)…
        ->assertDontSee('+441234567890');           // …nor the phone

    assertNoRawBlade($response);
});

// ── 3. GUARD 1 — an occupied unit is never offered, and a forced booking of it is rejected ───────
it('never offers an occupied unit and rejects a forced booking of one', function () {
    $s = ratedScenario(2);
    $s['units'][0]->update(['code' => 'TAKENROOM9']);   // distinctive codes — a 2-char code would collide
    $s['units'][1]->update(['code' => 'FREEROOM9']);    // with a CSRF token (the Wave-3 flake); avoid it

    // Occupy unit 0 across 1–3 Sep (overlaps a 2 Sep arrival).
    book($s, ['unit_id' => $s['units'][0]->id]);

    // The screen offers the free unit, and hides the taken one — never shows a unit as available that isn't.
    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get('/app/reservations/create?check_in=2026-09-02&check_out=2026-09-05')
        ->assertOk()
        ->assertSee('FREEROOM9')
        ->assertDontSee('TAKENROOM9');

    // Force a booking of the occupied unit anyway (tampering): server-side GUARD 1 + createBooking's lock
    // both refuse. Nothing new is created — the no-double-booking guarantee is intact.
    $before = Reservation::query()->count();
    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->post('/app/reservations/create', bookingPayload($s, ['unit_id' => $s['units'][0]->id]))
        ->assertRedirect()
        ->assertSessionHas('new_reservation_notice');

    expect(Reservation::query()->count())->toBe($before);   // no second reservation on that unit
});

// ── 4. 🪤 GUARD 3 — NO raw PAN. No card field, no PAN column, card method refused, no card data stored ──
it('never captures or stores a raw card number', function () {
    $s = ratedScenario(1);

    // The schema itself has no PAN column — card data cannot be persisted even by accident.
    expect(Schema::hasColumn('payments', 'card_number'))->toBeFalse()
        ->and(Schema::hasColumn('payments', 'pan'))->toBeFalse();

    // The screen renders no card-number input and no test PAN, and says card capture is coming soon.
    $response = $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get('/app/reservations/create?check_in=2026-09-02&check_out=2026-09-05');
    $response->assertOk()
        ->assertDontSee('card_number')
        ->assertDontSee('4242')
        ->assertSee('coming soon');

    // A "card" method is refused (deferred) — nothing created, no payment.
    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->post('/app/reservations/create', bookingPayload($s, ['payment_method' => 'card', 'payment_amount' => '100']))
        ->assertRedirect()->assertSessionHas('new_reservation_notice');
    expect(Reservation::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);
});

// ── 5. Optional MANUAL payment via the folio — real, and only for a payments.record holder ───────
it('records an optional manual payment through the folio, with no card data', function () {
    $s = ratedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Front Desk'))   // Front Desk holds payments.record
        ->post('/app/reservations/create', bookingPayload($s, ['payment_method' => 'cash', 'payment_amount' => '120.00']))
        ->assertRedirect();

    $reservation = Reservation::query()->latest('created_at')->firstOrFail();
    $payment = Payment::query()->where('reservation_id', $reservation->id)->where('type', 'payment')->firstOrFail();

    expect((int) $payment->amount_minor)->toBe(12000)
        ->and((string) $payment->gateway)->toBe('manual')   // the ManualPaymentGateway — cash/bank, no PAN
        ->and($payment->card_token)->toBeNull()             // no card data whatsoever
        // The balance reconciles: €300 charged − €120 paid = €180 due.
        ->and((int) $reservation->fresh()->balance_minor)->toBe(18000);
});

it('refuses the payment (and the booking) when the actor lacks payments.record', function () {
    $s = ratedScenario(1);

    // Reservation Agent may CREATE a booking but not RECORD a payment. A tampered payment request is refused
    // wholesale — no booking, no payment (the payment UI is hidden from them in the first place).
    $this->actingAs(operatorUser($s, ['name' => 'Rae Agent'], 'Reservation Agent'))
        ->post('/app/reservations/create', bookingPayload($s, ['payment_method' => 'cash', 'payment_amount' => '50']))
        ->assertRedirect()->assertSessionHas('new_reservation_notice');
    expect(Reservation::query()->count())->toBe(0);

    // …but the SAME agent CAN create a booking with no payment (the normal flow).
    $this->actingAs(operatorUser($s, ['name' => 'Rae Agent'], 'Reservation Agent'))
        ->post('/app/reservations/create', bookingPayload($s))   // no payment_method → 'later'
        ->assertRedirect(route('app.reservations.show', Reservation::query()->latest('created_at')->firstOrFail()->id));
    expect(Reservation::query()->count())->toBe(1);
});

// ── 6. PERMISSION (#30) — the create is gated on reservations.create; mutation-proven ────────────
it('gates the create on reservations.create — Housekeeping forbidden, Front Desk allowed with the same payload', function () {
    $s = ratedScenario(1);
    $payload = bookingPayload($s);

    $this->actingAs(operatorUser($s, ['name' => 'Hattie'], 'Housekeeping'))
        ->get('/app/reservations/create')->assertForbidden();
    $this->actingAs(operatorUser($s, ['name' => 'Hattie'], 'Housekeeping'))
        ->post('/app/reservations/create', $payload)->assertForbidden();
    expect(Reservation::query()->count())->toBe(0);   // the 403 wrote nothing

    // The SAME payload from a permitted role succeeds — so the 403 was the gate, not a bad request.
    $this->actingAs(operatorUser($s, ['name' => 'Fred Front-Desk'], 'Front Desk'))
        ->post('/app/reservations/create', $payload)->assertRedirect();
    expect(Reservation::query()->count())->toBe(1);
});

it('shows the New reservation button to a creator and hides it from a non-creator', function () {
    $s = ratedScenario(1);

    $this->actingAs(operatorUser($s, [], 'Front Desk'))->get('/app/reservations')
        ->assertOk()->assertSee(route('app.reservations.create'));

    $this->actingAs(operatorUser($s, ['name' => 'Hattie'], 'Housekeeping'))->get('/app/reservations')
        ->assertOk()->assertDontSee(route('app.reservations.create'));
});

// ── 7. TENANT — a cross-org room type is invisible to a fully-permitted actor (no leak, no booking) ──
it('refuses a cross-org room type for a permitted actor, creating nothing', function () {
    $foreign = ratedScenario(1);

    $s = ratedScenario(1);
    $frontDesk = operatorUser($s, ['name' => 'Fred Front-Desk'], 'Front Desk');   // genuinely holds reservations.create

    // The foreign room type/unit are not in this tenant's scope — the controller sees "not available" (never a
    // masked 403, since the actor is permitted). No booking is made against foreign inventory.
    $this->actingAs($frontDesk)->post('/app/reservations/create', bookingPayload($s, [
        'room_type_id' => $foreign['roomType']->id,
        'unit_id' => $foreign['units'][0]->id,
    ]))->assertRedirect()->assertSessionHas('new_reservation_notice');

    expect(Reservation::query()->withoutGlobalScopes()->count())->toBe(0);
});
