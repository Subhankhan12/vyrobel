<?php

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

    return grantBackOfficeRole($user, $scenario['org']);
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
        ->post(route('app.reservations.payment', $f['res']->id), ['amount' => '150.00'])
        ->assertRedirect();

    $this->assertDatabaseHas('payments', ['reservation_id' => $f['res']->id, 'type' => 'payment', 'amount_minor' => 15000, 'status' => 'succeeded']);
    $this->assertDatabaseHas('folios', ['id' => $f['folio']->id, 'paid_minor' => 15000, 'balance_minor' => 18000]);
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
    $this->actingAs($userB)->post(route('app.reservations.payment', $fA['res']->id), ['amount' => '99.00'])->assertNotFound();

    // A's folio is unchanged by B's attempts (still paid 200 / balance 130, one payment, no refund).
    $this->assertDatabaseHas('folios', ['id' => $fA['folio']->id, 'paid_minor' => 20000, 'balance_minor' => 13000]);
    expect(Payment::query()->where('reservation_id', $fA['res']->id)->where('type', 'refund')->count())->toBe(0);
});
