<?php

use Illuminate\Support\Facades\Blade;

/*
 * DO10-4 — THE STAR CAPSTONE. This is a CORRECTNESS guard, not a style one.
 *
 * A star row can state the WRONG rating in two ways, and the product had shipped both:
 *   1. ROUND-NOT-FLOOR — owner/unit-detail printed `str_repeat('★', round($average))`, so a 4.67 average
 *      rendered FIVE filled stars. The owner's own unit read as perfect when it is not. Verified live before
 *      the fix by forcing a real unit to 4.67.
 *   2. COLOUR-ONLY — filled and empty drawn with the SAME glyph, separated by colour alone (WCAG 1.4.1), so a
 *      greyscale or colour-blind reader counts wrong. That was O26's original defect (fixed) and G12's
 *      `ReviewList::stars_off` (fixed).
 *
 * ⚠️ AND THE CAPSTONE FOUND A THIRD: the shared component's OWN default colours failed 3:1 as a non-text
 * graphic (WCAG 1.4.11) — 2.08 filled / 2.25 empty. The component everything was being migrated TO drew a star
 * you could barely see. Worse, the first replacement pair was chosen against the two CARD plates and the LIVE
 * render then showed the row sits on the PEACH hero plate #FBD3BF, where it still failed. The pair below was
 * re-chosen against that worst real plate and measured on all three.
 *
 * These assertions are deliberately behavioural (render the component) rather than string-matching the file.
 */

function starRow(float $value, int $max = 5): string
{
    return Blade::render('<x-star-rating :value="$v" :max="$m" />', ['v' => $value, 'm' => $max]);
}

it('FLOORS the rating — a 4.6 shows four filled stars, never five', function () {
    $html = starRow(4.6);

    expect(substr_count($html, '★'))->toBe(4)
        ->and(substr_count($html, '☆'))->toBe(1);

    // The exact figure is still stated, so flooring never hides the real value.
    expect($html)->toContain('aria-label="4.6 out of 5 stars"');
});

it('never rounds up at the .5 boundary either', function () {
    // 4.5 is the case that makes round() look harmless — it is the one that prints a PERFECT score.
    expect(substr_count(starRow(4.5), '★'))->toBe(4)
        ->and(substr_count(starRow(4.9), '★'))->toBe(4)
        ->and(substr_count(starRow(5.0), '★'))->toBe(5)
        ->and(substr_count(starRow(0.9), '★'))->toBe(0)
        ->and(substr_count(starRow(0.9), '☆'))->toBe(5);
});

it('distinguishes filled from empty by GLYPH, so greyscale reads the right count', function () {
    $html = starRow(3.0);

    // The correctness core: two DIFFERENT characters. Strip every colour and the count still reads 3.
    $glyphs = preg_replace('/<[^>]*>/', '', $html) ?? '';
    $glyphs = trim(preg_replace('/\s+/', '', $glyphs) ?? '');

    expect($glyphs)->toBe('★★★☆☆');
});

it('carries ONE accessible name and hides the glyphs from assistive tech', function () {
    $html = starRow(3.5);

    expect($html)->toContain('role="img"')
        ->and($html)->toContain('aria-label="3.5 out of 5 stars"')
        // five glyph spans, every one hidden — a screen reader hears the rating once, not ten times.
        ->and(substr_count($html, 'aria-hidden="true"'))->toBe(5);
});

it('keeps both star inks above 3:1 on every real plate the product renders them on', function () {
    $ratio = function (string $ink, string $plate): float {
        $lum = static function (string $hex): float {
            $hex = ltrim($hex, '#');
            $f = static fn (float $v): float => ($v /= 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;

            return 0.2126 * $f((float) hexdec(substr($hex, 0, 2)))
                + 0.7152 * $f((float) hexdec(substr($hex, 2, 2)))
                + 0.0722 * $f((float) hexdec(substr($hex, 4, 2)));
        };
        $a = $lum($ink);
        $b = $lum($plate);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    };

    // ⚠️ Read the inks the product ACTUALLY RENDERS, never literals repeated from the source. A first cut of
    // this test asserted `$ratio('#8F5A06', …)` directly and was VACUOUS — reverting the component's default to
    // the failing #E8A63A left it green (O32: a passing assertion means vacuity or redundancy; resolve it).
    //
    // ⚠️ THE INK MOVED, SO THIS FOLLOWS IT (Wave 4 · DO10-4). The component now renders `color:var(--vy-star)`
    // instead of a literal hex, because the same hex had been hand-copied into four other view files and a
    // contrast fix applied here silently missed them. That indirection would have made this guard BLIND —
    // there is no longer a hex in the component's own output to measure — so the test now resolves the token
    // exactly as a browser does: confirm the component defers to the token, then read the token's real value
    // out of the rendered design-token block. The maths below is unchanged, and it still guards the single
    // value the whole product paints its stars with.
    $html = starRow(3.0);
    expect($html)->toContain('color:var(--vy-star)')
        ->and($html)->toContain('color:var(--vy-star-empty)')
        // …and no literal hex sneaked back in beside the token.
        ->and(preg_match('/color:#[0-9A-Fa-f]{6}/', $html))->toBe(0);

    $tokens = Blade::render('<x-design.tokens />');
    $ink = function (string $name) use ($tokens): string {
        expect(preg_match('/--'.$name.':\s*rgb\((\d+),\s*(\d+),\s*(\d+)\)/', $tokens, $t))->toBe(1, "token --$name not defined");

        return sprintf('#%02X%02X%02X', (int) $t[1], (int) $t[2], (int) $t[3]);
    };

    [$filled, $empty] = [$ink('vy-star'), $ink('vy-star-empty')];
    expect($filled)->not->toBe($empty);

    // #FBD3BF is the PEACH owner unit-detail hero plate — pixel-sampled from the live render, and the plate
    // that failed the first choice. #F8F4EF the owner card, #FFFDFB the light card.
    foreach (['#FBD3BF', '#F8F4EF', '#FFFDFB'] as $plate) {
        expect($ratio($filled, $plate))->toBeGreaterThanOrEqual(3.0, "filled star $filled below 3:1 on $plate")
            ->and($ratio($empty, $plate))->toBeGreaterThanOrEqual(3.0, "empty star $empty below 3:1 on $plate")
            // hierarchy: filled must read stronger than empty, or the row inverts
            ->and($ratio($filled, $plate))->toBeGreaterThan($ratio($empty, $plate));

        // non-vacuity of the BAR itself: the values these replaced genuinely failed it.
        expect($ratio('#E8A63A', $plate))->toBeLessThan(3.0)
            ->and($ratio('#B9A99A', $plate))->toBeLessThan(3.0);
    }
});

it('leaves no rating row rendering through the old round-and-repeat pattern', function () {
    $offenders = [];
    foreach ((glob(resource_path('views/**/*.blade.php')) ?: []) + (glob(resource_path('views/**/**/*.blade.php')) ?: []) as $path) {
        $src = (string) file_get_contents($path);
        // `str_repeat('★', round(...))` — the exact shape that overstated the rating.
        if (preg_match("/str_repeat\(\s*'★'\s*,[^)]*round\s*\(/u", $src) === 1) {
            $offenders[] = basename($path);
        }
    }

    expect($offenders)->toBe([]);
});
