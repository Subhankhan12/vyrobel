<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Scopes\OrganizationScope;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeSubscription;
use App\Services\BillingService;
use App\Support\Webhooks\WebhookEventClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ A THROWING HANDLER USED TO DESTROY ITS OWN EVENT.
 *
 * The idempotency row was claimed BEFORE the work ran, and the duplicate check read the row's EXISTENCE
 * rather than its STATE. So anything that threw inside consumed the delivery: Stripe retried, the row was
 * already there, the retry was answered "duplicate", and the event was **lost permanently**. The guard and
 * the loss mechanism were the same line.
 *
 * ⚠️ WORSE THAN THE ORDERING DEFECTS THIS SEQUENCE FIXED — wrong state can be corrected by a later event;
 * NO state cannot. A lost `payment_intent.succeeded` is a guest who paid and a folio that never learned.
 *
 * ⚠️ BOTH HALVES ARE ASSERTED, and neither alone would be enough: a FAILED delivery must be retryable, and a
 * PROCESSED one must never run twice. The second is the property most at risk from this change, so a
 * mutation that lets ANY existing row be retried must go red.
 */
uses(RefreshDatabase::class);

/**
 * ⚠️ THE BILLING endpoint, deliberately. Its handler's Stripe read-back sits INSIDE `once()`, so a failure
 * there is the realistic in-claim throw. (The Connect endpoint reads its account back BEFORE claiming, so it
 * cannot produce this case — which is itself the inconsistency reported in §3.)
 */
