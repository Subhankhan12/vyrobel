<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Services\BillingService;
use App\Support\Billing\PlanIntegrity;
use App\Support\Billing\PlanTerms;
use App\Support\Marketing\MarketingPricing;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * ⚠️ THE PLAN CATALOGUE'S THREE RESIDUALS (docs/PLAN-CATALOGUE-RESIDUALS.md) — proven INDEPENDENTLY,
 * because a single test touching all three would hide which rule actually bites.
 *
 * ⚠️ Unique codes per call and a rolled-back transaction: `RefreshDatabase` is deliberately disabled
 * (tests/Pest.php:23) and this suite's database persists between runs.
 */
uses(DatabaseTransactions::class);

function pcrPlan(array $attributes = []): Plan
{
    return Plan::create(array_merge([
        'code' => 'residual_'.Str::lower(Str::random(10)),
        'name' => 'Residual probe',
        'pricing_type' => Plan::PRICING_FLAT,
        'price_minor' => 4900,
        'currency' => 'EUR',
        'interval' => 'monthly',
        'is_active' => false,
    ], $attributes));
}

// ══ 23 · a real zero is not an invented one ═════════════════════════════════════════════════════════

it('⚠️⚠️ (23) a €0 flat plan no longer claims its gross is FINAL', function () {
    $draft = pcrPlan(['price_minor' => 0]);

    $charge = $draft->chargeFor();

    // The zero is still ANSWERED — refusing would strand a plan mid-creation. Only the confidence goes.
    expect($charge['gross_minor'])->toBe(0)
        ->and($charge['tax_known'])->toBeFalse();
});

it('⚠️ (23) THE OTHER HALF — a real priced plan still states a final gross', function () {
    $charge = pcrPlan(['price_minor' => 4900])->chargeFor();

    expect($charge['gross_minor'])->toBe(4900)
        ->and($charge['tax_known'])->toBeTrue();
});

it('⚠️ (23) the rule reaches CAPTURED terms too — one calculation, not two', function () {
    // A subscription billing from a snapshot whose captured price is zero must not claim finality either.
    $terms = PlanTerms::fromCaptured([
        'terms_pricing_type' => Plan::PRICING_FLAT,
        'terms_price_minor' => 0,
        'terms_currency' => 'EUR',
    ]);

    expect($terms->charge()['tax_known'])->toBeFalse();
});

it('⚠️ (23) a €0 plan still cannot be SOLD — the pre-existing guard is unchanged', function () {
    // ⚠️ This is why declining finality forbids nothing: the product already refuses to sell a €0 flat
    //    plan, so there is no free tier for this to break.
    expect(PlanIntegrity::blockedFromActivation(pcrPlan(['price_minor' => 0])))
        ->toContain('above zero')
        ->and(PlanIntegrity::isSellable(pcrPlan(['price_minor' => 0])))->toBeFalse();
});

// ══ 25 · the derived-key collision ══════════════════════════════════════════════════════════════════

function pcrValidate(array $input, ?string $ignore = null): array
{
    return Validator::make($input, PlanIntegrity::rules($ignore))->errors()->messages();
}

function pcrInput(array $overrides = []): array
{
    return array_merge([
        'code' => 'essential', 'name' => 'X', 'pricing_type' => Plan::PRICING_FLAT,
        'interval' => 'monthly', 'currency' => 'EUR', 'price' => '12.34',
    ], $overrides);
}

it('⚠️⚠️ (25) two codes deriving to ONE (tier, interval) are REFUSED', function () {
    app(BillingService::class)->ensurePlans();   // seeds essential_monthly @ monthly

    $errors = pcrValidate(pcrInput(['code' => 'essential']));

    expect($errors)->toHaveKey('code')
        ->and($errors['code'][0])->toContain('essential_monthly');
});

it('⚠️⚠️ (25) THE OTHER HALF — every existing code is still accepted when editing itself', function () {
    app(BillingService::class)->ensurePlans();

    // ⚠️ The direction that breaks the product: a rule that reds a real row strands it un-editable.
    foreach (Plan::query()->get(['id', 'code', 'interval']) as $plan) {
        $errors = pcrValidate(
            pcrInput(['code' => $plan->code, 'interval' => $plan->interval]),
            (string) $plan->getKey(),
        );

        expect($errors)->not->toHaveKey('code', "editing {$plan->code} was refused by its own row");
    }
});

it('⚠️ (25) a genuinely new tier is still accepted', function () {
    app(BillingService::class)->ensurePlans();

    expect(pcrValidate(pcrInput(['code' => 'studio_monthly'])))->not->toHaveKey('code');
});

it('⚠️⚠️ (25) the SAME tier in a DIFFERENT cycle is ACCEPTED — the rule is per (tier, INTERVAL)', function () {
    app(BillingService::class)->ensurePlans();

    // ⚠️ THIS PROBE HAD TO BE REWRITTEN. Its first version validated a tier NOTHING occupied, so it passed
    //    whether or not the rule considered the interval at all — and a mutation making the rule
    //    interval-blind came back GREEN, which is a gap, not a result. The tier must be occupied in
    //    exactly ONE cycle for the second cycle to mean anything.
    pcrPlan(['code' => 'studio_monthly', 'interval' => 'monthly', 'is_active' => true]);

    // Occupied monthly → a monthly collision is refused …
    expect(pcrValidate(pcrInput(['code' => 'studio', 'interval' => 'monthly'])))->toHaveKey('code');

    // … while the SAME tier at the free yearly cycle is exactly what the pairing exists to allow.
    expect(pcrValidate(pcrInput(['code' => 'studio', 'interval' => 'yearly'])))->not->toHaveKey('code');
});

