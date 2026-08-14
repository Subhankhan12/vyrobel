<?php

/*
 * DO-A11Y-1 + DO-A11Y-2 — THE SHARED-CLASS FAMILY, FIXED AT ITS SOURCE (2026-08-13).
 *
 * The DO-EMAIL-5 Part-A sweep showed its survivors were not gated-file defects but SHARED classes and shell
 * chrome that those pages merely revealed, spanning 42 operator pages. This pass fixed them ONCE at the class
 * definition in `components/layouts/operator.blade.php` and in the shared token file (O25/O27 — never from a
 * page gate), and this file guards that.
 *
 * ⚠️ THE PREMISE CORRECTION THAT SHAPED THE WHOLE PASS: DO-A11Y-1 and DO-A11Y-2 turned out to be ONE defect
 * wearing two labels. Sorted by the plate they land on, the failing classes split cleanly:
 *
 *   · on a CARD or a tag's own tint  → `--vy-on-grad` / `--vy-muted` already clear them; a MAPPING suffices.
 *   · on the saturated PAGE GRADIENT → NOTHING in the palette cleared them except `--vy-ink`, because
 *     `--vy-on-grad` — the token that exists precisely for this plate — was itself sub-AA.
 *
 * So DO-A11Y-2 was not one stray crumb: it was the reason half of DO-A11Y-1 had nowhere to map.
 *
 * ⚠️ AND THE ROOT CAUSE IS ONE THE OWNER SHELL ALREADY FIXED. Both `--vy-on-grad` and `--vy-accent-text` were
 * derived against a PICKED gradient stop rather than by sampling the plate behind the elements that use them.
 * The owner shell had this exact defect (O2/O7), fixed it by sampling 7,502 pixels, and left a note warning
 * "Re-measure by SAMPLING, not by picking a pixel." The operator shell never got that treatment. Sampling it
 * across ten real pages found plates far darker than the picked stops, and both tokens failing on them.
 *
 * ⚠️ THIS IS A REQUIRED COMPLIANCE FIX, NOT THE HEADROOM CHANGE POSTURE A DECLINED (§N.20.3). Posture A
 * declined darkening tokens that MEET AA with a thin margin. These MISSED AA — 4.33 down to 2.42. Distinct
 * question, distinct answer. `--vy-on-grad-soft` is deliberately UNCHANGED: it never failed on any sampled
 * page, and darkening a passing token IS the declined preference.
 */

/** WCAG contrast, computed here so the guard never depends on the palette it is checking (§N.18.1). */
function sharedRatio(array $fg, array $plate): float
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

/** Composite a colour over a plate at an alpha — how a dimmed ancestor actually PAINTS its text. */
function sharedComposite(array $fg, array $bg, float $alpha): array
{
    return [
        (int) round($fg[0] * $alpha + $bg[0] * (1 - $alpha)),
        (int) round($fg[1] * $alpha + $bg[1] * (1 - $alpha)),
        (int) round($fg[2] * $alpha + $bg[2] * (1 - $alpha)),
    ];
}

function operatorShellSource(): string
{
    return (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));
}

/*
 * ⚠️ THE WORST PLATE EACH TOKEN'S USERS ACTUALLY LAND ON, pixel-sampled across ten operator pages with the
 * validated sampler. Pinning the WORST is the whole point (N.16.6): a guard written against a light plate
 * passes without ever testing the case that fails, which is vacuous.
 */
// ⚠️ PREFIXED. A top-level `const` in a Pest file is a GLOBAL constant, and `OnGradientTextContrastTest`
// already defines A11Y_WORST_GRADIENT_PLATE. The collision made this file pass ALONE and fail IN-SUITE, against
// the other file's plate — the coupling that makes "isolate it" an unreliable oracle (§N.17.7).
const A11Y_WORST_GRADIENT_PLATE = [235, 152, 106];   // behind .vy-topbar-sub / .vy-acct-role / .vy-convo-back
const A11Y_WORST_TINT_PLATE = [249, 194, 166];       // a tag's own tint composited over card over gradient
const A11Y_WORST_CARD_PLATE = [252, 207, 183];       // behind th / .vy-fact-label
const A11Y_SIDEBAR_PLATE = [251, 235, 226];          // behind the locked nav item

