<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Support\Marketing\MarketingPricing;
use Database\Seeders\BillingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;
use Symfony\Component\Finder\Finder;

/**
 * ⚠️⚠️ TWO FIXES THAT NOTHING WAS PROTECTING.
 *
 * A sweep of the suite found that **no test named `viewBox` and no test named `PlanFeature`** — so the icon
 * restoration and the move of the card feature source onto the engine's real gates could both have been
 * silently undone, the icon fix by nothing more than a converter regeneration, and neither would have
 * turned anything red.
 *
 * ⚠️ THE SHAPE OF EACH FAILURE IS QUIET. A stripped `viewBox` does not error — every icon simply renders as
 * a 16×16 window onto the top-left corner of a 24×24 canvas, which reads as "the icons look wrong" long
 * after the change that caused it. And a card built from `config/billing.php` instead of `plan_features`
 * does not error either: it advertises whatever the config says, on the page where money is decided, while
 * `Organization::planAllows()` gates on something else entirely.
 */
uses(RefreshDatabase::class);

it('⚠️⚠️ ships no <svg> without a viewBox — a regeneration would clip every icon silently', function () {
    $offenders = [];
    $total = 0;

    /** @var SplFileInfo $file */
    foreach (Finder::create()->files()
        ->in([resource_path('views/marketing'), resource_path('views/components/marketing')])
        ->name('*.blade.php') as $file) {

        // ⚠️ COMMENTS OUT FIRST — Blade's and HTML's. An `<svg>` quoted inside a comment never renders, and
        //    a guard that counted it would report a defect that does not exist. (The reverse of the trap
        //    that has fired nine times here, and the same discipline.)
        $src = (string) preg_replace(['/\{\{--.*?--\}\}/s', '/<!--.*?-->/s'], '', (string) file_get_contents($file->getRealPath()));

        foreach (preg_split('/(?=<svg\b)/', $src) ?: [] as $chunk) {
            if (! str_starts_with($chunk, '<svg')) {
                continue;
            }
            $total++;
            $tag = substr($chunk, 0, (int) strpos($chunk, '>'));
            if (! str_contains($tag, 'viewBox')) {
                $offenders[] = str_replace(base_path().'/', '', $file->getRealPath());
            }
        }
    }

    // ⚠️ THE VACUITY HALF, and it is the half that matters. "No icon lacks a viewBox" passes perfectly
    //    against a site with no icons at all — which is exactly what a bad regeneration could produce.
    Assert::assertGreaterThanOrEqual(185, $total,
        "only {$total} <svg> elements across the marketing views; 185 were counted when the icons were "
        .'restored, so a lower number means icons were lost rather than fixed');

    Assert::assertSame([], array_values(array_unique($offenders)),
        'an <svg> ships with no viewBox. Without it the browser has no coordinate system to map the 24×24 '
        .'path onto a 16×16 box, so the icon renders as a corner crop — quietly, with no error anywhere. '
        .'⚠️ The converter strips unknown attributes; if a regeneration removed these, restore the strip '
        .'exemption in `tools/marketing/convert.py`, do not re-add them by hand.');
});

it('⚠️⚠️ builds the plan cards from `plan_features`, the gate the product actually enforces', function () {
    $this->seed(BillingPlanSeeder::class);

    $labels = fn (): array => collect(MarketingPricing::tiersForDisplay('monthly'))
        ->firstWhere('key', 'pro')['features'] ?? [];

    Assert::assertContains('Channel manager', $labels(),
        'the Pro card does not list the channel manager even before the gate is changed — this test would '
        .'prove nothing');

    // ⚠️ FLIP THE REAL GATE, NOT THE CONFIG. `config/billing.php` still says `channels => enabled: true`
    //    for Pro; `plan_features` is what the platform admin edits and what `planAllows()` reads. If the
    //    card followed the config, this change would be invisible to it — and the page would promise a
    //    capability the org would not get.
    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();
    PlanFeature::query()->where('plan_id', $pro->id)->where('key', 'channels')->update(['enabled' => false]);

    Assert::assertTrue((bool) config('billing.tiers.pro.features.channels.enabled'),
        'the config no longer enables `channels` for Pro, so this test can no longer tell the two sources '
        .'apart — the whole point of it');

    Assert::assertNotContains('Channel manager', $labels(),
        'the Pro card still advertises the channel manager after the plan\'s real gate was switched OFF. '
        .'The card is reading `config/billing.php` rather than `plan_features` — two sources for one '
        .'concept, on a money surface (§N.8f).');
});

it('⚠️ still falls back to the catalogue when a plan has no `plan_features` rows at all', function () {
    // ⚠️ The config-only path must keep working: an empty table must not blank every card. Guarded because
    //    the obvious "fix" for the test above — dropping the fallback — would break this silently.
    $this->seed(BillingPlanSeeder::class);

    $pro = Plan::query()->where('code', 'pro_monthly')->firstOrFail();
    PlanFeature::query()->where('plan_id', $pro->id)->delete();

    $features = collect(MarketingPricing::tiersForDisplay('monthly'))->firstWhere('key', 'pro')['features'] ?? [];

    Assert::assertContains('Channel manager', $features,
        'a plan with no `plan_features` rows rendered a blank card instead of falling back to the catalogue');
});
