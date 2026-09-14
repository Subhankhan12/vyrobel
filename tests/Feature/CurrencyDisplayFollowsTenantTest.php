<?php

use App\Models\RatePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * F-38 / F-42 — THE CURRENCY-DISPLAY CLASS.
 *
 * ⚠️⚠️ WHY THIS CLASS SURVIVED 3,400 TESTS. Every fixture in this repo is EUR — `seedScenario()` writes
 * 'EUR' into the property and the rate plan, `book()` writes it into the reservation, and the demo tenant is
 * a euro tenant. A screen that hardcodes '€' is therefore INDISTINGUISHABLE from a screen that resolves the
 * real currency, in every test that has ever run. The defect is not that nobody looked; it is that the
 * instrument could not see it. **So the instrument is the fix, and it is a GBP fixture.**
 *
 * ⚠️ A EUR CONTROL RUNS BESIDE EVERY GBP ASSERTION. Without one, "no € appeared" and "the page rendered
 * nothing" are the same observation — the vacuous-green shape that has bitten this project repeatedly
 * (§N.4p). Each behavioural case therefore requires the euro tenant to still show €.
 *
 * ⚠️ THE SOURCE GUARDS TOKENISE AND STRIP COMMENTS. A guard that greps its own needle reads the comment
 * explaining the defect and fails forever (§N.27.5, hit three times in one session). These read what RUNS.
 */
