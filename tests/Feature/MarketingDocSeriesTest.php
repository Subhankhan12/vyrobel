<?php

use App\Support\Marketing\MarketingUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Assert;

// ══ S2 · THE GUIDE / ARTICLE COUNTER ══════════════════════════════════════════════════════════════════
//
// ⚠️ WHAT THIS PROTECTS. Every doc master ends with "Guide N of 16" and every guide renders the full
// sixteen-item sibling sidebar, so each page ASSERTS THE WHOLE SET EXISTS. Zero guides are published. Until
// the counter derives from what is actually published, no subset can go out honestly — the publishable set
// was zero under every policy for that reason alone.
//
// ⚠️ THE COUNTER IS A RUNTIME DERIVATION, NOT A CONVERTER TRANSFORM, AND THAT IS A DELIBERATE DEPARTURE
// FROM §N.31.4. The doctrine says the fix belongs in the converter because a post-generation patch is undone
// by the next generation. ⚠️ But `pxbuild.py` WAS NEVER COMMITTED — `git log --all -- '*pxbuild*'` is empty
// and it lives only in a session scratchpad under /private/tmp. A rule enforced by an unversioned script
// that may be garbage-collected is not enforced at all. Deriving at runtime removes the literal from the
// generated file entirely, so there is nothing for a regeneration to restore — and the literal-counter guard
// at the bottom of this file is what makes that stick.

uses(RefreshDatabase::class);

/** Render the component in isolation — it has no consumer yet, because no doc page is published. */
function seriesHtml(string $key, string $prefix = 'guide-', string $label = 'Guide'): string
{
    return trim(Blade::render(
        '<x-marketing.series-counter :key="$key" :prefix="$prefix" :label="$label" />',
        ['key' => $key, 'prefix' => $prefix, 'label' => $label],
    ));
}

/** Register a fake published page so the route table — the counter's only source — actually changes. */
function publishSeriesPage(string $key): void
{
    Route::get('/__series/'.$key, fn (): string => 'ok')->name('marketing.page.'.$key);

    // ⚠️ WITHOUT THIS THE ROUTE IS INVISIBLE TO `Route::has()`, and every positive assertion in this file
    // silently measured the ZERO case instead — five tests failed identically until the cause was found.
    // A named route added after boot is not in the name lookup until the collection is refreshed.
    Route::getRoutes()->refreshNameLookups();
}

// ══ ⚠️ A · THE ZERO CASE — the state the product is in TODAY ══════════════════════════════════════════

it('⚠️ renders NOTHING when no guide is published — "Guide 1 of 0" must be impossible', function () {
    // ⚠️ This is not an edge case, it is the CURRENT state: all sixteen guides are deferred.
    Assert::assertSame([], MarketingUrls::publishedSeries('guide-'),
        'a guide became routable — this test is no longer measuring the zero case');

    expect(seriesHtml('guide-rates'))->toBe('');
});

it('⚠️ PROVES the component can render at all (the zero case above would otherwise prove nothing)', function () {
    // ⚠️ THE VACUITY CHECK FOR THE TEST ABOVE. "Renders nothing" passes identically on a component that is
    // broken, misnamed, or throwing into an empty string. This forces the positive.
    publishSeriesPage('guide-rates');

    expect(seriesHtml('guide-rates'))->toContain('Guide 1 of 1');
});

// ══ ⚠️ B · BOTH DIRECTIONS — publish one more, defer one ══════════════════════════════════════════════

it('⚠️ GROWS when another guide is published — the count is derived, never written down', function () {
    publishSeriesPage('guide-setup');
    publishSeriesPage('guide-rates');

    // MAP order decides position: guide-setup is first in the map, guide-rates sixth.
    expect(seriesHtml('guide-setup'))->toContain('Guide 1 of 2');
    expect(seriesHtml('guide-rates'))->toContain('Guide 2 of 2');

    publishSeriesPage('guide-checkin');

    expect(seriesHtml('guide-setup'))->toContain('Guide 1 of 3');
    expect(seriesHtml('guide-rates'))->toContain('Guide 3 of 3');
});

it('⚠️ SHRINKS when a guide is not published — a deferred page never inflates the total', function () {
    publishSeriesPage('guide-setup');
    publishSeriesPage('guide-rates');
    publishSeriesPage('guide-checkin');

    expect(MarketingUrls::publishedSeries('guide-'))->toHaveCount(3);

    // The deferred siblings — thirteen of the sixteen — contribute nothing.
    expect(seriesHtml('guide-checkin'))->toContain('of 3');
    expect(seriesHtml('guide-checkin'))->not->toContain('of 16');
});

