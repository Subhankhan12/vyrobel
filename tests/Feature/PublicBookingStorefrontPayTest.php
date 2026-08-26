<?php

use App\Models\Fee;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\GuestCreditEntry;
use App\Models\Organization;
use App\Models\Payment;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\GuestWalletService;
use App\Services\OperatorConnectService;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// Guest Portal · GATE 1 — the storefront "Reserve & pay". Two proofs:
//  (#17) the grand total the guest agrees to === the folio total, INCLUDING the property's real
//        non-optional cleaning fee (never a hardcoded €45, never a fee silently dropped).
//  (D1)  the TEST-MODE hosted-Stripe rail: book (balance due) → redirect to hosted Checkout → the
//        folio settles ONLY on the payment_intent.succeeded WEBHOOK (never the redirect). No PAN.

/**
 * ⚠️ THE MONEY SWITCH MADE ONBOARDING A PREREQUISITE, AND THIS SUITE PROVED IT BY FAILING.
 *
 * A card is now taken as a DIRECT charge on the operator's own Stripe account, so an organisation that has
 * not completed Stripe verification cannot take one at all — by design, and with no platform fallback.
 * ⚠️ NOT ONE ASSERTION IN THIS FILE CHANGED: the folio behaviour, the webhook-only settlement and the #17
 * total are all exactly as they were. What changed is the WORLD the fixture has to describe — an operator
 * who can accept a card is now an operator who has onboarded.
 */
function onboardedForCards(array $s): void
{
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    app(OperatorConnectService::class)->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();
    app(StripeClientInterface::class)
        ->connectChargesEnabled[(string) $org->stripe_connect_account_id] = true;
}

/** The demo-style non-optional flat €45 cleaning fee on the scenario's property. */
function cleaningFee(array $s, int $minor = 4500): Fee
{
    return Fee::create([
        'property_id' => $s['property']->id,
        'name' => 'Cleaning fee',
        'kind' => 'cleaning',
        'mode' => 'flat',
        'amount_minor' => $minor,
        'currency' => 'EUR',
        'taxable' => false,
        'optional' => false,
    ]);
}

/** The storefront checkout POST body for the scenario's room on 2 nights. */
function storefrontBody(array $s, array $overrides = []): array
{
    return array_merge([
        'room_type_id' => $s['roomType']->id,
        'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-03',   // 2 nights × €120 = €240 room
        'adults' => 2,
        'children' => 0,
        'first_name' => 'Lukas',
        'last_name' => 'Meyer',
        'email' => 'lukas@example.test',
    ], $overrides);
}

function storefrontFolioBalance(array $s): int
{
    $folio = Folio::query()
        ->whereHas('reservation', fn ($q) => $q->where('property_id', $s['property']->id))
        ->firstOrFail()->load(['items', 'payments']);

    return (int) FolioTotals::for($folio)['balance_minor'];
}

/** POST a signed Stripe payment_intent webhook. */
function storefrontIntentWebhook(string $type, string $intentId): TestResponse
{
    $event = ['id' => 'evt_'.Str::lower(Str::random(18)), 'type' => $type, 'data' => ['object' => ['id' => $intentId, 'object' => 'payment_intent']]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $sig = FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret'));

    return test()->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $raw);
}

