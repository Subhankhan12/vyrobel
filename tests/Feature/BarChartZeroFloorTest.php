<?php

use Illuminate\Support\Facades\Blade;

// DO25-1 / DO23-2 — the §N.8f drift pair, and what could honestly be done about it.
//
// ⚠️ BOTH MERGES WERE REFUSED AFTER READING BOTH SITES (see DECISIONS 2026-08-16). The register calls the
// owner Reports chart "a second implementation of one concept"; it is not the same concept — it is an
// absolutely-positioned TWO-series chart (a prior-year ghost bar behind the live one) with a value that
// floats at the bar's top and a peak badge, against a flex-column single-series chart with static label
// rows. Merging means a second layout mode inside a component with five working consumers. Likewise the two
// calendar grids share a look and not an interaction model: one drag-selects a range and POSTs a real block,
// the other opens a read-only detail popover.
//
// ⚠️ BUT THE SWEEP FOUND THE REAL COST, AND IT IS BIGGER THAN THE REGISTER SAYS: there are EIGHT bar
// implementations in this product, and every one of them hand-carries the same honesty rule — a bucket worth
// ZERO must draw NOTHING. That rule exists because the owner Reports chart once applied a two-percent floor
// and five months that genuinely earned €0.00 each rendered a visible bar, contradicting the figure printed
// beside them (#39). The fix then had to be copied by hand into every other chart.
//
// ⚠️ SO WHAT IS SHARED HERE IS THE INVARIANT, NOT THE MARKUP. The layouts genuinely differ and flattening
// them would be a new defect; the rule does not differ at all. This guard holds every implementation to it,
// so the next chart — or the next edit to an existing one — cannot quietly lose it the way the first fix
// nearly did.

/** Every bar implementation that draws a height from a percentage, with the column carrying the percentage. */
const BCZ_IMPLEMENTATIONS = [
    'resources/views/components/charts/bars.blade.php' => 'vyPct',
    'resources/views/livewire/owner/reports.blade.php' => "m['pct']",
    'resources/views/livewire/owner/dashboard.blade.php' => "bar['pct']",
    'resources/views/livewire/owner/insights.blade.php' => "m['occ_pct']",
    'resources/views/livewire/owner/unit-detail.blade.php' => "b['pct']",
    'resources/views/livewire/owner/bookings.blade.php' => "m['count_pct']",
];

// ── 1. ⚠️⚠️ THE SHARED COMPONENT, EXERCISED: a zero bucket draws nothing ──────────────────────────
it('draws no bar at all for a genuinely zero bucket', function () {
    $html = Blade::render(
        '<x-charts.bars :bars="$bars" />',
        ['bars' => [
            ['label' => 'Jan', 'display' => '€0.00', 'pct' => 0],
            ['label' => 'Feb', 'display' => '€400.00', 'pct' => 100],
        ]]
    );

    // ⚠️ The zero bar renders at height:0% with NO floor — the defect was a floor that gave a €0.00 month a
    // visible stub, which reads as a small non-zero value beside a label saying zero.
    expect($html)->toContain('height:0%')
        ->and($html)->not->toContain('height:0%;min-height');

    // …while a real bucket DOES get the hairline, so the rule is a floor-for-nonzero, not a blanket removal.
    expect($html)->toContain('height:100%;min-height');
});

// ── 2. ⚠️⚠️ EVERY OTHER IMPLEMENTATION CARRIES THE SAME RULE ──────────────────────────────────────
// ⚠️ A source guard, deliberately: these charts live inside Livewire pages whose data cannot be summoned
// without heavy per-page fixtures, and the rule is a rendering conditional rather than a computed value. It
// is written to read what RUNS — Blade comments are stripped first, because several of these files explain
// the floor in prose and a naive scan would match its own needle (§N.27.5).
it('keeps the zero-floor rule in every bar implementation', function () {
    foreach (BCZ_IMPLEMENTATIONS as $path => $pctExpr) {
        $source = file_get_contents(base_path($path));
        expect($source)->not->toBeFalse();

        // Strip Blade comments AND CSS comments so prose about the rule cannot satisfy the assertion.
        $code = preg_replace('/\{\{--.*?--\}\}/s', '', (string) $source);
        $code = preg_replace('/\/\*.*?\*\//s', '', (string) $code);

        // Every min-height applied to a bar must sit behind a "> 0" test on that bar's own percentage.
        preg_match_all('/min-height:\s*[\d.]+px/', (string) $code, $found, PREG_OFFSET_CAPTURE);
        // ⚠️ A file with no floor at all would pass the loop below vacuously — pin that it HAS one.
        expect(count($found[0]) > 0)->toBeTrue("no bar floor found in {$path} — did the chart change shape?");

        foreach ($found[0] as [$match, $offset]) {
            $line = substr((string) $code, max(0, $offset - 220), 260);
            // ⚠️ `toContain` treats extra arguments as MORE needles, not a message — so the assertion is
            // made on a boolean, which is the only form that carries a useful failure line here.
            expect(str_contains($line, '> 0'))->toBeTrue("an UNCONDITIONAL {$match} in {$path} — a zero bucket would draw a visible bar (#39)");
        }
    }
});

// ── 3. ⚠️ THE ONE THAT DOES IT WITH A CLASS INSTEAD — the same rule, a different mechanism ────────
it('keeps the zero-floor rule on the class-based chart too', function () {
    $source = (string) file_get_contents(base_path('resources/views/livewire/owner/statement.blade.php'));
    $code = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);

    // The occupancy bar floors at 3px …
    expect($code)->toContain('.vy-st-occ-bar')
        // … and a zero month resets that floor to 0 rather than inheriting a visible stub.
        ->and($code)->toContain('.vy-st-occ-bar.is-zero')
        ->and($code)->toMatch('/\.vy-st-occ-bar\.is-zero\s*\{\s*min-height:\s*0/');
});
