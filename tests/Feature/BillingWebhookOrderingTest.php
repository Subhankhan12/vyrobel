<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Payments\Stripe\FakeStripeClient;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeSubscription;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ WEBHOOKS ARRIVE OUT OF ORDER, AND THAT IS A DIFFERENT PROBLEM FROM RE-DELIVERY.
 *
 * Re-delivery was already solved — `webhook_events` is UNIQUE on `(source, external_event_id)`, so the same
 * event never applies twice. That says nothing about two DISTINCT events arriving in the wrong sequence,
 * and nothing stopped a late `customer.subscription.updated` writing its older payload over newer state.
 *
 * ⚠️ THE FIX READS THE OBJECT BACK FROM STRIPE, so both events write the same CURRENT truth and order stops
 * mattering. **A case removed beats a case handled.** ⚠️ Nothing is skipped, so there is no drop branch to
 * get wrong — which is the failure mode a watermark would have introduced.
 *
 * ⚠️ EVERY FIXTURE STARTS ON A PLAN NEITHER EVENT WOULD PRODUCE, and asserts it did. A probe that begins
 * where it expects to end proves nothing.
 */
uses(RefreshDatabase::class);

/** POST a signed billing webhook, with an explicit event `created` second so ordering is unambiguous. */
function woWebhook(string $type, string $orgId, array $object, int $created, ?string $eventId = null): void
{
    $event = [
        'id' => $eventId ?? 'evt_'.Str::lower(Str::random(20)),
        'type' => $type,
        'created' => $created,
        'data' => ['object' => array_merge(['metadata' => ['organization_id' => $orgId]], $object)],
    ];
    $raw = json_encode($event, JSON_THROW_ON_ERROR);

    test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => FakeStripeClient::signature($raw, (string) config('services.stripe.webhook_secret')),
        'CONTENT_TYPE' => 'application/json',
    ], $raw)->assertSuccessful();
}

function woEventObject(string $priceId, string $orgId, string $status, int $periodEnd): array
{
    return [
        'id' => 'sub_order', 'object' => 'subscription', 'status' => $status,
        'customer' => 'cus_order', 'current_period_end' => $periodEnd,
        'items' => ['data' => [['price' => ['id' => $priceId]]]],
        'metadata' => ['organization_id' => $orgId],
    ];
}

/** What Stripe would report right now for `sub_order`. */
function woStripeTruth(string $priceId, string $orgId, string $status, int $periodEnd): void
{
    app(StripeClientInterface::class)->subscriptions['sub_order'] = new StripeSubscription(
        id: 'sub_order', status: $status, customerId: 'cus_order',
        currentPeriodEnd: $periodEnd, priceIds: [$priceId],
        metadata: ['organization_id' => $orgId],
    );
}

/** Trialing on the trial tier — a state NEITHER of the two events below would produce. */
function woFixture(): object
{
    $org = newDemoOrg();
    app(BillingService::class)->ensurePlans();
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();

    Subscription::create([
        'organization_id' => $org->id, 'plan_id' => $pro->id,
        'status' => 'trialing', 'trial_ends_at' => now()->addDays(10),
    ]);

    Assert::assertSame('pro_monthly', Subscription::query()->latest()->first()?->plan?->code,
        'the fixture did not start on a third plan — a probe that begins where it ends proves nothing');

    return (object) ['org' => $org];
}

it('⚠️⚠️ applies CURRENT truth when two DISTINCT events arrive BACKWARDS', function () {
    $f = woFixture();
    $orgId = (string) $f->org->id;
    $yearly = Plan::query()->where('code', 'essential_yearly')->firstOrFail();
    $monthly = Plan::query()->where('code', 'essential_monthly')->firstOrFail();

    $newEnd = 2_000_000_000;
    $oldEnd = 1_000_000_000;

    // What Stripe actually holds now — matching the NEWER event.
    woStripeTruth((string) $yearly->stripe_price_id, $orgId, 'active', $newEnd);

    // ⚠️ TWO DISTINCT EVENTS, DELIVERED IN THE WRONG ORDER. Not a duplicate: the unique constraint already
    //    handles those, and a duplicate test would prove the wrong thing entirely.
    woWebhook('customer.subscription.updated', $orgId,
        woEventObject((string) $yearly->stripe_price_id, $orgId, 'active', $newEnd), 1_700_000_200, 'evt_newer');
    woWebhook('customer.subscription.updated', $orgId,
        woEventObject((string) $monthly->stripe_price_id, $orgId, 'past_due', $oldEnd), 1_700_000_100, 'evt_older');

    $after = Subscription::query()->latest()->first();

    // Without the re-fetch the LATE event's payload lands last and wins: essential_monthly / past_due.
    expect($after?->plan?->code)->toBe('essential_yearly')
        ->and($after->status)->toBe('active')
        ->and($after->current_period_end?->timestamp)->toBe($newEnd)
        // ⚠️ AND THE TERMS FOLLOWED THE PLAN, not the stale event — the re-capture rides the same write.
        ->and((int) $after->terms_price_minor)->toBe(49000);
});

