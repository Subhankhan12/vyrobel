<?php

declare(strict_types=1);

use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Models\OwnerLedgerEntry;
use App\Models\Payment;
use App\Models\Reservation;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Stripe\FakeStripeClient;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ THE HOLE, FOUND BY READING, NOW CAUGHT BY A TEST.
 *
 * A refund Stripe accepts but cannot fund yet is `pending` — it completes automatically. The folio had no
 * pending state, so it filed a FAILURE: the refund vanished from the running total, the full amount was
 * offered as refundable again, and the desk was invited to re-issue. The idempotency key only half-caught
 * it — the same amount replayed safely, but two halves produced distinct keys, both permitted because
 * `alreadyRefunded` was still 0.
 *
 * ⚠️ Stripe holds a card refund pending on the PLATFORM's own balance too, so this was reachable the moment
 * PAYMENTS_GATEWAY=stripe — not only after the Connect switch.
 */
function pendingRefundScenario(int $payMinor = 50000): array
{
    $s = seedScenario(1);
    $res = Reservation::create([
        'property_id' => $s['property']->id, 'reference' => 'VYR-PR1', 'status' => 'confirmed',
        'source' => 'direct', 'lead_guest_id' => $s['guest']->id,
        'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'currency' => 'EUR',
    ]);
    $folios = app(FolioService::class);
    $folio = $folios->openFolio($res);
    $folios->postCharge($folio, 'room', 'Room', Money::of($payMinor, 'EUR'));
    $payment = $folios->recordPayment($folio, Money::of($payMinor, 'EUR'));
    $folios->settlePayment($payment, 'succeeded');

    return ['s' => $s, 'folio' => $folio, 'payment' => $payment->refresh()];
}

/** Swap in the queuing gateway for the refund leg only. */
function withQueuingRefunds(callable $fn): QueuingRefundGateway
{
    $gateway = new QueuingRefundGateway;
    app()->instance(PaymentGateway::class, $gateway);
    app()->forgetInstance(FolioService::class);
    $fn(app(FolioService::class), $gateway);

    return $gateway;
}

it('⚠️⚠️ THE ACCEPTANCE TEST — a queued €500 then two €250s: BOTH REFUSED', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);

    withQueuingRefunds(function (FolioService $folios) use ($payment): void {
        // 1. Refund the lot. Stripe accepts it and queues it.
        $queued = $folios->refundPayment($payment, Money::of(50000, 'EUR'));
        expect((string) $queued->status)->toBe('pending');

        // ⚠️ It consumes the headroom IMMEDIATELY — this is the one line the whole fix turns on.
        expect($folios->refundedCommittedMinor($payment))->toBe(50000)
            ->and($folios->remainingRefundableMinor($payment))->toBe(0)
            // …and the guest has NOT been refunded anything yet. Two questions, two answers.
            ->and($folios->refundedSettledMinor($payment))->toBe(0);

        // 2. The desk, seeing a refund it thinks failed, re-issues as two halves.
        //    ⚠️ THIS EXACT SEQUENCE IS THE HOLE. Both must be refused.
        expect(fn () => $folios->refundPayment($payment, Money::of(25000, 'EUR')))
            ->toThrow(RefundExceedsPaymentException::class);
        expect(fn () => $folios->refundPayment($payment, Money::of(25000, 'EUR')))
            ->toThrow(RefundExceedsPaymentException::class);
    });

    // Only the original refund row exists — nothing else reached the gateway.
    expect(Payment::where('type', 'refund')->count())->toBe(1);
});

it('⚠️ the same amount re-issued still replays safely — the half that worked must not break', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);

    $gateway = withQueuingRefunds(function (FolioService $folios) use ($payment): void {
        $key = 'operator-submit-once';
        $first = $folios->refundPayment($payment, Money::of(50000, 'EUR'), null, $key);
        $second = $folios->refundPayment($payment, Money::of(50000, 'EUR'), null, $key);

        // A replayed submit returns the ORIGINAL row rather than issuing a second refund.
        expect((string) $second->getKey())->toBe((string) $first->getKey());
    });

    expect($gateway->refundCalls)->toBe(1)
        ->and(Payment::where('type', 'refund')->count())->toBe(1);
});

