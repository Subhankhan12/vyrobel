<?php

declare(strict_types=1);

use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Exceptions\Payments\OperatorNotReadyForPaymentsException;
use App\Models\Organization;
use App\Models\OwnerLedgerEntry;
use App\Models\Payment;
use App\Models\Reservation;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\FolioService;
use App\Services\OperatorConnectService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ THE MONEY SWITCH — the charge, the webhook routing and refunds are ONE UNIT.
 *
 * Ship the charge without the routing and Stripe succeeds while the folio never learns: PAYMENT TAKEN,
 * BOOKING UNPAID. Ship it without refunds and a direct charge cannot be refunded, because the refund must
 * be made on the account that took it.
 */
function switchOrg(array $s, bool $ready = true): Organization
{
    $org = Organization::withoutGlobalScopes()->findOrFail($s['org']->getKey());
    $svc = app(OperatorConnectService::class);
    $svc->startOnboarding($org, 'ops@example.test', 'https://r', 'https://x');
    $org->refresh();

    if ($ready) {
        app(StripeClientInterface::class)->connectChargesEnabled[(string) $org->stripe_connect_account_id] = true;
    }

    return $org;
}

/** A signed event on the CONNECT plane. */
function postConnect(string $type, array $object, bool $valid = true, ?string $secret = null): TestResponse
{
    $event = [
        'id' => 'evt_'.Str::lower(Str::random(16)),
        'type' => $type,
        'data' => ['object' => $object],
    ];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $secret ??= (string) config('services.stripe.connect_webhook_secret');
    $sig = $valid ? FakeStripeClient::signature($raw, $secret) : 't=1,v1=deadbeef';

    return test()->call('POST', '/webhooks/stripe/connect', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

/** A booking with an open folio and a balance due. */
function switchBooking(array $s, int $minor = 50000): array
{
    $res = Reservation::create([
        'property_id' => $s['property']->id, 'reference' => 'VYR-SW'.Str::random(4), 'status' => 'confirmed',
        'source' => 'direct', 'lead_guest_id' => $s['guest']->id,
        'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'currency' => 'EUR',
    ]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);
    $folios->postCharge($folio, 'room', 'Room', Money::of($minor, 'EUR'));

    return ['reservation' => $res, 'folio' => $folio, 'folios' => $folios];
}

it('⚠️⚠️ THE CHARGE IS DIRECT — it names the operator account, never the platform', function () {
    $s = seedScenario(1);
    $org = switchOrg($s);
    ['folio' => $folio, 'folios' => $folios] = switchBooking($s);

    $account = app(OperatorConnectService::class)->accountForCharge($org);
    expect($account)->toBe((string) $org->stripe_connect_account_id);

    $session = app(StripeClientInterface::class)->createPaymentCheckout(
        50000, 'EUR', 'Booking', 'https://ok', 'https://no', [], null, $account,
    );
    $folios->recordCheckoutIntent($folio, Money::of(50000, 'EUR'), (string) $session->paymentIntentId, [], $account);

    // ⚠️ The Checkout named the connected account — that header IS what makes the charge direct.
    expect(app(StripeClientInterface::class)->checkoutAccounts)->toContain($account);

    // ⚠️ …and the account is stamped on the payment, so the refund can find it later.
    expect((string) Payment::where('gateway_ref', $session->paymentIntentId)->first()->stripe_account_id)
        ->toBe($account);
});

it('⚠️⚠️ NO FALLBACK — an operator who is not charges_enabled CANNOT take a payment', function () {
    $s = seedScenario(1);
    $org = switchOrg($s, ready: false);

    expect(fn () => app(OperatorConnectService::class)->accountForCharge($org))
        ->toThrow(OperatorNotReadyForPaymentsException::class);

    // ⚠️ The refusal is ACTIONABLE — it says what is wrong AND what to do.
    try {
        app(OperatorConnectService::class)->accountForCharge($org);
    } catch (OperatorNotReadyForPaymentsException $e) {
        // ⚠️ Actionable means WHO can act and what to do meanwhile — NOT a named screen. See the
        //    dedicated "names no screen" test below for why that distinction cost a pass.
        expect($e->getMessage())->toContain('has not finished verification')
            ->and($e->getMessage())->toContain('manages billing');
    }

    // ⚠️ And no platform fallback exists anywhere in the charge path.
    $engine = file_get_contents(base_path('app/Http/Controllers/BookingEngineController.php'));
    expect($engine)->not->toContain('connectedAccountId: null');
});

it('⚠️⚠️ THE FOLIO LEARNS — a direct-charge payment settles on the CONNECT plane', function () {
    $s = seedScenario(1);
    $org = switchOrg($s);
    ['folio' => $folio, 'folios' => $folios] = switchBooking($s);
    $account = (string) $org->stripe_connect_account_id;

    $intent = 'pi_direct_'.Str::random(10);
    $folios->recordCheckoutIntent($folio, Money::of(50000, 'EUR'), $intent, [], $account);
    expect((string) Payment::where('gateway_ref', $intent)->first()->status)->toBe('pending');

    postConnect('payment_intent.succeeded', ['id' => $intent, 'object' => 'payment_intent'])->assertStatus(202);

    // ⚠️ Without this routing: payment taken, booking unpaid.
    expect((string) Payment::where('gateway_ref', $intent)->first()->status)->toBe('succeeded');
});

it('⚠️ a REDELIVERED Connect payment event does not double-post — proven ON THIS PLANE', function () {
    $s = seedScenario(1);
    $org = switchOrg($s);
    ['folio' => $folio, 'folios' => $folios] = switchBooking($s);
    $intent = 'pi_direct_'.Str::random(10);
    $folios->recordCheckoutIntent($folio, Money::of(50000, 'EUR'), $intent, [], (string) $org->stripe_connect_account_id);

    postConnect('payment_intent.succeeded', ['id' => $intent, 'object' => 'payment_intent'])->assertStatus(202);
    postConnect('payment_intent.succeeded', ['id' => $intent, 'object' => 'payment_intent'])->assertStatus(202);

    expect(Payment::where('gateway_ref', $intent)->count())->toBe(1)
        ->and((string) Payment::where('gateway_ref', $intent)->first()->status)->toBe('succeeded');
});

it('⚠️⚠️ SIGNATURE ISOLATION BOTH WAYS — neither plane accepts the other plane\'s signature', function () {
    $s = seedScenario(1);
    $org = switchOrg($s);
    ['folio' => $folio, 'folios' => $folios] = switchBooking($s);
    $intent = 'pi_direct_'.Str::random(10);
    $folios->recordCheckoutIntent($folio, Money::of(50000, 'EUR'), $intent, [], (string) $org->stripe_connect_account_id);

    // PLATFORM-signed → the CONNECT endpoint must reject it.
    postConnect('payment_intent.succeeded', ['id' => $intent, 'object' => 'payment_intent'],
        secret: (string) config('services.stripe.webhook_secret'))->assertStatus(400);
    expect((string) Payment::where('gateway_ref', $intent)->first()->status)->toBe('pending');

    // CONNECT-signed → the PLATFORM endpoint must reject it.
    $event = ['id' => 'evt_'.Str::random(12), 'type' => 'payment_intent.succeeded',
        'data' => ['object' => ['id' => $intent, 'object' => 'payment_intent']]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.connect_webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw)->assertStatus(400);

    // ⚠️ A wrong secret means events SILENTLY rejected — payment-taken-booking-unpaid by a second route.
    expect((string) Payment::where('gateway_ref', $intent)->first()->status)->toBe('pending');
});

it('⚠️⚠️ A REFUND IS ISSUED ON THE CONNECTED ACCOUNT, never the platform', function () {
    $s = seedScenario(1);
    $org = switchOrg($s);
    ['folio' => $folio, 'folios' => $folios] = switchBooking($s);
    $account = (string) $org->stripe_connect_account_id;

    $intent = 'pi_direct_'.Str::random(10);
    $folios->recordCheckoutIntent($folio, Money::of(50000, 'EUR'), $intent, [], $account);
    $payment = Payment::where('gateway_ref', $intent)->first();
    $folios->settlePayment($payment, 'succeeded');

    config()->set('services.payments.gateway', 'stripe');
    app()->forgetInstance(FolioService::class);
    $fake = app(StripeClientInterface::class);
    $fake->refundAccounts = [];

    app(FolioService::class)->refundPayment($payment->refresh(), Money::of(10000, 'EUR'));

    // ⚠️ The refund named the account that TOOK the charge. On the platform it would not exist.
    expect($fake->refundAccounts)->toBe([$account]);
});

it('⚠️⚠️ THE TWO-HALVES SEQUENCE IS REFUSED ON THE CONNECT PLANE TOO', function () {
    $s = seedScenario(1);
    $org = switchOrg($s);
    ['folio' => $folio, 'folios' => $folios] = switchBooking($s);
    $account = (string) $org->stripe_connect_account_id;

    $intent = 'pi_direct_'.Str::random(10);
    $folios->recordCheckoutIntent($folio, Money::of(50000, 'EUR'), $intent, [], $account);
    $payment = Payment::where('gateway_ref', $intent)->first();
    $folios->settlePayment($payment, 'succeeded');

    // A refund on the connected account queues — the NORMAL case there, because automatic daily payouts
    // run a near-zero balance by design.
    $gateway = new QueuingRefundGateway;
    app()->instance(PaymentGateway::class, $gateway);
    app()->forgetInstance(FolioService::class);
    $f = app(FolioService::class);

    $queued = $f->refundPayment($payment->refresh(), Money::of(50000, 'EUR'));
    expect((string) $queued->status)->toBe('pending');

    // ⚠️ The bound counts it here exactly as on the platform plane — both halves refused.
    expect(fn () => $f->refundPayment($payment->refresh(), Money::of(25000, 'EUR')))
        ->toThrow(RefundExceedsPaymentException::class);
    expect(fn () => $f->refundPayment($payment->refresh(), Money::of(25000, 'EUR')))
        ->toThrow(RefundExceedsPaymentException::class);

    // ⚠️ And the queued refund settles on THIS plane — leave it unrouted and it is invisible for ever.
    postConnect('refund.updated', ['id' => (string) $queued->gateway_ref, 'object' => 'refund', 'status' => 'succeeded'])
        ->assertStatus(202);

    expect((string) $queued->refresh()->status)->toBe('succeeded')
        ->and($f->refundedSettledMinor($payment->refresh()))->toBe(50000);
});

it('⚠️ the ledger AND the guest email both DEFER while a Connect refund is queued', function () {
    Mail::fake();
    $s = seedScenario(1);
    $org = switchOrg($s);
    ['folio' => $folio, 'folios' => $folios] = switchBooking($s);

    $intent = 'pi_direct_'.Str::random(10);
    $folios->recordCheckoutIntent($folio, Money::of(50000, 'EUR'), $intent, [], (string) $org->stripe_connect_account_id);
    $payment = Payment::where('gateway_ref', $intent)->first();
    $folios->settlePayment($payment, 'succeeded');

    $before = OwnerLedgerEntry::withoutGlobalScopes()->count();

    $gateway = new QueuingRefundGateway;
    app()->instance(PaymentGateway::class, $gateway);
    app()->forgetInstance(FolioService::class);
    app(FolioService::class)->refundPayment($payment->refresh(), Money::of(20000, 'EUR'), null, null, 'operator');

    // ⚠️ The owner is debited when the money LEAVES; the guest is told when it LEAVES. Neither on `pending`.
    expect(OwnerLedgerEntry::withoutGlobalScopes()->count())->toBe($before);
    Mail::assertNothingOutgoing();
});

// ── THE SWITCH FOLLOW-UP ────────────────────────────────────────────────────────────────────────────

it('⚠️⚠️ THE REFUSAL NAMES NO SCREEN — it says WHO can act, not WHERE to go', function () {
    $message = OperatorNotReadyForPaymentsException::make()->getMessage();

    // ⚠️ A first version read "…from Billing → Connect" — a screen the onboarding pass deliberately did
    //    NOT build. A message that sends someone to a page they cannot reach is worse than one that says
    //    less: it costs them the time to go and look. "Make it actionable" is not "name a route".
    expect($message)->not->toContain('Billing → Connect')
        ->and($message)->not->toContain('Settings')
        ->and($message)->not->toContain('/app')
        // …and it still says WHO can act and what to do meanwhile, which is the actionable part.
        ->and($message)->toContain('manages billing')
        ->and($message)->toContain('take payment another way');
});

it('⚠️⚠️ THE DESK PATH STILL CHARGES THE PLATFORM — the switch is INCOMPLETE, and this pins it', function () {
    // ⚠️ This is a FINDING pinned as a test, not a feature. `recordPayment()` (the desk card path) charges
    //    through `PaymentGateway::charge()`, which has NO connected-account parameter — so under
    //    PAYMENTS_GATEWAY=stripe it charges the PLATFORM and stamps no account. Only the two hosted-Checkout
    //    paths were switched.
    // ⚠️ It is masked today ONLY by PAYMENTS_GATEWAY defaulting to `manual`. Nothing enforces that default.
    // ⚠️ When the desk path is switched, this test should FAIL — and that failure is the signal to add the
    //    null-account refusal in the SAME unit. A refusal added before then blocks a legitimate path.
    $reflection = new ReflectionMethod(PaymentGateway::class, 'charge');
    $names = array_map(fn (ReflectionParameter $p): string => $p->getName(), $reflection->getParameters());

    expect($names)->not->toContain('connectedAccountId');
});

it('⚠️⚠️ THE REFUND EVENT IS MATCHED BY TYPE, NEVER BY A SNIFFED ID', function () {
    $s = seedScenario(1);
    $org = switchOrg($s);
    ['folio' => $folio, 'folios' => $folios] = switchBooking($s);

    $intent = 'pi_direct_'.Str::random(10);
    $folios->recordCheckoutIntent($folio, Money::of(50000, 'EUR'), $intent, [], (string) $org->stripe_connect_account_id);
    $payment = Payment::where('gateway_ref', $intent)->first();
    $folios->settlePayment($payment, 'succeeded');

    $gateway = new QueuingRefundGateway;
    app()->instance(PaymentGateway::class, $gateway);
    app()->forgetInstance(FolioService::class);
    $queued = app(FolioService::class)->refundPayment($payment->refresh(), Money::of(50000, 'EUR'));

    // ⚠️ `StripeEvent::paymentIntentId()` falls back to `data.object.id` when there is no `payment_intent`
    //    field — so this refund object's `re_…` id READS AS a payment-intent id. If the router matched on
    //    that sniff instead of the event TYPE, this settlement would be swallowed and the refund would stay
    //    invisible for ever: the third route to payment-taken-booking-unpaid.
    $refundRef = (string) $queued->gateway_ref;
    expect(str_starts_with($refundRef, 're_'))->toBeTrue();

    postConnect('refund.updated', ['id' => $refundRef, 'object' => 'refund', 'status' => 'succeeded'])
        ->assertStatus(202);

    expect((string) $queued->refresh()->status)->toBe('succeeded');
});
