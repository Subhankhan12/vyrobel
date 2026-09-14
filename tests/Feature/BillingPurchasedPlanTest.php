<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeSubscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ A PURCHASE MUST WRITE THE PLAN IT WAS FOR — AND THE STRIPE PRICE IS WHAT SAYS WHICH.
 *
 * Nothing wrote `subscriptions.plan_id` in response to a purchase. Every org begins on the trial tier and
 * then buys something, so from the FIRST subscription onward Stripe and the application disagreed: Stripe
 * billed the plan that was bought, the app went on showing the trial plan and billing from its captured
 * terms. Not an edge case — the ordinary path.
 *
 * ⚠️ THE PRICE, NEVER THE METADATA. `startCheckout()` stamps `metadata['plan']` — what the app INTENDED.
 * The Price on the subscription Stripe created is what Stripe CHARGED. One of the mutations below resolves
 * from the metadata instead, and must still go red: this defends *the Price is the truth*, not merely
 * *something writes plan_id*.
 *
 * ⚠️ EVERY FIXTURE HERE STARTS ON A DIFFERENT PLAN FROM THE ONE IT BUYS. A probe that begins where it
 * expects to end proves nothing — the previous pass's first attempt did exactly that and had to be redone.
 *
 * ⚠️ Proven through a genuinely SIGNED webhook POST, never over `artisan serve` (which was shown to execute
 * a stale copy of an `app/` class).
 */
uses(RefreshDatabase::class);

/** POST a signed Stripe billing webhook. Self-contained so this file runs alone as well as in company. */
function ppWebhook(string $type, string $orgId, array $object = [], ?string $eventId = null): void
{
    $event = [
        'id' => $eventId ?? 'evt_'.Str::lower(Str::random(20)),
        'type' => $type,
        'data' => ['object' => array_merge(['metadata' => ['organization_id' => $orgId]], $object)],
    ];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);

    test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw)->assertSuccessful();   // the endpoint acknowledges with 202
}

/**
 * Register what Stripe would report for this subscription — the object the handler now READS BACK.
 *
 * ⚠️ The fake FAILS CLOSED: an unregistered id returns null, so a test that forgets this exercises the
 * degraded "could not read back" path instead, and says so in the log. That is deliberate — a fake that
 * synthesised a plausible subscription would let a test pass against data nobody wrote.
 */
function ppRegister(string $id, string $priceId, string $orgId, ?string $metadataPlan = null, string $status = 'active', ?int $periodEnd = null): void
{
    $metadata = ['organization_id' => $orgId];
    if ($metadataPlan !== null) {
        $metadata['plan'] = $metadataPlan;
    }

    app(StripeClientInterface::class)->subscriptions[$id] = new StripeSubscription(
        id: $id, status: $status, customerId: 'cus_purchased',
        currentPeriodEnd: $periodEnd, priceIds: [$priceId], metadata: $metadata,
    );
}

/** A Stripe subscription object billing `$priceId`, as `customer.subscription.*` delivers it. */
function ppSubscriptionObject(string $priceId, string $orgId, ?string $metadataPlan = null): array
{
    $metadata = ['organization_id' => $orgId];
    if ($metadataPlan !== null) {
        $metadata['plan'] = $metadataPlan;
    }

    return [
        'id' => 'sub_purchased', 'status' => 'active', 'customer' => 'cus_purchased',
        'metadata' => $metadata,
        'items' => ['data' => [['price' => ['id' => $priceId]]]],
    ];
}

/** An org on the TRIAL tier — deliberately NOT the plan any test below buys. */
function ppTrialingOnPro(): array
{
    $org = newDemoOrg();
    app(BillingService::class)->ensurePlans();
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();

    $subscription = Subscription::create([
        'organization_id' => $org->id, 'plan_id' => $pro->id,
        'status' => 'trialing', 'trial_ends_at' => now()->addDays(10),
    ]);

    Assert::assertSame('pro_monthly', $subscription->plan?->code, 'the fixture did not start on the trial tier');
    Assert::assertSame(9900, (int) $subscription->terms_price_minor, 'the fixture did not capture Pro’s terms');

    return ['org' => $org, 'subscription' => $subscription];
}

it('⚠️⚠️ records the plan the customer PAID FOR, resolved from the Stripe Price', function () {
    ['org' => $org] = ppTrialingOnPro();
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();

    ppRegister('sub_purchased', (string) $essential->stripe_price_id, (string) $org->id, 'essential_monthly');
    ppWebhook('customer.subscription.created', (string) $org->id,
        ppSubscriptionObject((string) $essential->stripe_price_id, (string) $org->id, 'essential_monthly'));

    $after = Subscription::query()->latest()->first();

    Assert::assertSame('essential_monthly', $after?->plan?->code,
        'the subscription still names the trial plan after the customer bought a different one — Stripe and '
        .'the application disagree about what was purchased');

    // ⚠️ RE-CAPTURE FOLLOWS — CONFIRMED, NOT ASSUMED. `plan_id` goes in the same `update()` as the status, so
    //    the model's `updating` hook sees it dirty and re-freezes the terms in the SAME write. No window.
    Assert::assertSame(4900, (int) $after->terms_price_minor,
        'plan_id moved but the captured terms did not — the customer would be billed the old plan’s price');
    Assert::assertSame(Plan::PRICING_FLAT, $after->terms_pricing_type);
});