it('⚠️⚠️ owner_ledger is UNCHANGED while a refund is pending, and the borne-by choice survives', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);
    $before = OwnerLedgerEntry::withoutGlobalScopes()->count();
    $sumBefore = (int) OwnerLedgerEntry::withoutGlobalScopes()->sum('amount_minor');

    $queued = null;
    withQueuingRefunds(function (FolioService $folios) use ($payment, &$queued): void {
        $queued = $folios->refundPayment($payment, Money::of(20000, 'EUR'), 'guest goodwill', null, 'operator');
    });

    // ⚠️ The owner is debited when the money LEAVES, not when it is queued.
    expect(OwnerLedgerEntry::withoutGlobalScopes()->count())->toBe($before)
        ->and((int) OwnerLedgerEntry::withoutGlobalScopes()->sum('amount_minor'))->toBe($sumBefore);

    // ⚠️ …and the operator's borne-by decision rides on the row, so it survives the wait.
    expect((string) $queued->refresh()->refund_borne_by)->toBe('operator');
});

it('⚠️⚠️ a pending refund that FAILS releases the headroom — the bound must not eat money that never left', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);
    $before = OwnerLedgerEntry::withoutGlobalScopes()->count();

    $queued = null;
    $folios = null;
    withQueuingRefunds(function (FolioService $f) use ($payment, &$queued, &$folios): void {
        $queued = $f->refundPayment($payment, Money::of(50000, 'EUR'));
        $folios = $f;
    });

    expect($folios->remainingRefundableMinor($payment))->toBe(0);

    // Stripe gives up on it.
    $folios->settleRefund($queued, 'failed');

    // ⚠️ The amount is refundable again — no money left, so no headroom may be consumed.
    expect($folios->remainingRefundableMinor($payment->refresh()))->toBe(50000)
        ->and($folios->refundedCommittedMinor($payment))->toBe(0)
        // ⚠️ And a refund that ultimately FAILED must have posted NOTHING to the owner.
        ->and(OwnerLedgerEntry::withoutGlobalScopes()->count())->toBe($before);
});

it('⚠️ settlement to succeeded posts ONCE, and a redelivered settlement does not post again', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);

    $queued = null;
    $folios = null;
    withQueuingRefunds(function (FolioService $f) use ($payment, &$queued, &$folios): void {
        $queued = $f->refundPayment($payment, Money::of(50000, 'EUR'));
        $folios = $f;
    });

    $folios->settleRefund($queued, 'succeeded');
    $after = OwnerLedgerEntry::withoutGlobalScopes()->count();
    expect((string) $queued->refresh()->status)->toBe('succeeded')
        ->and($folios->refundedSettledMinor($payment))->toBe(50000);

    // ⚠️ A redelivered settlement is a no-op: the row is no longer `pending`, checked inside the lock.
    $folios->settleRefund($queued, 'succeeded');
    $folios->settleRefund($queued->refresh(), 'succeeded');

    expect(OwnerLedgerEntry::withoutGlobalScopes()->count())->toBe($after);
});

it('⚠️ AN IMMEDIATE REFUND IS UNCHANGED — the pending path is additive', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);
    $folios = app(FolioService::class);   // the default (manual) gateway settles immediately

    $refund = $folios->refundPayment($payment, Money::of(20000, 'EUR'));

    expect((string) $refund->status)->toBe('succeeded')
        ->and($folios->refundedSettledMinor($payment))->toBe(20000)
        // ⚠️ For a settled refund the two questions give the SAME answer — they only diverge in flight.
        ->and($folios->refundedCommittedMinor($payment))->toBe(20000)
        ->and($folios->remainingRefundableMinor($payment))->toBe(30000);
});

// ── ⚠️ SETTLEMENT BY WEBHOOK, ON THE PLANE WHERE THIS IS LIVE TODAY ─────────────────────────────────