it('⚠️ (25) every live plan row already satisfies the rule — checked, not assumed', function () {
    app(BillingService::class)->ensurePlans();

    $keys = Plan::query()->get(['code', 'interval'])
        ->map(fn (Plan $p): string => MarketingPricing::tierKey((string) $p->code).'|'.$p->interval);

    expect($keys->count())->toBe($keys->unique()->count());
});

it('⚠️⚠️ (25) with the rule in force the pricing page renders every active plan', function () {
    app(BillingService::class)->ensurePlans();

    $active = Plan::query()->where('is_active', true)->count();
    $cards = MarketingPricing::tiersWithCycles();

    // Four plans pair into two tiers × two cycles. Nothing is dropped.
    $rendered = 0;
    foreach ($cards as $card) {
        $rendered += count(array_filter($card['cycles']));
    }

    expect($rendered)->toBe($active);
});

// ══ 26 · delete — a recommendation, not a build ═════════════════════════════════════════════════════

it('⚠️ (26) there is still NO delete path for a plan, anywhere', function () {
    // ⚠️ This is the recommendation held as a test: deactivating is reversible and honoured everywhere,
    //    while deleting a plan with subscribers 404s their billing page and a trashed plan once kept
    //    selling. If a delete is ever added it needs a restore beside it and this test should be the
    //    conversation, not a silent deletion.
    $routes = collect(app('router')->getRoutes())
        ->filter(fn ($r): bool => str_contains((string) $r->uri(), 'plans'))
        ->map(fn ($r): string => implode('|', $r->methods()).' '.$r->uri())
        ->values()->all();

    expect(collect($routes)->filter(fn (string $r): bool => str_starts_with($r, 'DELETE')))->toBeEmpty();

    $controller = file_get_contents(base_path('app/Http/Controllers/Admin/PlanController.php'));

    expect($controller)->not->toContain('->delete()')
        ->and($controller)->not->toContain('->forceDelete()')
        ->and($controller)->not->toContain('function destroy');
});

// ══ Plan management must still work through the REAL routes, after every rule added ═════════════════

it('⚠️ an admin can still CREATE, EDIT and TOGGLE a plan through the real routes', function () {
    $admin = platformAdmin();
    app(BillingService::class)->ensurePlans();
    $code = 'studio_'.Str::lower(Str::random(6)).'_monthly';

    // CREATE — a genuinely new tier passes every rule including the new one.
    $this->actingAs($admin, 'admin')->post('/admin/plans', ['reason' => 'Test fixture — a written reason, as every admin write now requires.',
        'code' => $code, 'name' => 'Studio', 'pricing_type' => Plan::PRICING_FLAT,
        'interval' => 'monthly', 'currency' => 'EUR', 'price' => '19.00',
        'stripe_price_id' => 'price_fake_'.Str::lower(Str::random(6)),
    ])->assertRedirect();

    $plan = Plan::query()->where('code', $code)->firstOrFail();
    expect($plan->is_active)->toBeFalse();   // created as a draft, as `store()` intends

    // EDIT — the same row must not be refused by its own tier key.
    $this->actingAs($admin, 'admin')->put("/admin/plans/{$plan->getKey()}", ['reason' => 'Test fixture — a written reason, as every admin write now requires.',
        'code' => $code, 'name' => 'Studio renamed', 'pricing_type' => Plan::PRICING_FLAT,
        'interval' => 'monthly', 'currency' => 'EUR', 'price' => '21.00',
        'stripe_price_id' => (string) $plan->stripe_price_id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($plan->fresh()->name)->toBe('Studio renamed');

    // TOGGLE — activation still works for a sellable plan, and deactivation still works after.
    $this->actingAs($admin, 'admin')->post("/admin/plans/{$plan->getKey()}/toggle", ['reason' => 'Test fixture — a written reason, as every admin write now requires.'])->assertRedirect();
    expect($plan->fresh()->is_active)->toBeTrue();

    $this->actingAs($admin, 'admin')->post("/admin/plans/{$plan->getKey()}/toggle", ['reason' => 'Test fixture — a written reason, as every admin write now requires.'])->assertRedirect();
    expect($plan->fresh()->is_active)->toBeFalse();
});

it('⚠️ the new rule BITES through the real create route, not only in isolation', function () {
    $admin = platformAdmin();
    app(BillingService::class)->ensurePlans();

    $this->actingAs($admin, 'admin')->post('/admin/plans', ['reason' => 'Test fixture — a written reason, as every admin write now requires.',
        'code' => 'essential', 'name' => 'Collider', 'pricing_type' => Plan::PRICING_FLAT,
        'interval' => 'monthly', 'currency' => 'EUR', 'price' => '12.34',
    ])->assertSessionHasErrors('code');

    // ⚠️ Independent check (§N.18.1) — a raw query, because `Plan` soft-deletes.
    expect(DB::table('plans')->where('code', 'essential')->count())->toBe(0);
});

it('⚠️ /pricing still renders, with three plans and correct pairing', function () {
    app(BillingService::class)->ensurePlans();

    // A third tier with only ONE interval — the shape that once 500'd the page.
    Plan::create([
        'code' => 'studio_monthly', 'name' => 'Studio', 'pricing_type' => Plan::PRICING_FLAT,
        'price_minor' => 1900, 'currency' => 'EUR', 'interval' => 'monthly', 'is_active' => true,
        'stripe_price_id' => 'price_fake_studio',
    ]);

    $this->get('/pricing')->assertOk()->assertSee('Studio');

    $cards = MarketingPricing::tiersWithCycles();
    $studio = collect($cards)->firstWhere('key', 'studio');

    expect($studio)->not->toBeNull()
        ->and($studio['cycles']['monthly'])->not->toBeNull()
        ->and($studio['cycles']['yearly'])->toBeNull();   // honest absence, not a fabricated price
});
