<?php

use App\Models\Folio;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Reservation;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Services\OperatorConnectService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// PHASE 8 · live Stripe · SG2 — the GUEST FOLIO CARD CHARGE (test-mode, FakeStripeClient). The guest pays their
// OWN booking's balance via a hosted Stripe Checkout (the card never touches the server); the folio settles ONLY
// on the payment_intent.succeeded WEBHOOK — never the redirect. Guest isolation (#28) + structural idempotency.

/** A guest who can sign in to the portal (email + password). */
function chargeGuest(Guest $g, string $email): Guest
{
    $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $g->password = 'guest-password-1234';
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

/** A booking with an OUTSTANDING balance ($300 charged, nothing paid). */
/**
 * ⚠️ THE MONEY SWITCH MADE ONBOARDING A PREREQUISITE FOR TAKING A CARD, and this suite proved it by
 * failing. A guest card payment is now a DIRECT charge on the operator's own Stripe account, so an
 * organisation that has not completed verification cannot take one — by design, with no platform fallback.
 * ⚠️ NOT ONE ASSERTION HERE CHANGED: the hosted-Checkout redirect, the webhook-only settlement, the failed
 * charge and the no-double-charge proof are all exactly as they were. Only the fixture's world moved.
 */
function guestCardsOnboarded(array $s): void
{
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();
    app(StripeClientInterface::class)
        ->connectChargesEnabled[(string) $org->stripe_connect_account_id] = true;
}

function bookingWithBalance(array $s, Guest $guest): Reservation
{
    guestCardsOnboarded($s);

    $res = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-08', 'price_minor' => 30000, 'currency' => 'EUR']],
    );
    $folio = app(FolioService::class)->openFolio($res);
    app(FolioService::class)->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));

    return $res;
}

/** POST a signed Stripe payment_intent webhook for a given intent id. */
function postIntentWebhook(string $type, string $intentId): TestResponse
{
    $event = ['id' => 'evt_'.Str::lower(Str::random(18)), 'type' => $type, 'data' => ['object' => ['id' => $intentId, 'object' => 'payment_intent']]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $sig = FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret'));

    return test()->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $raw);
}

function balanceOf(Reservation $res): int
{
    $folio = Folio::query()->where('reservation_id', $res->getKey())->firstOrFail()->load(['items', 'payments']);

    return (int) FolioTotals::for($folio)['balance_minor'];
}

// ── 1. THE CHARGE — pay creates a PENDING payment + redirects to Stripe; the folio is NOT paid yet ──
it('starts a hosted Stripe Checkout and pre-creates a pending payment without settling the folio', function () {
    $s = seedScenario(1);
    $guest = chargeGuest($s['guest'], 'ada@example.test');
    $res = bookingWithBalance($s, $guest);

    $response = $this->actingAs($guest, 'guest')->post("/guest/payments/{$res->id}/pay");
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('checkout.stripe.test');   // off-site, hosted (SAQ A)

    // A PENDING Stripe payment exists against a PaymentIntent id — but the folio is NOT paid (pending ≠ paid).
    $payment = Payment::query()->where('reservation_id', $res->id)->where('status', 'pending')->firstOrFail();
    expect($payment->gateway)->toBe('stripe')->and($payment->gateway_ref)->toStartWith('pi_');
    expect(balanceOf($res))->toBe(30000);   // 🔒 no settlement on the redirect
});

// ── 2. 🔒 RECONCILE ON THE WEBHOOK — the folio settles on payment_intent.succeeded, not the redirect ─
it('settles the folio on the payment_intent.succeeded webhook', function () {
    $s = seedScenario(1);
    $guest = chargeGuest($s['guest'], 'ada@example.test');
    $res = bookingWithBalance($s, $guest);

    $this->actingAs($guest, 'guest')->post("/guest/payments/{$res->id}/pay")->assertRedirect();
    $intentId = (string) Payment::query()->where('reservation_id', $res->id)->value('gateway_ref');

    // Before the webhook — still owed. The webhook is the source of truth.
    expect(balanceOf($res))->toBe(30000);

    postIntentWebhook('payment_intent.succeeded', $intentId)->assertStatus(202);

    // The REAL charge posts to the folio (integer money, reconciling) → paid in full.
    expect(balanceOf($res))->toBe(0);
    expect(Payment::query()->where('gateway_ref', $intentId)->value('status'))->toBe('succeeded');
});

// ── 3. 🔒 A FAILED charge → not paid (honest) ───────────────────────────────────────────────────────
it('leaves the folio unpaid when the charge fails', function () {
    $s = seedScenario(1);
    $guest = chargeGuest($s['guest'], 'ada@example.test');
    $res = bookingWithBalance($s, $guest);
    $this->actingAs($guest, 'guest')->post("/guest/payments/{$res->id}/pay")->assertRedirect();
    $intentId = (string) Payment::query()->where('reservation_id', $res->id)->value('gateway_ref');

    postIntentWebhook('payment_intent.payment_failed', $intentId)->assertStatus(202);

    expect(balanceOf($res))->toBe(30000);   // still owed — no fake "paid"
    expect(Payment::query()->where('gateway_ref', $intentId)->value('status'))->toBe('failed');
});

// ── 4. 🔒 GUEST ISOLATION (#28) — a guest can't pay ANOTHER guest's booking ──────────────────────────
it('refuses to pay another guest’s booking', function () {
    $s = seedScenario(2);
    $ada = chargeGuest($s['guest'], 'ada@example.test');
    $res = bookingWithBalance($s, $ada);

    // A different guest with their own login.
    $mallory = chargeGuest(Guest::create(['first_name' => 'Mallory', 'last_name' => 'X']), 'mallory@example.test');

    $this->actingAs($mallory, 'guest')->post("/guest/payments/{$res->id}/pay")->assertNotFound();
    expect(Payment::query()->where('reservation_id', $res->id)->count())->toBe(0);   // no charge started
});

// ── 5. 🔒 IDEMPOTENT — a double-click doesn't double-charge (one session, one pending payment) ───────
it('does not double-charge on a repeated pay', function () {
    $s = seedScenario(1);
    $guest = chargeGuest($s['guest'], 'ada@example.test');
    $res = bookingWithBalance($s, $guest);

    $this->actingAs($guest, 'guest')->post("/guest/payments/{$res->id}/pay")->assertRedirect();
    $this->actingAs($guest, 'guest')->post("/guest/payments/{$res->id}/pay")->assertRedirect();

    // The sending-side idempotency key returns the SAME session (+ PaymentIntent) → ONE pending payment.
    expect(Payment::query()->where('reservation_id', $res->id)->count())->toBe(1);
    expect(app(StripeClientInterface::class)->checkoutSessions)->toHaveCount(1);
});