/** A signed refund event on the PLATFORM plane. */
function postRefundWebhook(string $type, string $refundRef, string $status, bool $validSignature = true, ?string $secret = null): TestResponse
{
    $event = [
        'id' => 'evt_'.Str::lower(Str::random(16)),
        'type' => $type,
        'data' => ['object' => ['id' => $refundRef, 'object' => 'refund', 'status' => $status]],
    ];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $secret ??= (string) config('services.stripe.webhook_secret');
    $sig = $validSignature ? FakeStripeClient::signature($raw, $secret) : 't=1,v1=deadbeef';

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $sig,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

it('⚠️⚠️ the webhook settles a pending refund — and the folio LEARNS', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);
    $queued = null;
    withQueuingRefunds(function (FolioService $f) use ($payment, &$queued): void {
        $queued = $f->refundPayment($payment, Money::of(50000, 'EUR'));
    });
    expect((string) $queued->status)->toBe('pending');

    postRefundWebhook('refund.updated', (string) $queued->gateway_ref, 'succeeded')->assertStatus(202);

    expect((string) $queued->refresh()->status)->toBe('succeeded')
        ->and(app(FolioService::class)->refundedSettledMinor($payment))->toBe(50000);
});

it('⚠️ a REDELIVERED settlement event does not post twice', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);
    $queued = null;
    withQueuingRefunds(function (FolioService $f) use ($payment, &$queued): void {
        $queued = $f->refundPayment($payment, Money::of(50000, 'EUR'));
    });

    $before = OwnerLedgerEntry::withoutGlobalScopes()->count();
    postRefundWebhook('refund.updated', (string) $queued->gateway_ref, 'succeeded')->assertStatus(202);
    $after = OwnerLedgerEntry::withoutGlobalScopes()->count();

    // Same refund, delivered again (a different event id, so `once()` alone would let it through —
    // settleRefund's in-lock `pending` re-check is what makes it a no-op).
    postRefundWebhook('refund.updated', (string) $queued->gateway_ref, 'succeeded')->assertStatus(202);

    expect(OwnerLedgerEntry::withoutGlobalScopes()->count())->toBe($after)
        ->and($after)->toBeGreaterThanOrEqual($before);
});

it('⚠️ refund.failed settles to failed and releases the headroom', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);
    $queued = null;
    withQueuingRefunds(function (FolioService $f) use ($payment, &$queued): void {
        $queued = $f->refundPayment($payment, Money::of(50000, 'EUR'));
    });

    postRefundWebhook('refund.failed', (string) $queued->gateway_ref, 'failed')->assertStatus(202);

    expect((string) $queued->refresh()->status)->toBe('failed')
        ->and(app(FolioService::class)->remainingRefundableMinor($payment->refresh()))->toBe(50000);
});

it('⚠️⚠️ a still-pending update settles NOTHING — the status is read from the payload, not the event name', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);
    $queued = null;
    withQueuingRefunds(function (FolioService $f) use ($payment, &$queued): void {
        $queued = $f->refundPayment($payment, Money::of(50000, 'EUR'));
    });

    // `refund.updated` also fires for metadata/ARN changes while the refund is still queued.
    postRefundWebhook('refund.updated', (string) $queued->gateway_ref, 'pending')->assertStatus(202);

    expect((string) $queued->refresh()->status)->toBe('pending');
});

it('⚠️ a badly-signed refund event is REJECTED', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);
    $queued = null;
    withQueuingRefunds(function (FolioService $f) use ($payment, &$queued): void {
        $queued = $f->refundPayment($payment, Money::of(50000, 'EUR'));
    });

    postRefundWebhook('refund.updated', (string) $queued->gateway_ref, 'succeeded', validSignature: false)
        ->assertStatus(400);

    expect((string) $queued->refresh()->status)->toBe('pending');
});

it('⚠️⚠️ a CONNECT-signed refund event is rejected by the PLATFORM endpoint', function () {
    ['payment' => $payment] = pendingRefundScenario(50000);
    $queued = null;
    withQueuingRefunds(function (FolioService $f) use ($payment, &$queued): void {
        $queued = $f->refundPayment($payment, Money::of(50000, 'EUR'));
    });

    // Signed with the CONNECT secret — the wrong plane. A wrong secret means events silently rejected,
    // which is payment-taken-booking-unpaid by a second route, so it must be a hard 400 and no state change.
    postRefundWebhook(
        'refund.updated', (string) $queued->gateway_ref, 'succeeded',
        secret: (string) config('services.stripe.connect_webhook_secret'),
    )->assertStatus(400);

    expect((string) $queued->refresh()->status)->toBe('pending');
});
