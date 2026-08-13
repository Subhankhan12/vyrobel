<?php

use PHPUnit\Framework\Assert;

/*
 * WCAG AA CONTRAST ON THE SHARED TEXT TOKENS (audit 2026-08-07 · L3-03).
 *
 * Measured against the REAL painted card background (rgba(255,252,248,.58) over the warm gradient):
 *   --vy-muted rgb(138,128,121) = 3.77  → failed AA for normal text (needs 4.5)
 *   --vy-faint rgb(166,154,146) = 2.68  → failed even the 3.0 LARGE-text bar
 * They carry timestamps, captions, subtitles, placeholders and secondary labels across ~90 and ~80 view
 * files in the operator, owner and guest shells — i.e. most of the secondary text in the product.
 *
 * Darkened along the SAME warm hue (R>G>B preserved, a warmer stone rather than a cold grey) and kept
 * DISTINCT from each other so the muted/faint emphasis hierarchy survives:
 *   --vy-muted rgb(108,100,95)  = 5.66  PASS
 *   --vy-faint rgb(123,114,108) = 4.60  PASS
 * --vy-ink (15.05) and --vy-accent (5.06) already passed and are untouched.
 */

$shells = [
    'resources/views/components/layouts/operator.blade.php',
    'resources/views/components/layouts/owner.blade.php',
    'resources/views/components/guest/shell.blade.php',
    'resources/views/components/public/shell.blade.php',
];

/** Relative luminance per WCAG 2.1. */
function wcagLuminance(array $rgb): float
{
    $f = function (float $v): float {
        $v /= 255;

        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    };

    return 0.2126 * $f($rgb[0]) + 0.7152 * $f($rgb[1]) + 0.0722 * $f($rgb[2]);
}

function wcagRatio(array $a, array $b): float
{
    $l1 = wcagLuminance($a);
    $l2 = wcagLuminance($b);

    return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
}

it('keeps every shared text token at WCAG AA', function () {
    // ⚠️ THE TRUE WORST CASE, computed rather than guessed: the card is rgba(255,252,248,.58), so its real
    // painted colour is that composited over each stop of the page gradient. Compositing all four stops and
    // taking the DARKEST gives rgb(249,223,207) — the hardest background for dark text anywhere on a card.
    // (A first pass used a flat near-white, which is more forgiving and would have let `faint` through at
    // 4.45.) Passing here means passing on every card in the product.
    $cardBg = [249, 223, 207];

    foreach (['--vy-muted' => [95, 88, 83], '--vy-faint' => [108, 100, 95]] as $name => $rgb) {
        expect(wcagRatio($rgb, $cardBg))->toBeGreaterThanOrEqual(4.5, "{$name} fails WCAG AA for normal text");
    }

    // --vy-ink was already comfortably compliant and is untouched.
    expect(wcagRatio([43, 35, 32], $cardBg))->toBeGreaterThanOrEqual(4.5);
});

it('keeps the palette WARM and the emphasis hierarchy intact', function () {
    $muted = [95, 88, 83];
    $faint = [108, 100, 95];

    // Warm stone, not a cold grey: red > green > blue in both.
    foreach (['muted' => $muted, 'faint' => $faint] as $label => $rgb) {
        expect($rgb[0])->toBeGreaterThan($rgb[1], "{$label} lost its warmth");
        expect($rgb[1])->toBeGreaterThan($rgb[2], "{$label} lost its warmth");
    }

    // muted carries MORE emphasis than faint — darkening both must not collapse them together.
    expect(wcagLuminance($muted))->toBeLessThan(wcagLuminance($faint));
});

it('declares the AA values in every shell', function (string $shell) {
    // ⚠️ G8 — AMENDED BY DO-TOKEN-1. A shell no longer carries the whole palette: the 31 SHARED tokens moved
    // to one canonical base that every shell includes, leaving only its own deliberate overrides behind. So a
    // shell's EFFECTIVE palette is base + shell, in that order — which is exactly the cascade the browser
    // sees, and exactly what this file has always been measuring. Reading the shell alone would now measure a
    // palette no user ever gets.
    $css = (string) file_get_contents(base_path('resources/views/components/design/tokens.blade.php'))
        ."\n".(string) file_get_contents(base_path($shell));

    expect($css)->toContain('--vy-muted: rgb(95, 88, 83)');
    expect($css)->toContain('--vy-faint: rgb(108, 100, 95)');

    /*
     * The failing originals must not creep back in — guarded in the two ways that actually matter:
     * as the TOKEN VALUES themselves, and as a hardcoded TEXT colour anywhere in the shell.
     *
     * ⚠️ SCOPED TO THE TEXT ROLE ON PURPOSE (2026-08-09). This was a bare whole-file substring match
     * (`not->toContain('rgb(138, 128, 121)')`), and it fired on `--vy-hk-ooo: rgb(138, 128, 121)` — the
     * housekeeping "Out of order" STATUS-DOT token added by the design-parity pass, which happens to equal
     * the pre-AA `--vy-muted` value. That token is painted as a 9px `background` on `.vy-hk-dot`, never as
     * text, and every dot is printed beside its status WORD — so 1.4.1 is satisfied and the 4.5:1 text bar
     * (1.4.3) does not apply to it at all. A decorative fill is not what this guard is for; the guard is
     * about SECONDARY TEXT reverting to an unreadable grey.
     *
     * ⚠️ The lookbehind is load-bearing: a naive `color:` match also hits `background-color:`,
     * `border-color:` and `outline-color:`, which are decorative. (Same trap the P14 accent-token sweep
     * recorded — a naive replace matched property names ENDING in "color:".)
     */
    foreach (['rgb(138, 128, 121)', 'rgb(166, 154, 146)'] as $failing) {
        Assert::assertStringNotContainsString("--vy-muted: {$failing}", $css, "--vy-muted reverted to a pre-AA value in {$shell}");
        Assert::assertStringNotContainsString("--vy-faint: {$failing}", $css, "--vy-faint reverted to a pre-AA value in {$shell}");

        expect($css)->not->toMatch(
            '/(?<![-\w])color:\s*'.preg_quote($failing, '/').'/',
            "a pre-AA grey is hardcoded as TEXT colour in {$shell}"
        );
    }
})->with($shells);
