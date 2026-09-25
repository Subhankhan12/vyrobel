<?php

declare(strict_types=1);

use App\Events\DomainEventRecorded;
use App\Listeners\AdjustOwnerLedgerOnRefund;
use App\Listeners\DispatchNotificationsForDomainEvent;
use App\Models\AuditEvent;
use App\Models\DomainEvent;
use App\Models\Folio;
use App\Models\OwnerLedgerEntry;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Payments\Stripe\FakeStripeClient;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * ⚠️⚠️ THE SETTLEMENT COMMITS, A POST-COMMIT RECORDER THROWS, AND THE TRAIL IS HOLED
 * (docs/POST-COMMIT-TRAIL.md).
 *
 * `settlePayment()`/`settleRefund()` commit the money inside a transaction and record the trail AFTER it.
 * If anything after the commit throws, the retry Stripe sends hits the handlers' own status guard and
 * returns early — so the record stays permanently missing. **The protection and the hole were the same
 * line**, and that guard is not weakened here: two passes proved it makes these handlers monotonic and
 * ordering-proof, and re-settling to recover a record would trade a missing row for a DOUBLE WRITE ON A
 * MONEY PATH.
 *
 * ⚠️ AND THE LOSS WAS NOT ONLY FORENSIC. `AdjustOwnerLedgerOnRefund` — which posts the owner's debit for a
 * guest refund — ran on the same synchronous bus BEHIND two best-effort listeners, so a failed notification
 * meant the guest was refunded and the OWNER'S LEDGER WAS NEVER DEBITED.
 *
 * ⚠️ Every fixture below uses unique ids and rolls back: `RefreshDatabase` is deliberately disabled
 * (tests/Pest.php:23) and this suite's database persists.
 */
uses(DatabaseTransactions::class);

function pcRefundWebhook(string $type, string $ref, string $status): TestResponse
{
    $event = ['id' => 'evt_'.Str::lower(Str::random(20)), 'type' => $type,
        'data' => ['object' => ['id' => $ref, 'object' => 'refund', 'status' => $status]]];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

/** Read across tenants — a webhook leaves the context wherever it finished. */
function pcSuper(callable $read): mixed
{
    return app(TenantContext::class)->runAsSuperAdmin($read);
}

/** An OWNER-ATTRIBUTED reservation with a settled payment and one PENDING Stripe refund. */
function pcOwnedRefund(): object
{
    $s = seedScenario(1);
    ownUnit($s['units'][0], ['commission_pct' => 18]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 30000);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(30000, 'EUR'));

    $ref = 're_'.Str::lower(Str::random(22));
    $refund = Payment::create([
        'organization_id' => $folio->organization_id, 'folio_id' => $folio->getKey(),
        'reservation_id' => $folio->reservation_id, 'type' => 'refund', 'method' => 'card',
        'status' => 'pending', 'amount_minor' => -10000, 'currency' => 'EUR',
        'gateway' => 'stripe', 'gateway_ref' => $ref, 'metadata' => ['refunds_payment_id' => $payment->getKey()],
    ]);

    expect($refund->status)->toBe('pending');

    return (object) ['reservation' => $reservation, 'refund' => $refund, 'ref' => $ref];
}

function pcAuditRows(Payment $p): array
{
    return pcSuper(fn (): array => AuditEvent::query()
        ->where('subject_id', (string) $p->getKey())->pluck('action')->all());
}

function pcAdjustments(string $reservationId): int
{
    return pcSuper(fn (): int => OwnerLedgerEntry::query()
        ->where('reservation_id', $reservationId)->where('entry_type', 'adjustment')->count());
}

/** Replace a listener with one that throws, reproducing a post-commit failure exactly. */
function pcBreak(string $listener): void
{
    app()->bind($listener, fn () => new class
    {
        public function handle(DomainEventRecorded $event): void
        {
            if ($event->domainEvent->event_type === 'payment.refunded') {
                throw new RuntimeException('post-commit recorder blew up');
            }
        }
    });
}

it('⚠️⚠️ THROW AFTER THE COMMIT, THEN RETRY — the audit row lands and the settlement is NOT repeated', function () {
    $f = pcOwnedRefund();

    pcBreak(DispatchNotificationsForDomainEvent::class);
    pcRefundWebhook('refund.updated', $f->ref, 'succeeded')->assertStatus(500);

    // The money committed; the trail did not.
    expect(pcSuper(fn () => Payment::query()->whereKey($f->refund->getKey())->value('status')))->toBe('succeeded')
        ->and(pcAuditRows($f->refund))->toBe([]);

    $settledAt = pcSuper(fn () => Payment::query()->whereKey($f->refund->getKey())->value('processed_at'));
    $ledgerBefore = pcAdjustments((string) $f->reservation->getKey());

    // ── Stripe redelivers once the transient failure is gone ─────────────────────────────────────
    app()->forgetInstance(DispatchNotificationsForDomainEvent::class);
    app()->bind(DispatchNotificationsForDomainEvent::class,
        fn ($app) => $app->make(DispatchNotificationsForDomainEvent::class.'\\Real'));
    app()->bind(DispatchNotificationsForDomainEvent::class.'\\Real', fn () => new class
    {
        public function handle(DomainEventRecorded $event): void {}
    });

    pcRefundWebhook('refund.updated', $f->ref, 'succeeded')->assertStatus(202);

    // ⚠️ THE ROW NOW LANDS …
    expect(pcAuditRows($f->refund))->toContain('payment.refund_settled');

    // ⚠️ … AND NOTHING WAS RE-SETTLED. Same terminal status, same `processed_at` stamp, and the owner
    //    ledger did not gain a second adjustment. That is the half this pass exists to protect.
    expect(pcSuper(fn () => Payment::query()->whereKey($f->refund->getKey())->value('status')))->toBe('succeeded')
        ->and(pcSuper(fn () => Payment::query()->whereKey($f->refund->getKey())->value('processed_at'))?->timestamp)
        ->toBe($settledAt?->timestamp)
        ->and(pcAdjustments((string) $f->reservation->getKey()))->toBe($ledgerBefore);
});

