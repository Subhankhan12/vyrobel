<?php

declare(strict_types=1);

// ⚠️ A CLASS NAME TWO VIEWS' WORTH OF CSS — found 2026-11-11 in a browser, never by a test. The A8 pass moved an x-show element's inline
// `display:inline-flex` into a class, and named it `.vy-cal-confirm` — which the same stylesheet already used for the gradient "Block these dates"
// button. One rule then styled both: the per-block "Remove?" span took the button's gradient and padding, and the button lost its layout. 4,703 tests
// and both engines stayed green; only a browser look at the page showed it. A SECOND top-level definition of one class that carries a `display` is
// where that happens, so it is refused unless it is listed here with its reason.

const CDT_ALLOWED = [
    // the shared segmented box: a later restyle (display:flex, gap, a rounded box) deliberately wins over the first pill rule, which stays
    // merged underneath. Pre-existing and measured when the box was built (2026-10-06); not touched by this guard's pass.
    'resources/views/components/layouts/operator.blade.php .vy-seg' => 'a later restyle wins by design',
];

/** @return list<string> "file selector" for every top-level class a view's <style> defines twice in a way that can clash (see the rule below) */
function cdt_find(string $path, string $src): array
{
    // THE RULE: a second definition of one class is a clash when (a) two of them set `display` to DIFFERENT values, or (b) exactly one sets
    // `display` and another carries what the box LOOKS like (padding, background, border, shadow, radius) — one name, two jobs.
    $display = fn (string $b): ?string => preg_match('/(?:^|;)\s*display\s*:\s*([^;]+)/', $b, $m) === 1 ? trim($m[1]) : null;
    $looks = fn (string $b): bool => preg_match('/(?:^|;)\s*(?:padding|background|border|box-shadow|border-radius)[a-z-]*\s*:/', $b) === 1;

    $hits = [];
    preg_match_all('#<style[^>]*>(.*?)</style>#s', $src, $blocks);
    foreach ($blocks[1] as $css) {
        $css = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;
        $css = preg_replace('#@media[^{]*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}#s', '', $css) ?? $css;   // overrides inside a media query are meant
        $seen = [];
        if (preg_match_all('#(?:^|(?<=\}))\s*(\.[A-Za-z0-9_-]+)\s*\{([^{}]*)\}#', $css, $rules, PREG_SET_ORDER)) {
            foreach ($rules as $r) {
                $seen[$r[1]][] = $r[2];
            }
        }
        foreach ($seen as $selector => $bodies) {
            if (count($bodies) < 2) {
                continue;
            }
            $displays = array_values(array_filter(array_map($display, $bodies), fn ($d): bool => $d !== null));
            $differ = count(array_unique($displays)) > 1;
            $oneWithOtherLooks = count($displays) === 1 && count(array_filter($bodies, fn (string $b): bool => $display($b) === null && $looks($b))) > 0;
            if ($differ || $oneWithOtherLooks) {
                $hits[] = $path.' '.$selector;
            }
        }
    }

    return $hits;
}

it('⚠️ no view stylesheet gives one class two jobs (two displays, or a display beside another rule\'s padding/background) — unless listed with its reason', function () {
    $found = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $abs = str_replace('\\', '/', (string) $file);
        if (! str_ends_with($abs, '.blade.php')) {
            continue;
        }
        $rel = 'resources/views/'.substr($abs, strlen(str_replace('\\', '/', resource_path('views'))) + 1);
        array_push($found, ...cdt_find($rel, (string) file_get_contents($abs)));
    }

    $unlisted = array_values(array_diff($found, array_keys(CDT_ALLOWED)));
    expect($unlisted)->toBe([], "a class defined twice in one view's CSS in a way that can clash — rename the newer one, or list it with its reason:\n".implode("\n", $unlisted));
    // a stale allow-list entry is a lie about the code
    expect(array_values(array_diff(array_keys(CDT_ALLOWED), $found)))->toBe([]);
});

it('CONTROL: the scanner finds exactly the collision that shipped (a second `display` rule on a class the stylesheet already styles)', function () {
    $broken = '<style> .vy-cal-confirm { flex:1; padding:11px; background:red; } .vy-cal-confirm:hover { transform:none; } .vy-cal-confirm { display:inline-flex; gap:6px; } </style>';
    $fixed = '<style> .vy-cal-confirm { flex:1; padding:11px; background:red; } .vy-cal-relconfirm { display:inline-flex; gap:6px; } </style>';
    $inMedia = '<style> .a { display:flex; } @media (max-width:600px) { .a { display:block; } } </style>';
    $sameDisplay = '<style> .a { display:block; margin:0; } .a { display:block; color:red; } </style>';
    $positionOnly = '<style> .a { display:flex; } .a { position:relative; } </style>';

    expect(cdt_find('x', $broken))->toBe(['x .vy-cal-confirm'])
        ->and(cdt_find('x', $fixed))->toBe([])
        ->and(cdt_find('x', $inMedia))->toBe([])
        ->and(cdt_find('x', $sameDisplay))->toBe([])
        ->and(cdt_find('x', $positionOnly))->toBe([])
        ->and(cdt_find('x', '<style> .a { display:flex; } .a { display:block; } </style>'))->toBe(['x .a']);
});