it('⚠️ DO-A11Y-2 — --vy-on-grad clears the darkest plate its users actually sit on', function () {
    $shell = operatorShellSource();

    // ⚠️ NON-VACUITY: the token is really declared here (it is a deliberate per-shell override, DO-TOKEN-1).
    expect($shell)->toContain('--vy-on-grad:');

    expect($shell)->toContain('--vy-on-grad: rgb(68, 59, 54)')
        ->and($shell)->not->toContain('rgb(84, 74, 68)');   // the picked-stop value

    expect(sharedRatio([68, 59, 54], A11Y_WORST_GRADIENT_PLATE))->toBeGreaterThanOrEqual(4.5);

    // ⚠️ And the old value genuinely FAILED there — so this was a real AA failure, not a headroom preference,
    // which is what separates it from the posture-A question the founder declined.
    expect(sharedRatio([84, 74, 68], A11Y_WORST_GRADIENT_PLATE))->toBeLessThan(4.5);
});

it('⚠️ --vy-on-grad-soft is deliberately UNCHANGED — it never failed, and darkening it would be posture B', function () {
    // The distinction this pass turns on. Guarding the non-change stops a future "consistency" tidy-up from
    // quietly making the declined headroom change.
    expect(operatorShellSource())->toContain('--vy-on-grad-soft: rgb(88, 78, 72)');
});

it('⚠️ DO-A11Y-1 — --vy-accent-text clears the darkest TINT its users sit on', function () {
    $tokens = (string) file_get_contents(resource_path('views/components/design/tokens.blade.php'));

    expect($tokens)->toContain('--vy-accent-text:')                      // non-vacuity
        ->and($tokens)->toContain('--vy-accent-text: rgb(147, 42, 7)')
        ->and($tokens)->not->toContain('rgb(176, 56, 8)');

    expect(sharedRatio([147, 42, 7], A11Y_WORST_TINT_PLATE))->toBeGreaterThanOrEqual(4.5)
        // The old value failed on the tag/link tints — .vy-tag.tone-confirmed measured 3.89.
        ->and(sharedRatio([176, 56, 8], A11Y_WORST_TINT_PLATE))->toBeLessThan(4.5);
});

it('⚠️ the GRADIENT-sitting classes route to --vy-on-grad, not to a card grey', function () {
    $shell = operatorShellSource();

    // Each of these sits on the page gradient, where --vy-faint/--vy-muted cannot reach AA at all.
    expect($shell)->toMatch('/\.vy-acct-role \{[^}]*color: var\(--vy-on-grad\)/')
        ->and($shell)->toMatch('/\.vy-convo-back \{[^}]*color: var\(--vy-on-grad\)/')
        ->and($shell)->toMatch('/\.vy-soon-tag \{[^}]*color: var\(--vy-on-grad\)/')
        ->and($shell)->toMatch('/\.vy-deferred-note \{[^}]*color: var\(--vy-on-grad\)/')
        ->and($shell)->toMatch('/\.vy-seg-opt \{[^}]*color: var\(--vy-on-grad\)/')
        ->and($shell)->toMatch('/\.vy-search-pill \{[^}]*color: var\(--vy-on-grad\)/');

    // ⚠️ The measured reason, pinned: a card grey on this plate is not close to AA.
    expect(sharedRatio([108, 100, 95], A11Y_WORST_GRADIENT_PLATE))->toBeLessThan(4.5)   // --vy-faint
        ->and(sharedRatio([95, 88, 83], A11Y_WORST_GRADIENT_PLATE))->toBeLessThan(4.5); // --vy-muted
});

it('⚠️ the CARD-sitting classes route to --vy-muted — the smallest step that clears them', function () {
    $shell = operatorShellSource();

    expect($shell)->toMatch('/\.vy-table th \{[^}]*color: var\(--vy-muted\)/')
        ->and($shell)->toMatch('/\.vy-utable thead th \{[^}]*color: var\(--vy-muted\)/')
        ->and($shell)->toMatch('/\.vy-fact-label \{[^}]*color: var\(--vy-muted\)/');

    // --vy-faint failed on the card plate (th measured 4.07); --vy-muted clears it without going to ink, so
    // the type hierarchy survives the fix.
    expect(sharedRatio([108, 100, 95], A11Y_WORST_CARD_PLATE))->toBeLessThan(4.5)
        ->and(sharedRatio([95, 88, 83], A11Y_WORST_CARD_PLATE))->toBeGreaterThanOrEqual(4.5);
});