it('⚠️⚠️ is unaffected by a TIE — two events in the SAME second, which no timestamp could order', function () {
    // ⚠️ THE TIE IS TESTED, NOT REASONED. Stripe's `created` is a UNIX SECOND and the subscription object
    //    carries no version or sequence field, so a watermark could not have ordered these at all: it would
    //    have had to either apply the older (defeating itself) or drop a real event.
    $f = woFixture();
    $orgId = (string) $f->org->id;
    $yearly = Plan::query()->where('code', 'essential_yearly')->firstOrFail();
    $monthly = Plan::query()->where('code', 'essential_monthly')->firstOrFail();

    woStripeTruth((string) $yearly->stripe_price_id, $orgId, 'active', 2_000_000_000);

    $sameSecond = 1_700_000_000;
    woWebhook('customer.subscription.updated', $orgId,
        woEventObject((string) $yearly->stripe_price_id, $orgId, 'active', 2_000_000_000), $sameSecond, 'evt_tie_a');
    woWebhook('customer.subscription.updated', $orgId,
        woEventObject((string) $monthly->stripe_price_id, $orgId, 'past_due', 1_000_000_000), $sameSecond, 'evt_tie_b');

    $after = Subscription::query()->latest()->first();

    expect($after?->plan?->code)->toBe('essential_yearly')->and($after->status)->toBe('active');
});

it('⚠️ SKIPS NOTHING — both out-of-order events are recorded and both applied', function () {
    // ⚠️ THE FAILURE MODE A WATERMARK WOULD HAVE INTRODUCED IS A DROPPED EVENT, after which the app diverges
    //    from Stripe permanently. There is no drop branch here at all: every delivery is processed.
    $f = woFixture();
    $orgId = (string) $f->org->id;
    $yearly = Plan::query()->where('code', 'essential_yearly')->firstOrFail();

    woStripeTruth((string) $yearly->stripe_price_id, $orgId, 'active', 2_000_000_000);

    woWebhook('customer.subscription.updated', $orgId,
        woEventObject((string) $yearly->stripe_price_id, $orgId, 'active', 2_000_000_000), 1_700_000_200, 'evt_rec_new');
    woWebhook('customer.subscription.updated', $orgId,
        woEventObject((string) $yearly->stripe_price_id, $orgId, 'past_due', 1_000_000_000), 1_700_000_100, 'evt_rec_old');

    $recorded = WebhookEvent::query()->whereIn('external_event_id', ['evt_rec_new', 'evt_rec_old'])->get();

    expect($recorded)->toHaveCount(2)
        ->and($recorded->pluck('status')->unique()->all())->toBe(['processed']);
});

it('⚠️ RE-DELIVERY is still idempotent — the same event twice changes nothing further', function () {
    $f = woFixture();
    $orgId = (string) $f->org->id;
    $yearly = Plan::query()->where('code', 'essential_yearly')->firstOrFail();
    woStripeTruth((string) $yearly->stripe_price_id, $orgId, 'active', 2_000_000_000);
    $object = woEventObject((string) $yearly->stripe_price_id, $orgId, 'active', 2_000_000_000);

    woWebhook('customer.subscription.updated', $orgId, $object, 1_700_000_000, 'evt_same');
    $first = Subscription::query()->latest()->first();
    woWebhook('customer.subscription.updated', $orgId, $object, 1_700_000_000, 'evt_same');
    $second = Subscription::query()->latest()->first();

    expect($second?->terms_captured_at?->toIso8601String())->toBe($first->terms_captured_at?->toIso8601String())
        ->and(WebhookEvent::query()->where('external_event_id', 'evt_same')->count())->toBe(1);
});

it('⚠️⚠️ says so LOUDLY when it cannot read the subscription back, and never silently', function () {
    // ⚠️ The fake FAILS CLOSED, so not registering `sub_order` reproduces the degraded path exactly.
    $f = woFixture();
    $orgId = (string) $f->org->id;
    $monthly = Plan::query()->where('code', 'essential_monthly')->firstOrFail();

    Log::spy();

    woWebhook('customer.subscription.updated', $orgId,
        woEventObject((string) $monthly->stripe_price_id, $orgId, 'active', 1_000_000_000), 1_700_000_000);

    Log::shouldHaveReceived('warning')->once()
        ->withArgs(fn (string $m): bool => str_contains($m, 'Could not read the Stripe subscription back'));

    // ⚠️ AND IT STILL APPLIES THE EVENT. The payload is Stripe's own SIGNED statement — evidence, only older
    //    — so the customer is not left unprocessed because a read-back was unavailable.
    expect(Subscription::query()->latest()->first()?->plan?->code)->toBe('essential_monthly');
});
