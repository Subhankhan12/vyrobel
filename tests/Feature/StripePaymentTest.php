<?php

use App\Models\Folio;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\StripeGateway;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\FolioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// Phase 2 Step 6 — Stripe payment gateway. All Stripe interaction is against the in-memory
// FakeStripeClient (STRIPE_FAKE=true, pinned in phpunit.xml) — NO network. The webhook signature
// uses Stripe's real documented scheme (the fake signs/verifies identically). Payments always
// flow THROUGH FolioService (the single ledger recompute point); the append-only, token-only, and
// deposit-hold guarantees from Phase 1 are re-asserted here under the Stripe gateway.

/**
 * A booked reservation with an open folio and one room charge, with the Stripe gateway selected.
 *
 * @return array{folio: Folio, fake: FakeStripeClient, folios: FolioService, scenario: array<string, mixed>}
 */
function stripeFolioScenario(int $roomChargeMinor = 30000): array
{
    config(['services.payments.gateway' => 'stripe']);

    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);

    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur($roomChargeMinor));

    /** @var FakeStripeClient $fake */
    $fake = app(StripeClientInterface::class);

    return ['folio' => $folio, 'fake' => $fake, 'folios' => $folios, 'scenario' => $s];
}

/**
 * POST a Stripe webhook for a payment_intent event, signed with the (fake = real-scheme) secret.
 *
 * @param  array<string, mixed>  $data  merged into the event's data.object
 */
