<?php

declare(strict_types=1);

use App\Models\AuditEvent;
use App\Models\Folio;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\ReconciliationRun;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeSubscription;
use App\Services\BillingService;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Money\Money;
use App\Support\Reconciliation\BillingReconciler;
use App\Support\Reconciliation\CheckResult;
use App\Support\Retention\RetentionPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

/**
 * ⚠️⚠️ THE RECONCILIATION, PROVEN AGAINST CONSTRUCTED DRIFT (docs/RECONCILIATION.md).
 *
 * ⚠️ NOTHING HAS EVER BEEN THROUGH STRIPE — `stripe_subscription_id` NULL, `stripe_customer_id` NULL,
 * `webhook_events` 0. A run today therefore finds nothing, and **a green run on an empty world is
 * vacuous**. Every check below is proven twice: the drift is BUILT and the check must find it, and
 * agreement is built and the check must stay quiet. A check that always reports drift is as useless as one
 * that never does.
 *
 * ⚠️ Unique ids per call and a rolled-back transaction — `RefreshDatabase` is deliberately disabled
 * (tests/Pest.php:23) and this suite's database persists between runs.
 */
uses(DatabaseTransactions::class);

function rcRun(): array
{
    return app(BillingReconciler::class)->run();
}

function rcCheck(string $key): CheckResult
{
    $found = collect(rcRun())->firstWhere('key', $key);

    expect($found)->not->toBeNull("the reconciler has no check named [{$key}]");

    return $found;
}

function rcSuper(callable $read): mixed
{
    return app(TenantContext::class)->runAsSuperAdmin($read);
}

/** A subscription anchored to a unique Stripe id, on a known plan. */
function rcSubscription(string $planCode = 'pro_monthly'): object
{
    $subId = 'sub_'.Str::lower(Str::random(22));
    $org = newDemoOrg();
    app(BillingService::class)->ensurePlans();
    $plan = Plan::query()->where('code', $planCode)->firstOrFail();

    $subscription = Subscription::create([
        'organization_id' => $org->id, 'plan_id' => $plan->id, 'status' => 'active',
        'trial_ends_at' => now()->subDays(40), 'stripe_subscription_id' => $subId,
        'stripe_customer_id' => 'cus_'.Str::lower(Str::random(18)),
        'current_period_end' => now()->addDays(20),
    ]);

    return (object) ['org' => $org, 'subscription' => $subscription, 'subId' => $subId, 'plan' => $plan];
}

function rcStripeSays(string $subId, string $status, string $priceId): void
{
    app(StripeClientInterface::class)->subscriptions[$subId] = new StripeSubscription(
        id: $subId, status: $status, customerId: 'cus_'.Str::lower(Str::random(18)),
        currentPeriodEnd: now()->addDays(20)->timestamp, priceIds: [$priceId],
    );
}

// ══ 1 · subscription plan vs the Stripe Price — BILLING-PURCHASED-PLAN ══════════════════════════════

it('⚠️⚠️ FINDS a subscription whose plan disagrees with the Stripe Price being billed', function () {
    $f = rcSubscription('pro_monthly');
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();

    // The drift the purchased-plan pass measured: Stripe bills Essential, the row still says Pro.
    rcStripeSays($f->subId, 'active', (string) $essential->stripe_price_id);

    $check = rcCheck('subscription_plan_vs_stripe');

    expect($check->disagreements)->not->toBeEmpty()
        ->and($check->disagreements[0]['detail'])->toContain('essential_monthly')
        ->and($check->isClear())->toBeFalse();
});

it('⚠️ THE OTHER HALF — a subscription whose plan AGREES is not reported', function () {
    $f = rcSubscription('pro_monthly');
    rcStripeSays($f->subId, 'active', (string) $f->plan->stripe_price_id);

    $check = rcCheck('subscription_plan_vs_stripe');

    expect($check->disagreements)->toBeEmpty()
        ->and($check->examined)->toBeGreaterThan(0);
});

// ══ 2 · subscription status vs Stripe — the ordering + inert-handler passes ══════════════════════════

it('⚠️⚠️ FINDS an account this product calls active that Stripe has cancelled', function () {
    $f = rcSubscription();
    rcStripeSays($f->subId, 'canceled', (string) $f->plan->stripe_price_id);

    $check = rcCheck('subscription_status_vs_stripe');

    expect($check->disagreements)->not->toBeEmpty()
        ->and($check->disagreements[0]['detail'])->toContain('canceled');
});

it('⚠️ THE OTHER HALF — a status that agrees is not reported', function () {
    $f = rcSubscription();
    rcStripeSays($f->subId, 'active', (string) $f->plan->stripe_price_id);

    expect(rcCheck('subscription_status_vs_stripe')->disagreements)->toBeEmpty();
});