it('⚠️ a NORMAL settlement records the audit row exactly once, and a redelivery adds nothing', function () {
    $f = pcOwnedRefund();

    pcRefundWebhook('refund.updated', $f->ref, 'succeeded')->assertStatus(202);

    $rows = array_filter(pcAuditRows($f->refund), fn (string $a): bool => $a === 'payment.refund_settled');
    expect($rows)->toHaveCount(1);

    // A redelivery of the same shape must not add a second row — the resume is idempotent by query.
    pcRefundWebhook('refund.updated', $f->ref, 'succeeded')->assertStatus(202);

    $after = array_filter(pcAuditRows($f->refund), fn (string $a): bool => $a === 'payment.refund_settled');
    expect($after)->toHaveCount(1);
});

it('⚠️⚠️ MONEY FIRST — a failing notification no longer costs the OWNER LEDGER its debit', function () {
    // ⚠️ This is the accounting half. `AdjustOwnerLedgerOnRefund` used to be registered BEHIND the
    //    notification listener on the same synchronous bus, so a throw there meant the guest was refunded
    //    and the owner was never debited — and the retry could not recover it.
    $f = pcOwnedRefund();

    pcBreak(DispatchNotificationsForDomainEvent::class);
    pcRefundWebhook('refund.updated', $f->ref, 'succeeded')->assertStatus(500);

    expect(pcAdjustments((string) $f->reservation->getKey()))
        ->toBe(1, 'the owner ledger adjustment must land even when a later listener throws');
});

it('⚠️ the money listeners are registered BEFORE the best-effort ones — the order is the guarantee', function () {
    // ⚠️ Asserted on the registration source, because the ordering IS the protection: these listeners are
    //    synchronous and a throw stops everything after it.
    $provider = file_get_contents(base_path('app/Providers/AppServiceProvider.php'));

    $ledger = strpos($provider, 'AdjustOwnerLedgerOnRefund::class);');
    $reattribute = strpos($provider, 'ReattributeOwnerOnUnitMove::class);');
    $notifications = strpos($provider, 'DispatchNotificationsForDomainEvent::class);');
    $emails = strpos($provider, 'SendGuestTransactionalEmails::class);');

    expect($ledger)->toBeLessThan($notifications)
        ->and($ledger)->toBeLessThan($emails)
        ->and($reattribute)->toBeLessThan($notifications);
});

it('⚠️ a notification failure is caught and logged rather than aborting the chain', function () {
    // Its sibling `SendGuestTransactionalEmails` already did this; one convention, not two.
    $source = file_get_contents(base_path('app/Listeners/DispatchNotificationsForDomainEvent.php'));

    expect($source)->toContain('} catch (Throwable $e) {')
        ->and($source)->toContain('Domain-event notification dispatch failed');
});

it('⚠️ the domain event is NOT re-recorded on resume — a second row would resend the guest refund email', function () {
    $f = pcOwnedRefund();

    pcBreak(DispatchNotificationsForDomainEvent::class);
    pcRefundWebhook('refund.updated', $f->ref, 'succeeded')->assertStatus(500);

    app()->forgetInstance(DispatchNotificationsForDomainEvent::class);
    app()->bind(DispatchNotificationsForDomainEvent::class, fn () => new class
    {
        public function handle(DomainEventRecorded $event): void {}
    });

    pcRefundWebhook('refund.updated', $f->ref, 'succeeded')->assertStatus(202);

    $events = pcSuper(fn (): int => DomainEvent::query()
        ->where('subject_id', (string) $f->refund->getKey())
        ->where('event_type', 'payment.refunded')->count());

    expect($events)->toBe(1);
});

it('⚠️ a row that was never a pending refund is left completely alone', function () {
    $f = pcOwnedRefund();
    pcSuper(fn () => Payment::query()->whereKey($f->refund->getKey())->update(['type' => 'payment']));

    pcRefundWebhook('refund.updated', $f->ref, 'succeeded')->assertStatus(202);

    expect(pcAuditRows($f->refund))->not->toContain('payment.refund_settled');
});

it('⚠️ the failed delivery is visible while it is unrepaired, and the reason is recorded', function () {
    $f = pcOwnedRefund();

    pcBreak(DispatchNotificationsForDomainEvent::class);
    pcRefundWebhook('refund.updated', $f->ref, 'succeeded')->assertStatus(500);

    $row = pcSuper(fn () => WebhookEvent::query()->latest('id')->first());

    expect($row->status)->toBe('failed')
        ->and($row->error)->toContain('post-commit recorder blew up');
});