function postStripeWebhook(string $type, string $paymentIntentId, array $data = [], bool $validSignature = true): TestResponse
{
    $event = [
        'id' => 'evt_'.Str::lower(Str::random(16)),
        'type' => $type,
        'data' => ['object' => array_merge(['id' => $paymentIntentId, 'object' => 'payment_intent'], $data)],
    ];

    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $secret = (string) config('services.stripe.webhook_secret');
    $sig = $validSignature ? FakeStripeClient::signature($raw, $secret) : 't=1,v1=deadbeef';

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $sig,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

it('selects StripeGateway from config and charges through FolioService, updating the ledger; card_token is a token, not a PAN', function () {
    ['folio' => $folio, 'folios' => $folios] = stripeFolioScenario(30000);

    expect(app(FolioService::class))->toBeInstanceOf(FolioService::class);
    // Confirm the wired gateway really is Stripe.
    expect(app(PaymentGateway::class))->toBeInstanceOf(StripeGateway::class);

    $payment = $folios->recordPayment($folio, moneyEur(30000), 'pm_card_visa');

    expect($payment->gateway)->toBe('stripe')
        ->and($payment->status)->toBe('succeeded')
        ->and($payment->gateway_ref)->toStartWith('pi_fake_')      // a Stripe intent id
        ->and($payment->card_token)->toBe('pm_card_visa')          // token only
        ->and((string) $payment->card_token)->not->toMatch('/\d{13,19}/'); // never a raw PAN

    $folio->refresh();
    expect($folio->paid_minor)->toBe(30000)
        ->and($folio->balance_minor)->toBe(0)
        ->and($folio->reservation->refresh()->payment_status)->toBe('paid');
});

it('places a deposit hold (authorize) that is NOT counted as paid until captured', function () {
    ['folio' => $folio, 'folios' => $folios, 'fake' => $fake] = stripeFolioScenario(30000);

    $auth = $folios->authorizeDeposit($folio, moneyEur(10000), 'pm_card_visa');

    expect($auth->status)->toBe('authorized')
        ->and($folio->refresh()->paid_minor)->toBe(0);          // hold ≠ paid
    expect($fake->intents)->toHaveCount(1);                      // one manual-capture intent

    $capture = $folios->captureDeposit($auth, moneyEur(10000));

    expect($capture->type)->toBe('capture')
        ->and($capture->status)->toBe('captured')
        ->and($auth->refresh()->status)->toBe('captured')
        ->and($folio->refresh()->paid_minor)->toBe(10000)        // capture counts as paid
        ->and($fake->captures)->toHaveCount(1);
});

it('records a refund as a NEW offsetting entry via Stripe and never mutates the original (append-only)', function () {
    ['folio' => $folio, 'folios' => $folios, 'fake' => $fake] = stripeFolioScenario(30000);

    $payment = $folios->recordPayment($folio, moneyEur(30000), 'pm_card_visa');
    $originalMinor = $payment->amount_minor;

    $refund = $folios->refundPayment($payment, moneyEur(10000));

    $payment->refresh();
    expect($payment->amount_minor)->toBe($originalMinor)         // original untouched
        ->and($payment->status)->toBe('succeeded')
        ->and($refund->getKey())->not->toBe($payment->getKey())
        ->and($refund->type)->toBe('refund')
        ->and($refund->gateway_ref)->toStartWith('re_fake_')      // a Stripe refund id
        ->and(Payment::query()->where('folio_id', $folio->id)->count())->toBe(2)
        ->and($fake->refunds)->toHaveCount(1);

    $folio->refresh();
    expect($folio->paid_minor)->toBe(20000)->and($folio->balance_minor)->toBe(10000);
});

it('surfaces SCA/3DS as a pending payment (not a false success), then a webhook confirms it', function () {
    ['folio' => $folio, 'folios' => $folios, 'fake' => $fake] = stripeFolioScenario(30000);

    $fake->nextIntentStatus = 'requires_action'; // simulate a 3DS challenge on the next charge

    $payment = $folios->recordPayment($folio, moneyEur(30000), 'pm_card_visa');

    // Not a false success: pending, carrying the client_secret for the frontend, not yet paid.
    expect($payment->status)->toBe('pending')
        ->and($payment->metadata['client_secret'] ?? null)->not->toBeNull()
        ->and($folio->refresh()->paid_minor)->toBe(0)
        ->and($folio->reservation->refresh()->payment_status)->toBe('unpaid');

    // Stripe confirms asynchronously → the webhook settles it through FolioService.
    postStripeWebhook('payment_intent.succeeded', (string) $payment->gateway_ref)->assertStatus(202);

    expect($payment->refresh()->status)->toBe('succeeded')
        ->and($folio->refresh()->paid_minor)->toBe(30000)
        ->and($folio->reservation->refresh()->payment_status)->toBe('paid');
});

it('processes a Stripe webhook exactly once under N redeliveries and rejects an invalid signature', function () {
    ['folio' => $folio, 'folios' => $folios, 'fake' => $fake] = stripeFolioScenario(30000);
    $fake->nextIntentStatus = 'requires_action';
    $payment = $folios->recordPayment($folio, moneyEur(30000), 'pm_card_visa');
    $intentId = (string) $payment->gateway_ref;

    // Invalid signature → rejected, nothing recorded, payment stays pending.
    postStripeWebhook('payment_intent.succeeded', $intentId, validSignature: false)->assertStatus(400);
    expect(WebhookEvent::query()->where('source', 'stripe')->count())->toBe(0)
        ->and($payment->refresh()->status)->toBe('pending');

    // Same event id delivered N times → applied once (one webhook_event, paid once, not doubled).
    $event = [
        'id' => 'evt_once_'.Str::lower(Str::random(10)),
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => $intentId, 'object' => 'payment_intent']],
    ];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $sig = FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret'));

    foreach (range(1, 4) as $ignored) {
        test()->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $raw)
            ->assertStatus(202);
    }

    expect(WebhookEvent::query()->where('source', 'stripe')->where('external_event_id', $event['id'])->count())->toBe(1)
        ->and($payment->refresh()->status)->toBe('succeeded')
        ->and($folio->refresh()->paid_minor)->toBe(30000); // settled once, never double-counted
});

it('leaves the ManualPaymentGateway working for the offline path', function () {
    config(['services.payments.gateway' => 'manual']);

    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($reservation);
    $folios->postCharge($folio, 'room', 'Room', moneyEur(25000));

    $payment = $folios->recordPayment($folio, moneyEur(25000), 'manual-token');

    expect($payment->gateway)->toBe('manual')
        ->and($payment->status)->toBe('succeeded')
        ->and($folio->refresh()->paid_minor)->toBe(25000)
        ->and($folio->reservation->refresh()->payment_status)->toBe('paid');
});