// ══ 3 · ⚠️⚠️ UNREACHABLE IS NOT CLEAR ═══════════════════════════════════════════════════════════════

it('⚠️⚠️ reports UNCHECKED — never "agrees" — when Stripe does not know the subscription', function () {
    // ⚠️ The fake FAILS CLOSED: an unregistered id returns null rather than synthesising agreement. Not
    //    registering it reproduces an unreachable Stripe exactly.
    $f = rcSubscription();

    $check = rcCheck('subscription_status_vs_stripe');

    expect($check->unchecked)->not->toBeEmpty()
        ->and($check->disagreements)->toBeEmpty()
        // ⚠️ THE WHOLE POINT: it examined something and reported nothing agreed.
        ->and($check->agreed())->toBe(0)
        ->and($check->isClear())->toBeFalse('unchecked must never read as clear');
});

it('⚠️⚠️ the SUMMARY distinguishes "all agreed" from "nothing could be checked"', function () {
    $f = rcSubscription();   // deliberately NOT registered with the fake

    $results = rcRun();
    $unchecked = array_sum(array_map(fn (CheckResult $r): int => count($r->unchecked), $results));
    $agreed = array_sum(array_map(fn (CheckResult $r): int => $r->agreed(), $results));

    expect($unchecked)->toBeGreaterThan(0);

    // Register it and the same world reports agreement instead — the two are genuinely different states.
    rcStripeSays($f->subId, 'active', (string) $f->plan->stripe_price_id);

    $after = rcRun();
    $uncheckedAfter = array_sum(array_map(fn (CheckResult $r): int => count($r->unchecked), $after));

    expect($uncheckedAfter)->toBeLessThan($unchecked)
        ->and(array_sum(array_map(fn (CheckResult $r): int => $r->agreed(), $after)))->toBeGreaterThan($agreed);
});

// ══ 4 · owner payout flag vs Connect — CONNECT-ACCOUNT-ORDERING ═════════════════════════════════════

it('⚠️⚠️ FINDS an owner this product believes may be paid out when Stripe says otherwise', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 18]);

    rcSuper(fn () => Owner::query()->whereKey($owner->getKey())->update([
        'stripe_connect_account_id' => 'acct_'.Str::lower(Str::random(16)),
        'stripe_connect_payouts_enabled' => true,
    ]));

    // The fake holds no such account, so Stripe reports payouts DISABLED — the drift the Connect pass found.
    $check = rcCheck('owner_payouts_flag_vs_stripe');

    expect($check->disagreements)->not->toBeEmpty()
        ->and($check->disagreements[0]['detail'])->toContain('payouts_enabled=false');
});

it('⚠️ THE OTHER HALF — an owner whose flag agrees with Stripe is not reported', function () {
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 18]);
    $account = 'acct_'.Str::lower(Str::random(16));

    rcSuper(fn () => Owner::query()->whereKey($owner->getKey())->update([
        'stripe_connect_account_id' => $account, 'stripe_connect_payouts_enabled' => true,
    ]));
    app(StripeClientInterface::class)->connectAccounts[$account] = true;

    expect(rcCheck('owner_payouts_flag_vs_stripe')->disagreements)->toBeEmpty();
});

// ══ 5 · the LOCAL checks — no network, and they work when Stripe does not ═══════════════════════════

it('⚠️⚠️ FINDS a webhook delivery that never reached processed — and stays quiet when all did', function () {
    $before = rcCheck('webhook_events_unprocessed');
    expect($before->disagreements)->toBeEmpty();

    rcSuper(fn () => WebhookEvent::create([
        'source' => 'stripe', 'external_event_id' => 'evt_'.Str::lower(Str::random(20)),
        'event_type' => 'invoice.payment_failed', 'signature_verified' => true,
        'payload' => [], 'status' => WebhookEvent::STATUS_FAILED,
        'received_at' => now(), 'error' => 'constructed drift',
    ]));

    $after = rcCheck('webhook_events_unprocessed');

    expect($after->disagreements)->not->toBeEmpty()
        ->and($after->disagreements[0]['detail'])->toContain('constructed drift');
});

it('⚠️⚠️ FINDS a settled payment whose audit row is missing — and stays quiet when it is there', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 30000);
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(30000, 'EUR'));

    expect(rcCheck('settlement_without_audit')->disagreements)->toBeEmpty();

    // ⚠️ Constructed drift: the settlement stands, its trail is removed — exactly the post-commit hole.
    rcSuper(fn () => AuditEvent::query()->where('subject_id', (string) $payment->getKey())->delete());

    $check = rcCheck('settlement_without_audit');

    expect(collect($check->disagreements)->pluck('subject'))->toContain((string) $payment->getKey());
});

