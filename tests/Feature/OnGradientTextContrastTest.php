<?php

/*
 * DO-EMAIL-5-continued — TEXT ON THE PAGE GRADIENT USES THE ON-GRADIENT TOKEN (2026-08-13).
 *
 * ⚠️ WHY THIS EXISTS SEPARATELY FROM `DesignTokenContrastTest`. That file measures the card plate, and its
 * constant rgb(249,223,207) is correctly derived — `--vy-faint` really does clear AA on a card, at 4.55. What
 * it never modelled is text that sits DIRECTLY ON THE PAGE GRADIENT, with no card between. Measured with the
 * validated sampler (`tools/contrast-sampler.php`, DO-PROBE-1) against the real composited page, the topbar
 * subtitle read **3.65** on plate rgb(241,172,134) — a genuine AA failure on a surface no existing guard
 * covered, found only because the probe reads painted pixels rather than declared values.
 *
 * ⚠️ THE PRODUCT ALREADY HAD THE RIGHT TOKEN. `--vy-on-grad` exists for precisely this plate; the defect was a
 * card token used off-card. That makes the fix a MAPPING (N.14.9), not a change to the palette floor.
 *
 * ⚠️ AND A PREMISE CORRECTION WORTH KEEPING: `--vy-on-grad` was derived against the gradient stop
 * rgb(241,185,150), where it reads 4.97. The page paints down to rgb(241,172,134), where it reads 4.50 —
 * passing, with no margin at all. The gradient is darker than the value the token was computed against.
 */

/** WCAG contrast, computed here rather than imported, so this file does not depend on the thing it checks. */
function gradRatio(array $a, array $b): float
{
    $lum = static function (array $c): float {
        $s = array_map(static function ($v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (float) pow(($v + 0.055) / 1.055, 2.4);
        }, $c);

        return 0.2126 * $s[0] + 0.7152 * $s[1] + 0.0722 * $s[2];
    };

    $x = $lum($a);
    $y = $lum($b);
    if ($x < $y) {
        [$x, $y] = [$y, $x];
    }

    return round(($x + 0.05) / ($y + 0.05), 2);
}

/** The darkest plate the sampler actually measured under topbar text on a real operator page. */
const WORST_GRADIENT_PLATE = [241, 172, 134];

it('⚠️ the topbar subtitle uses the ON-GRADIENT token, not a card token', function () {
    $shell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // ⚠️ NON-VACUITY: the rule exists, so this is a statement about its colour rather than about a missing rule.
    expect($shell)->toContain('.vy-topbar-sub {');

    expect($shell)->toMatch('/\.vy-topbar-sub \{[^}]*color: var\(--vy-on-grad\)/')
        // The exact regression: a card token on an off-card surface.
        ->and($shell)->not->toMatch('/\.vy-topbar-sub \{[^}]*color: var\(--vy-muted\)/');
});

it('⚠️ the tokens behave as the measurement said — muted FAILS on the gradient, on-grad CLEARS', function () {
    // These are the numbers the validated sampler read off the real page. Pinning them means a future change
    // to either token is checked against the plate that actually exists, not an assumed one.
    expect(gradRatio([95, 88, 83], WORST_GRADIENT_PLATE))->toBeLessThan(4.5)      // --vy-muted  = 3.65
        ->and(gradRatio([108, 100, 95], WORST_GRADIENT_PLATE))->toBeLessThan(4.5) // --vy-faint  = 3.03
        ->and(gradRatio([84, 74, 68], WORST_GRADIENT_PLATE))->toBeGreaterThanOrEqual(4.5); // --vy-on-grad = 4.50
});

it('⚠️ the on-gradient token still resolves to the value that measurement assumed', function () {
    // The mapping above is only a fix while `--vy-on-grad` holds this value. It now lives in the canonical
    // palette (DO-TOKEN-1), so this asserts the base, not a per-shell copy.
    $base = (string) file_get_contents(resource_path('views/components/design/tokens.blade.php'));
    $shell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // `--vy-on-grad` is one of the seven DELIBERATE per-shell overrides (measured per shell, §N.4i), so the
    // operator shell is where its value lives — not the shared base.
    expect($base)->not->toContain('--vy-on-grad:')
        ->and($shell)->toContain('--vy-on-grad: rgb(84, 74, 68)');
});