it('⚠️ renders nothing for a page that is NOT itself published — it has no honest position', function () {
    publishSeriesPage('guide-setup');

    expect(seriesHtml('guide-setup'))->toContain('Guide 1 of 1');
    expect(seriesHtml('guide-gdpr'))->toBe('');
});

// ══ ⚠️ C · THE SERIES ARE SEPARATE ════════════════════════════════════════════════════════════════════

it('⚠️ never counts one series into another', function () {
    // ⚠️ THE THREE SCENARIO PAGES ARE GENUINELY PUBLISHED (/example-scenarios/…), which is why this test
    // reads "of 3" without registering anything. My first draft expected "1 of 1" and failed — the
    // component was right and the expectation was wrong. Left as a REAL-DATA assertion rather than a
    // synthetic one, because it proves the derivation against the live route table.
    expect(MarketingUrls::publishedSeries('story-'))->toHaveCount(3);

    publishSeriesPage('guide-setup');

    expect(seriesHtml('guide-setup'))->toContain('Guide 1 of 1');
    expect(seriesHtml('story-cyclades', 'story-', 'Scenario'))->toContain('Scenario 1 of 3');

    // …and publishing a guide does not move the scenario total, nor the reverse.
    publishSeriesPage('guide-rates');
    expect(seriesHtml('story-cyclades', 'story-', 'Scenario'))->toContain('Scenario 1 of 3');
    expect(seriesHtml('guide-setup'))->toContain('Guide 1 of 2');
});

// ══ ⚠️ D · THE LITERAL-COUNTER GUARD — what replaces the converter transform ═══════════════════════════

it('⚠️ has no HARDCODED series counter in any marketing view', function () {
    // ⚠️ THIS IS THE ZERO-MATCH RULE EXPRESSED AS A TEST. Gate 7's converter transform must fail loudly when
    // its needle matches nothing; here the equivalent obligation is that no generated page may carry a
    // literal count at all. If a future regeneration writes "Guide 3 of 16" back into a view, this reds —
    // which is the protection the unversioned converter cannot provide.
    $views = array_merge(
        glob(resource_path('views/marketing/*.blade.php')) ?: [],
        glob(resource_path('views/components/marketing/*.blade.php')) ?: [],
    );

    Assert::assertGreaterThan(30, count($views), 'the view sweep found almost nothing — it would be vacuous');

    foreach ($views as $view) {
        // ⚠️⚠️ THE COMPONENT ITSELF IS THE ONE LEGITIMATE PLACE THE PATTERN APPEARS — in the documentation
        // explaining what it renders. Excluded by name, not by weakening the needle.
        if (basename($view) === 'series-counter.blade.php') {
            continue;
        }

        // ⚠️ COMMENTS FIRST, AND ALL THREE KINDS. §N.27.5 — a source-scanning guard reads its own needle —
        // for the SIXTH time in this project, and it caught this file on its first run: the component's
        // `@props` block documents the output as "Guide 3 of 16" in a `//` comment, which a Blade-comment
        // strip does not touch.
        $src = (string) preg_replace(
            ['/\{\{--.*?--\}\}/s', '#/\*.*?\*/#s', '#//[^\n]*#'],
            '',
            (string) file_get_contents($view),
        );

        Assert::assertDoesNotMatchRegularExpression(
            '/\b(Guide|Article|Scenario)\s+\d+\s+of\s+\d+/i',
            $src,
            basename($view).' carries a HARDCODED series counter — it must use <x-marketing.series-counter>, '
            .'which derives the total from the published route set',
        );
    }
});

it('⚠️ PROVES the literal-counter needle can fire (the positive control)', function () {
    // Without this, the sweep above passes just as happily on a regex that matches nothing.
    expect(preg_match('/\b(Guide|Article|Scenario)\s+\d+\s+of\s+\d+/i', 'Guide 3 of 16'))->toBe(1);
    expect(preg_match('/\b(Guide|Article|Scenario)\s+\d+\s+of\s+\d+/i', 'Article 2 of 7'))->toBe(1);
    expect(preg_match('/\b(Guide|Article|Scenario)\s+\d+\s+of\s+\d+/i', 'Guide 3 of 16 written guides'))->toBe(1);

    // …and does not fire on ordinary prose, or the sweep would forbid writing about guides at all.
    expect(preg_match('/\b(Guide|Article|Scenario)\s+\d+\s+of\s+\d+/i', 'a guide to the first property'))->toBe(0);
});