it('⚠️ follows a PERIOD change too, because each interval is its own plan row', function () {
    ['org' => $org] = ppTrialingOnPro();
    $yearly = Plan::query()->where('code', 'pro_yearly')->firstOrFail();

    ppRegister('sub_purchased', (string) $yearly->stripe_price_id, (string) $org->id);
    ppWebhook('customer.subscription.updated', (string) $org->id,
        ppSubscriptionObject((string) $yearly->stripe_price_id, (string) $org->id));

    $after = Subscription::query()->latest()->first();

    expect($after?->plan?->code)->toBe('pro_yearly')
        // The rate is unambiguously a term, and it followed.
        ->and((int) $after->terms_price_minor)->toBe(99000);
});

it('⚠️⚠️ REFUSES rather than guessing when the Price names no plan row — and never falls back to metadata', function () {
    ['org' => $org, 'subscription' => $sub] = ppTrialingOnPro();

    Log::spy();

    // ⚠️ THE METADATA SAYS `essential_monthly` AND IS DELIBERATELY IGNORED. Using it here would be a guess
    //    wearing evidence's clothes: it is what the app hoped for, not what the money was taken for.
    ppRegister('sub_purchased', 'price_that_exists_nowhere', (string) $org->id, 'essential_monthly');
    ppWebhook('customer.subscription.created', (string) $org->id,
        ppSubscriptionObject('price_that_exists_nowhere', (string) $org->id, 'essential_monthly'));

    $after = Subscription::query()->latest()->first();

    Assert::assertSame('pro_monthly', $after?->plan?->code,
        'an unresolvable Price was resolved anyway — from the metadata, which is a guess');

    // ⚠️ AND THE CUSTOMER IS STILL ACTIVATED. They have paid; refusing them service would be a second harm on
    //    top of the first. The refusal is about the PLAN, not about fulfilment.
    Assert::assertSame('active', $after->status, 'a paying customer was left unactivated by a catalogue problem');

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $m): bool => str_contains($m, 'does not resolve'));
});

it('⚠️ REFUSES an AMBIGUOUS Price — `plans.stripe_price_id` carries no unique index', function () {
    ['org' => $org] = ppTrialingOnPro();
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();

    // ⚠️ A REAL STATE, NOT A HYPOTHETICAL: only `id` and `code` are unique on `plans`, so two rows may hold
    //    one Price id. Taking the first would silently assign a customer to whichever sorted earlier.
    Plan::query()->where('code', 'essential_yearly')->update(['stripe_price_id' => $essential->stripe_price_id]);

    Log::spy();
    ppRegister('sub_purchased', (string) $essential->stripe_price_id, (string) $org->id);
    ppWebhook('customer.subscription.created', (string) $org->id,
        ppSubscriptionObject((string) $essential->stripe_price_id, (string) $org->id));

    Assert::assertSame('pro_monthly', Subscription::query()->latest()->first()?->plan?->code,
        'an ambiguous Price was resolved to one of its candidates instead of refusing');
    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $m): bool => str_contains($m, 'does not resolve'));
});

it('⚠️⚠️ records the CHARGED plan when the metadata disagrees — the money already moved', function () {
    ['org' => $org] = ppTrialingOnPro();
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();

    Log::spy();

    // Stripe charged Essential; the session claimed Pro. Something changed between the two.
    ppRegister('sub_purchased', (string) $essential->stripe_price_id, (string) $org->id, 'pro_monthly');
    ppWebhook('customer.subscription.created', (string) $org->id,
        ppSubscriptionObject((string) $essential->stripe_price_id, (string) $org->id, 'pro_monthly'));

    Assert::assertSame('essential_monthly', Subscription::query()->latest()->first()?->plan?->code,
        'the intended plan won over the charged one — the customer would be recorded against a price they '
        .'were not charged');

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $m): bool => str_contains($m, 'different plan'));
});

it('⚠️ a re-delivered event changes nothing further — idempotent by the webhook_events unique index', function () {
    ['org' => $org] = ppTrialingOnPro();
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();
    ppRegister('sub_purchased', (string) $essential->stripe_price_id, (string) $org->id);
    $object = ppSubscriptionObject((string) $essential->stripe_price_id, (string) $org->id);

    ppWebhook('customer.subscription.created', (string) $org->id, $object, 'evt_repeat_once');
    $first = Subscription::query()->latest()->first();

    ppWebhook('customer.subscription.created', (string) $org->id, $object, 'evt_repeat_once');
    $second = Subscription::query()->latest()->first();

    expect($second?->plan?->code)->toBe('essential_monthly')
        ->and($second->terms_captured_at?->toIso8601String())->toBe($first->terms_captured_at?->toIso8601String());
});

it('⚠️ leaves the plan alone on an event carrying no Price at all', function () {
    ['org' => $org] = ppTrialingOnPro();

    // `checkout.session.completed` has no line items unless the session was expanded, so it cannot answer
    // "what was bought" — and silence must never move `plan_id`.
    ppWebhook('checkout.session.completed', (string) $org->id, ['subscription' => 'sub_x', 'customer' => 'cus_x']);

    $after = Subscription::query()->latest()->first();

    expect($after?->plan?->code)->toBe('pro_monthly')
        ->and($after->status)->toBe('active');   // it still activates — that is what this event is for
});