it('⚠️⚠️ FINDS a succeeded refund with no owner-ledger adjustment — the ACCOUNTING half', function () {
    $s = seedScenario(1);
    ownUnit($s['units'][0], ['commission_pct' => 18]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 30000);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    $payment = app(FolioService::class)->recordPayment($folio, Money::of(30000, 'EUR'));

    // A refund that SUCCEEDED with no `refund:{id}:*` adjustment behind it — the ledger debit that was lost.
    $refund = Payment::create([
        'organization_id' => $folio->organization_id, 'folio_id' => $folio->getKey(),
        'reservation_id' => $folio->reservation_id, 'type' => 'refund', 'method' => 'card',
        'status' => 'succeeded', 'amount_minor' => -10000, 'currency' => 'EUR',
        'gateway' => 'stripe', 'gateway_ref' => 're_'.Str::lower(Str::random(20)),
        'refunds_payment_id' => $payment->getKey(), 'processed_at' => now(),
    ]);

    $check = rcCheck('refund_without_owner_adjustment');

    expect(collect($check->disagreements)->pluck('subject'))->toContain((string) $refund->getKey());
});

it('⚠️⚠️ FINDS two plans resolving to one (tier, interval) — the collision a validator cannot reach', function () {
    app(BillingService::class)->ensurePlans();

    expect(rcCheck('plan_tier_collision')->disagreements)->toBeEmpty();

    // ⚠️ Written DIRECTLY, bypassing the validator — which is precisely the gap this check covers.
    Plan::create([
        'code' => 'essential', 'name' => 'Collider', 'pricing_type' => Plan::PRICING_FLAT,
        'price_minor' => 1234, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true,
    ]);

    $check = rcCheck('plan_tier_collision');

    expect($check->disagreements)->not->toBeEmpty()
        ->and($check->disagreements[0]['detail'])->toContain('same tier and cycle');
});

it('⚠️ the LOCAL checks still work when Stripe is unreachable', function () {
    // Nothing registered with the fake at all. The four local checks must still return real counts.
    $local = collect(rcRun())->reject(fn (CheckResult $r): bool => $r->needsStripe);

    expect($local)->toHaveCount(4)
        ->and($local->every(fn (CheckResult $r): bool => $r->unchecked === []))->toBeTrue();
});

// ══ 6 · the NON-VACUITY floor, and it must not be silent itself ═════════════════════════════════════

it('⚠️⚠️ states how many objects it EXAMINED — "no drift" across zero is not a result', function () {
    $s = seedScenario(1);
    app(BillingService::class)->ensurePlans();
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 30000);
    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    app(FolioService::class)->recordPayment($folio, Money::of(30000, 'EUR'));

    $results = rcRun();
    $examined = array_sum(array_map(fn (CheckResult $r): int => $r->examined, $results));

    expect($examined)->toBeGreaterThan(0);

    // ⚠️ AND WHERE IT IS ACTUALLY READ. A mutation that zeroed only the SERIALISED count came back green
    //    because this assertion read the object. The floor has to hold in the report and in the row too —
    //    those are what a founder sees.
    $serialised = array_sum(array_map(fn (CheckResult $r): int => (int) $r->toArray()['examined'], $results));
    expect($serialised)->toBe($examined);

    $this->artisan('billing:reconcile')->assertSuccessful();
    $run = rcSuper(fn () => ReconciliationRun::query()->latest('started_at')->first());

    expect($run->examined)->toBeGreaterThan(0)
        ->and(array_sum(array_map(fn (array $c): int => (int) $c['examined'], $run->report)))->toBe($run->examined);
});

it('⚠️⚠️ writes a run row so "the report was empty" and "the report never ran" differ', function () {
    $before = rcSuper(fn (): int => ReconciliationRun::query()->count());

    $this->artisan('billing:reconcile')->assertSuccessful();

    $run = rcSuper(fn () => ReconciliationRun::query()->latest('started_at')->first());

    expect(rcSuper(fn (): int => ReconciliationRun::query()->count()))->toBe($before + 1)
        ->and($run->status)->toBe(ReconciliationRun::STATUS_COMPLETED)
        ->and($run->started_at)->not->toBeNull()
        ->and($run->finished_at)->not->toBeNull()
        ->and($run->report)->toBeArray();
});

