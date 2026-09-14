<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeSubscription;
use App\Services\BillingService;
use App\Support\Billing\SubscriptionStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * ⚠️⚠️ A LATE INVOICE MUST NOT REACTIVATE A CANCELLED ACCOUNT (docs/BILLING-INVOICE-ORDERING.md).
 *
 * `invoice.paid` used to write `status = 'active'` unconditionally and `current_period_end` straight from
 * the invoice payload, so an out-of-order delivery handed a CANCELLED org the whole app back and extended
 * its period with it. This is the third instance of a shape fixed twice before — but the object to re-fetch
 * is the SUBSCRIPTION, not the INVOICE that arrived, because the subscription is what is being written.
 *
 * ⚠️ EVERY PROBE BELOW DELIVERS TWO DISTINCT, CORRECTLY-SIGNED EVENTS — never a duplicate, which the unique
 * constraint already handles and which would prove the wrong thing.
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

function ivWebhook(string $type, ?string $orgId, array $object, int $created): TestResponse
{
    $meta = $orgId === null ? [] : ['metadata' => ['organization_id' => $orgId]];

    $event = [
        'id' => 'evt_'.Str::lower(Str::random(20)),
        'type' => $type,
        'created' => $created,
        'data' => ['object' => array_merge($meta, $object)],
    ];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

/** An invoice payload in the OLDER shape — the subscription id at the top level. */
function ivInvoice(int $periodEnd, string $subscriptionId): array
{
    return ['id' => 'in_test', 'object' => 'invoice', 'subscription' => $subscriptionId, 'period_end' => $periodEnd];
}

/** What Stripe would report right now for the subscription. */
function ivStripeTruth(string $subscriptionId, string $status, int $periodEnd): void
{
    app(StripeClientInterface::class)->subscriptions[$subscriptionId] = new StripeSubscription(
        id: $subscriptionId, status: $status, customerId: 'cus_inv',
        currentPeriodEnd: $periodEnd, priceIds: ['price_pro_monthly'],
    );
}

/**
 * An org whose subscription is CANCELLED and whose period has already run out — a state neither event
 * below would produce by accident, so nothing can pass by starting where it ends.
 */
function ivCancelled(): object
{
    // ⚠️ UNIQUE PER CALL — `RefreshDatabase` is deliberately disabled (tests/Pest.php:23) and the test
    //    database persists, so a fixed `sub_...` would be shared with every previous run of this file.
    $subId = 'sub_'.Str::lower(Str::random(22));

    $org = newDemoOrg();
    app(BillingService::class)->ensurePlans();
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();

    $subscription = Subscription::create([
        'organization_id' => $org->id, 'plan_id' => $pro->id,
        'status' => 'canceled', 'trial_ends_at' => now()->subDays(40),
        'stripe_subscription_id' => $subId,
        'current_period_end' => now()->subDays(9),
    ]);

    expect((new SubscriptionStatus($subscription))->hasFullAccess())->toBeFalse();

    return (object) ['org' => $org, 'subscription' => $subscription, 'subId' => $subId];
}

it('⚠️⚠️ CANCEL then a STALE PAID INVOICE — the account is STILL CANCELLED', function () {
    $f = ivCancelled();
    $periodWas = $f->subscription->current_period_end;

    // ⚠️ Stripe's CURRENT truth: still cancelled, and its period ALREADY RAN OUT. The stale invoice
    //    claims a period 30 days out — so the two disagree on both fields, and only one of them is truth.
    //    (A cancelled subscription CAN legitimately carry a future period end — "cancel at period end" —
    //    and writing that would be correct; this fixture deliberately removes that ambiguity so the probe
    //    can only pass by rejecting the PAYLOAD.)
    ivStripeTruth($f->subId, 'canceled', $periodWas->timestamp);

    ivWebhook('invoice.paid', (string) $f->org->id, ivInvoice(now()->addDays(30)->timestamp, $f->subId), 1_700_000_000)
        ->assertSuccessful();

    $after = Subscription::query()->latest()->first();

    expect($after->status)->toBe('canceled')
        ->and((new SubscriptionStatus($after))->hasFullAccess())->toBeFalse();

    // ⚠️ AND THE PERIOD DID NOT MOVE EITHER — a restored period end on a lapsed account is service that
    //    was never paid for, and it is written by the same line as the status.
    expect($after->current_period_end?->timestamp)->toBe($periodWas?->timestamp);
});

it('⚠️ the REVERSE direction: a stale FAILED invoice does not lapse an account Stripe says is live', function () {
    // ⚠️ The direction that costs a PAYING customer their service. It gets the same re-fetch rather than
    //    the payload, so the live object decides both ways.
    $f = ivCancelled();
    $f->subscription->update(['status' => 'active']);

    ivStripeTruth($f->subId, 'active', now()->addDays(20)->timestamp);

    ivWebhook('invoice.payment_failed', (string) $f->org->id, ivInvoice(now()->addDays(1)->timestamp, $f->subId), 1_700_000_000)
        ->assertSuccessful();

    expect(Subscription::query()->latest()->first()->status)->toBe('active');
});

it('⚠️ a genuine renewal still applies — and the period comes from the SUBSCRIPTION, not the invoice', function () {
    $f = ivCancelled();
    $f->subscription->update(['status' => 'past_due']);

    $subscriptionPeriod = now()->addDays(31)->startOfSecond();
    $invoicePeriod = now()->addDays(7)->startOfSecond();   // an invoice that billed a shorter span

    ivStripeTruth($f->subId, 'active', $subscriptionPeriod->timestamp);

    ivWebhook('invoice.paid', (string) $f->org->id, ivInvoice($invoicePeriod->timestamp, $f->subId), 1_700_000_000)
        ->assertSuccessful();

    $after = Subscription::query()->latest()->first();

    expect($after->status)->toBe('active')
        ->and($after->current_period_end->timestamp)->toBe($subscriptionPeriod->timestamp)
        ->and($after->current_period_end->timestamp)->not->toBe($invoicePeriod->timestamp);
});

it('⚠️ an invoice that names NO subscription writes nothing — it refuses rather than guessing', function () {
    $f = ivCancelled();

    // A one-off charge: an invoice with no subscription behind it. Nothing to renew.
    ivWebhook('invoice.paid', (string) $f->org->id, ['id' => 'in_oneoff', 'object' => 'invoice', 'period_end' => now()->addDays(30)->timestamp], 1_700_000_000)
        ->assertSuccessful();

    expect(Subscription::query()->latest()->first()->status)->toBe('canceled');
});

it('⚠️ an invoice naming a DIFFERENT subscription than the org holds writes nothing', function () {
    $f = ivCancelled();                       // holds sub_inv
    ivStripeTruth('sub_somebody_else', 'active', now()->addDays(30)->timestamp);

    ivWebhook('invoice.paid', (string) $f->org->id, ivInvoice(now()->addDays(30)->timestamp, 'sub_somebody_else'), 1_700_000_000)
        ->assertSuccessful();

    expect(Subscription::query()->latest()->first()->status)->toBe('canceled');
});

it('⚠️ reads the MODERN payload shape too, where the subscription moved under parent', function () {
    $f = ivCancelled();
    $f->subscription->update(['status' => 'past_due']);
    ivStripeTruth($f->subId, 'active', now()->addDays(30)->startOfSecond()->timestamp);

    ivWebhook('invoice.paid', (string) $f->org->id, [
        'id' => 'in_modern', 'object' => 'invoice',
        'parent' => ['subscription_details' => ['subscription' => $f->subId]],
    ], 1_700_000_000)->assertSuccessful();

    expect(Subscription::query()->latest()->first()->status)->toBe('active');
});

it('⚠️⚠️ REFUSES LOUDLY and leaves a diagnosable FAILED row when it cannot read the subscription back', function () {
    $f = ivCancelled();
    // ⚠️ The fake FAILS CLOSED — an unregistered subscription reads as null, never as healthy — so simply
    //    not registering it reproduces an unreachable Stripe exactly.

    ivWebhook('invoice.paid', (string) $f->org->id, ivInvoice(now()->addDays(30)->timestamp, $f->subId), 1_700_000_000)
        ->assertStatus(500);

    // ⚠️ It did NOT degrade to the payload. That is the whole point: an unverifiable move TOWARDS service
    //    is the defect this file exists to stop.
    expect(Subscription::query()->latest()->first()->status)->toBe('canceled');

    // ⚠️ And the event was not lost. The claim/process split writes a `failed` row carrying the reason,
    //    which is re-claimable, so Stripe's retry is admitted rather than swallowed.
    $row = WebhookEvent::query()->where('external_event_id', '!=', '')->latest('id')->first();

    expect($row->status)->toBe('failed')
        ->and($row->error)->toContain($f->subId);
});

it('⚠️⚠️ a REAL renewal — an invoice carrying NO metadata at all — now reaches the handler', function () {
    // ⚠️⚠️ THIS TEST USED TO ASSERT THE OPPOSITE, AND IT WAS RIGHT TO. It recorded the reachability finding:
    //    the org was resolved from the event object's `metadata`, `createSubscriptionCheckout` stamps that
    //    on the Checkout SESSION and on `subscription_data` (the SUBSCRIPTION), and Stripe does not copy
    //    subscription metadata onto the invoices it generates — so a production `invoice.paid` arrived
    //    with none and was answered `202 ignored`. The finding is now FIXED rather than merely recorded,
    //    so the assertion is inverted rather than deleted: same event, opposite expectation.
    $f = ivCancelled();
    $f->subscription->update(['status' => 'past_due']);
    ivStripeTruth($f->subId, 'active', now()->addDays(30)->timestamp);

    // ⚠️ `null` metadata — the REAL production shape. A fixture that stamps it proves nothing.
    $response = ivWebhook('invoice.paid', null, ivInvoice(now()->addDays(30)->timestamp, $f->subId), 1_700_000_000);

    expect($response->json('status'))->toBe('processed')
        ->and(Subscription::query()->latest()->first()->status)->toBe('active');
});

it('⚠️ a flat-plan org still makes NO Stripe call on an invoice it does not own', function () {
    $f = ivCancelled();
    $fake = app(StripeClientInterface::class);
    $before = count($fake->subscriptions);

    ivWebhook('invoice.paid', (string) $f->org->id, ['id' => 'in_x', 'object' => 'invoice'], 1_700_000_000)
        ->assertSuccessful();

    expect(count($fake->subscriptions))->toBe($before);
});
