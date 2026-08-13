<?php

/*
 * DO-PROBE-1 — THE VALIDATION HARNESS FOR THE COMPOSITED-PLATE SAMPLER (2026-08-13).
 *
 * ⚠️ THIS FILE IS WHY THE TOOL MAY BE BELIEVED. Three DOM-based probes failed in a single pass (`2bb4b54`), and
 * the third one PASSED ITS ANCHORS while scoring a white button label 1.2 on a cream plate — it had resolved a
 * card's gradient instead of the button's own. **Anchor-validation proves a tool can measure SOMETHING; it does
 * not prove it measured the RIGHT PLATE.** So this harness checks the anchors AND the two cases that actually
 * break probes, and it runs against COMMITTED fixtures so a future session re-verifies the instrument in
 * seconds rather than re-deriving it and re-hitting the trap.
 *
 * The fixture PNGs are real Chromium screenshots of `tests/Fixtures/probe/fixture.html`, taken full-page at
 * `scale: css` so one image pixel is one CSS pixel — the coordinate contract the sampler enforces.
 */

function sampler(string $png, string $candidates): array
{
    $out = shell_exec(sprintf(
        'php %s %s %s --report-all 2>&1',
        escapeshellarg(base_path('tools/contrast-sampler.php')),
        escapeshellarg(base_path('tests/Fixtures/probe/'.$png)),
        escapeshellarg(base_path('tests/Fixtures/probe/'.$candidates)),
    ));

    return json_decode((string) $out, true) ?? [];
}

it('⚠️ VALIDATION 1 — reproduces the known ANCHORS (proves it can see something at all)', function () {
    $r = sampler('fixture.png', 'candidates.json');

    // The two published self-validating anchors. If these do not come back exactly, the tool is blind and
    // NOTHING it reports may be trusted — this is the floor, not the ceiling.
    expect($r['ratios']['a1'])->toBe(5.18)      // white on the flat accent
        ->and($r['ratios']['a2'])->toBe(15.4);  // ink on flat white
});

it('⚠️ VALIDATION 2 — THE HARD CASE: a gradient BUTTON is read on the BUTTON’S OWN plate', function () {
    $r = sampler('fixture.png', 'candidates.json');

    $btn = collect($r['failures'])->firstWhere('text', 'btn');

    // ⚠️ THE CHECK ANCHOR-VALIDATION MISSES. Attempt 3 scored this label 1.2 on "cream" — an ancestor card's
    // plate. The plate here must be the accent GRADIENT painted by the button itself.
    expect($btn['plate'])->toBe('rgb(225,104,50)');

    // …and the resulting ratio matches the value the design record independently documents for
    // white-on-accent-gradient (≈3.4), which is a THIRD anchor arrived at from a different direction.
    expect($r['ratios']['btn'])->toBeGreaterThan(3.2)->toBeLessThan(3.6);
});

it('⚠️ VALIDATION 3 — a BODY-GRADIENT page is MEASURED, never a blind zero', function () {
    $r = sampler('fixture.png', 'candidates.json');

    // ⚠️ Attempt 2's trap: excluding gradients made 166 elements unmeasurable and reported "0 failures" — a
    // clean bill indistinguishable from a clean page. Text sitting directly on the body gradient must be
    // MEASURED, and measured correctly: dark ink on that gradient is comfortably legible.
    expect($r['measured'])->toBeGreaterThan(0)
        ->and($r['ratios']['good'])->toBeGreaterThan(4.5);
});

it('⚠️ FAIL LOUD — an unmeasurable element reports UNMEASURABLE, never a plausible number', function () {
    $r = sampler('fixture.png', 'candidates.json');

    // The off-screen element would read as BLACK if sampled unchecked, and score a dramatic, fictional failure.
    expect($r['unmeasurable'])->toBe(1)
        ->and($r['unmeasurableReasons'])->toHaveKey('outside-screenshot')
        ->and(collect($r['failures'])->firstWhere('text', 'off'))->toBeNull()
        ->and($r['ratios'])->not->toHaveKey('off');

    // ⚠️ AND THE COUNTS TRAVEL WITH THE RESULT, so "0 failures / 0 measured" cannot be reported as a clean bill.
    expect($r)->toHaveKeys(['measured', 'failed', 'unmeasurable']);
});

it('⚠️ NON-VACUITY — a genuinely low-contrast element IS flagged, and clears when darkened', function () {
    $before = sampler('fixture.png', 'candidates.json');
    $after = sampler('fixture-mutated.png', 'candidates-mutated.json');

    // Pale grey on cream — flagged. A tool that flags nothing would satisfy every other test here.
    expect($before['ratios']['bad'])->toBeLessThan(2.0)
        ->and(collect($before['failures'])->firstWhere('text', 'bad'))->not->toBeNull();

    // Darkened to the AA token value — the flag clears. The verdict tracks the pixels, not the fixture.
    expect($after['ratios']['bad'])->toBeGreaterThan(4.5)
        ->and(collect($after['failures'])->firstWhere('text', 'bad'))->toBeNull();
});

it('⚠️ NO FALSE POSITIVE ON A GRADIENT — legible text passes, and flags only when actually weakened', function () {
    $before = sampler('fixture.png', 'candidates.json');
    $after = sampler('fixture-mutated.png', 'candidates-mutated.json');

    // Attempt 1 fell through to white here and reported 1.00 for text that is perfectly legible.
    expect($before['ratios']['good'])->toBeGreaterThan(4.5);

    // Weakened to mid-grey on the same gradient — now it fails, so the pass above was not indifference.
    expect($after['ratios']['good'])->toBeLessThan(2.5)
        ->and(collect($after['failures'])->firstWhere('text', 'good'))->not->toBeNull();
});

it('⚠️ THE COORDINATE CONTRACT IS CHECKED, NOT ASSUMED — a scale mismatch REFUSES to sample', function () {
    $bad = base_path('tests/Fixtures/probe/candidates-badsize.json');
    file_put_contents($bad, str_replace('"docWidth":1200', '"docWidth":1600',
        (string) file_get_contents(base_path('tests/Fixtures/probe/candidates.json'))));

    $out = shell_exec(sprintf('php %s %s %s 2>&1; echo "EXIT:$?"',
        escapeshellarg(base_path('tools/contrast-sampler.php')),
        escapeshellarg(base_path('tests/Fixtures/probe/fixture.png')),
        escapeshellarg($bad),
    ));

    @unlink($bad);

    // ⚠️ A silent mismatch would sample the wrong pixels and return perfectly plausible numbers — the single
    // most dangerous outcome available to this tool. It refuses instead.
    expect($out)->toContain('mismatch')->toContain('refusing to sample')->toContain('EXIT:3');
});
