<?php

declare(strict_types=1);

use App\Models\Payment;
use App\Payments\Stripe\FakeStripeClient;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * ⚠️⚠️ THE TWO SETTLEMENT HANDLERS NEED NO RE-FETCH — AND THIS FILE IS THE PROOF, NOT THE ASSUMPTION.
 *
 * `settlePayment` and `settleRefund` were examined alongside `invoice.paid` because they are the same
 * family: a webhook writing a status onto a row. They take DIFFERENT inputs — `settlePayment` derives its
 * status from the EVENT TYPE (`payment_intent.succeeded` → succeeded), while `settleRefund` reads it from
 * the PAYLOAD's own `status` field — so they were checked separately rather than together.
 *
 * ⚠️ NEITHER IS ORDER-SENSITIVE, because both are MONOTONIC. A Payment settles out of `pending` exactly
 * once: `settlePayment` returns early unless `status === 'pending'`, and `settleRefund` re-checks the same
 * thing INSIDE a `lockForUpdate`. A second webhook of either kind, in any order, finds a non-pending row
 * and writes nothing. There is no stale write to prevent, so nothing here was changed — a handler that
 * needs no fix is the best outcome available, and this is what makes that claim checkable.
 *
 * ⚠️⚠️ WHAT THAT MONOTONICITY DOES COST IS RECORDED HONESTLY: it is FIRST-WINS, not correct-wins. See
 * docs/BILLING-INVOICE-ORDERING.md — that is a different defect from ordering and was not fixed here.
 */
/**
 * ⚠️⚠️ THIS FILE ROLLS ITS OWN FIXTURES BACK, AND THAT IS NOT OPTIONAL HERE.
 *
 * `RefreshDatabase` is deliberately DISABLED for this suite (tests/Pest.php:23) — the test database
 * PERSISTS, and every row a test creates is still there for the next one. Two neighbours count globals and
 * break when anything upstream adds rows: `TenantIsolationTest` asserts exactly two properties exist under
 * a super-admin read, and `BillingStripeTaxTest` creates the plan catalogue assuming it is absent.
 *
 * Both failed the moment these files were added — not because either is wrong, but because these fixtures
 * leaked. The fixtures are what changed, so the fixtures are what is fixed: `DatabaseTransactions` rolls
 * back everything below, so this file is invisible to the rest of the suite.
 */
uses(DatabaseTransactions::class);

function soIntentWebhook(string $type, string $intentId): TestResponse
{
    $event = ['id' => 'evt_'.Str::lower(Str::random(18)), 'type' => $type,
        'data' => ['object' => ['id' => $intentId, 'object' => 'payment_intent']]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

function soRefundWebhook(string $type, string $refundId, string $status): TestResponse
{
    $event = ['id' => 'evt_'.Str::lower(Str::random(18)), 'type' => $type,
        'data' => ['object' => ['id' => $refundId, 'object' => 'refund', 'status' => $status]]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

/**
 * A folio carrying one PENDING Stripe payment — a state no settlement event would produce by accident.
 *
 * ⚠️ THE GATEWAY REF IS UNIQUE PER CALL, AND THAT IS NOT COSMETIC. `RefreshDatabase` is deliberately
 * DISABLED for this suite (tests/Pest.php:23) — the test database PERSISTS across runs. A fixed
 * `pi_...` reference therefore accumulated one row per run, and the webhook's `where(gateway_ref)->first()`
 * kept resolving the OLDEST, already-settled row instead of this test's pending one. It passed on a clean
 * database and failed on every run after, which is the shape of a probe that proves nothing.
 */
function soPendingPayment(): Payment
{
    $s = seedScenario(1);

    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-05',
            'check_out' => '2026-09-08', 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id,
            'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-05', 'check_out' => '2026-09-08']],
    );

    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room stay', Money::of(30000, 'EUR'));

    $ref = 'pi_'.Str::lower(Str::random(22));

    $payment = Payment::create([
        'organization_id' => $folio->organization_id,
        'folio_id' => $folio->getKey(),
        'reservation_id' => $folio->reservation_id,
        'type' => 'payment',
        'method' => 'card',
        'status' => 'pending',
        'amount_minor' => 30000,
        'currency' => 'EUR',
        'gateway' => 'stripe',
        'gateway_ref' => $ref,
    ]);

    expect($payment->status)->toBe('pending');

    return $payment;
}

/**
 * A PENDING refund row against a settled payment.
 *
 * ⚠️ Written directly rather than through `refundPayment()` ON PURPOSE. What is under test is the SETTLE
 * transition the webhook drives, not refund CREATION — and `refundPayment()` refuses a Stripe payment that
 * does not record which account received it, which is a different guard on a different path. Going through
 * it would test that guard instead. No money is posted here: a pending refund has not moved anything.
 */
function soPendingRefund(): Payment
{
    $payment = soPendingPayment();
    app(FolioService::class)->settlePayment($payment, 'succeeded');
    $payment = $payment->fresh();

    return Payment::create([
        'organization_id' => $payment->organization_id,
        'folio_id' => $payment->folio_id,
        'reservation_id' => $payment->reservation_id,
        'type' => 'refund',
        'method' => 'card',
        'status' => 'pending',
        'amount_minor' => -10000,
        'currency' => 'EUR',
        'gateway' => 'stripe',
        'gateway_ref' => 're_'.Str::lower(Str::random(22)),
        'metadata' => ['refunds_payment_id' => $payment->getKey()],
    ]);
}

it('⚠️ settlePayment is MONOTONIC — a later, contradicting event in either order writes nothing', function () {
    $payment = soPendingPayment();

    // Two DISTINCT, correctly-signed events about the SAME intent — never a duplicate.
    $ref = (string) $payment->gateway_ref;

    soIntentWebhook('payment_intent.succeeded', $ref)->assertStatus(202);
    expect($payment->fresh()->status)->toBe('succeeded');

    $settledAt = $payment->fresh()->processed_at;

    soIntentWebhook('payment_intent.payment_failed', $ref)->assertStatus(202);

    // ⚠️ The stale contradiction did NOT land: no status change, and not even a re-stamped timestamp.
    expect($payment->fresh()->status)->toBe('succeeded')
        ->and($payment->fresh()->processed_at?->timestamp)->toBe($settledAt?->timestamp);
});

it('⚠️ settleRefund is MONOTONIC — and its status comes from the PAYLOAD, not the event name', function () {
    $refund = soPendingRefund();

    $ref = (string) $refund->gateway_ref;

    soRefundWebhook('refund.updated', $ref, 'succeeded')->assertStatus(202);
    expect($refund->fresh()->status)->toBe('succeeded');

    // A later, contradicting delivery — the row is no longer pending, so it writes nothing.
    soRefundWebhook('refund.failed', $ref, 'failed')->assertStatus(202);
    expect($refund->fresh()->status)->toBe('succeeded');
});

it('⚠️ a refund payload whose status is still pending settles NOTHING — the name is not trusted', function () {
    $refund = soPendingRefund();
    $ref = (string) $refund->gateway_ref;

    // ⚠️ `refund.updated` fires for metadata changes too. The handler reads the payload's own status
    //    rather than inferring "settled" from the event name, so this changes nothing.
    soRefundWebhook('refund.updated', $ref, 'pending')->assertStatus(202);

    expect($refund->fresh()->status)->toBe('pending');
});
