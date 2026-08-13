<?php

/*
 * DO-A11Y-3 / -4 / -5 — THE CONTRAST CLEANUP TAIL (2026-08-13).
 *
 * ⚠️ DO-A11Y-3 IS THE ONE THAT WAS HELD BACK, and only for its blast radius: white on the accent CTA gradient
 * measured 3.22–3.39 on every button in the product. It is a REAL AA FAILURE, so it is a REQUIRED fix — not
 * the headroom preference posture A declined (§N.20.3 governs tokens that MEET AA; these missed).
 *
 * ⚠️ THE READING IS IN A VALIDATED INPUT CLASS, checked before acting (§N.20.1). "White label on a gradient
 * BUTTON, read on the button's own plate" is precisely `ContrastSamplerValidationTest`'s VALIDATION 2 — the
 * hard case the harness exists to certify. So this is not the steep-gradient scope gap, and the number could
 * be trusted.
 *
 * ⚠️ A PREMISE IN THE RECORD WAS TOO STRONG, AND CHECKING IT CHANGED THE FIX. The guest shell carried a note
 * reading "No warm orange lighter than #C2410C reaches 4.5 for white", which would have meant the CTA gradient
 * had to collapse to a flat colour. It is false: white needs relative luminance <= 0.1833, and #C2410C sits at
 * 0.1528 — so a band of lighter oranges qualifies. #D14102 is LIGHTER than --vy-accent, sits at the brand hue
 * (18.3 deg vs the original stop's 18.6), and carries white at 4.70. The gradient survives, narrowed.
 *
 * ⚠️ AND NOT AT 4.50. The lightest qualifying orange (#DD3910) lands on exactly 4.50 — the zero-margin state
 * DO-PLATE-1 showed is one rounding away from failing. #D14102 was chosen for a real margin instead.
 */

/** WCAG contrast, computed here so the guard does not depend on the palette it checks (§N.18.1). */
function ctaRatio(array $fg, array $plate): float
{
    $lum = static function (array $c): float {
        $s = array_map(static function ($v) {
            $v /= 255;

            return $v <= 0.03928 ? $v / 12.92 : (float) pow(($v + 0.055) / 1.055, 2.4);
        }, $c);

        return 0.2126 * $s[0] + 0.7152 * $s[1] + 0.0722 * $s[2];
    };

    $x = $lum($fg);
    $y = $lum($plate);
    if ($x < $y) {
        [$x, $y] = [$y, $x];
    }

    return round(($x + 0.05) / ($y + 0.05), 2);
}

/** Prefixed — a top-level Pest `const` is GLOBAL, and an unprefixed one collided in DO-A11Y-1. */
const A11Y3_NEW_LIGHT_STOP = [209, 65, 2];      // #D14102 — the gradient's light end, where white is worst
const A11Y3_OLD_LIGHT_STOP = [232, 112, 58];    // #E8703A
const A11Y3_DARK_STOP = [194, 65, 12];          // #C2410C = --vy-accent
const A11Y4_FD_REL_PLATE = [236, 151, 106];     // the front-desk relative-date chip, on the page gradient
const A11Y4_FD_SEG_PLATE = [231, 194, 175];     // the segmented control's tint
const A11Y5_SUBLABEL_PLATE = [232, 201, 184];   // the guests-list row sub-label

/** Every view file, so "all copies and mirrors" is checked rather than assumed (§N.8f). */
function allViewSources(): string
{
    $out = '';
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
    foreach ($it as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
            $out .= (string) file_get_contents($f->getPathname());
        }
    }

    return $out;
}

