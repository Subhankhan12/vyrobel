<?php

use App\Models\FolioItem;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Design pass · Operator Screen 4 (Reservation Detail → ReservationDetail, /app/reservations/{id}).
// Locks its PRESENTATION guarantees: the folio renders as the real append-only ledger and RECONCILES
// to the cent; NO invented line is shown; TENANT isolation holds (view + actions 404 cross-org); the
// wired money actions (record payment, full refund) persist APPEND-ONLY through FolioService (a refund
// is a NEW offsetting entry, the original untouched); encrypted guest PII is never rendered.

/** A real OPERATOR: an active member of the scenario's org holding a back-office role. */
function detailMember(array $scenario, string $name = 'Marisol Vandenberg'): User
{
    $user = User::factory()->create(['name' => $name]);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    // Group Admin: this screen exercises BOTH `payments.record` and `payments.refund` (now enforced), and
    // still needs `reservations.view` to render — Accountant lacks that, Group Admin holds all three.
    return grantBackOfficeRole($user, $scenario['org'], 'Group Admin');
}

/** Book a reservation and give it a folio: room €300 + city tax €30, optionally a €200 payment. */
function resWithFolio(array $s, bool $withPayment = false): array
{
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);
    $folios->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));
    $folios->postCharge($folio, 'tax', 'City tax', Money::of(3000, 'EUR'));
    $payment = $withPayment ? $folios->recordPayment($folio, Money::of(20000, 'EUR')) : null;

    return ['res' => $res, 'folio' => $folio->refresh(), 'payment' => $payment];
}

// ── 1. The folio renders as the real ledger, reconciles to the cent, no invented lines, no PII ──
it('renders the real folio ledger, reconciled, with no fabricated lines', function () {
    $s = seedScenario(1);
    $s['guest']->update(['first_name' => 'Zephyrina', 'last_name' => 'Quibblewick', 'email' => 'zephyrina-secret@example.test']);
    $f = resWithFolio($s, withPayment: true);

    $this->actingAs(detailMember($s))->get("/app/reservations/{$f['res']->id}")
        ->assertOk()
        ->assertSee($f['res']->reference)
        ->assertSee('Zephyrina Quibblewick')   // REAL guest name (operator surface)
        ->assertSee('Room stay')               // REAL charge line
        ->assertSee('City tax')                // REAL charge line
        ->assertSee('€330.00')                 // charges = 300 + 30
        ->assertSee('€200.00')                 // paid
        ->assertSee('€130.00')                 // balance = 330 − 200, reconciled to the folio cache
        ->assertSee('Reconciled to the folio ledger')
        ->assertDontSee('Card processing')     // no fabricated fee (the owner-statement precedent)
        ->assertDontSee('Service charge')
        ->assertDontSee('Resort fee')
        ->assertDontSee('zephyrina-secret@example.test');   // encrypted PII never rendered
});

// ── 2. Full refund persists APPEND-ONLY — a NEW offsetting entry, the original untouched ─────────
it('refunds a payment append-only through FolioService', function () {
    $s = seedScenario(1);
    $f = resWithFolio($s, withPayment: true);
    $original = $f['payment'];

    $this->actingAs(detailMember($s))
        ->post(route('app.reservations.refund', $f['res']->id), ['payment' => $original->id])
        ->assertRedirect();

    // A NEW refund row exists…
    $this->assertDatabaseHas('payments', ['reservation_id' => $f['res']->id, 'type' => 'refund', 'amount_minor' => 20000, 'status' => 'succeeded']);
    // …and the ORIGINAL payment is completely untouched (never edited/struck).
    $this->assertDatabaseHas('payments', ['id' => $original->id, 'type' => 'payment', 'status' => 'succeeded', 'amount_minor' => 20000]);
    // Folio recomputed: paid back to 0, balance back to the full 330.00.
    $this->assertDatabaseHas('folios', ['id' => $f['folio']->id, 'paid_minor' => 0, 'balance_minor' => 33000]);
});

// ── 3. Record payment persists append-only through FolioService ──────────────────────────────────
it('records a payment append-only through FolioService', function () {
    $s = seedScenario(1);
    $f = resWithFolio($s);   // charges only, no payment yet

    $this->actingAs(detailMember($s))
        ->post(route('app.reservations.payment', $f['res']->id), ['amount' => '150.00', 'method' => 'cash', 'idempotency_key' => 'nonce-pay-1'])
        ->assertRedirect();

    $this->assertDatabaseHas('payments', ['reservation_id' => $f['res']->id, 'type' => 'payment', 'amount_minor' => 15000, 'status' => 'succeeded']);
    $this->assertDatabaseHas('folios', ['id' => $f['folio']->id, 'paid_minor' => 15000, 'balance_minor' => 18000]);
});

