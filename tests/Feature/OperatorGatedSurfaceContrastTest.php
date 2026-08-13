<?php

/*
 * DO-EMAIL-5-continued · PART A — THE ROLE-GATED OPERATOR SURFACES (2026-08-13).
 *
 * ⚠️ WHY THESE FILES NEEDED THEIR OWN PASS. §N.14.1: a contrast sweep is bounded by the ROLE it runs as. The
 * earlier gates ran as the demo Hotel Manager, so markup behind `settings.manage`, `properties.manage`,
 * `payouts.manage`, `payments.refund` and `guests.erase` was never in the DOM to be measured. This pass
 * re-rendered all eight gated surfaces as the org admin (`Property Owner`, ['*']) and measured the painted
 * pixels with the validated sampler.
 *
 * ⚠️ THE CAPTURE METHOD ITSELF WAS WRONG TWICE, AND BOTH FAULTS INVENTED DEFECTS RATHER THAN HIDING THEM —
 * the more dangerous direction, because the numbers looked ordinary:
 *
 *   1. A fullPage screenshot does not repaint a `position: fixed` sidebar below the fold, so every nav item
 *      past the fold landed on blank canvas and read white-on-white 1.00. Fixed by sizing the VIEWPORT to the
 *      document and capturing without fullPage. (The register's recurring "1.00 mis-sample, bg pure white"
 *      notes from earlier gates are almost certainly this, logged as noise rather than diagnosed.)
 *   2. An element clipped by an `overflow:auto` ancestor still reports a page rect. Payout-run's "Mark paid"
 *      lays out at x=870 while its scroll container ends at x=785, so the sampler measured the card painted
 *      there instead and reported 1.17 for a button that is not drawn at that point at all. Fixed by
 *      HIT-TESTING every candidate: it must be the element actually painted at its own sample point.
 *
 * ⚠️ WHAT THE SWEEP ACTUALLY FOUND. Nearly all surviving failures are NOT gated-file defects — they are
 * SHARED classes (`.vy-tag`, `th`, `.vy-fact-label`, `.vy-assign-link`, `.vy-u-code`) and shell chrome
 * (`.vy-plan-tag` 2.46, `.vy-acct-role` 3.83, `.vy-avatar` 2.27) that these pages merely revealed. Retuning
 * those from a page gate is the mistake the owner walk recorded (O25/O27), and they span 42 pages, so they
 * are LOGGED for their own realm-wide gate rather than absorbed here.
 *
 * This file therefore guards the one gated surface whose failures were genuinely LOCAL to it: the
 * `vy-np-*` rules in `notification-preferences`, which no other page uses. PAGE failures there went 11 → 0.
 */

/** WCAG contrast, computed here so the guard does not depend on the palette it is checking. */
function gatedRatio(array $fg, array $plate): float
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

/** The notification-preferences view source. */
function notificationPreferencesSource(): string
{
    return (string) file_get_contents(resource_path('views/livewire/operator/notification-preferences.blade.php'));
}

it('⚠️ the local vy-np-* rules no longer use the colours measured below AA', function () {
    $source = notificationPreferencesSource();

    // ⚠️ NON-VACUITY: the rules exist, so each assertion below is about a rule's colour rather than about a
    // rule that was deleted.
    foreach (['.vy-np-crumb {', '.vy-np-chan-detail {', '.vy-np-soon {', '.vy-np-group {', '.vy-np-status.is-on {'] as $rule) {
        expect($source)->toContain($rule);
    }

    // The exact regressions, each pinned to what the sampler read on the real page.
    expect($source)->toMatch('/\.vy-np-crumb \{[^}]*color: var\(--vy-ink\)/')            // was --vy-muted, 3.38
        ->and($source)->toMatch('/\.vy-np-chan-detail \{[^}]*color: var\(--vy-muted\)/')  // was --vy-faint, 3.95–4.15
        ->and($source)->toMatch('/\.vy-np-soon \{[^}]*color: var\(--vy-ink\)/')           // was --vy-faint, 3.73
        ->and($source)->toMatch('/\.vy-np-group \{[^}]*color: var\(--vy-ink\)/')          // was --vy-accent, 3.64–4.37
        ->and($source)->toMatch('/\.vy-np-status\.is-on \{[^}]*color: #14532D/');         // was #1F6E4B, 3.50
});

it('⚠️ the replacements clear AA on the plates that were MEASURED, not on assumed ones', function () {
    // Each plate is the darkest the sampler read beneath that element on the real rendered page. Pinning the
    // plate as well as the colour means a future background change is checked against reality (DO-PLATE-1).
    expect(gatedRatio([43, 35, 32], [234, 165, 124]))->toBeGreaterThanOrEqual(4.5)    // crumb on the gradient
        ->and(gatedRatio([95, 88, 83], [233, 215, 204]))->toBeGreaterThanOrEqual(4.5) // chan-detail on the card
        ->and(gatedRatio([43, 35, 32], [237, 200, 180]))->toBeGreaterThanOrEqual(4.5) // soon tag
        ->and(gatedRatio([43, 35, 32], [252, 207, 184]))->toBeGreaterThanOrEqual(4.5) // group heading
        ->and(gatedRatio([20, 83, 45], [211, 192, 168]))->toBeGreaterThanOrEqual(4.5); // verified tag
});

it('⚠️ the colours that were REPLACED genuinely failed — so the fix was not cosmetic', function () {
    // Without this the file would pass just as happily if the old colours had been left in place: it proves
    // the measurements motivating each change were real failures, not preferences.
    expect(gatedRatio([95, 88, 83], [234, 165, 124]))->toBeLessThan(4.5)      // --vy-muted crumb  = 3.38
        ->and(gatedRatio([108, 100, 95], [233, 215, 204]))->toBeLessThan(4.5) // --vy-faint detail = 4.15
        ->and(gatedRatio([108, 100, 95], [237, 200, 180]))->toBeLessThan(4.5) // --vy-faint soon   = 3.73
        ->and(gatedRatio([194, 65, 12], [252, 207, 184]))->toBeLessThan(4.5)  // --vy-accent group = 3.64
        ->and(gatedRatio([31, 110, 75], [211, 192, 168]))->toBeLessThan(4.5); // #1F6E4B verified  = 3.50
});

it('⚠️ --vy-on-grad was NOT sufficient here — the mapping had to go further than the usual one', function () {
    // Worth pinning as a fact rather than a preference: the crumb sits on a part of the page gradient DARKER
    // than the stop `--vy-on-grad` was measured against, so the token that fixed `.vy-topbar-sub` reaches only
    // 4.17 here. A future "tidy-up" that swaps ink for on-grad would silently reintroduce the failure.
    expect(gatedRatio([84, 74, 68], [234, 165, 124]))->toBeLessThan(4.5);
});
