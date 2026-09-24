<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\Platform\PlatformQuery;
use App\Support\Platform\Revenue\PlatformRevenue;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ⚠️⚠️ ADMIN 01 · PHASE 4 — REVENUE, TRACED TO THE CHARGE: paid Stripe invoices only (the `webhook_events` rows the
// verified webhook stores), never a plan's list price; per currency; customers only; "no live billing yet" in words.

uses(DatabaseTransactions::class);

function rvOrg(bool $internal = false): Organization
{
    $org = Organization::create(['name' => 'Rev '.Str::random(6), 'slug' => 'rev-'.Str::lower(Str::random(10)), 'status' => 'active', 'default_currency' => 'EUR']);
    if ($internal) {
        $org->forceFill(['is_internal' => true])->save();
    }

    return $org;
}

/** A live-billed subscription. The plan's list price is deliberately ABSURD — it must never reach a figure. */
function rvSub(Organization $org, string $status = 'active', ?string $stripeId = null, ?CarbonImmutable $trialEnds = null): Subscription
{
    $plan = Plan::create(['code' => 'rv_'.Str::lower(Str::random(8)), 'name' => 'RV', 'price_minor' => 777_777_77, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => false]);

    return app(TenantContext::class)->forOrganization((string) $org->id, fn () => Subscription::create([
        'organization_id' => $org->id, 'plan_id' => $plan->id, 'status' => $status,
        'stripe_subscription_id' => $stripeId ?? 'sub_'.Str::random(10), 'trial_ends_at' => $trialEnds,
    ]));
}

/** A Stripe invoice event exactly as the webhook stores it. Period defaults to one month. */
function rvInvoice(Organization $org, string $subId, int $amount, string $paidAt, string $currency = 'eur', int $periodDays = 30, string $type = 'invoice.paid', string $status = 'processed'): void
{
    $at = CarbonImmutable::parse($paidAt, 'UTC');
    DB::table('webhook_events')->insert([
        'id' => (string) Str::ulid(), 'organization_id' => $org->id, 'source' => 'stripe', 'external_event_id' => 'evt_'.Str::random(16),
        'event_type' => $type, 'signature_verified' => true, 'status' => $status, 'received_at' => $at, 'created_at' => $at, 'updated_at' => $at,
        'payload' => json_encode(['type' => $type, 'data' => ['object' => [
            'object' => 'invoice', 'subscription' => $subId, 'currency' => $currency,
            $type === 'invoice.paid' ? 'amount_paid' : 'amount_due' => $amount,
            'status_transitions' => ['paid_at' => $at->timestamp],
            'lines' => ['data' => [['period' => ['start' => $at->timestamp, 'end' => $at->addDays($periodDays)->timestamp]]]],
        ]]]),
    ]);
}

function rvCompute(bool $internal = false): array
{
    test()->actingAs(platformAdmin(), 'admin');

    return app(PlatformQuery::class)->read('test.revenue', fn () => app(PlatformRevenue::class)->compute($internal));
}

it('⚠️⚠️ with no paid invoice the page says "No live billing yet" — never a confident zero', function () {
    $this->actingAs(platformAdmin(), 'admin')->get(route('admin.revenue'))
        ->assertOk()->assertSee('No live billing yet')->assertSee('That is not a bad month');
})->group('security');

it('⚠️⚠️ MRR is what Stripe CHARGED — the plan\'s list price never reaches it', function () {
    $before = rvCompute()['mrr']['ISK']['mrr_minor'] ?? 0;   // a currency of its own isolates the fixture
    $org = rvOrg();
    $sub = rvSub($org);
    rvInvoice($org, $sub->stripe_subscription_id, 4900, '2026-07-05', 'isk');
    rvInvoice($org, $sub->stripe_subscription_id, 5900, '2026-07-28', 'isk');   // the LATEST charge is the one valued

    $r = rvCompute();
    expect($r['live'])->toBeTrue()
        ->and(($r['mrr']['ISK']['mrr_minor'] ?? 0) - $before)->toBe(5900)
        ->and(json_encode($r))->not->toContain('77777777');
})->group('security');

it('⚠️ a YEARLY charge is ÷12 and a 30-day monthly one is itself — by interval, never by counting seconds — and currencies are never summed', function () {
    $before = rvCompute()['mrr'] ?? [];
    $a = rvOrg();
    $b = rvOrg();
    $subA = rvSub($a);
    $subB = rvSub($b);
    rvInvoice($a, $subA->stripe_subscription_id, 120000, '2026-07-10', 'eur', 365);
    rvInvoice($b, $subB->stripe_subscription_id, 3000, '2026-07-10', 'gbp');

    $r = rvCompute();
    $eurDelta = ($r['mrr']['EUR']['mrr_minor'] ?? 0) - ($before['EUR']['mrr_minor'] ?? 0);
    $gbpDelta = ($r['mrr']['GBP']['mrr_minor'] ?? 0) - ($before['GBP']['mrr_minor'] ?? 0);

    expect($eurDelta)->toBe(10000)   // a year's charge ÷ 12 — by interval, not by counting seconds
        ->and($gbpDelta)->toBe(3000)
        ->and($r['mrr']['EUR']['arr_minor'])->toBe($r['mrr']['EUR']['mrr_minor'] * 12);
})->group('security');

it('⚠️ INTERNAL organisations\' charges are left out unless asked for', function () {
    $mine = rvOrg(internal: true);
    $sub = rvSub($mine);
    rvInvoice($mine, $sub->stripe_subscription_id, 999_00, '2026-07-10', 'chf');

    expect(rvCompute()['mrr']['CHF'] ?? null)->toBeNull()
        ->and(rvCompute(true)['mrr']['CHF']['mrr_minor'] ?? null)->toBe(99900);
})->group('security');

it('only PROCESSED invoices count — a failed or in-flight webhook row is not a charge', function () {
    $org = rvOrg();
    $sub = rvSub($org);
    rvInvoice($org, $sub->stripe_subscription_id, 4200, '2026-07-10', 'nok', status: 'failed');
    rvInvoice($org, $sub->stripe_subscription_id, 4300, '2026-07-11', 'nok', status: 'processing');

    expect(rvCompute()['mrr']['NOK'] ?? null)->toBeNull();
})->group('security');

it('movements — new, expansion, contraction, churned — per organisation, this month against last', function () {
    // Frozen clock: 2026-08-01 → this month is August, last month July. A currency of its own isolates the fixture.
    $grow = rvOrg();
    $gs = rvSub($grow);
    rvInvoice($grow, $gs->stripe_subscription_id, 1000, '2026-07-02', 'sek');
    rvInvoice($grow, $gs->stripe_subscription_id, 1500, '2026-08-01 08:00', 'sek');
    $shrink = rvOrg();
    $ss = rvSub($shrink);
    rvInvoice($shrink, $ss->stripe_subscription_id, 2000, '2026-07-02', 'sek');
    rvInvoice($shrink, $ss->stripe_subscription_id, 1200, '2026-08-01 08:00', 'sek');
    $fresh = rvOrg();
    $fs = rvSub($fresh);
    rvInvoice($fresh, $fs->stripe_subscription_id, 700, '2026-08-01 08:00', 'sek');
    $gone = rvOrg();
    $xs = rvSub($gone, 'canceled');
    rvInvoice($gone, $xs->stripe_subscription_id, 900, '2026-07-02', 'sek');
    // ⚠️ ACTIVE, charged last month, not yet renewed this month — NOT churn. Only a cancelled subscription churns.
    $renewing = rvOrg();
    $rs = rvSub($renewing, 'active');
    rvInvoice($renewing, $rs->stripe_subscription_id, 400, '2026-07-20', 'sek');

    expect(rvCompute()['movements']['SEK'])->toBe(['new' => 700, 'expansion' => 500, 'contraction' => 800, 'churned' => 900]);
})->group('security');

it('failed payments and trial-to-paid come from the charge record too', function () {
    $r0 = rvCompute();
    $org = rvOrg();
    $sub = rvSub($org, 'active', null, CarbonImmutable::parse('2026-07-01'));
    rvInvoice($org, $sub->stripe_subscription_id, 4900, '2026-07-10', 'dkk');
    rvInvoice($org, $sub->stripe_subscription_id, 4900, '2026-07-25', 'dkk', type: 'invoice.payment_failed');
    $never = rvOrg();
    rvSub($never, 'trialing', null, CarbonImmutable::parse('2026-07-15'));

    $r = rvCompute();
    expect($r['failed_payments_30d'] - ($r0['failed_payments_30d'] ?? 0))->toBe(1)
        ->and($r['trial_to_paid']['ended'] - ($r0['trial_to_paid']['ended'] ?? 0))->toBe(2)
        ->and($r['trial_to_paid']['converted'] - ($r0['trial_to_paid']['converted'] ?? 0))->toBe(1);
})->group('security');

it('⚠️ a charge whose line STATES a yearly interval is ÷12 too — whatever its period says', function () {
    $before = rvCompute()['mrr']['HUF']['mrr_minor'] ?? 0;
    $org = rvOrg();
    $sub = rvSub($org);
    rvInvoice($org, $sub->stripe_subscription_id, 24000, '2026-07-10', 'huf', 40);   // an odd first period…
    $row = DB::table('webhook_events')->where('organization_id', $org->id)->first();
    $payload = json_decode((string) $row->payload, true);
    $payload['data']['object']['lines']['data'][0]['price'] = ['recurring' => ['interval' => 'year', 'interval_count' => 1]];   // …on a yearly price
    DB::table('webhook_events')->where('id', $row->id)->update(['payload' => json_encode($payload)]);

    expect((rvCompute()['mrr']['HUF']['mrr_minor'] ?? 0) - $before)->toBe(2000);
})->group('security');

it('the interval STRIPE STATES wins over the period length', function () {
    $before = rvCompute()['mrr']['PLN']['mrr_minor'] ?? 0;
    $org = rvOrg();
    $sub = rvSub($org);
    rvInvoice($org, $sub->stripe_subscription_id, 9000, '2026-07-10', 'pln', 31);
    // Rewrite the stored line to say "every 3 months" — a quarterly price with an odd first period.
    $row = DB::table('webhook_events')->where('organization_id', $org->id)->first();
    $payload = json_decode((string) $row->payload, true);
    $payload['data']['object']['lines']['data'][0]['price'] = ['recurring' => ['interval' => 'month', 'interval_count' => 3]];
    DB::table('webhook_events')->where('id', $row->id)->update(['payload' => json_encode($payload)]);

    expect((rvCompute()['mrr']['PLN']['mrr_minor'] ?? 0) - $before)->toBe(3000);
})->group('security');

it('⚠️ revenue is Owner, Billing and Analyst — Support is refused, and the nav agrees', function () {
    foreach (['owner' => 200, 'billing' => 200, 'analyst' => 200, 'support' => 403] as $role => $status) {
        $this->flushSession();
        expect($this->actingAs(platformAdmin(['role' => $role]), 'admin')->get(route('admin.revenue'))->getStatusCode())->toBe($status, $role);
    }
    $this->flushSession();
    $this->actingAs(platformAdmin(['role' => 'support']), 'admin')->get(route('admin.dashboard'))->assertDontSee(route('admin.revenue'));
})->group('security');
