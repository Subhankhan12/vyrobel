<?php

declare(strict_types=1);

use App\Models\Plan;
use Database\Seeders\BillingPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Assert;
use Symfony\Component\Finder\Finder;

/**
 * ⚠️⚠️ `withoutGlobalScopes()` WITH NO ARGUMENTS ALSO REMOVES `SoftDeletingScope`.
 *
 * The framework registers soft-deletion AS A GLOBAL SCOPE. Nobody reading `withoutGlobalScopes()` thinks
 * "and trashed rows too" — they think "across tenants" — which is exactly why this is invisible on
 * inspection and why it needs a guard rather than a convention.
 *
 * ⚠️ SECOND SIGHTING IN THIS PROJECT. The first made a REFUSAL ASSERTION PASS against a removal that had
 * actually gone through: the test verified the deletion through the same scope-free query that could still
 * see the deleted row (§N.18.1 — a check must be independent of what it checks). This one put a plan an
 * admin had deleted back on the PUBLIC PRICING PAGE, with a price and a Stripe id behind it.
 *
 * ⚠️ THE CLASS GUARD IS THE POINT. An instance test naming `/pricing` catches one site; this catches the
 * shape, and would have caught the first sighting too.
 */
uses(RefreshDatabase::class);

/**
 * Every `withoutGlobalScopes()` call in production source, with comments and strings removed.
 *
 * ⚠️⚠️ TOKENISED, NOT GREPPED — and this is not defensive writing, it is a fix for a defect this project
 * has now hit NINE times. A plain scan reads its OWN needle: the very comment explaining why the bare form
 * is wrong contains the bare form, so a `grep`-shaped guard fires on its own documentation and the next
 * person "fixes" it by loosening the pattern. `token_get_all()` sees what RUNS.
 *
 * @return list<string>
 */
function sdsBareCalls(): array
{
    $offenders = [];

    /** @var SplFileInfo $file */
    foreach (Finder::create()->files()->in(app_path())->name('*.php') as $file) {
        $tokens = token_get_all((string) file_get_contents($file->getRealPath()));
        $code = [];

        foreach ($tokens as $token) {
            if (is_array($token)) {
                // ⚠️ Comments AND doc-comments out. Strings stay: a string containing this text would be
                //    strange enough to want reporting.
                if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $code[] = [$token[1], $token[2]];

                continue;
            }
            $code[] = [$token, null];
        }

        $flat = '';
        $lines = [];
        foreach ($code as [$text, $line]) {
            $lines[strlen($flat)] = $line;
            $flat .= $text;
        }

        // The bare plural form only — `withoutGlobalScopes([...])` and `withoutGlobalScope(X)` are fine.
        if (preg_match_all('/withoutGlobalScopes\s*\(\s*\)/', $flat, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as [$_, $offset]) {
                $line = null;
                foreach ($lines as $at => $n) {
                    if ($at <= $offset && $n !== null) {
                        $line = $n;
                    }
                }
                $offenders[] = str_replace(base_path().'/', '', $file->getRealPath()).':'.($line ?? '?');
            }
        }
    }

    sort($offenders);

    return $offenders;
}

it('⚠️⚠️ has no bare withoutGlobalScopes() anywhere in app/ — it silently un-hides deleted rows', function () {
    // ⚠️ THE ALLOW-LIST IS EMPTY, AND DELIBERATELY SO. One call site genuinely wants trashed rows —
    //    `TenantProvisioningService::uniqueSlug`, because `properties.slug` carries a UNIQUE index that a
    //    soft-deleted row still occupies — and it now says `->withTrashed()` OUT LOUD instead of getting
    //    it as a side effect. An intent expressed is not an exception to be listed.
    //    ⚠️ If a future call site truly needs the bare form, add it here WITH ITS REASON — never widen the
    //    pattern, which would disarm the guard for every site at once.
    $allowed = [];

    Assert::assertSame($allowed, sdsBareCalls(),
        '`withoutGlobalScopes()` with no arguments removes EVERY global scope, INCLUDING `SoftDeletingScope`. '
        .'Name the scopes to drop — `withoutGlobalScopes([OrganizationScope::class])` — or, where the model '
        .'has no tenant scope at all (`Plan`, `Organization`), just use `query()`. ⚠️ Do NOT bolt on a '
        .'`whereNull(\'deleted_at\')`: that re-implements a scope the framework already applies, and the next '
        .'caller who copies the pattern inherits the defect.');
});

it('⚠️ finds the offenders it is looking for — the guard proved non-vacuous', function () {
    // ⚠️ A guard asserting "the list is empty" passes just as well when it can no longer SEE anything.
    //    This drives the same scanner over a file that definitely contains the shape.
    $probe = base_path('tests/Feature/SoftDeleteScopeGuardTest.php');
    Assert::assertStringContainsString('withoutGlobalScopes()', (string) file_get_contents($probe),
        'this file no longer contains the needle, so the check below proves nothing');

    // ...and yet the scanner reports nothing for it, because every occurrence here is inside a comment
    // or a string — which is exactly the discrimination a `grep` cannot make.
    Assert::assertSame([], array_values(array_filter(sdsBareCalls(), fn (string $s): bool => str_contains($s, 'SoftDeleteScopeGuard'))));
});

it('⚠️ does not advertise a plan the admin deleted — asserted from the served page', function () {
    $this->seed(BillingPlanSeeder::class);

    $before = $this->get('/pricing')->assertOk()->getContent();
    Assert::assertStringContainsString('data-plan="essential_monthly"', $before,
        'the plan is not on the page even before deletion — this test would prove nothing');

    Plan::query()->where('code', 'essential_monthly')->delete();   // SoftDeletes

    $after = $this->get('/pricing')->assertOk()->getContent();
    Assert::assertStringNotContainsString('data-plan="essential_monthly"', $after,
        'a soft-deleted plan is still advertised on /pricing — with a price and a Stripe Price id behind '
        .'it — while `BillingService` (which uses `Plan::query()`) can no longer find it at all.');

    // ⚠️ AND THE REST OF THE PAGE SURVIVES: the tier keeps a card because its YEARLY plan is still sold.
    //    "Stops advertising the deleted row" must not become "drops the tier".
    Assert::assertStringContainsString('data-plan="essential_yearly"', $after,
        'deleting the monthly plan removed the whole tier, including the yearly plan that is still sold');
});

// ⚠️⚠️ NO SITEMAP INSTANCE TEST, AND THE ABSENCE IS DELIBERATE — RECORDED RATHER THAN FAKED.
//
// The sitemap's property query was one of the twelve call sites and is fixed. Whether an ARCHIVED property
// would actually have reached the served document is **UNPROVEN**: my first attempt read a `Cache::remember`d
// copy, and my second compared against a `php artisan serve` process that was still running the OLD class —
// two invalid instruments, one after the other. A downstream sellability probe may well have excluded it
// anyway.
//
// Proving it needs the storefront suite's `seoSellableTenant()` fixture (a property the ENGINE can sell:
// org, property, room type, rate plan, availability), which lives in `StorefrontSeoTest.php`. Depending on
// a function defined in another test file would make this guard pass in company and fail alone, and moving
// the helper would amend a certified suite. So: the CLASS guard above covers the call site, and the gap is
// named here instead of papered over. `StorefrontSeoTest` covers sitemap inclusion/exclusion for
// SELLABILITY and for `status`, but not for archiving.
