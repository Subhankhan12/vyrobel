<?php

use App\Models\Fee;
use App\Models\Folio;
use App\Models\Payment;
use App\Payments\Stripe\FakeStripeClient;
use App\Support\Folio\FolioTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// Guest Portal · GATE 1 — the storefront "Reserve & pay". Two proofs:
//  (#17) the grand total the guest agrees to === the folio total, INCLUDING the property's real
//        non-optional cleaning fee (never a hardcoded €45, never a fee silently dropped).
//  (D1)  the TEST-MODE hosted-Stripe rail: book (balance due) → redirect to hosted Checkout → the
//        folio settles ONLY on the payment_intent.succeeded WEBHOOK (never the redirect). No PAN.

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
it('posts the real cleaning fee onto the storefront booking folio (grand total reconciles)', function () {
    $s = publicPropertyScenario(units: 1);                    // €120/night room
    cleaningFee($s);                                          // + €45 non-optional cleaning
    config(['services.payments.gateway' => 'manual']);        // pay-on-arrival → settles inline

    test()->post("/book/{$s['property']->slug}/checkout", storefrontBody($s))->assertRedirect();

    $folio = Folio::query()
        ->whereHas('reservation', fn ($q) => $q->where('property_id', $s['property']->id))
        ->firstOrFail()->load(['items', 'payments']);

    // The ledger carries BOTH the room and the real cleaning fee.
    expect($folio->items->where('type', 'room')->sum('amount_minor'))->toBe(24000);
    expect($folio->items->where('type', 'fee')->sum('amount_minor'))->toBe(4500);
    // #17 — the true total is room + cleaning, and the full-balance payment settles it to the cent.
    expect((int) FolioTotals::for($folio)['charges_minor'])->toBe(28500);
    expect((int) FolioTotals::for($folio)['balance_minor'])->toBe(0);
    expect($folio->payments->where('status', 'succeeded')->sum('amount_minor'))->toBe(28500);
});

// ── 2. D1 — TEST-MODE hosted Stripe: redirect off-site, PENDING for the true total, NOT paid yet ────
it('starts a test-mode hosted Stripe Checkout for the full total incl. cleaning, unpaid until the webhook', function () {
    $s = publicPropertyScenario(units: 1);
    cleaningFee($s);
    config(['services.payments.gateway' => 'stripe']);        // the founder-chosen rail

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

    test()->post("/book/{$s['property']->slug}/checkout", storefrontBody($s))->assertRedirect();
    $intentId = (string) Payment::query()->where('status', 'pending')->value('gateway_ref');

    storefrontIntentWebhook('payment_intent.payment_failed', $intentId)->assertStatus(202);

    expect(storefrontFolioBalance($s))->toBe(28500);          // still owed — no fake "paid"
    expect(Payment::query()->where('gateway_ref', $intentId)->value('status'))->toBe('failed');
});
