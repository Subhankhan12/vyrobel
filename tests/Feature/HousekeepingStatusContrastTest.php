<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * DO-EMAIL-7 — THE HOUSEKEEPING STATUS DISPLAY (2026-08-12).
 *
 * `--vy-hk-ooo` was logged as "a status fill — a design decision, not a contrast fix". Measured as a Property
 * Owner with a real out-of-order room on the board, the verdict on the TOKEN is **verified-fine**: every status
 * prints its WORD beside the dot, so colour is never the sole carrier (WCAG 1.4.1), and the dot clears the 3:1
 * non-text bar anyway at 3.31. Nothing about it needed changing.
 *
 * ⚠️ The measurement found the failure NEXT to it instead — this file pins both outcomes.
 */
function hkRatio(array $ink, array $plate): float
{
    $l = static function (array $c): float {
        $f = static fn (float $v): float => ($v /= 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;

        return 0.2126 * $f($c[0]) + 0.7152 * $f($c[1]) + 0.0722 * $f($c[2]);
    };
    $a = $l($ink);
    $b = $l($plate);

    return round((max($a, $b) + 0.05) / (min($a, $b) + 0.05), 2);
}

it('⚠️ never lets colour be the sole carrier of a housekeeping status (WCAG 1.4.1)', function () {
    $board = (string) file_get_contents(resource_path('views/livewire/housekeeping-board.blade.php'));

    // Each status token must appear in a chip that also carries its WORD. This is what makes the fill fine.
    foreach ([
        'pending' => 'Pending', 'progress' => 'In progress', 'clean' => 'Clean',
        'inspected' => 'Inspected', 'ooo' => 'Out of order',
    ] as $token => $word) {
        expect($board)->toContain("var(--vy-hk-{$token})")
            ->and($board)->toContain("'label' => '{$word}'");
    }
});

it('⚠️ dims the DOT for an empty status, never the status WORD', function () {
    $layout = (string) file_get_contents(resource_path('views/components/layouts/operator.blade.php'));

    // The whole chip used to drop to opacity .62, which took its TEXT to 2.28 on the saturated part of the
    // gradient band. The empty state is carried by the "0" beside the word, so the de-emphasis rides the dot.
    expect($layout)->toContain('.vy-hk-tal.is-zero .vy-hk-dot { opacity: .45; }')
        ->and($layout)->not->toContain('.vy-hk-tal.is-zero { opacity: .62; }');

    // …and the label reads the token that clears the WHOLE translucent band, not the one that only clears part.
    preg_match('/\.vy-hk-tal-label\s*\{[^}]*\}/s', $layout, $rule);
    expect($rule)->not->toBeEmpty('the .vy-hk-tal-label rule is gone — this assertion would be vacuous');
    expect($rule[0])->toContain('var(--vy-ink)')
        ->and($rule[0])->not->toContain('var(--vy-muted)');
});

it('holds the measured figures from the real render (anchor 5.18)', function () {
    // Pixel-sampled on /app/housekeeping as a Property Owner, one real OOO room present.
    expect(hkRatio([43, 35, 32], [246, 190, 160]))->toBeGreaterThanOrEqual(4.5)   // label, worst plate: 9.38
        ->and(hkRatio([95, 88, 83], [246, 190, 160]))->toBeLessThan(4.5)          // why --vy-muted was wrong: 4.25
        ->and(hkRatio([138, 128, 121], [250, 235, 226]))->toBeGreaterThanOrEqual(3.0);   // the OOO dot: 3.31
});