// ── 1. #17 — the folio carries the REAL cleaning fee; the total the guest owes === room + cleaning ──
it('posts the real cleaning fee and leaves the balance DUE on pay-on-arrival — never a fabricated payment (#17, F-GUEST-1)', function () {
    $s = publicPropertyScenario(units: 1);                    // €120/night room
    cleaningFee($s);                                          // + €45 non-optional cleaning
    config(['services.payments.gateway' => 'manual']);        // no card rail → pay-on-arrival (settle at the property)

    test()->post("/book/{$s['property']->slug}/checkout", storefrontBody($s))->assertRedirect();

    $folio = Folio::query()
        ->whereHas('reservation', fn ($q) => $q->where('property_id', $s['property']->id))
        ->firstOrFail()->load(['items', 'payments']);

    // The ledger carries BOTH the room and the real cleaning fee.
    expect($folio->items->where('type', 'room')->sum('amount_minor'))->toBe(24000);
    expect($folio->items->where('type', 'fee')->sum('amount_minor'))->toBe(4500);
    // #17 — the true total is room + cleaning; pay-on-arrival leaves it FULLY DUE with NO payment recorded (#39 —
    // the guest was told "settle at the property", so we never fabricate a "paid in full" they didn't make).
    expect((int) FolioTotals::for($folio)['charges_minor'])->toBe(28500);
    expect((int) FolioTotals::for($folio)['balance_minor'])->toBe(28500);
    expect($folio->payments->count())->toBe(0);
});

// ── F-GUEST-1 — the guest's payment CHOICE is honored, and the confirmation is honest ───────────────
it('honors pay-on-arrival: no payment, balance due, confirmation says "Pay on arrival" (F-GUEST-1)', function () {
    $s = publicPropertyScenario(units: 1);
    cleaningFee($s);
    config(['services.payments.gateway' => 'stripe']);        // card rail IS wired — yet the guest CHOOSES on-arrival

    $resp = test()->post("/book/{$s['property']->slug}/checkout", storefrontBody($s, ['payment_option' => 'on_arrival']));
    $resp->assertRedirect();
    // Honored: it does NOT redirect off-site to Stripe — it goes to the confirmation with the balance due.
    expect($resp->headers->get('Location'))->not->toContain('checkout.stripe.test');

    $folio = Folio::query()
        ->whereHas('reservation', fn ($q) => $q->where('property_id', $s['property']->id))
        ->firstOrFail()->load(['items', 'payments']);
    expect($folio->payments->count())->toBe(0);                          // 🔒 nothing charged
    expect((int) FolioTotals::for($folio)['balance_minor'])->toBe(28500); // fully due (#17)

    // The confirmation is honest — "Pay on arrival", never "Paid in full".
    test()->get($resp->headers->get('Location'))
        ->assertOk()
        ->assertSee('Pay on arrival')
        ->assertDontSee('Paid in full');
});

// ── 2. D1 — TEST-MODE hosted Stripe: redirect off-site, PENDING for the true total, NOT paid yet ────
it('starts a test-mode hosted Stripe Checkout for the full total incl. cleaning, unpaid until the webhook', function () {
    $s = publicPropertyScenario(units: 1);
    cleaningFee($s);
    config(['services.payments.gateway' => 'stripe']);        // the founder-chosen rail
    onboardedForCards($s);

    $response = test()->post("/book/{$s['property']->slug}/checkout", storefrontBody($s));
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('checkout.stripe.test');   // off-site, hosted (SAQ A)

    // A PENDING Stripe payment for the TRUE total (room €240 + cleaning €45 = €285), folio NOT paid yet.
    $payment = Payment::query()->where('status', 'pending')->firstOrFail();
    expect($payment->gateway)->toBe('stripe')
        ->and($payment->gateway_ref)->toStartWith('pi_')
        ->and((int) $payment->amount_minor)->toBe(28500);
    expect(storefrontFolioBalance($s))->toBe(28500);          // 🔒 nothing settled on the redirect
});

// ── 3. 🔒 reconcile on the WEBHOOK — the folio settles on payment_intent.succeeded, not the redirect ─
it('settles the storefront folio only on the payment_intent.succeeded webhook', function () {
    $s = publicPropertyScenario(units: 1);
    cleaningFee($s);
    config(['services.payments.gateway' => 'stripe']);
    onboardedForCards($s);

    test()->post("/book/{$s['property']->slug}/checkout", storefrontBody($s))->assertRedirect();
    $intentId = (string) Payment::query()->where('status', 'pending')->value('gateway_ref');

    expect(storefrontFolioBalance($s))->toBe(28500);          // still owed before the webhook

    storefrontIntentWebhook('payment_intent.succeeded', $intentId)->assertStatus(202);

    expect(storefrontFolioBalance($s))->toBe(0);              // paid in full, reconciling (#17)
    expect(Payment::query()->where('gateway_ref', $intentId)->value('status'))->toBe('succeeded');
});