it('⚠️ DO-A11Y-3 — white clears AA on the CTA gradient’s LIGHTEST stop, which is where it is worst', function () {
    // ⚠️ PINNED TO THE LIGHT STOP DELIBERATELY. A guard written against the dark stop (#C2410C, white 5.18)
    // would pass without ever testing the end that failed — the vacuous-guard trap in its gradient form.
    expect(ctaRatio([255, 255, 255], A11Y3_NEW_LIGHT_STOP))->toBeGreaterThanOrEqual(4.5)
        // ⚠️ NON-VACUITY: the stop it replaced genuinely failed, so this is a fix and not a preference.
        ->and(ctaRatio([255, 255, 255], A11Y3_OLD_LIGHT_STOP))->toBeLessThan(4.5)
        // …and the dark end was never the problem.
        ->and(ctaRatio([255, 255, 255], A11Y3_DARK_STOP))->toBeGreaterThanOrEqual(4.5);

    // ⚠️ NOT A ZERO-MARGIN CHOICE. The lightest orange that technically qualifies lands on exactly 4.50;
    // DO-PLATE-1 showed what a zero margin costs, so this keeps real headroom.
    expect(ctaRatio([255, 255, 255], A11Y3_NEW_LIGHT_STOP))->toBeGreaterThan(4.55);

    // ⚠️ AND THE NEW STOP IS GENUINELY LIGHTER THAN THE DARK ONE — the gradient still reads as a gradient,
    // which is what makes this a fix rather than a flattening.
    expect(ctaRatio([255, 255, 255], A11Y3_NEW_LIGHT_STOP))
        ->toBeLessThan(ctaRatio([255, 255, 255], A11Y3_DARK_STOP));
});

it('⚠️ §N.8f — EVERY copy of the CTA gradient moved, across all 29 files', function () {
    $all = allViewSources();

    // ⚠️ NON-VACUITY: the gradient really is declared, in quantity — 43 occurrences across 29 files, including
    // 10 shells that predate DO-TOKEN-1 and still declare their own --vy-grad, plus email/PDF CTA mirrors.
    expect(substr_count(strtoupper($all), '#D14102'))->toBeGreaterThan(20);

    // ⚠️ THE REGRESSION THIS CATCHES: one missed copy is exactly the .vy-avatar owner-shell miss (§N.21.5),
    // and would leave CTAs failing on whichever shell was forgotten.
    expect(preg_match('/linear-gradient\(\s*1\d{2}deg,\s*#e8703a\s*,\s*#c2410c\s*\)/i', $all))->toBe(0);
});

it('⚠️ the ~42 STANDALONE uses of the old orange are untouched — this darkened a gradient, not a brand colour', function () {
    // ⚠️ THE OVER-DARKENING GUARD. #E8703A is also a border, dot and decorative fill colour carrying no white
    // text; darkening those would be changing passing declarations (posture A). They must survive.
    expect(substr_count(strtoupper(allViewSources()), '#E8703A'))->toBeGreaterThan(30);
});

it('⚠️ DO-A11Y-4 — the page-local .vy-fd-* rules clear their worst plates', function () {
    $fd = (string) file_get_contents(resource_path('views/livewire/front-desk.blade.php'));

    expect($fd)->toContain('.vy-fd-rel {')->toContain('.vy-fd-seg-btn {')   // non-vacuity
        ->and($fd)->toMatch('/\.vy-fd-rel \{[^}]*color: var\(--vy-on-grad\)/')
        ->and($fd)->toMatch('/\.vy-fd-seg-btn \{[^}]*color: var\(--vy-on-grad\)/');

    // --vy-accent-text sat on the page GRADIENT here, outside the card/tint scope it was derived for.
    expect(ctaRatio([68, 59, 54], A11Y4_FD_REL_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(ctaRatio([147, 42, 7], A11Y4_FD_REL_PLATE))->toBeLessThan(4.5)
        ->and(ctaRatio([68, 59, 54], A11Y4_FD_SEG_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(ctaRatio([95, 88, 83], A11Y4_FD_SEG_PLATE))->toBeLessThan(4.5);
});

it('⚠️ DO-A11Y-5 — the row sub-label clears AA, and it was never actually "unclassed"', function () {
    $shell = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // ⚠️ PREMISE CORRECTION: the register called this "one unclassed link". The <a> carries no colour of its
    // own — it INHERITS `.vy-utable .vy-u-sub`, so the fix belonged on that shared rule at the source, not on
    // the anchor. Had it been treated as a one-off inline fix, every other sub-label would still read 4.48.
    expect($shell)->toContain('.vy-utable .vy-u-sub {')
        ->and($shell)->toMatch('/\.vy-utable \.vy-u-sub \{[^}]*color: var\(--vy-on-grad\)/');

    expect(ctaRatio([68, 59, 54], A11Y5_SUBLABEL_PLATE))->toBeGreaterThanOrEqual(4.5)
        ->and(ctaRatio([95, 88, 83], A11Y5_SUBLABEL_PLATE))->toBeLessThan(4.5);   // --vy-muted read 4.48
});
