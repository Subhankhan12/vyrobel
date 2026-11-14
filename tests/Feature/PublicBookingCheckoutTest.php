<?php

use App\Models\Reservation;
use App\Services\BookingEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Booking Screen 3 (public checkout — the FIRST public screen that WRITES inventory).
// The critical guarantees: the booking routes THROUGH AssignmentService (source=direct, rejects when
// full — never overbooks); the advisory hold reserves NO inventory (sold unchanged at hold, and after
// an expired hold); TWO holds on the last unit both succeed but only ONE book() converts (the other is
// cleanly rejected, sold never exceeds capacity); payment is real + append-only via FolioService;
// public isolation (checkout on X cannot book Y's room); and the styled write is CSRF-protected.

/** The checkout POST body for a scenario's room over 2 nights. */
function checkoutBody(array $s, array $overrides = []): array
{
    return array_merge([
        'room_type_id' => $s['roomType']->id,
        'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Zephyrina',
        'last_name' => 'Quibblewick',
        'email' => 'guest@example.test',
    ], acceptedTerms($s['property']), $overrides);
}

// ── 1. Full flow → a REAL reservation via AssignmentService (source=direct), inventory at BOOK ────
it('books a real reservation through AssignmentService and leaves the balance due on pay-on-arrival (F-GUEST-1)', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);   // no card rail → pay-on-arrival (settle at the property)

    // No auth — a public visitor. CSRF is auto-skipped only under the test runner (real browser must send it).
    $resp = test()->post("/book/{$s['property']->slug}/checkout", checkoutBody($s))->assertRedirect();

    // A REAL direct reservation exists, confirmed, and inventory was reserved at book().
    $reservation = Reservation::query()->where('source', 'direct')->first();
    expect($reservation)->not->toBeNull()
        ->and($reservation->status)->toBe('confirmed')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);   // reserved at book, exactly once

    // Real, append-only ledger through FolioService (a room charge). Pay-on-arrival records NO payment — the
    // balance is genuinely DUE and settled at the property (never a fabricated "paid in full", #39/F-GUEST-1).
    $this->assertDatabaseHas('folio_items', ['type' => 'room']);
    $this->assertDatabaseMissing('payments', ['reservation_id' => $reservation->id, 'type' => 'payment']);

    // The styled confirmation shows the REAL reference and the honest pay-on-arrival state.
    test()->get($resp->headers->get('Location'))
        ->assertOk()
        ->assertSee('Booking confirmed')
        ->assertSee($reservation->reference)
        ->assertSee('Pay on arrival');
});

// ── 2. The advisory hold reserves NO inventory (sold unchanged; an expired hold too) ─────────────
it('creates an advisory hold that never changes sold', function () {
    $s = publicPropertyScenario(units: 1);
    $engine = app(BookingEngineService::class);

    $quote = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'a@example.test');
    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);   // hold reserves nothing

    // Expire it — still no inventory ever touched.
    $quote->update(['expires_at' => now()->subMinute()]);
    $engine->expireStaleQuotes();
    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);
});

// ── 3. CRITICAL — two holds on the LAST unit both succeed, only ONE book() converts, no overbook ─
it('lets two holds on the last unit both succeed but only one book converts', function () {
    $s = publicPropertyScenario(units: 1);   // exactly ONE sellable unit
    config(['services.payments.gateway' => 'manual']);
    $engine = app(BookingEngineService::class);

    // Two advisory holds on the same last unit — BOTH succeed (they reserve nothing).
    $q1 = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'one@example.test');
    $q2 = $engine->quote($s['property'], $s['roomType']->id, $s['ratePlan']->id, '2026-09-01', '2026-09-03', 2, 0, 'two@example.test');
    expect(soldOn($s['roomType']->id, '2026-09-01'))->toBe(0);

    // Only ONE converts; the second is cleanly rejected as unavailable — never overbooked.
    $r1 = $engine->book($q1, 'offline', ['email' => 'one@example.test', 'first_name' => 'One', 'last_name' => 'A']);
    $r2 = $engine->book($q2, 'offline', ['email' => 'two@example.test', 'first_name' => 'Two', 'last_name' => 'B']);

    expect($r1['status'])->toBe('confirmed')
        ->and($r2['status'])->toBe('unavailable')
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);   // NEVER 2 — the guarantee holds
});

// ── 3b. Same guarantee THROUGH the styled screen — two checkout POSTs, one books ─────────────────
it('rejects the second checkout on the last unit without overbooking', function () {
    $s = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);
    $slug = $s['property']->slug;

    test()->post("/book/{$slug}/checkout", checkoutBody($s, ['email' => 'first@example.test']))->assertRedirect();
    $second = test()->post("/book/{$slug}/checkout", checkoutBody($s, ['email' => 'second@example.test']))->assertRedirect();

    // The second is bounced back to checkout with an honest error — not a fake confirmation.
    expect($second->headers->get('Location'))->toContain('/checkout');
    test()->get($second->headers->get('Location'))->assertOk()->assertSee('just booked by someone else');

    expect(Reservation::query()->where('source', 'direct')->count())->toBe(1)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe(1);   // never overbooked
});

// ── 4. Public isolation — checkout on property X cannot book property Y's room ────────────────────
it('cannot book another property\'s room through checkout', function () {
    $x = publicPropertyScenario(units: 1);
    $y = publicPropertyScenario(units: 1);
    config(['services.payments.gateway' => 'manual']);

    // POST to X's checkout with Y's room id → property-scoped resolve 404s; nothing is booked.
    test()->post("/book/{$x['property']->slug}/checkout", checkoutBody($y))->assertNotFound();

    expect(Reservation::query()->where('source', 'direct')->count())->toBe(0)
        ->and(soldOn($y['roomType']->id, '2026-09-01'))->toBe(0);
});

// ── 5. The styled write is CSRF-protected (deliberate — not just disabled) ────────────────────────
it('renders a CSRF token on the checkout form (the styled write is protected)', function () {
    $s = publicPropertyScenario(units: 1);

    test()->get("/book/{$s['property']->slug}/checkout?room_type_id={$s['roomType']->id}&check_in=2026-09-01&check_out=2026-09-03&adults=2")
        ->assertOk()
        ->assertSee('name="_token"', false);   // @csrf hidden field — the POST is session-CSRF-validated
});