function cdftGbpFixture(): array
{
    $s = seedScenario(2);

    // A GENUINELY GBP TENANT — the property, its rate plan, and the published rates all agree.
    $s['property']->forceFill(['currency' => 'GBP'])->save();
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();
    $plan->forceFill(['currency' => 'GBP'])->save();

    foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $d) {
        DB::table('rate_calendar')->updateOrInsert(
            ['rate_plan_id' => $plan->id, 'date' => $d],
            ['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id,
                'price_minor' => 18000, 'currency' => 'GBP', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    $user = operatorUser($s, [], 'Revenue Manager');

    return ['s' => $s, 'user' => $user, 'plan' => $plan,
        'session' => ['current_property_id' => $s['property']->id]];
}

function cdftEurFixture(): array
{
    $s = seedScenario(2);
    $plan = RatePlan::query()->where('room_type_id', $s['roomType']->id)->firstOrFail();

    foreach (['2026-09-01', '2026-09-02', '2026-09-03'] as $d) {
        DB::table('rate_calendar')->updateOrInsert(
            ['rate_plan_id' => $plan->id, 'date' => $d],
            ['id' => (string) Str::ulid(), 'organization_id' => $s['org']->id,
                'price_minor' => 18000, 'currency' => 'EUR', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    $user = operatorUser($s, [], 'Revenue Manager');

    return ['s' => $s, 'user' => $user, 'plan' => $plan,
        'session' => ['current_property_id' => $s['property']->id]];
}

function cdftGrid(array $f): string
{
    return test()->actingAs($f['user'])->withSession($f['session'])
        ->get('/app/rates?from=2026-09-01')->assertOk()->getContent();
}

/**
 * ⚠️ F-38 ITSELF. `RatesGrid::unitRows()` printed every per-unit cell through `money($minor, 'EUR')` — a
 * LITERAL handed to a formatter whose currency argument is REQUIRED — while the TYPE row six lines above it
 * had always passed the real `$rate['currency']`. So one grid showed two currencies for one price, and on a
 * GBP property BOTH rows were wrong in the unit half.
 */
test('the rates grid prices a GBP property in pounds, in the unit rows as well as the type rows', function (): void {
    $html = cdftGrid(cdftGbpFixture());

    expect($html)->toContain('£180.00');

    // ⚠️ NOT "contains no €" — the page's own chrome may legitimately carry one elsewhere. This asserts the
    // euro spelling of THIS figure is absent, which is exactly what F-38 produced.
    expect($html)->not->toContain('€180.00');
});

/** The positive control — without it the assertion above passes on an empty page. */
test('the same grid still prices a EUR property in euros', function (): void {
    $html = cdftGrid(cdftEurFixture());

    expect($html)->toContain('€180.00');
    expect($html)->not->toContain('£180.00');
});

/**
 * ⚠️ THE STRUCTURAL GUARD — the answer to "can a guard make the wrong call impossible?".
 *
 * It cannot be made impossible at the TYPE level: `MoneyFormatter::display(int, string)` already REQUIRES a
 * currency, and F-38 satisfied that requirement with a literal. A required argument stops you forgetting the
 * currency; it cannot stop you asserting the wrong one. The only thing that closes the gap is a rule about
 * what may appear AT a call site — which is what this is.
 */
test('no currency literal is passed to a money formatter anywhere in app/', function (): void {
    $offenders = [];

    foreach (cdftPhpSources() as $path => $source) {
        foreach (explode("\n", $source) as $i => $line) {
            if (preg_match('/(?:money|signedMoney|display|compact|currencySymbol|symbol)\s*\([^)]*?[\'"](?:EUR|GBP|USD)[\'"]/', $line) === 1) {
                $offenders[] = $path.':'.($i + 1).'  '.trim($line);
            }
        }
    }

    expect($offenders)->toBe([], "A currency literal reached a money formatter:\n".implode("\n", $offenders));
});

/**
 * ⚠️ AND THE SYMBOL ITSELF, IN TENANT-FACING VIEWS. The formatter guard above cannot see a '€' typed
 * straight into a Blade template, an input label, or an Alpine `x-text` — which is where 15 of the 21 sites
 * actually lived. Marketing pages are EXCLUDED BY DESIGN: `/pricing` states what Vyrobel charges in its own
 * platform currency, which is not any tenant's currency and must not follow one.
 */
test('no tenant-facing view hardcodes a currency symbol', function (): void {
    $offenders = [];

    foreach (cdftBladeSources() as $path => $source) {
        foreach (explode("\n", $source) as $i => $line) {
            if (preg_match('/[€£]/u', $line) === 1) {
                $offenders[] = $path.':'.($i + 1).'  '.trim($line);
            }
        }
    }

    expect($offenders)->toBe([], "A tenant-facing view hardcodes a currency symbol:\n".implode("\n", $offenders));
});

/**
 * ⚠️ PROSE COUNTS — F-42 was the word "euro" in a page subtitle, invisible to every symbol scan. A
 * currency NAME in tenant-facing copy is the same defect in letters.
 */
test('no tenant-facing view names a currency in its prose', function (): void {
    $offenders = [];

    foreach (cdftBladeSources() as $path => $source) {
        foreach (explode("\n", $source) as $i => $line) {
            if (preg_match('/\b(euros?|pounds?|dollars?|sterling)\b/i', $line) === 1) {
                $offenders[] = $path.':'.($i + 1).'  '.trim($line);
            }
        }
    }

    expect($offenders)->toBe([], "A tenant-facing view names a currency in prose:\n".implode("\n", $offenders));
});

// ── instruments ──────────────────────────────────────────────────────────────────────────────────────────

/**
 * PHP sources with comments BLANKED (not deleted, so line numbers survive a report). The map itself and the
 * config default are the two places a currency code legitimately appears as a literal.
 *
 * @return array<string, string>
 */
function cdftPhpSources(): array
{
    $skip = ['app/Support/Money/MoneyFormatter.php'];
    $out = [];

    foreach (cdftFiles(base_path('app'), 'php') as $file) {
        $rel = str_replace(base_path().'/', '', $file);
        if (in_array($rel, $skip, true)) {
            continue;
        }
        $out[$rel] = cdftBlankComments((string) file_get_contents($file), ['#/\*[\s\S]*?\*/#', '#//[^\n]*#']);
    }

    return $out;
}

/**
 * Blade sources with comments, <style> and <script> BLANKED, marketing excluded.
 *
 * @return array<string, string>
 */
function cdftBladeSources(): array
{
    $out = [];

    foreach (cdftFiles(base_path('resources/views'), 'blade.php') as $file) {
        $rel = str_replace(base_path().'/resources/views/', '', $file);
        if (str_starts_with($rel, 'marketing/') || str_starts_with($rel, 'components/marketing/')) {
            continue;
        }
        $out[$rel] = cdftBlankComments((string) file_get_contents($file), [
            '#\{\{--[\s\S]*?--\}\}#', '#<!--[\s\S]*?-->#', '#<style[\s\S]*?</style>#', '#<script[\s\S]*?</script>#',
        ]);
    }

    return $out;
}

/**
 * ⚠️ BLANK, DO NOT DELETE. Deleting a comment shifts every line number after it, so an offender is reported
 * at the wrong line — which is how two sweeps in this programme cited lines that did not contain the thing
 * they named.
 *
 * @param  list<string>  $patterns
 */
function cdftBlankComments(string $source, array $patterns): string
{
    foreach ($patterns as $pattern) {
        $source = (string) preg_replace_callback(
            $pattern,
            fn (array $m): string => (string) preg_replace('/[^\n]/u', ' ', $m[0]),
            $source,
        );
    }

    return $source;
}

/** @return list<string> */
function cdftFiles(string $dir, string $suffix): array
{
    $found = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));

    foreach ($it as $file) {
        if ($file->isFile() && str_ends_with($file->getPathname(), '.'.$suffix)) {
            $found[] = $file->getPathname();
        }
    }

    sort($found);

    return $found;
}