// ── 3b. QA D2-OBS-1 — the on-desk collect stores the REAL method (cash vs bank transfer), money unchanged ──
it('records the on-desk collect with the real chosen method — cash vs bank transfer — money unchanged', function () {
    $s = seedScenario(1);
    $member = detailMember($s);
    $f = resWithFolio($s);   // one reservation, charges €330, nothing paid

    // Cash collect → method 'cash', label "Cash"; amount stored as integer minor (label-only fix).
    $this->actingAs($member)
        ->post(route('app.reservations.payment', $f['res']->id), ['amount' => '100.00', 'method' => 'cash', 'idempotency_key' => 'm-cash'])
        ->assertRedirect();
    $cashPay = Payment::query()->where('reservation_id', $f['res']->id)->where('idempotency_key', 'm-cash')->sole();
    expect($cashPay->method)->toBe('cash')
        ->and($cashPay->methodLabel())->toBe('Cash')
        ->and((int) $cashPay->amount_minor)->toBe(10000);                  // money untouched (integer minor)

    // Bank-transfer collect on the SAME folio → method 'bank_transfer' — NOT collapsed to a generic 'manual'.
    $this->actingAs($member)
        ->post(route('app.reservations.payment', $f['res']->id), ['amount' => '100.00', 'method' => 'bank_transfer', 'idempotency_key' => 'm-bank'])
        ->assertRedirect();
    $bankPay = Payment::query()->where('reservation_id', $f['res']->id)->where('idempotency_key', 'm-bank')->sole();
    expect($bankPay->method)->toBe('bank_transfer')
        ->and($bankPay->methodLabel())->toBe('Bank transfer')
        ->and($bankPay->method)->not->toBe('manual');

    // #17 — the two €100 collects reconcile exactly on the folio (paid €200, balance €130), money untouched.
    $this->assertDatabaseHas('folios', ['id' => $f['folio']->id, 'paid_minor' => 20000, 'balance_minor' => 13000]);

    // The detail ledger surfaces BOTH real methods on the payment lines (not a bare "Received").
    $this->actingAs($member)->get("/app/reservations/{$f['res']->id}")->assertOk()
        ->assertSee('Cash')->assertSee('Bank transfer');
});

// ── 4. Tenant isolation — org B cannot view OR act on org A's reservation/folio ──────────────────
it('isolates tenants — org B cannot view or act on org A\'s folio', function () {
    $sA = seedScenario(1);
    $fA = resWithFolio($sA, withPayment: true);
    $userA = detailMember($sA, 'Amara Okafor');

    $sB = seedScenario(1);
    $userB = detailMember($sB, 'Bao Nguyen');

    // A sees A's reservation; B 404s on it.
    $this->actingAs($userA)->get("/app/reservations/{$fA['res']->id}")->assertOk()->assertSee($fA['res']->reference);
    $this->actingAs($userB)->get("/app/reservations/{$fA['res']->id}")->assertNotFound();

    // B cannot refund or pay against A's reservation — tenant-scoped resolve 404s, nothing mutates.
    $this->actingAs($userB)->post(route('app.reservations.refund', $fA['res']->id), ['payment' => $fA['payment']->id])->assertNotFound();
    $this->actingAs($userB)->post(route('app.reservations.payment', $fA['res']->id), ['amount' => '99.00', 'method' => 'cash', 'idempotency_key' => 'nonce-x'])->assertNotFound();

    // A's folio is unchanged by B's attempts (still paid 200 / balance 130, one payment, no refund).
    $this->assertDatabaseHas('folios', ['id' => $fA['folio']->id, 'paid_minor' => 20000, 'balance_minor' => 13000]);
    expect(Payment::query()->where('reservation_id', $fA['res']->id)->where('type', 'refund')->count())->toBe(0);
});

// ── Batch C · GATE 2 — MOVE ROOM through AssignmentService::assignUnit (same-type free only) ──
it('moves a reservation to a free same-type unit through the guarded assignUnit path', function () {
    $s = seedScenario(2);                                   // U0, U1 — same room type
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $res->reservationUnits()->first();

    $this->actingAs(detailMember($s))
        ->post(route('app.reservations.move', $res->id), [
            'reservation_unit_id' => $line->id,
            'unit_id' => $s['units'][1]->id,
        ])
        ->assertRedirect(route('app.reservations.show', $res->id));

    expect((string) $line->fresh()->unit_id)->toBe((string) $s['units'][1]->id);   // really moved
});