// ── 4. 🔒 a failed charge → the booking exists but the folio stays honestly unpaid ──────────────────
it('leaves the storefront folio unpaid when the hosted charge fails', function () {
    $s = publicPropertyScenario(units: 1);
    cleaningFee($s);
    config(['services.payments.gateway' => 'stripe']);
    onboardedForCards($s);

    test()->post("/book/{$s['property']->slug}/checkout", storefrontBody($s))->assertRedirect();
    $intentId = (string) Payment::query()->where('status', 'pending')->value('gateway_ref');

    storefrontIntentWebhook('payment_intent.payment_failed', $intentId)->assertStatus(202);

    expect(storefrontFolioBalance($s))->toBe(28500);          // still owed — no fake "paid"
    expect(Payment::query()->where('gateway_ref', $intentId)->value('status'))->toBe('failed');
});

// ── D3 — a signed-in guest's REAL wallet credit reduces the booking total (capped, reconciling #17) ──
it('applies a signed-in guest\'s real wallet credit to the checkout, reconciling', function () {
    $s = publicPropertyScenario(units: 1);
    cleaningFee($s);                                          // room €240 + cleaning €45 = €285 owed
    config(['services.payments.gateway' => 'manual']);        // pay-on-arrival → the reduced balance stays DUE

    // A returning guest with a REAL €50 wallet credit, signed in.
    $guest = Guest::create(['first_name' => 'Lukas', 'last_name' => 'Meyer', 'email' => 'lukas@example.test']);
    app(GuestWalletService::class)->credit($guest, GuestCreditEntry::TYPE_PROMO, 'Loyalty', Money::of(5000, 'EUR'));

    test()->actingAs($guest, 'guest')
        ->post("/book/{$s['property']->slug}/checkout", storefrontBody($s, ['apply_credit' => '1']))
        ->assertRedirect();

    $folio = Folio::query()
        ->whereHas('reservation', fn ($q) => $q->where('property_id', $s['property']->id))
        ->firstOrFail()->load(['items', 'payments']);

    // A REAL wallet debit + a folio discount line of −€50; the guest's charges drop by exactly €50 (#17).
    expect($folio->items->where('type', 'discount')->sum('amount_minor'))->toBe(-5000);
    expect((int) FolioTotals::for($folio)['charges_minor'])->toBe(28500 - 5000);   // €285 − €50 = €235
    expect((int) FolioTotals::for($folio)['balance_minor'])->toBe(23500);          // €235 DUE on arrival (no fabricated payment)
    expect($folio->payments->count())->toBe(0);                                    // 🔒 wallet credit ≠ a payment
    expect(app(GuestWalletService::class)->spendableMinor($guest->fresh()))->toBe(0);   // wallet spent
});

// ── D3 — wallet credit NEVER exceeds spendable or what's owed, and is anonymous-safe ────────────────
it('never applies a fabricated credit for an anonymous booker', function () {
    $s = publicPropertyScenario(units: 1);
    cleaningFee($s);
    config(['services.payments.gateway' => 'manual']);

    // No signed-in guest → apply_credit is ignored (no wallet, no fabricated €50).
    test()->post("/book/{$s['property']->slug}/checkout", storefrontBody($s, ['apply_credit' => '1']))->assertRedirect();

    $folio = Folio::query()
        ->whereHas('reservation', fn ($q) => $q->where('property_id', $s['property']->id))
        ->firstOrFail()->load(['items', 'payments']);
    expect($folio->items->where('type', 'discount')->count())->toBe(0);            // no fabricated credit
    expect((int) FolioTotals::for($folio)['charges_minor'])->toBe(28500);          // full €285
});
