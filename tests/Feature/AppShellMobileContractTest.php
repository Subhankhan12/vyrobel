<?php

declare(strict_types=1);

use PHPUnit\Framework\Assert;

/**
 * ⚠️⚠️ WHAT THIS FILE CAN AND CANNOT PROVE — READ BEFORE TRUSTING A GREEN RUN.
 *
 * The operator back office was **two to four times the viewport width on a phone**: 29 of 31 reachable
 * screens overflowed at 375px, by 379px to 986px. The owner portal overflowed on 17 of 29. Both were
 * measured in a real browser (`docs/APP-AUDIT-2026-09-02.md`), and the cause was a **bare `1fr` grid
 * track** — `minmax(auto, 1fr)`, whose `auto` is a min-content floor a track cannot shrink below —
 * together with shell children computing `min-width: auto`.
 *
 * ⚠️ THIS GUARDS THE CLASS, NOT THOSE TWO LINES. The same bare-`1fr` defect was fixed on the marketing
 * footer grid days earlier; naming the operator's line 1635 would catch nothing the next time it appears.
 *
 * ⚠️⚠️ WHAT IS **NOT** PROVEN HERE: that any rendered page does not overflow. A source guard reads CSS
 * text; only a browser can measure layout. The measurements are RECORDED, not asserted:
 *   🔬 2026-09-02, 375×812, chrome-headless-shell over CDP —
 *      operator: 29 screens overflowing (379–986px)  →  **2** (`/app/schedule`, `/app/schedule/pdf`, 95px)
 *      owner:    17 screens overflowing (14–374px)   →  **10** (14–232px, page-level offenders)
 *      desktop 1280px: 0 overflow before AND after, no status or focusable-count change.
 * ⚠️ If a colour or a component changes, this file stays green and the layout can move underneath it.
 */
function asmShellCss(string $file): string
{
    $s = (string) file_get_contents(resource_path('views/components/layouts/'.$file));
    preg_match('/<style\b[^>]*>(.*?)<\/style>/s', $s, $m);

    // ⚠️ CSS COMMENTS SHIP AND HAVE FOOLED FIVE SOURCE SCANS IN THIS PROJECT — one of them read
    //    `translateX(-120%)` as page prose. Strip them before matching anything.
    return (string) preg_replace('#/\*.*?\*/#s', '', $m[1] ?? '');
}

/** Every `@media (max-width: …)` block's body, so a phone rule can be told from a desktop one. */
function asmNarrowBlocks(string $css): array
{
    preg_match_all('/@media\s*\(max-width:\s*(\d+)px\)\s*\{((?:[^{}]|\{[^{}]*\})*)\}/s', $css, $m, PREG_SET_ORDER);

    return array_values(array_filter(
        array_map(fn (array $x): array => ['width' => (int) $x[1], 'body' => $x[2]], $m),
        fn (array $b): bool => $b['width'] <= 900
    ));
}

it('⚠️⚠️ declares no bare 1fr grid track in a phone breakpoint, in either shell', function () {
    foreach (['operator.blade.php', 'owner.blade.php'] as $shell) {
        $blocks = asmNarrowBlocks(asmShellCss($shell));

        Assert::assertNotEmpty($blocks, "[{$shell}] no narrow breakpoint found — this guard would be vacuous");

        foreach ($blocks as $b) {
            preg_match_all('/grid-template-columns:\s*([^;}]+)/', $b['body'], $decls);

            foreach ($decls[1] as $decl) {
                $decl = trim($decl);

                // `repeat(auto-fill|auto-fit, minmax(Npx, 1fr))` is a different, self-limiting shape.
                if (str_contains($decl, 'auto-fill') || str_contains($decl, 'auto-fit')) {
                    continue;
                }

                // ⚠️ STRIP `minmax(…)` FIRST. My first version matched the `1fr` INSIDE `minmax(0, 1fr)`
                //    and red on the very fix it exists to require — a guard that fails correct work is a
                //    wrong guard. Only a track written as a bare `1fr` may match.
                $bare = (string) preg_replace('/minmax\([^)]*\)/', 'MINMAX', $decl);

                Assert::assertDoesNotMatchRegularExpression('/(^|\s)1fr(\s|$)/', $bare,
                    "[{$shell}] a @media (max-width: {$b['width']}px) rule declares a bare `1fr` track: ".
                    "\"{$decl}\". A bare `1fr` is `minmax(auto, 1fr)`, and that `auto` is a min-content ".
                    'floor — the track cannot shrink below its content, which is what made 29 operator '.
                    'screens two to four times the viewport width. Use `minmax(0, 1fr)`.');
            }
        }
    }
});