it('⚠️ the amber + vip tags use their TEXT values, not their FILL values', function () {
    $shell = operatorShellSource();

    // --vy-amber is the fill; --vy-amber-text is the purpose-built text value (N.14.9). tone-pending measured
    // 3.01 and tone-risk 2.96 with the fill.
    expect($shell)->toMatch('/\.vy-tag\.tone-pending \{[^}]*color: var\(--vy-amber-text\)/')
        ->and($shell)->toMatch('/\.vy-tag\.tone-risk \{[^}]*color: var\(--vy-amber-text\)/')
        ->and($shell)->toMatch('/\.vy-tag\.tone-no-show \{[^}]*color: var\(--vy-amber-text\)/')
        ->and($shell)->toContain('.vy-tag.tone-vip { background: rgba(180, 130, 44, 0.16); color: #6f4f0c; }');

    // ⚠️ --vy-amber-text was darkened again in the Part-B data sweep: it measured 4.48 on the owner expense
    // "Pending review" chip, a tint only visible once expenses are POPULATED. Same correction as accent-text.
    expect(sharedRatio([112, 66, 4], A11Y_WORST_TINT_PLATE))->toBeGreaterThanOrEqual(4.5)      // --vy-amber-text
        ->and(sharedRatio([178, 106, 8], A11Y_WORST_TINT_PLATE))->toBeLessThan(4.5)            // --vy-amber (fill)
        ->and(sharedRatio([111, 79, 12], [241, 194, 161]))->toBeGreaterThanOrEqual(4.5)   // #6f4f0c vip
        ->and(sharedRatio([138, 100, 16], [241, 194, 161]))->toBeLessThan(4.5);           // #8a6410 vip
});

it('⚠️ SUPERSEDED BY DO-RESTORE-3 — white-on-a-light-accent-tint is RESTORED, and accepted', function () {
    $shell = operatorShellSource();

    // ⚠️ THIS TEST INVERTED. DO-A11Y-1 moved .vy-avatar and .vy-plan-tag off the wireframe light tint because
    // white on it measured 2.27/2.46 — the worst readings in the product. DO-RESTORE-3 restored them by
    // founder decision, ACCEPTING that. The assertion is now the mirror of what it was, and it still reads the
    // SOURCE rather than a pinned constant (DO-RESTORE-2's guard trap).
    expect($shell)->toMatch('/\.vy-avatar \{[^}]*background:\s*linear-gradient\(140deg, #F0A57A, #D9662E\)/')
        ->and($shell)->toMatch('/\.vy-plan-tag \{[^}]*background:\s*var\(--vy-grad\)/');

    // `.vy-chan-glyph` always carried this gradient legitimately (no white text on it) — unchanged throughout,
    // so the guard is about the avatar concept, never a blanket ban on the colour.
    expect($shell)->toContain('.vy-chan-glyph { background: linear-gradient(140deg, #F0A57A, #D9662E); }');

    // ⚠️ THE ACCEPTED COST, STATED PLAINLY: white on #F0A57A is 2.03 — below AA, below even the 3:1 large-text
    // bar, and the lowest ratio anywhere in the product. Accepted per DO-RESTORE-3 with the number surfaced.
    expect(sharedRatio([255, 255, 255], [240, 165, 122]))->toBeLessThan(3.0);

    // ⚠️ AND THE ALTERNATIVE THE FOUNDER CAN STILL TAKE (option b): the same wireframe peach with DARK text
    // clears AA comfortably. Pinned so the option stays visible in the record rather than needing re-derivation.
    expect(sharedRatio([43, 35, 32], [240, 165, 122]))->toBeGreaterThanOrEqual(4.5);   // --vy-ink = 7.60
});

it('⚠️ N.16.6 — the locked nav item no longer DIMS its own meaning-bearing text', function () {
    $shell = operatorShellSource();

    // ⚠️ NON-VACUITY: the rule still exists and still marks the state — only the opacity is gone.
    expect($shell)->toContain('.vy-nav-item[aria-disabled="true"] { cursor: default; }')
        ->and($shell)->not->toContain('.vy-nav-item[aria-disabled="true"] { cursor: default; opacity: .55; }');

    // ⚠️ THE MEASURED REASON, modelled on the PAINTED pair rather than the declared one: at .55 both the label
    // and its "PRO" tag composite to well under AA, and even .9 leaves the tag failing. Only full opacity
    // clears it — which costs nothing, because the explicit "PRO" text and aria-disabled both still carry the
    // state (the redundant mark the rule asks for).
    $dimmedTag = sharedRatio(
        sharedComposite([255, 255, 255], A11Y_SIDEBAR_PLATE, 0.55),
        sharedComposite([194, 65, 12], A11Y_SIDEBAR_PLATE, 0.55),
    );
    $fullTag = sharedRatio([255, 255, 255], [194, 65, 12]);

    expect($dimmedTag)->toBeLessThan(3.0)
        ->and($fullTag)->toBeGreaterThanOrEqual(4.5);

    // The label too: --vy-on-grad at .55 over the sidebar.
    expect(sharedRatio(sharedComposite([68, 59, 54], A11Y_SIDEBAR_PLATE, 0.55), A11Y_SIDEBAR_PLATE))->toBeLessThan(4.5);
});
