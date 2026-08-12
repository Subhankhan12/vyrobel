<?php

/*
 * DO-EMAIL-2 (2026-08-12) — the three pre-AA greys on the operator/owner WEB screens.
 *
 * ⚠️ NOT A REGRESSION, and not an instrument failure either. These screens were gated (operator 42/42,
 * owner 32/32) and their probes were sound. The greys survived for a blunter reason: **the markup was not on
 * the page when the probe ran.** All eleven occurrences in `livewire/operator/owners.blade.php` sit inside
 * `@can('users.manage')` — a permission the demo Hotel Manager the gates were driven as does NOT hold — and
 * inside an Alpine `x-show="open"` modal on top of that. A browser probe as that role finds zero of them.
 * Measuring required BOTH a role swap (to a `Property Owner`) and a FORCED Alpine state (§N.8e / G10).
 *
 * ⚠️ THE ROOT CAUSE IS TOKEN DRIFT, WHICH IS WHY THE FIX IS A TOKEN, NOT ANOTHER LITERAL. The three
 * literals ARE the pre-AA values of the two text tokens:
 *     #8A8079 → the old --vy-muted   (now rgb(95,88,83)  = #5F5853)
 *     #A69A92 → the old --vy-faint   (now rgb(108,100,95) = #6C645F)
 *     #A99E95 → a near-neighbour of the old --vy-faint, hardcoded on a "Powered by Vyrobel" line
 * Hardcoding them froze those runs at the pre-remediation palette while the tokens moved underneath.
 * On WEB a token is available (unlike email), so every TEXT occurrence now reads `var(--vy-muted)` /
 * `var(--vy-faint)` and will follow the token if it is ever retuned again.
 *
 * MEASURED ON THE REAL RENDER (pixel-sampled, two self-validating anchors — never getComputedStyle):
 *   invite modal, plate #F8F4EF (glass over a scrim — no declared computation could produce it)
 *       anchors #2B2320 14.06 (pass) · #B7ADA4 2.01 (fail)
 *       #A69A92 2.50 FAIL → var(--vy-faint) 5.29 PASS
 *       #8A8079 3.52 FAIL → var(--vy-muted) 6.38 PASS
 *   operator Settings "Powered by Vyrobel", plate #F6EBE2
 *       #A99E95 2.23 FAIL → var(--vy-faint) 4.94 PASS
 *   billing/feature-locked <p>, plate #FCF7F2
 *       rgb(138,128,121) 3.62 FAIL → #5F5853 6.56 PASS
 *       ⚠️ a LITERAL, not a token: that page is a standalone 403 document with its own <style> and no
 *          --vy-* in scope. Logged as a missing-token item rather than pretended away.
 *
 * DELIBERATELY NOT CHANGED (each measured or classified, none of them a text-contrast defect):
 *   • two status DOTS (#8A8079 backgrounds) and three decorative input-adornment SVG strokes — non-text,
 *     each paired with a real visible label. The strokes were retargeted to the literal #6C645F because
 *     `var()` in an SVG presentation attribute renders nothing if unsupported, and a vanished decorative
 *     icon is a worse trade than a hardcoded value.
 *   • `--vy-hk-ooo: rgb(138,128,121)` — a housekeeping status FILL token that merely shares the value.
 *     Recolouring a status indicator is a design decision, not a contrast fix.
 *   • `.vy-inv-lbl { color: #9C9089 }` — a FOURTH grey, measured 2.84 on the same plate. Real, out of this
 *     sweep's scope, logged as DO-EMAIL-5.
 */

/** The two operator/owner shells must keep the text tokens at their AA-remediated values. */
it('keeps --vy-muted and --vy-faint at the remediated values in both shells', function () {
    foreach ([
        'resources/views/components/layouts/operator.blade.php',
        'resources/views/components/layouts/owner.blade.php',
    ] as $shell) {
        $css = (string) file_get_contents(base_path($shell));

        expect($css)->toContain('--vy-muted: rgb(95, 88, 83)')
            ->and($css)->toContain('--vy-faint: rgb(108, 100, 95)');
    }
});

it('declares no pre-AA grey as a colour on any operator or owner view', function () {
    $views = collect([
        ...(glob(resource_path('views/livewire/**/*.blade.php')) ?: []),
        ...(glob(resource_path('views/livewire/*.blade.php')) ?: []),
        ...(glob(resource_path('views/operator/*.blade.php')) ?: []),
        ...(glob(resource_path('views/owner/*.blade.php')) ?: []),
        ...(glob(resource_path('views/billing/*.blade.php')) ?: []),
        ...(glob(resource_path('views/components/layouts/*.blade.php')) ?: []),
    ])->unique();

    expect($views)->not->toBeEmpty();

    // Matched as a COLOUR DECLARATION, never as a bare substring: four of the surviving occurrences are
    // prose in comments that document this very remediation, and two of those are CSS comments (which DO
    // ship). A substring guard would fail on its own documentation.
    $patterns = [
        '/color\s*:\s*#(8A8079|A69A92|A99E95)\b/i',
        '/stroke\s*=\s*"#(8A8079|A69A92|A99E95)"/i',
        '/color\s*:\s*rgb\(\s*138\s*,\s*128\s*,\s*121\s*\)/i',
        '/color\s*:\s*rgb\(\s*166\s*,\s*154\s*,\s*146\s*\)/i',
    ];

    $offenders = [];
    foreach ($views as $path) {
        $source = (string) file_get_contents($path);
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $source, $m) === 1) {
                $offenders[] = basename($path).' → '.$m[0];
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('proves the mapping is not vacuous — the old values fail and the new ones pass on the measured plates', function () {
    $ratio = function (string $ink, string $plate): float {
        $lum = static function (string $hex): float {
            $hex = ltrim($hex, '#');
            $f = static function (float $v): float {
                $v /= 255;

                return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
            };

            return 0.2126 * $f((float) hexdec(substr($hex, 0, 2)))
                + 0.7152 * $f((float) hexdec(substr($hex, 2, 2)))
                + 0.0722 * $f((float) hexdec(substr($hex, 4, 2)));
        };
        $a = $lum($ink);
        $b = $lum($plate);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    };

    // #F8F4EF = the pixel-sampled invite-modal plate; #F6EBE2 = the Settings wash; #FCF7F2 = the 403 card.
    foreach (['#F8F4EF', '#F6EBE2', '#FCF7F2'] as $plate) {
        expect($ratio('#8A8079', $plate))->toBeLessThan(4.5)
            ->and($ratio('#A69A92', $plate))->toBeLessThan(4.5)
            ->and($ratio('#A99E95', $plate))->toBeLessThan(4.5)
            ->and($ratio('#5F5853', $plate))->toBeGreaterThanOrEqual(4.5)   // --vy-muted
            ->and($ratio('#6C645F', $plate))->toBeGreaterThanOrEqual(4.5);  // --vy-faint
    }
});