it('⚠️ lets both shells’ children shrink below their content on a phone', function () {
    foreach (['operator.blade.php', 'owner.blade.php'] as $shell) {
        $narrow = implode("\n", array_column(asmNarrowBlocks(asmShellCss($shell)), 'body'));

        // ⚠️ THE SECOND HALF OF THE MECHANISM, and it is not the same as the track. MEASURED: with the
        //    track already 375px wide, `.vy-side` and `.vy-main` still rendered 985px, because a grid or
        //    flex child computes `min-width: auto` and refuses to shrink below its own content.
        foreach (['.vy-side', '.vy-main'] as $child) {
            Assert::assertMatchesRegularExpression(
                '/'.preg_quote($child, '/').'[^{}]*\{[^{}]*min-width:\s*0/',
                $narrow,
                "[{$shell}] {$child} has no `min-width: 0` in a phone breakpoint — it will size to its ".
                'content and overflow the track no matter how the track is declared'
            );
        }
    }
});

/**
 * ⚠️⚠️ THE CLASS: A PRIMARY ACTION THAT SCROLLS OUT OF THE VIEWPORT INSIDE A SCROLL CONTAINER.
 *
 * The housekeeping list is a 720px table in `.vy-utable-wrap` (`overflow-x: auto`) — the correct house
 * pattern for a wide table, where the row is the unit of meaning. But its LAST column held the only
 * control that advances a task, so at 375px the "Start" button rendered at **x=669**: 294px outside the
 * viewport. A housekeeper had to discover a sideways scroll inside a table before they could mark a room
 * clean. 🔬 Measured, and 🔬 after pinning that column: **x=290–344, inside the viewport**, and clicking it
 * advanced a real task (`pending → in_progress`, `started_at` stamped).
 *
 * ⚠️ WHAT THIS PROVES: that a table whose last cell carries a submit button declares the modifier that
 * pins it. ⚠️⚠️ WHAT IT CANNOT PROVE: where anything actually renders. A source guard reads Blade; only a
 * browser knows a bounding box. The positions above are RECORDED with their date, never asserted here.
 */
it('⚠️⚠️ pins the action column of any wide table whose last cell submits', function () {
    $offenders = [];

    foreach (glob(resource_path('views/livewire/**/*.blade.php')) + glob(resource_path('views/livewire/*.blade.php')) as $file) {
        // ⚠️ Blade comments do not ship, but they DO contain the words this scan looks for — and a
        //    comment-blind scan has misfired six times in this project. Strip them first.
        $src = (string) preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($file));

        if (! str_contains($src, 'vy-utable-wrap')) {
            continue;
        }

        // Each <table class="vy-utable…"> … </table> in this view.
        preg_match_all('/<table[^>]*class="([^"]*vy-utable[^"]*)"[^>]*>(.*?)<\/table>/s', $src, $tables, PREG_SET_ORDER);

        foreach ($tables as [$whole, $classes, $body]) {
            // Does its LAST cell of a row carry a submit button? (the shape that scrolls away)
            $lastCellSubmits = (bool) preg_match('/<td[^>]*>(?:(?!<\/td>).)*type="submit"(?:(?!<td[^>]*>).)*<\/td>\s*<\/tr>/s', $body);

            if (! $lastCellSubmits) {
                continue;
            }

            if (! str_contains($classes, 'vy-utable--stick-action')) {
                $offenders[] = basename($file).' — a table whose last column submits, without --stick-action';
            }
        }
    }

    Assert::assertSame([], $offenders,
        'a wide table puts its primary action in the last column, inside a horizontal scroller, without '.
        'pinning it. At 375px that action renders outside the viewport and the person who needs it cannot '.
        "reach it without discovering a sideways scroll:\n  ".implode("\n  ", $offenders));
});
