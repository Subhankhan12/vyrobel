<?php

declare(strict_types=1);

/**
 * ⚠️ THE HORIZONTAL-OVERFLOW CONTRACT (docs/REMAINING-OVERFLOW.md).
 *
 * Twelve screens scrolled sideways on a 375px phone — by 14px to 232px — and EIGHT of them were one
 * defect: a bare `1fr` grid track, which is `minmax(auto, 1fr)`, whose `auto` is a min-content floor the
 * track cannot shrink below. This file holds the sites that were fixed, and the ceiling that stops the
 * defect spreading into new ones.
 *
 * ⚠️ THIS IS A SOURCE CONTRACT, NOT A MEASUREMENT, AND THE DIFFERENCE IS STATED SO NOBODY READS MORE INTO
 * A GREEN THAN IT CARRIES. Whether a page actually scrolls sideways can only be answered by a browser at a
 * real viewport; the measurement that cleared all twelve is recorded in the doc with its method. What runs
 * here is narrower and durable: the specific declarations that were fixed still carry their floor, the
 * count of bare tracks does not grow, and the deliberately-withdrawn card-scroller rule does not return.
 */
it('keeps an explicit floor on every grid track that was overflowing at 375px', function () {
    // file => the substrings that must still be present for that screen to stay narrow
    $contract = [
        'resources/views/components/layouts/owner.blade.php' => [
            '.vy-kpi-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));',
            '.vy-topbar { flex-wrap: wrap; }',
            '.vy-topbar-actions { min-width: 0; flex-wrap: wrap; }',
            '.vy-ov-payout-halo { left: 0; right: 0; }',
        ],
        'resources/views/components/layouts/operator.blade.php' => [
            '.vy-sch-panels { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));',
        ],
        'resources/views/livewire/owner/reports.blade.php' => [
            '.vy-rep-kpis { display:grid; grid-template-columns:repeat(3,minmax(0,1fr));',
            '.vy-rep-bar-col { min-width:0; }',
        ],
        'resources/views/livewire/owner/rates.blade.php' => [
            '.vy-rt-fieldrow { display:grid; grid-template-columns:repeat(3,minmax(0,1fr));',
        ],
        'resources/views/livewire/owner/block-calendar.blade.php' => [
            '.vy-cal-cell, .vy-cal-dow { min-width:0; overflow:hidden; }',
        ],
        'resources/views/livewire/owner/messages.blade.php' => [
            '.vy-msg-rail { width:auto; min-width:0; }',
        ],
    ];

    foreach ($contract as $file => $needles) {
        $source = file_get_contents(base_path($file));

        foreach ($needles as $needle) {
            // ⚠️ `assertStringContainsString`, NOT `expect()->toContain()`. Pest's `toContain` is VARIADIC:
            //    a second argument is read as ANOTHER NEEDLE, not as a failure message, so the friendly
            //    explanation would silently become an assertion that the shell contains its own error text
            //    — and this test went red on exactly that before anyone read the message it was printing.
            $this->assertStringContainsString(
                $needle,
                $source,
                "{$file} lost the rule that keeps it inside a 375px viewport: {$needle}"
            );
        }
    }
});

it('wraps the portfolio table in the house scroll wrapper rather than restoring the withdrawn card rule', function () {
    $view = file_get_contents(base_path('resources/views/livewire/operator/portfolio.blade.php'));

    // The durable fix the shell asked for BY NAME (operator.blade.php:1700).
    expect($view)->toContain('<div class="vy-utable-wrap">');

    // ⚠️ AND THE WITHDRAWN ONE MUST NOT COME BACK. A `.vy-card:has(> .vy-table) { overflow-x: auto }`
    //    rule cleared the same 98px and was pulled deliberately, because turning a whole card into a
    //    horizontal scroller can put a card's action button behind a gesture. The wrapper above scrolls
    //    the TABLE; that rule scrolled the CARD, action included.
    $shell = file_get_contents(base_path('resources/views/components/layouts/operator.blade.php'));
    $shellCss = preg_replace('#/\*.*?\*/#s', '', $shell);

    expect($shellCss)->not->toContain(':has(> .vy-table)')
        ->and($shellCss)->not->toContain(':has(>.vy-table)');
});

it('does not grow the number of bare 1fr grid tracks', function () {
    // ⚠️ A CEILING, NOT A ZERO, AND THE NUMBER IS HONEST ABOUT WHAT IT MEANS. 236 sites across the views
    //    still write a bare `fr` track. They are NOT all defects: a track only overflows when its content
    //    is wider than the room it is given, and most of these are never that. Sweeping all 236 is a
    //    separate piece of work with its own measurement, and inventing a "fixed" for a page nobody has
    //    measured would be the fabrication this project refuses. What this holds is the direction: the
    //    twelve measured screens are fixed above, and the backlog cannot quietly grow underneath them.
    $offenders = vyBareFrTracks();

    expect(count($offenders))->toBeLessThanOrEqual(236,
        'A new bare `1fr` grid track was added. `1fr` is `minmax(auto, 1fr)` and that `auto` is a '
        .'min-content floor — write `minmax(0, 1fr)`. Newest sites: '
        .json_encode(array_slice($offenders, -3)));
});