it('⚠️ a run that throws midway leaves a FAILED row carrying the reason, not silence', function () {
    $this->mock(BillingReconciler::class, function ($mock): void {
        $mock->shouldReceive('run')->andThrow(new RuntimeException('constructed midway failure'));
    });

    $this->artisan('billing:reconcile')->assertFailed();

    $run = rcSuper(fn () => ReconciliationRun::query()->latest('started_at')->first());

    expect($run->status)->toBe(ReconciliationRun::STATUS_FAILED)
        ->and($run->error)->toContain('constructed midway failure')
        ->and($run->started_at)->not->toBeNull();
});

it('⚠️ the command exits NON-ZERO when it finds drift, so a scheduler notices', function () {
    rcSuper(fn () => WebhookEvent::create([
        'source' => 'stripe', 'external_event_id' => 'evt_'.Str::lower(Str::random(20)),
        'event_type' => 'invoice.paid', 'signature_verified' => true,
        'payload' => [], 'status' => WebhookEvent::STATUS_FAILED, 'received_at' => now(), 'error' => 'drift',
    ]));

    $this->artisan('billing:reconcile')->assertFailed();
});

// ══ 7 · read-only, and off the network surface ══════════════════════════════════════════════════════

it('⚠️⚠️ is READ-ONLY — it touches nothing but its own run row', function () {
    // ⚠️ THIS PROBE HAD TO BE STRENGTHENED. Its first version registered the SAME price, so the plan
    //    agreed and the plan-comparison branch never executed — a mutation making that branch REPAIR the
    //    subscription came back GREEN, which is a gap, not a result. Every drift the reconciler can find
    //    must actually be present, or "it did not write" only proves "it did not look".
    $f = rcSubscription('pro_monthly');
    $essential = Plan::query()->where('code', 'essential_monthly')->firstOrFail();

    // Plan drift AND status drift, so both Stripe checks reach their comparison bodies.
    rcStripeSays($f->subId, 'canceled', (string) $essential->stripe_price_id);

    // Local drift too, so every local check has something to find.
    rcSuper(fn () => WebhookEvent::create([
        'source' => 'stripe', 'external_event_id' => 'evt_'.Str::lower(Str::random(20)),
        'event_type' => 'invoice.paid', 'signature_verified' => true,
        'payload' => [], 'status' => WebhookEvent::STATUS_FAILED, 'received_at' => now(), 'error' => 'drift',
    ]));

    $fingerprint = fn (): array => rcSuper(fn (): array => [
        'subscriptions' => Subscription::query()->orderBy('id')->get(['id', 'plan_id', 'status', 'current_period_end'])->toJson(),
        'owners' => Owner::query()->orderBy('id')->get(['id', 'stripe_connect_payouts_enabled'])->toJson(),
        'plans' => Plan::query()->orderBy('id')->get(['id', 'code', 'is_active', 'price_minor'])->toJson(),
        'payments' => Payment::query()->orderBy('id')->get(['id', 'status', 'processed_at'])->toJson(),
        'webhook_events' => WebhookEvent::query()->orderBy('id')->get(['id', 'status', 'error'])->toJson(),
        'ledger' => OwnerLedgerEntry::query()->orderBy('id')->get(['id', 'amount_minor', 'entry_type'])->toJson(),
        'audit' => AuditEvent::query()->count(),
    ]);

    // ⚠️ THE FINGERPRINT IS TAKEN FIRST, AND THAT ORDERING IS THE TEST. Its previous version called the
    //    reconciler once to assert the drift existed and only THEN photographed the world — so a mutation
    //    that repaired during that first call had already finished before anything was recorded, and came
    //    back GREEN. Nothing may read the world through the reconciler before the baseline exists.
    $before = $fingerprint();

    $results = rcRun();
    $found = collect($results)->sum(fn (CheckResult $r): int => count($r->disagreements));

    // … it must have had REAL drift to walk over, or "it did not write" only proves "it did not look".
    expect($found)->toBeGreaterThan(1, 'the read-only probe must have real drift to walk over');

    $this->artisan('billing:reconcile');

    expect($fingerprint())->toBe($before, 'the reconciliation wrote to something other than its own run row');
});

it('⚠️ is not reachable over HTTP — a reconciliation endpoint is disclosure nobody asked for', function () {
    $matching = collect(app('router')->getRoutes())
        ->filter(fn ($r): bool => str_contains(strtolower((string) $r->uri()), 'reconcil'))
        ->map(fn ($r): string => (string) $r->uri())->values()->all();

    expect($matching)->toBeEmpty();
});

it('⚠️ its run rows follow the EXISTING retention convention rather than a new one', function () {
    // ⚠️ A nightly report is not evidence — unlike `audit_events`/`domain_events`, which are deliberately
    //    never pruned. Declared in the one place windows live.
    expect(RetentionPolicy::windows())->toHaveKey('reconciliation_runs');
});