it('refuses a move to an OCCUPIED unit — the assignUnit availability guard holds (not a raw update)', function () {
    $s = seedScenario(2);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);     // U0, Sep 1–3
    book($s, ['unit_id' => $s['units'][1]->id]);            // U1 occupied, same dates
    $line = $res->reservationUnits()->first();

    $this->actingAs(detailMember($s))
        ->post(route('app.reservations.move', $res->id), [
            'reservation_unit_id' => $line->id,
            'unit_id' => $s['units'][1]->id,
        ])->assertRedirect();

    expect((string) $line->fresh()->unit_id)->toBe((string) $s['units'][0]->id);   // unchanged — refused by the guard
});

it('404s a move on a cross-org reservation (tenant-scoped write path)', function () {
    $s = seedScenario(2);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $line = $res->reservationUnits()->first();

    $this->actingAs(detailMember(seedScenario(1)))          // an operator in ANOTHER org
        ->post(route('app.reservations.move', $res->id), [
            'reservation_unit_id' => $line->id, 'unit_id' => $s['units'][1]->id,
        ])->assertNotFound();
})->group('security');

// ── Batch C · GATE 2 — ADD CHARGE through FolioService::postCharge (#17 integer, append-only) ──
it('adds a folio charge through postCharge (integer money, append-only)', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);

    $this->actingAs(detailMember($s))
        ->post(route('app.reservations.charge', $res->id), [
            'type' => 'add_on', 'description' => 'Late checkout', 'amount' => '25.00', 'idempotency_key' => 'charge-nonce-1',
        ])->assertRedirect(route('app.reservations.show', $res->id));

    $this->assertDatabaseHas('folio_items', ['description' => 'Late checkout', 'amount_minor' => 2500, 'type' => 'add_on']);
});

it('refuses a ≤0 charge — no zero/negative folio line', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);

    $this->actingAs(detailMember($s))
        ->post(route('app.reservations.charge', $res->id), ['type' => 'add_on', 'description' => 'ZeroCharge', 'amount' => '0', 'idempotency_key' => 'charge-nonce-zero']);

    $this->assertDatabaseMissing('folio_items', ['description' => 'ZeroCharge']);
});

it('gates add-charge on folios.manage — a role without it is refused (403)', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);

    // Housekeeping is back-office but does NOT hold folios.manage → the route gate 403s.
    $this->actingAs(operatorUser($s, [], 'Housekeeping'))
        ->post(route('app.reservations.charge', $res->id), ['type' => 'add_on', 'description' => 'NopeCharge', 'amount' => '10', 'idempotency_key' => 'charge-nonce-nope'])
        ->assertForbidden();

    $this->assertDatabaseMissing('folio_items', ['description' => 'NopeCharge']);
})->group('security');

// ── ADD-CHARGE server idempotency (mirrors the on-desk payment gate): a raced double-submit → ONE line ──
it('adds a folio charge ONCE on a double-submit with the same idempotency key (money gate)', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $member = detailMember($s);

    // A double-click / raced retry of the SAME rendered form → the SAME per-submit nonce, twice.
    $payload = ['type' => 'add_on', 'description' => 'Minibar', 'amount' => '25.00', 'idempotency_key' => 'charge-same-nonce'];
    $this->actingAs($member)->post(route('app.reservations.charge', $res->id), $payload)->assertRedirect();
    $this->actingAs($member)->post(route('app.reservations.charge', $res->id), $payload)->assertRedirect();

    // Exactly ONE charge line — the server guard held even if the UI double-submit-disable is bypassed.
    expect(FolioItem::query()->where('description', 'Minibar')->count())->toBe(1);
    // And the folio total reflects ONE €25 charge, not two (reconciles to SUM(folio_items.amount_minor) #17).
    $folio = app(FolioService::class)->openFolio($res->fresh());
    expect((int) $folio->total_minor)->toBe(2500);
});

it('records TWO charges for two DIFFERENT nonces — the guard is keyed, no over-dedup (mutation proof)', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $member = detailMember($s);

    // Two genuine add-charges (two renders → two fresh nonces) BOTH land — the guard dedups a DUPLICATE
    // submission, never two deliberate charges, even when they happen to describe the same thing.
    $this->actingAs($member)->post(route('app.reservations.charge', $res->id),
        ['type' => 'add_on', 'description' => 'Minibar', 'amount' => '25.00', 'idempotency_key' => 'charge-nonce-x'])->assertRedirect();
    $this->actingAs($member)->post(route('app.reservations.charge', $res->id),
        ['type' => 'add_on', 'description' => 'Minibar', 'amount' => '25.00', 'idempotency_key' => 'charge-nonce-y'])->assertRedirect();

    expect(FolioItem::query()->where('description', 'Minibar')->count())->toBe(2);
})->group('security');

