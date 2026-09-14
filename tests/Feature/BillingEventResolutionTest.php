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
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * ⚠️⚠️ TWO BILLING HANDLERS WERE INERT IN PRODUCTION, AND NOTHING SAID SO (docs/BILLING-EVENT-RESOLUTION.md).
 *
 * Org resolution read `data.object.metadata.organization_id`. `createSubscriptionCheckout()` stamps that on
 * the Checkout SESSION and on `subscription_data` — the SUBSCRIPTION — and **Stripe does not copy
 * subscription metadata onto invoices**. So a real `invoice.paid` / `invoice.payment_failed` carried none,
 * resolved to nothing, and was answered `202 ignored`, which is indistinguishable from success.
 *
 * ⚠️ EVERY PROBE HERE DELIVERS AN EVENT WITH **NO METADATA** — the real production shape. A fixture that
 * stamps metadata proves nothing, and is exactly how this survived to be found by accident.
 */
uses(DatabaseTransactions::class);

function beWebhook(string $type, ?string $orgId, array $object): TestResponse
{
    $meta = $orgId === null ? [] : ['metadata' => ['organization_id' => $orgId]];

    $event = [
        'id' => 'evt_'.Str::lower(Str::random(20)),
        'type' => $type,
        'created' => 1_700_000_000,
        'data' => ['object' => array_merge($meta, $object)],
    ];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

/**
 * A subscription's status read ACROSS tenants.
 *
 * ⚠️ These probes assert on organisations OTHER than whichever one the webhook last set as context, and
 * `OrganizationScope` would silently filter them to null — which reads as "the row changed" when it means
 * "you cannot see the row". Read as super-admin, explicitly.
 */
function beStatus(Subscription $subscription): ?string
{
    return app(TenantContext::class)->runAsSuperAdmin(
        fn (): ?string => Subscription::query()->whereKey($subscription->getKey())->value('status'),
    );
}

/** The most recent webhook_events row, read across tenants — an unresolved row has no organisation. */
function beLastEvent(): ?WebhookEvent
{
    return app(TenantContext::class)->runAsSuperAdmin(
        fn (): ?WebhookEvent => WebhookEvent::query()->latest('id')->first(),
    );
}

/** How many webhook_events rows exist, across tenants. */
function beEventCount(): int
{
    return app(TenantContext::class)->runAsSuperAdmin(
        fn (): int => WebhookEvent::query()->count(),
    );
}

/** An org on a live subscription, anchored to unique Stripe ids — the state before a card fails. */
function beActiveOrg(): object
{
    // ⚠️ UNIQUE PER CALL. `RefreshDatabase` is deliberately disabled (tests/Pest.php:23) and the test
    //    database persists, so fixed ids accumulate rows across runs and resolve the wrong one.
    $subId = 'sub_'.Str::lower(Str::random(22));
    $cusId = 'cus_'.Str::lower(Str::random(22));

    $org = newDemoOrg();
    app(BillingService::class)->ensurePlans();
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();

    $subscription = Subscription::create([
        'organization_id' => $org->id, 'plan_id' => $pro->id,
        'status' => 'active', 'trial_ends_at' => now()->subDays(40),
        'stripe_subscription_id' => $subId, 'stripe_customer_id' => $cusId,
        'current_period_end' => now()->addDays(20),
    ]);

    expect((new SubscriptionStatus($subscription))->hasFullAccess())->toBeTrue();

    return (object) ['org' => $org, 'subscription' => $subscription, 'subId' => $subId, 'cusId' => $cusId];
}

it('⚠️⚠️ a FAILED PAYMENT now revokes access — an invoice with NO metadata resolves and marks past_due', function () {
    $f = beActiveOrg();

    app(StripeClientInterface::class)->subscriptions[$f->subId] = new StripeSubscription(
        id: $f->subId, status: 'past_due', customerId: $f->cusId,
        currentPeriodEnd: now()->addDays(20)->timestamp, priceIds: ['price_pro_monthly'],
    );

    // ⚠️ THE REAL SHAPE: no metadata anywhere on the invoice.
    beWebhook('invoice.payment_failed', null, [
        'id' => 'in_'.Str::lower(Str::random(18)), 'object' => 'invoice',
        'subscription' => $f->subId, 'customer' => $f->cusId,
    ])->assertSuccessful();

    $after = app(TenantContext::class)->runAsSuperAdmin(
        fn (): Subscription => Subscription::query()->whereKey($f->subscription->getKey())->firstOrFail(),
    );

    expect($after->status)->toBe('past_due')
        ->and((new SubscriptionStatus($after))->hasFullAccess())->toBeFalse();
});

it('⚠️ resolves from the CUSTOMER when the invoice names no subscription at all', function () {
    $f = beActiveOrg();

    // A one-off invoice: no subscription anywhere, only a customer. It must still be PLACED — the handler
    // then refuses to write, which is a different decision from failing to find the org.
    $response = beWebhook('invoice.paid', null, [
        'id' => 'in_'.Str::lower(Str::random(18)), 'object' => 'invoice', 'customer' => $f->cusId,
    ]);

    expect($response->json('status'))->toBe('processed')
        ->and(beStatus($f->subscription))->toBe('active');
});

it('⚠️ reads BOTH payload shapes — no API version is pinned, so either may arrive', function () {
    foreach (['old', 'modern'] as $shape) {
        $f = beActiveOrg();

        app(StripeClientInterface::class)->subscriptions[$f->subId] = new StripeSubscription(
            id: $f->subId, status: 'past_due', customerId: $f->cusId,
            currentPeriodEnd: now()->addDays(20)->timestamp, priceIds: ['price_pro_monthly'],
        );

        $object = $shape === 'old'
            ? ['id' => 'in_'.Str::lower(Str::random(18)), 'object' => 'invoice', 'subscription' => $f->subId]
            : ['id' => 'in_'.Str::lower(Str::random(18)), 'object' => 'invoice',
                'parent' => ['subscription_details' => ['subscription' => $f->subId]]];

        beWebhook('invoice.payment_failed', null, $object)->assertSuccessful();

        expect(beStatus($f->subscription))
            ->toBe('past_due', "the {$shape} payload shape did not resolve");
    }
});

it('⚠️⚠️ REFUSES an ambiguous resolution rather than guessing, and says which id was ambiguous', function () {
    // ⚠️ Neither anchor column is unique — `stripe_customer_id` carries a plain index and
    //    `stripe_subscription_id` has none — so two organisations CAN carry the same customer id. Writing
    //    billing state into the wrong tenant is the worst outcome on this path.
    $a = beActiveOrg();
    $b = beActiveOrg();
    Subscription::query()->whereKey($b->subscription->getKey())->update(['stripe_customer_id' => $a->cusId]);

    $response = beWebhook('invoice.payment_failed', null, [
        'id' => 'in_'.Str::lower(Str::random(18)), 'object' => 'invoice', 'customer' => $a->cusId,
    ]);

    expect($response->json('status'))->toBe('unresolved');

    // Neither org was touched.
    expect(beStatus($a->subscription))->toBe('active')
        ->and(beStatus($b->subscription))->toBe('active');

    $row = beLastEvent();
    expect($row->error)->toContain($a->cusId)->toContain('refusing to guess');
});

it('⚠️⚠️ REFUSES when the metadata and the Stripe ids disagree — a cross-check, not a fallback', function () {
    $a = beActiveOrg();
    $b = beActiveOrg();

    // The invoice's ids belong to A; its metadata claims B. One of them is wrong and nothing says which.
    $response = beWebhook('invoice.payment_failed', (string) $b->org->id, [
        'id' => 'in_'.Str::lower(Str::random(18)), 'object' => 'invoice', 'subscription' => $a->subId,
    ]);

    expect($response->json('status'))->toBe('unresolved')
        ->and(beStatus($a->subscription))->toBe('active')
        ->and(beStatus($b->subscription))->toBe('active');
});

it('⚠️⚠️ an UNRESOLVABLE event no longer reads as processed — it is written down with its reason', function () {
    // ⚠️ This is the half that costs nothing: an event the product could not place used to leave NO trace
    //    at all — no log line, no row — and read exactly like one handled perfectly.
    $before = beEventCount();

    $response = beWebhook('invoice.payment_failed', null, [
        'id' => 'in_'.Str::lower(Str::random(18)), 'object' => 'invoice',
        'subscription' => 'sub_belongs_to_nobody_'.Str::lower(Str::random(8)),
    ]);

    expect($response->json('status'))->toBe('unresolved')
        ->and(beEventCount())->toBe($before + 1);

    $row = beLastEvent();

    expect($row->status)->toBe('failed')
        ->and($row->status)->not->toBe('processed')
        ->and($row->error)->not->toBeNull()
        ->and($row->signature_verified)->toBeTrue();
});

it('⚠️ the WORKING path is unchanged — a subscription event still resolves from its metadata', function () {
    // ⚠️ On a FIRST purchase the local row has no Stripe ids yet — they are what the event is delivering —
    //    so metadata is the only evidence that exists, and it must keep working.
    $org = newDemoOrg();
    app(BillingService::class)->ensurePlans();
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();

    $subscription = Subscription::create([
        'organization_id' => $org->id, 'plan_id' => $pro->id,
        'status' => 'trialing', 'trial_ends_at' => now()->addDays(10),
    ]);

    expect($subscription->stripe_subscription_id)->toBeNull();

    $newSubId = 'sub_'.Str::lower(Str::random(22));
    app(StripeClientInterface::class)->subscriptions[$newSubId] = new StripeSubscription(
        id: $newSubId, status: 'active', customerId: 'cus_'.Str::lower(Str::random(18)),
        currentPeriodEnd: now()->addDays(30)->timestamp, priceIds: ['price_pro_monthly'],
    );

    beWebhook('customer.subscription.created', (string) $org->id, [
        'id' => $newSubId, 'object' => 'subscription', 'status' => 'active',
        'items' => ['data' => [['price' => ['id' => 'price_pro_monthly']]]],
    ])->assertSuccessful();

    expect(beStatus($subscription))->toBe('active');
});

it('⚠️ an event naming an organisation this deployment does not have is recorded, not retried', function () {
    $response = beWebhook('invoice.payment_failed', (string) Str::ulid(), [
        'id' => 'in_'.Str::lower(Str::random(18)), 'object' => 'invoice',
    ]);

    // 202, not 5xx: retrying forever on an org that will never exist here is a poison message.
    $response->assertStatus(202);
    expect($response->json('status'))->toBe('unresolved');

    expect(beLastEvent()->status)->toBe('failed');
});

// ── What `past_due` ACTUALLY causes — established before the handler that writes it was made live ────

it('⚠️⚠️ past_due REVOKES THE WHOLE BACK OFFICE — the dangerous half of making this handler live', function () {
    // ⚠️ `BillingAccessGateTest` covers an expired trial and a `canceled` subscription. It never covered
    //    `past_due` — the state this pass just made reachable — so what it does was, until now, untested.
    $scenario = seedScenario(1);
    app(BillingService::class)->ensurePlans();
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();

    Subscription::create([
        'organization_id' => $scenario['org']->id, 'plan_id' => $pro->id,
        'status' => 'past_due', 'trial_ends_at' => now()->subDays(40),
    ]);

    $user = operatorUser($scenario, [], 'Property Owner');

    // The entire /app group — front desk, reservations, everything — becomes a redirect to the paywall.
    $this->actingAs($user)->get('/app/dashboard')->assertRedirect(route('app.billing'));
    $this->actingAs($user)->get('/app/reservations')->assertRedirect(route('app.billing'));

    // ⚠️ Except billing itself, or a lapsed org could never pay to come back.
    $this->actingAs($user)->get('/app/billing')->assertOk();
});

it('⚠️ the operator\'s OWNERS and GUESTS are third parties and are NOT gated by their operator\'s billing', function () {
    // ⚠️ Established rather than assumed: `EnsureSubscriptionActive` appears on exactly one route group
    //    (routes/web.php:674, the /app group). The owner portal and the guest realm do not carry it, so a
    //    past-due operator does not lock out people who did nothing wrong.
    $routes = file_get_contents(base_path('routes/web.php'));

    expect(substr_count($routes, 'EnsureSubscriptionActive::class'))->toBe(1);

    $group = collect(explode("\n", $routes))
        ->first(fn (string $line): bool => str_contains($line, 'EnsureSubscriptionActive::class'));

    expect($group)->toContain("prefix('app')");
});
