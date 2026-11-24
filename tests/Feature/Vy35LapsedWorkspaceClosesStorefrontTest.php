<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;

// ⚠️⚠️ VY-35 (audit of 2026-11-24, read in code) — A LAPSED WORKSPACE'S STOREFRONT IS CLOSED.
//
// The paywall closed a lapsed workspace's back office (an expired trial, a cancelled or unpaid subscription) and left its public storefront
// open: `ResolvePublicProperty` closed only suspended and cancelled organisations, so bookings could arrive that nobody at the hotel could see.
// One rule now answers for all three public surfaces (StorefrontAvailability): the property storefront, the organisation landing page and the
// storefront sitemap. The rule IS the paywall's (`SubscriptionStatus::isLapsed()`), so the two cannot disagree — and it fails OPEN on a
// missing billing record, exactly as the paywall does. Every DENY below has its ALLOW beside it, in the same file: a guard proven only in
// deny may be denying everyone (R-SUSPEND's lesson).

uses(DatabaseTransactions::class);

/** A sellable storefront (publicPropertyScenario) whose organisation is given a subscription in the state named. */
function vy35World(?string $state): array
{
    $s = publicPropertyScenario(units: 1);
    $tenant = app(TenantContext::class);
    $tenant->runAsSuperAdmin(fn () => Organization::query()->whereKey($s['org']->id)->update(['status' => 'trialing']));

    if ($state !== null) {
        app(BillingService::class)->ensurePlans();
        $plan = Plan::query()->where('code', 'pro_monthly')->firstOrFail();
        [$status, $trialEnds] = match ($state) {
            'in_trial' => ['trialing', now()->addDays(5)],
            'trial_lapsed' => ['trialing', now()->subDay()],
            'active' => ['active', now()->subDays(40)],
            'past_due' => ['past_due', now()->subDays(40)],
            'canceled' => ['canceled', now()->subDays(40)],
        };
        $tenant->runAsSuperAdmin(fn () => Subscription::create([
            'organization_id' => $s['org']->id, 'plan_id' => $plan->id, 'status' => $status, 'trial_ends_at' => $trialEnds,
            'current_period_end' => now()->addDays(20),
        ]));
    }
    Cache::flush();

    return $s;
}

it('⚠️⚠️ VY-35 (as read): a workspace whose trial has lapsed shows the closed sign on its storefront — no rooms, no checkout', function () {
    $s = vy35World('trial_lapsed');
    $slug = $s['property']->slug;

    $page = $this->get("book/{$slug}")->assertNotFound();
    $page->assertSee(__('php/http/middleware/resolve_public_property.closed_title'));

    $this->get("book/{$slug}/search?check_in=2026-09-02&check_out=2026-09-04&adults=1")->assertNotFound();
    $this->postJson("book/{$slug}/quote", [
        'room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id,
        'check_in' => '2026-09-02', 'check_out' => '2026-09-04', 'adults' => 1,
    ])->assertNotFound();
});

it('⚠️ VY-35: closed for every lapsed state — past due and cancelled too', function (string $state) {
    $s = vy35World($state);

    $this->get('book/'.$s['property']->slug)->assertNotFound();
})->with(['past_due', 'canceled']);

it('VY-35 ALLOW: in a trial, paid, or with no billing record at all (the paywall fails open, and so does this) the storefront is open', function (?string $state) {
    $this->travelTo('2026-09-01 10:00:00');
    $s = vy35World($state);

    $this->get('book/'.$s['property']->slug)->assertOk()
        ->assertDontSee(__('php/http/middleware/resolve_public_property.closed_title'));
})->with(['in_trial', 'active', null]);

it('⚠️ VY-35: the organisation landing page closes with it, and opens with it', function () {
    $lapsed = vy35World('trial_lapsed');
    $this->get('stay/'.$lapsed['org']->slug)->assertNotFound();

    $this->travelTo('2026-09-01 10:00:00');
    $open = vy35World('in_trial');
    $this->get('stay/'.$open['org']->slug)->assertOk();
});

it('⚠️ VY-35: the storefront sitemap never advertises a lapsed workspace (and still lists one in its trial)', function () {
    $this->travelTo('2026-09-01 10:00:00');
    $lapsed = vy35World('trial_lapsed');
    $open = vy35World('in_trial');
    Cache::flush();

    $xml = $this->get('/sitemap-storefront.xml')->assertOk()->getContent();

    expect($xml)->not->toContain('book/'.$lapsed['property']->slug)
        ->and($xml)->not->toContain('stay/'.$lapsed['org']->slug)
        ->and($xml)->toContain('book/'.$open['property']->slug);
});
