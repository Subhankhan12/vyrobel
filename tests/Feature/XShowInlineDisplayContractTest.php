<?php

declare(strict_types=1);

// ⚠️⚠️ X-SHOW ERASES AN INLINE DISPLAY (2026-09-29, found on /app/guests — "the Add guest dialog is not in the centre").
//
// Alpine's `x-show` HIDES an element by setting an inline `display: none`, and SHOWS it again by REMOVING the inline
// `display` property altogether. So an element that carries both `x-show` and an inline `style="display: flex"` loses
// its flex the first time it is hidden: when it comes back, it falls back to whatever the stylesheet says — `block` for
// a div. A dialog backdrop that centred itself with an inline `display: flex; align-items: center; justify-content:
// center` therefore opened in the TOP-LEFT corner. 🖥 Measured on the Add guest dialog in a 1280×900 window: its centre
// sat at (260, 242), not (640, 450), and the backdrop's computed display was `block`.
//
// ⚠️ THE FIX IS A CLASS, NOT A BIGGER INLINE STYLE. A display that comes from a stylesheet survives x-show (removing the
// inline property uncovers it), so dialogs centre with the shared `.vy-modal-backdrop` and panels lay out with classes.
//
// ⚠️ WHAT THIS PROVES, AND WHAT IT CANNOT. It reads the views; it does not run Alpine. It proves the PATTERN is absent
// where it was fixed and is not growing elsewhere. That the fixed dialogs now open centred was measured in a browser.
//
// ⚠️ A CEILING, NOT A ZERO. 39 more sites carry the same pattern in other screens (rates, units, security, owner pages) —
// 45 after the Guests pass, 39 after the Maintenance pass fixed its six.
// Most are panels whose content happens to lay out acceptably as `block`; each needs looking at in a browser, and that
// sweep is its own pass (asked for, not assumed). The ceiling lets the number fall and never rise.

const VY_XSHOW_INLINE_DISPLAY_CEILING = 39;

/** The views the Guests and Maintenance passes fixed — the pattern must be gone from these entirely. */
const VY_XSHOW_FIXED_VIEWS = [
    'livewire/operator/guests-list.blade.php',
    'livewire/operator/guest-profile.blade.php',
    'livewire/maintenance-list.blade.php',            // 2026-09-29, the Maintenance rebuild (4 sites)
    'livewire/maintenance-ticket-detail.blade.php',   // 2026-09-29, the same pass (2 sites)
];

/**
 * Every tag in a view that carries `x-show` AND a static inline display other than `none`.
 *
 * ⚠️ The tag walk respects quoted attribute values, so an Alpine expression holding a `>` cannot end a tag early, and
 * Blade comments are stripped first (newlines kept, so line numbers stay true) — a guard must never read its own prose.
 *
 * @return list<string> "path:line"
 */
function vyXShowInlineDisplay(string $source, string $label): array
{
    $source = (string) preg_replace_callback('/\{\{--.*?--\}\}/s', fn (array $m): string => str_repeat("\n", substr_count($m[0], "\n")), $source);
    $hits = [];
    $length = strlen($source);
    $i = 0;

    while (($lt = strpos($source, '<', $i)) !== false) {
        if ($lt + 1 >= $length || preg_match('/[a-zA-Z]/', $source[$lt + 1]) !== 1) {
            $i = $lt + 1;

            continue;
        }

        $quote = null;
        $end = null;
        for ($j = $lt; $j < $length; $j++) {
            $ch = $source[$j];
            if ($quote !== null) {
                if ($ch === $quote) {
                    $quote = null;
                }

                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;

                continue;
            }
            if ($ch === '>') {
                $end = $j;
                break;
            }
        }
        if ($end === null) {
            break;
        }

        $tag = substr($source, $lt, $end - $lt + 1);
        $i = $end + 1;

        if (preg_match('/\sx-show(?:\.[\w.]+)?\s*=/', $tag) !== 1 || preg_match('/\sstyle\s*=\s*"([^"]*)"/', $tag, $style) !== 1) {
            continue;
        }

        if (preg_match('/(?:^|;)\s*display\s*:\s*(?!none\b)[a-z-]+/i', $style[1]) === 1) {
            $hits[] = $label.':'.(substr_count(substr($source, 0, $lt), "\n") + 1);
        }
    }

    return $hits;
}

/** @return list<string> */
function vyXShowInlineDisplayCensus(): array
{
    $hits = [];
    $root = resource_path('views');

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
        if (! $file->isFile() || ! str_ends_with((string) $file->getFilename(), '.blade.php')) {
            continue;
        }

        $label = str_replace($root.'/', '', (string) $file->getRealPath());
        array_push($hits, ...vyXShowInlineDisplay((string) file_get_contents((string) $file->getRealPath()), $label));
    }

    sort($hits);

    return $hits;
}

it('⚠️⚠️ leaves no element on the Guests and Maintenance pages whose display x-show would erase', function () {
    $offenders = array_values(array_filter(
        vyXShowInlineDisplayCensus(),
        fn (string $hit): bool => in_array(explode(':', $hit)[0], VY_XSHOW_FIXED_VIEWS, true),
    ));

    expect($offenders)->toBe([]);
});

it('⚠️ does not let the pattern grow anywhere else', function () {
    $hits = vyXShowInlineDisplayCensus();

    expect(count($hits))->toBeLessThanOrEqual(VY_XSHOW_INLINE_DISPLAY_CEILING,
        "An element now carries x-show AND an inline display. x-show removes the inline display when it shows the element,\n"
        ."so it comes back as `block`. Put the display in a class. Sites:\n".implode("\n", $hits));
});

it('actually detects the shape it is guarding — and not the shapes that are fine', function () {
    // ⚠️ NON-VACUITY. A scanner that silently stopped matching would read exactly like a clean codebase.
    expect(vyXShowInlineDisplay('<div x-show="open" style="position: fixed; display: flex;">', 'probe'))->toBe(['probe:1'])
        ->and(vyXShowInlineDisplay("<div class=\"x\"\n x-show=\"a > 0\" style=\"display:grid\">", 'probe'))->toBe(['probe:1'])
        ->and(vyXShowInlineDisplay('<div x-show.transition="a" style="gap: 4px; display: inline-flex">', 'probe'))->toBe(['probe:1']);

    // a class carries the display · `display: none` is what x-show itself writes · no x-show at all · a comment about it
    expect(vyXShowInlineDisplay('<div x-show="open" class="vy-modal-backdrop">', 'probe'))->toBe([])
        ->and(vyXShowInlineDisplay('<div x-show="open" style="display: none">', 'probe'))->toBe([])
        ->and(vyXShowInlineDisplay('<div style="display: flex">', 'probe'))->toBe([])
        ->and(vyXShowInlineDisplay('{{-- <div x-show="open" style="display: flex"> --}}', 'probe'))->toBe([]);

    // and the census walks the real views — it finds the other screens' sites, so it is reading something
    expect(count(vyXShowInlineDisplayCensus()))->toBeGreaterThan(0);
});