it('proves the KEY is load-bearing: same key → ONE line, no key → TWO (guard-off doubles)', function () {
    $s = seedScenario(1);
    $res = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);

    // WITHOUT a key (every pre-existing caller — postAddOn/applyTax/applyFee) each write is DISTINCT → doubling
    // is the un-guarded baseline, proving the guard (not some other invariant) is what dedupes the route path.
    $folios->postCharge($folio, 'add_on', 'Unkeyed', Money::of(1000, 'EUR'));
    $folios->postCharge($folio, 'add_on', 'Unkeyed', Money::of(1000, 'EUR'));
    expect(FolioItem::query()->where('description', 'Unkeyed')->count())->toBe(2);

    // WITH the same key, the second call returns the ORIGINAL line — one row, no re-fired event.
    $first = $folios->postCharge($folio, 'add_on', 'Keyed', Money::of(1000, 'EUR'), idempotencyKey: 'k-1');
    $again = $folios->postCharge($folio, 'add_on', 'Keyed', Money::of(1000, 'EUR'), idempotencyKey: 'k-1');
    expect($again->id)->toBe($first->id);
    expect(FolioItem::query()->where('description', 'Keyed')->count())->toBe(1);
})->group('security');

// ── Batch C · GATE 3 — THE MONEY GATE: on-desk payment idempotency + method selector ──────────────
it('records an on-desk payment ONCE on a double-submit with the same idempotency key (money gate)', function () {
    $s = seedScenario(1);
    $f = resWithFolio($s);
    $member = detailMember($s);

    // A double-click of the SAME rendered form → the SAME nonce, twice.
    $payload = ['amount' => '150.00', 'method' => 'cash', 'idempotency_key' => 'same-nonce'];
    $this->actingAs($member)->post(route('app.reservations.payment', $f['res']->id), $payload)->assertRedirect();
    $this->actingAs($member)->post(route('app.reservations.payment', $f['res']->id), $payload)->assertRedirect();

    // Exactly ONE payment — the idempotency guard held (append-only #17; paid = one €150).
    expect(Payment::query()->where('reservation_id', $f['res']->id)->where('type', 'payment')->count())->toBe(1);
    $this->assertDatabaseHas('folios', ['id' => $f['folio']->id, 'paid_minor' => 15000]);
});

it('records TWO payments for two DIFFERENT nonces — the guard is keyed, not a global block (mutation proof)', function () {
    $s = seedScenario(1);
    $f = resWithFolio($s);
    $member = detailMember($s);

    // WITHOUT the same-key match a second submit DOES record — proving the guard is the KEY, so a
    // double-click (same key) records once but two genuine payments (two renders → two keys) both land.
    $this->actingAs($member)->post(route('app.reservations.payment', $f['res']->id),
        ['amount' => '100.00', 'method' => 'cash', 'idempotency_key' => 'nonce-a'])->assertRedirect();
    $this->actingAs($member)->post(route('app.reservations.payment', $f['res']->id),
        ['amount' => '50.00', 'method' => 'bank_transfer', 'idempotency_key' => 'nonce-b'])->assertRedirect();

    expect(Payment::query()->where('reservation_id', $f['res']->id)->where('type', 'payment')->count())->toBe(2);
})->group('security');

it('records cash/bank as a real manual payment now, and DEFERS card to the Stripe seam (no charge in the build)', function () {
    $s = seedScenario(1);
    $f = resWithFolio($s);
    $member = detailMember($s);

    // Card → deferred: NO payment recorded (the account-gated Stripe seam), an honest redirect message.
    $this->actingAs($member)->post(route('app.reservations.payment', $f['res']->id),
        ['amount' => '150.00', 'method' => 'card', 'idempotency_key' => 'nonce-card'])->assertRedirect();
    expect(Payment::query()->where('reservation_id', $f['res']->id)->count())->toBe(0);

    // Cash → recorded now via the manual gateway, the method captured on the payment metadata.
    $this->actingAs($member)->post(route('app.reservations.payment', $f['res']->id),
        ['amount' => '150.00', 'method' => 'cash', 'idempotency_key' => 'nonce-cash'])->assertRedirect();
    $payment = Payment::query()->where('reservation_id', $f['res']->id)->where('type', 'payment')->first();
    expect($payment)->not->toBeNull()
        ->and((string) $payment->gateway)->toBe('manual')
        ->and($payment->metadata['method'] ?? null)->toBe('cash');
});
