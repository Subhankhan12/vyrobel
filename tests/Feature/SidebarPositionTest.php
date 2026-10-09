<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * ⚠️ THE SIDEBAR STAYS WHERE IT WAS (2026-10-09, his report: "if I open the owners or the reports … upon the page refresh the
 * sidebar is going up again … it shouldn't be shaking, it shouldn't be moving … fix that issue across the entire application").
 *
 * 🔬 Why (measured in a browser, 1280 × 600): every sidebar is its own scroll box, every menu link loads a new page, so the
 * new sidebar started at the top with the item just opened off screen; and every sidebar replayed a 0.45 s slide-in on every
 * page. Fixed in all three shells by ONE component, `<x-sidebar-position>`, placed straight after the sidebar.
 *
 * ⚠️ WHAT THESE TESTS CAN AND CANNOT SEE: they prove each shell carries the component, aimed at the box that actually scrolls,
 * running inline before the page is drawn, with its rules intact — and that no sidebar animates. Whether the position is kept
 * is a browser fact: 🖥 walked 2026-10-09 in all three realms (operator 520 px over six loads, owner 580 over six, admin 313
 * over four; a direct link and a shorter window both show the current item; 390 px unchanged; no console error).
 */
uses(DatabaseTransactions::class);

/** The keeper as each shell renders it: right after the sidebar, inline, aimed at `$box`, remembering under `$key`. */
function keeperPattern(string $box, string $key): string
{
    return '#</aside>\s*(?:<!--.*?-->\s*)?<script>\s*\(function \(\) \{\s*var side = document\.querySelector\(\''
        .preg_quote($box, '#').'\'\);.*?var KEY = \'vy\.sidebar\.\' \+ \''.preg_quote($key, '#').'\';#s';
}

it('keeps the operator sidebar in place — the keeper sits right after it, aimed at the sidebar itself', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(operatorUser($s))->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->toMatch(keeperPattern('.vy-side', 'operator'));
});

it('keeps the owner portal menu in place — aimed at the menu INSIDE the sidebar, the box that scrolls there', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(ownerRoleUser($s))->get('/owner')->assertOk()->getContent();

    // ⚠️ `.vy-side` alone is the wrong box in the owner portal: its scroll height equals its height, and the menu inside
    //    it (`.vy-nav`) is what scrolls — measured in the walk, where the first version restored nothing there.
    expect($html)->toMatch(keeperPattern('.vy-side .vy-nav', 'owner'));
});

it('keeps the admin console sidebar in place', function () {
    $html = (string) $this->actingAs(platformAdmin(), 'admin')->get(route('admin.customers.index'))->assertOk()->getContent();

    expect($html)->toMatch(keeperPattern('.ad-side', 'admin'));
});

it('runs before the page is drawn, keeps the current item in view, and never creeps', function () {
    $s = seedScenario(1);
    $html = (string) $this->actingAs(operatorUser($s))->get('/app/dashboard')->assertOk()->getContent();
    preg_match('#</aside>\s*(<script>.*?</script>)#s', $html, $m);
    $script = $m[1] ?? '';

    expect($script)->not->toBe('')
        // Inline and synchronous: a deferred or module script runs after the first paint, which is the jump itself.
        ->and($script)->toStartWith('<script>')
        // The page you are on is always shown, even with nothing remembered (a bookmark, an email link).
        ->and($script)->toContain('side.querySelector(\'[aria-current="page"]\')')
        ->and($script)->toContain('if (c.top < s.top || c.bottom > s.bottom) {')
        // ⚠️ No creep: the exact value (rounding 520.5 up moved it a pixel a page) and no scroll anchoring (the badge that
        //    appears after load shifted it half a pixel a page) — both measured in the walk.
        ->and($script)->toContain("side.style.overflowAnchor = 'none';")
        ->and($script)->toContain('store.setItem(KEY, String(side.scrollTop))')
        ->and($script)->toContain('parseFloat(saved)')
        ->and($script)->not->toContain('Math.round')
        // Storage can throw (a private window, blocked site data): every call is guarded.
        ->and(substr_count($script, 'try {'))->toBe(3);
});

it('animates no sidebar — the same on every page, so it stays still', function () {
    $rules = [
        'resources/views/components/layouts/operator.blade.php' => '.vy-side {',
        'resources/views/components/layouts/owner.blade.php' => '.vy-side {',
        'resources/views/components/admin/shell.blade.php' => '.ad-side {',
    ];

    foreach ($rules as $file => $selector) {
        $css = (string) file_get_contents(base_path($file));
        $start = strpos($css, '        '.$selector);
        expect($start)->not->toBeFalse("{$selector} in {$file}");

        $body = substr($css, $start, strpos($css, '}', $start) - $start);
        // ⚠️ Comments stripped first — the rule carries a note that says "No entrance animation", and a guard that reads
        //    its own needle would pass or fail on the note rather than the CSS.
        $declarations = (string) preg_replace('#/\*.*?\*/#s', '', $body);

        expect($declarations)->toContain('overflow-y: auto')   // the right rule: the scroll box itself
            ->and($declarations)->not->toContain('animation');
    }
});
