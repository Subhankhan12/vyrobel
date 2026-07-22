<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

// PHASE 8 · live Stripe · SG1 — subscription billing, TEST-MODE (FakeStripeClient), WEBHOOK-DRIVEN. The
// subscribe action creates a hosted Checkout Session (the card never touches our server); the org becomes
// ACTIVE only on the checkout.session.completed WEBHOOK — never on the redirect. The 7b SubscriptionStatus
// derives access from `status`, so the lifecycle (active/past_due/canceled) drives the paywall from real events.

/** An org on a trialing subscription + a Property-Owner (billing.manage) operator. */
function billingScenario(): array
{
    $org = newDemoOrg();
    app(BillingService::class)->ensurePlans();
    $plan = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    $subscription = Subscription::create([
        'organization_id' => $org->id,
        'plan_id' => $plan->id,
        'status' => 'trialing',
        'trial_ends_at' => now()->addDays(10),
    ]);
    $user = operatorUser(['org' => $org], ['name' => 'Founder'], 'Property Owner');

    return compact('org', 'subscription', 'user');
}

/** POST a signed Stripe billing webhook (checkout.session.completed / customer.subscription.* / invoice.*). */
function postBillingWebhook(string $type, string $orgId, array $object = [], ?string $eventId = null): TestResponse
{
    $event = [
        'id' => $eventId ?? 'evt_'.Str::lower(Str::random(20)),
        'type' => $type,
        'data' => ['object' => array_merge(['metadata' => ['organization_id' => $orgId]], $object)],
    ];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);
    $sig = FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret'));

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $sig,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}

// ── 1. CHECKOUT — subscribe creates a hosted session + redirects; it does NOT activate the org ──────
it('starts a hosted Stripe Checkout on subscribe without activating the org', function () {
    $s = billingScenario();

    $res = $this->actingAs($s['user'])->post('/app/billing/subscribe', ['plan' => 'essential', 'interval' => 'monthly']);
    $res->assertRedirect();
    expect($res->headers->get('Location'))->toContain('checkout.stripe.test');   // off-site to Stripe's hosted page

    // 🔒 The card never touched the server, and the org is STILL trialing — no activation on the redirect.
    expect($s['subscription']->fresh()->status)->toBe('trialing');
    $fake = app(StripeClientInterface::class);
    expect($fake)->toBeInstanceOf(FakeStripeClient::class);
    expect($fake->checkoutSessions)->not->toBeEmpty();   // a hosted session was created
});

// ── 2. 🔒 FULFIL ON THE WEBHOOK — the org activates on checkout.session.completed, not the redirect ──
it('activates the org on the webhook, never the redirect (a closed tab still activates)', function () {
    $s = billingScenario();

    // Before any webhook — trialing (the redirect alone never activates).
    expect($s['subscription']->fresh()->status)->toBe('trialing');

    postBillingWebhook('checkout.session.completed', $s['org']->id, [
        'object' => 'checkout.session',
        'id' => 'cs_test_1',
        'customer' => 'cus_test_1',
        'subscription' => 'sub_test_1',
    ])->assertStatus(202);

    $fresh = $s['subscription']->fresh();
    expect($fresh->status)->toBe('active')
        ->and($fresh->stripe_subscription_id)->toBe('sub_test_1')
        ->and($fresh->stripe_customer_id)->toBe('cus_test_1');
});

// ── 3. 🔒 IDEMPOTENT — a re-delivered checkout event does NOT double-activate ────────────────────────
it('processes a re-delivered billing event exactly once', function () {
    $s = billingScenario();

    postBillingWebhook('checkout.session.completed', $s['org']->id, ['subscription' => 'sub_x', 'customer' => 'cus_x'], eventId: 'evt_dupe')->assertStatus(202);
    // Same event.id redelivered → duplicate, not re-run.
    postBillingWebhook('checkout.session.completed', $s['org']->id, ['subscription' => 'sub_x', 'customer' => 'cus_x'], eventId: 'evt_dupe')
        ->assertStatus(202)->assertJson(['status' => 'duplicate']);

    expect(WebhookEvent::where('source', 'stripe')->where('external_event_id', 'evt_dupe')->count())->toBe(1);
    expect($s['subscription']->fresh()->status)->toBe('active');
});

// ── 4. LIFECYCLE — subscription.updated → past_due, deleted → canceled (the 7b gate reacts) ──────────
it('drives the subscription lifecycle from real Stripe events', function () {
    $s = billingScenario();
    postBillingWebhook('checkout.session.completed', $s['org']->id, ['subscription' => 'sub_1', 'customer' => 'cus_1'])->assertStatus(202);
    expect($s['subscription']->fresh()->status)->toBe('active');

    // A failed renewal → past_due (dunning); the gate now treats the org as lapsed.
    postBillingWebhook('customer.subscription.updated', $s['org']->id, ['object' => 'subscription', 'id' => 'sub_1', 'status' => 'past_due'])->assertStatus(202);
    expect($s['subscription']->fresh()->status)->toBe('past_due');
    expect($s['org']->fresh()->billingStatus()->hasFullAccess())->toBeFalse();

    // Cancellation at period end → canceled.
    postBillingWebhook('customer.subscription.deleted', $s['org']->id, ['object' => 'subscription', 'id' => 'sub_1'])->assertStatus(202);
    expect($s['subscription']->fresh()->status)->toBe('canceled');
});

// ── 5. 🔒 A FORGED billing webhook is rejected (signature over the raw body) ─────────────────────────
it('rejects a forged billing webhook and changes nothing', function () {
    $s = billingScenario();

    $raw = json_encode(['id' => 'evt_forged', 'type' => 'checkout.session.completed', 'data' => ['object' => ['metadata' => ['organization_id' => $s['org']->id], 'subscription' => 'sub_forged']]], JSON_THROW_ON_ERROR);
    test()->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=deadbeef', 'CONTENT_TYPE' => 'application/json'], $raw)
        ->assertStatus(400);

    expect($s['subscription']->fresh()->status)->toBe('trialing');   // untouched
    expect(WebhookEvent::where('external_event_id', 'evt_forged')->count())->toBe(0);
});