function wcWebhook(string $type, array $object, string $eventId, string $orgId): TestResponse
{
    $event = [
        'id' => $eventId, 'type' => $type,
        'data' => ['object' => array_merge(['metadata' => ['organization_id' => $orgId]], $object)],
    ];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

/** An org on a subscription, so the billing handler has something to act on. */
function wcOrg(): object
{
    $org = newDemoOrg();
    app(BillingService::class)->ensurePlans();
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();
    Subscription::create([
        'organization_id' => $org->id, 'plan_id' => $pro->id,
        'status' => 'trialing', 'trial_ends_at' => now()->addDays(10),
    ]);

    return $org;
}

/** The Stripe subscription object the handler will read back. */
function wcSubscriptionObject(string $orgId, string $priceId): array
{
    return [
        'id' => 'sub_claim', 'object' => 'subscription', 'status' => 'active', 'customer' => 'cus_claim',
        'items' => ['data' => [['price' => ['id' => $priceId]]]],
        'metadata' => ['organization_id' => $orgId],
    ];
}

function wcStripeTruth(string $orgId, string $priceId): void
{
    app(StripeClientInterface::class)->subscriptions['sub_claim'] = new StripeSubscription(
        id: 'sub_claim', status: 'active', customerId: 'cus_claim',
        priceIds: [$priceId], metadata: ['organization_id' => $orgId],
    );
}

/** ⚠️ Break the read-back that runs INSIDE `once()` — the realistic throw this pass is about. */
function wcBreakStripe(): void
{
    $throwing = Mockery::mock(app(StripeClientInterface::class))->makePartial();
    $throwing->shouldReceive('retrieveSubscription')->andThrow(new RuntimeException('boom'));
    app()->instance(StripeClientInterface::class, $throwing);
}

it('⚠️⚠️ HALF ONE — an event whose handler threw is RETRIED and PROCESSED, not swallowed as a duplicate', function () {
    $org = wcOrg();
    $orgId = (string) $org->id;
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    $object = wcSubscriptionObject($orgId, (string) $essential->stripe_price_id);

    // ── delivery 1: the read-back INSIDE `once()` throws.
    wcBreakStripe();
    wcWebhook('customer.subscription.updated', $object, 'evt_wc_retry', $orgId)->assertStatus(500);

    // ⚠️ The row survives — it is the ONLY trace the event was ever seen — but it is marked FAILED with the
    //    reason, NOT left looking like a completed delivery.
    $row = WebhookEvent::withoutGlobalScopes([OrganizationScope::class])
        ->where('external_event_id', 'evt_wc_retry')->firstOrFail();
    expect($row->status)->toBe(WebhookEvent::STATUS_FAILED)->and($row->error)->toContain('boom');

    // Nothing was applied: the subscription still names the tier it started on.
    expect(Subscription::withoutGlobalScopes([OrganizationScope::class])->latest()->first()?->plan?->code)
        ->toBe('pro_monthly');

    // ── delivery 2: Stripe retries the SAME event. Before this fix it was answered "duplicate" and the
    //    event was gone forever.
    app()->forgetInstance(StripeClientInterface::class);
    wcStripeTruth($orgId, (string) $essential->stripe_price_id);

    wcWebhook('customer.subscription.updated', $object, 'evt_wc_retry', $orgId)
        ->assertSuccessful()
        ->assertJson(['status' => WebhookEventClaim::RESULT_PROCESSED]);

    expect(WebhookEvent::withoutGlobalScopes([OrganizationScope::class])
        ->where('external_event_id', 'evt_wc_retry')->firstOrFail()->status)
        ->toBe(WebhookEvent::STATUS_PROCESSED)
        // ⚠️ AND THE WORK ACTUALLY LANDED THIS TIME — the retry was not merely acknowledged.
        ->and(Subscription::withoutGlobalScopes([OrganizationScope::class])->latest()->first()?->plan?->code)
        ->toBe('essential_monthly');
});

it('⚠️⚠️ HALF TWO — an event that SUCCEEDED is never reprocessed', function () {
    $org = wcOrg();
    $orgId = (string) $org->id;
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    wcStripeTruth($orgId, (string) $essential->stripe_price_id);
    $object = wcSubscriptionObject($orgId, (string) $essential->stripe_price_id);

    wcWebhook('customer.subscription.updated', $object, 'evt_wc_once', $orgId)
        ->assertSuccessful()->assertJson(['status' => WebhookEventClaim::RESULT_PROCESSED]);

    $processedAt = WebhookEvent::withoutGlobalScopes([OrganizationScope::class])
        ->where('external_event_id', 'evt_wc_once')->firstOrFail()->processed_at;

    // ⚠️ THE PROPERTY MOST AT RISK FROM THIS CHANGE. Admitting retries must not admit re-processing.
    wcWebhook('customer.subscription.updated', $object, 'evt_wc_once', $orgId)
        ->assertSuccessful()->assertJson(['status' => WebhookEventClaim::RESULT_DUPLICATE]);

    $row = WebhookEvent::withoutGlobalScopes([OrganizationScope::class])
        ->where('external_event_id', 'evt_wc_once')->firstOrFail();

    expect(WebhookEvent::withoutGlobalScopes([OrganizationScope::class])
        ->where('external_event_id', 'evt_wc_once')->count())->toBe(1)
        ->and($row->processed_at?->toIso8601String())->toBe($processedAt?->toIso8601String());
});

it('⚠️⚠️ refuses a CONCURRENT second delivery while the first still holds the claim', function () {
    $org = wcOrg();
    $orgId = (string) $org->id;
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    wcStripeTruth($orgId, (string) $essential->stripe_price_id);

    // A claim taken moments ago by a delivery still in flight.
    WebhookEvent::query()->create([
        'source' => 'stripe', 'external_event_id' => 'evt_wc_inflight', 'event_type' => 'customer.subscription.updated',
        'signature_verified' => true, 'payload' => [], 'status' => WebhookEvent::STATUS_PROCESSING,
        'received_at' => now(),
    ]);

    // ⚠️ The eligibility test and the claim are ONE statement, so the loser's update affects zero rows.
    //    A read-then-write would let both through.
    wcWebhook('customer.subscription.updated', wcSubscriptionObject($orgId, (string) $essential->stripe_price_id), 'evt_wc_inflight', $orgId)
        ->assertSuccessful()->assertJson(['status' => WebhookEventClaim::RESULT_IN_FLIGHT]);

    // Nothing was applied by the refused delivery.
    expect(Subscription::withoutGlobalScopes([OrganizationScope::class])->latest()->first()?->plan?->code)
        ->toBe('pro_monthly');
});

it('⚠️⚠️ takes over a STALE claim — the case an exception handler cannot cover', function () {
    $org = wcOrg();
    $orgId = (string) $org->id;
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    wcStripeTruth($orgId, (string) $essential->stripe_price_id);

    // ⚠️ A PROCESS THAT DIED MID-HANDLER: no throw to catch, no completion, the row simply left `processing`.
    //    Without an expiry every retry would be refused forever — the same permanent loss by another route.
    $row = WebhookEvent::query()->create([
        'source' => 'stripe', 'external_event_id' => 'evt_wc_stale', 'event_type' => 'customer.subscription.updated',
        'signature_verified' => true, 'payload' => [], 'status' => WebhookEvent::STATUS_PROCESSING,
        'received_at' => now(),
    ]);
    WebhookEvent::withoutGlobalScopes([OrganizationScope::class])->whereKey($row->getKey())->update([
        'updated_at' => now()->subMinutes(WebhookEventClaim::STALE_CLAIM_MINUTES + 1),
    ]);

    wcWebhook('customer.subscription.updated', wcSubscriptionObject($orgId, (string) $essential->stripe_price_id), 'evt_wc_stale', $orgId)
        ->assertSuccessful()->assertJson(['status' => WebhookEventClaim::RESULT_PROCESSED]);

    expect(Subscription::withoutGlobalScopes([OrganizationScope::class])->latest()->first()?->plan?->code)
        ->toBe('essential_monthly');
});

it('⚠️ leaves a fresh claim alone — the expiry does not steal work that is genuinely running', function () {
    $org = wcOrg();
    $orgId = (string) $org->id;
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    wcStripeTruth($orgId, (string) $essential->stripe_price_id);

    $row = WebhookEvent::query()->create([
        'source' => 'stripe', 'external_event_id' => 'evt_wc_fresh', 'event_type' => 'customer.subscription.updated',
        'signature_verified' => true, 'payload' => [], 'status' => WebhookEvent::STATUS_PROCESSING,
        'received_at' => now(),
    ]);
    // Just inside the window — a handler that is slow, not dead.
    WebhookEvent::withoutGlobalScopes([OrganizationScope::class])->whereKey($row->getKey())->update([
        'updated_at' => now()->subMinutes(WebhookEventClaim::STALE_CLAIM_MINUTES - 1),
    ]);

    wcWebhook('customer.subscription.updated', wcSubscriptionObject($orgId, (string) $essential->stripe_price_id), 'evt_wc_fresh', $orgId)
        ->assertSuccessful()->assertJson(['status' => WebhookEventClaim::RESULT_IN_FLIGHT]);
});

it('⚠️ both receivers share ONE implementation of the rule', function () {
    // ⚠️ The copies were merged because the receivers' own docblock pre-committed to it on exactly this
    //    trigger — a change of the rule's shape, and a third writer of the table.
    foreach ([
        'app/Http/Controllers/StripeWebhookController.php',
        'app/Http/Controllers/StripeConnectWebhookController.php',
    ] as $file) {
        $src = (string) file_get_contents(base_path($file));

        Assert::assertStringContainsString('WebhookEventClaim::once(', $src, "{$file} does not use the shared claim");
        Assert::assertStringNotContainsString('WebhookEvent::createOrFirst(', $src,
            "{$file} still writes the idempotency rule itself — that is the duplication the merge removed");
    }
});
