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

function sampler(string $png, string $candidates, string $extra = ''): array
{
    $out = shell_exec(sprintf(
        'php %s %s %s --report-all '.$extra.' 2>&1',
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
    //
    // ⚠️ G8 — AMENDED BY DO-PROBE-2. This pinned the exact pixel `rgb(225,104,50)`; the clear-column method
    // selects a marginally different point of the same gradient (`rgb(227,106,52)`), so an exact string would
    // now fail for a better reading. The INVARIANT — the plate is the button's own ACCENT, never an ancestor's
    // cream — is asserted directly instead, and it still discriminates: cream is roughly (248,231,219), whose
    // green and blue are nowhere near this range.
    preg_match('/rgb\((\d+),(\d+),(\d+)\)/', $btn['plate'], $m);
    // (named plateR/G/B rather than r/g/b — `$r` already holds the sampler result in this test)
    [$plateR, $plateG, $plateB] = [(int) $m[1], (int) $m[2], (int) $m[3]];

    expect($plateR)->toBeGreaterThan(200)
        ->and($plateG)->toBeGreaterThan(60)->toBeLessThan(140)     // cream would be ~231
        ->and($plateB)->toBeGreaterThan(20)->toBeLessThan(90);     // cream would be ~219

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

/*
 * ⚠️ DO-PROBE-2 — THE THIRD CORRECTION: dense small text (2026-08-13).
 *
 * The cases above certified the tool for LARGE, PADDED, FLAT and GRADIENT-BUTTON elements — and certified
 * nothing about the class that actually broke it. In a dense 12px box the anti-alias halo is the MAJORITY of
 * non-ink pixels, so the box-interior method read the halo: on a real page it gave median 4.35 where the true
 * plate beside the same text was 4.50+, and a plate cannot be darker where the text is than where it is not.
 * That reading had already been written up as a product defect before it was caught (DO-PLATE-1).
 *
 * The plate is now read from CLEAR COLUMNS — columns whose every pixel IS the plate — beside the glyph run.
 * These fixtures pin the correction, and the legacy path is kept solely to prove the old method would have
 * missed it.
 */

/** The known answer: --vy-faint rgb(108,100,95) on the flat plate rgb(250,221,206) is exactly 4.50. */
const DENSE_KNOWN_RATIO = 4.5;

it('⚠️ DENSE SMALL TEXT — the adjacent band reads the KNOWN plate, on every line', function () {
    $r = sampler('dense.png', 'dense-candidates.json');

    // ⚠️ NON-VACUITY: all three lines are measured, none skipped.
    expect($r['measured'])->toBe(3)->and($r['unmeasurable'])->toBe(0);

    // d3 is the genuinely dense line — text across the full width, plate visible only between words.
    expect($r['ratios']['d1'])->toBe(DENSE_KNOWN_RATIO)
        ->and($r['ratios']['d2'])->toBe(DENSE_KNOWN_RATIO)
        ->and($r['ratios']['d3'])->toBe(DENSE_KNOWN_RATIO);
});

it('⚠️ THE REGRESSION WITNESS — the OLD interior method would have missed it', function () {
    $new = sampler('dense.png', 'dense-candidates.json');
    $old = sampler('dense.png', 'dense-candidates.json', '--legacy-interior');

    // The interior method reads the halo on the dense line and under-reports the plate. Pinning BOTH numbers
    // means this case fails if the fix is reverted AND documents what it was reverted from.
    expect($old['ratios']['d3'])->toBeLessThan(DENSE_KNOWN_RATIO)
        ->and($new['ratios']['d3'])->toBe(DENSE_KNOWN_RATIO);

    // ⚠️ Sparse labels never exposed the bug — which is exactly why the original fixture certified nothing.
    expect($old['ratios']['d1'])->toBe(DENSE_KNOWN_RATIO);
});

it('⚠️ THE INDEPENDENT CROSS-CHECK — the band is not darker than a hand-verified plate pixel', function () {
    // The check that caught the retraction, made permanent: read one pixel beside the glyph run directly from
    // the PNG, with no involvement of the sampler's logic, and confirm the tool did not report something
    // darker. A tool still catching halo would.
    $img = imagecreatefrompng(base_path('tests/Fixtures/probe/dense.png'));
    $rgb = imagecolorat($img, 700, 62);
    $hand = [($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255];

    expect($hand)->toBe([250, 221, 206]);   // the fixture's declared plate, read from the pixels

    $r = sampler('dense.png', 'dense-candidates.json');
    expect($r['ratios']['d1'])->toBe(DENSE_KNOWN_RATIO);
});

it('⚠️ NO CLEAR BAND ⇒ UNMEASURABLE — never a fallback to the untrustworthy interior', function () {
    // A box sitting entirely within the glyph run: there is no column of pure plate to read, so the plate
    // cannot be isolated. ⚠️ The old behaviour — fall back to averaging the interior — is exactly the halo
    // reading this correction exists to eliminate, so refusing is the only honest answer.
    $r = sampler('dense.png', 'dense-noband-candidates.json');

    expect($r['unmeasurable'])->toBe(1)
        ->and($r['unmeasurableReasons'])->toHaveKey('no-clear-band')
        ->and($r['measured'])->toBe(0)
        ->and($r['failed'])->toBe(0);

    // ⚠️ AND IT IS NOT REPORTED AS A NUMBER — a 0-measured result carries its zero openly rather than posing
    // as a clean bill.
    expect($r['ratios'])->not->toHaveKey('inside-glyphs');
});

/*
 * ⚠️ DO-PROBE-3 — GAP A (ancestor opacity) and GAP B(i) (solid plate), 2026-08-14.
 *
 * These two gaps were NAMED unmeasurable by earlier passes. They are now measured, and the fixture states its
 * answers BY HAND so the tool cannot certify itself:
 *
 *   plate #FFFFFF, text #000000, ancestor opacity .55
 *     → painted = .55*0 + .45*255 = rgb(115,115,115) → 4.74   (declared black would say 21.00)
 *   nested .55 * .50 = .275 → rgb(185,185,185) → 1.96
 *   opacity 0 → INVISIBLE: must report a reason, never ~1.0
 *
 * ⚠️ THE REAL-WORLD WITNESS is DO-A11Y-1's locked nav item, which DECLARED a colour measuring 9.40 while
 * PAINTING at 2.89. Measuring the declared colour there was the worst false clean bill this tool has produced.
 */
const PROBE3_DIMMED_EXPECT = ['plain' => 21.0, 'dim' => 4.74, 'nested' => 1.96];

it('⚠️ GAP A — an ancestor-dimmed element is MEASURED at what it PAINTS, not what it declares', function () {
    $r = sampler('dimmed.png', 'dimmed-candidates.json');

    // ⚠️ NON-VACUITY: three measured, one refused — not an empty result posing as a pass.
    expect($r['measured'])->toBe(3)->and($r['unmeasurable'])->toBe(1);

    foreach (PROBE3_DIMMED_EXPECT as $key => $expected) {
        // (float) because JSON hands back 21 as an int while the hand-computed constant is 21.0 — a strict
        // toBe() would fail on a value that is exactly right.
        expect((float) $r['ratios'][$key])->toBe($expected, "{$key} did not hit its hand-computed value");
    }

    // ⚠️ THE POINT OF THE WHOLE GAP: declared-vs-painted differ by a factor of four here. A tool reporting
    // 21.00 for `dim` would be describing a colour that is not on the screen.
    expect($r['ratios']['plain'])->toBeGreaterThan(4 * $r['ratios']['dim']);
});

it('⚠️ GAP A — zero opacity is INVISIBLE, and refuses rather than scoring ~1.0', function () {
    $r = sampler('dimmed.png', 'dimmed-candidates.json');

    // Compositing at alpha 0 yields the plate itself, i.e. a ratio of 1.0 — which reads as a catastrophic
    // contrast failure rather than "there is nothing painted here". Fail loud instead.
    expect($r['unmeasurableReasons'])->toHaveKey('invisible-zero-opacity')
        ->and($r['ratios'])->not->toHaveKey('invisible');
});

it('⚠️ §N.18.1 — the ancestor-opacity model is checked against RAW PIXELS, not the tool’s own arithmetic', function () {
    // The strongest available check: read the painted glyph straight out of the PNG and confirm the browser
    // paints exactly what the alpha model predicts. If this holds, the model describes reality; the sampler's
    // ratio is then just arithmetic on top of it.
    $img = imagecreatefrompng(base_path('tests/Fixtures/probe/dimmed.png'));
    $cands = json_decode((string) file_get_contents(base_path('tests/Fixtures/probe/dimmed-candidates.json')), true);

    $darkest = static function (array $e) use ($img): int {
        $min = 255;
        for ($y = $e['y']; $y < $e['y'] + $e['h']; $y++) {
            for ($x = $e['x']; $x < $e['x'] + $e['w']; $x++) {
                $min = min($min, (imagecolorat($img, $x, $y) >> 16) & 255);
            }
        }

        return $min;
    };

    $by = [];
    foreach ($cands['candidates'] as $c) {
        $by[$c['t']] = $c;
    }

    // black over white at alpha a paints (1-a)*255: 0 at alpha 1, 115 at .55, 185 at .275.
    expect($darkest($by['plain']))->toBe(0)
        ->and($darkest($by['dim']))->toBe(115)
        ->and($darkest($by['nested']))->toBe(185);
});

it('⚠️ GAP B(i) — a SOLID background IS the plate, so dense text no longer needs a clear column', function () {
    $r = sampler('dense2.png', 'dense2-candidates.json');

    // #767676 on #FFFFFF is 4.54 by hand. Recovering the plate from the computed background removes both the
    // anti-alias fringe the column scan can pick up AND the need for any clear pixel to exist.
    expect((float) $r['ratios']['solid'])->toBe(4.54);

    // The gradient case with line-leading is recovered by the existing clear-column path.
    expect($r['ratios']['leading'])->toBeGreaterThan(4.0);
});

it('⚠️ THE RESIDUE STILL REFUSES — no plate, no number (the honest bound)', function () {
    // ⚠️ GAP B IS PARTIAL, DELIBERATELY. Where text genuinely fills a GRADIENT element edge to edge there is no
    // background pixel to read, and no solid to fall back on — so any figure would be invented. This is the
    // committed residue case, and it must keep reporting a REASON rather than a ratio.
    $r = sampler('dense.png', 'dense-noband-candidates.json');

    expect($r['unmeasurable'])->toBe(1)
        ->and($r['unmeasurableReasons'])->toHaveKey('no-clear-band')
        ->and($r['measured'])->toBe(0);
});
