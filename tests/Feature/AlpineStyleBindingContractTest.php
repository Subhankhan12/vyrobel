<?php

declare(strict_types=1);

// ⚠️⚠️ A STRING :style REPLACES THE WHOLE style ATTRIBUTE (2026-09-29, found on the work order page's Mark resolved dialog
// while the Maintenance board was rebuilt).
//
// Alpine binds `:style` two ways. An OBJECT — `{ opacity: '.5' }` — is merged into the element's inline style, property by
// property. A STRING — `'opacity:.5'` — REPLACES the style attribute outright. So an element that carries a static
// `style="width:38px;height:22px;…"` beside a string `:style` loses every static property the moment Alpine binds it.
// 🖥 Measured on the work order page: the "Notify the front desk" switch rendered 16×6 px (its style attribute read back as
// the bound background alone); the Reassign dialog's people lost their row layout; the update composer's Note / Progress /
// Part ordered tabs and its Post update button lost their padding.
//
// ⚠️ THE FIX IS THE OBJECT FORM, with the same values. ⚠️ WHAT THIS PROVES: the pattern is gone from the views it was fixed
// in and is not growing elsewhere. It reads the views; it does not run Alpine — that the controls render was measured in a
// browser.
//
// ⚠️ A CEILING, NOT A ZERO. 10 more sites carry the pattern on other screens (schedule, notification preferences, owner units
// and reports, housekeeping, maintenance settings, email previews). Each needs looking at in a browser, and that sweep is
// its own pass (asked for, not assumed). The ceiling lets the number fall and never rise.

const VY_STRING_STYLE_CEILING = 10;

/** The views this pattern was removed from — it must stay gone from these entirely. */
const VY_STRING_STYLE_FIXED_VIEWS = [
    'livewire/maintenance-list.blade.php',
    'livewire/maintenance-ticket-detail.blade.php',   // 2026-09-29 — both dialogs and the update composer (14 sites)
];

/**
 * Every tag in a view that carries a static `style="…"` AND a `:style` / `x-bind:style` whose value is a string rather
 * than an object (or a choice between objects).
 *
 * ⚠️ The tag walk respects quoted attribute values, so an Alpine expression holding a `>` cannot end a tag early, and Blade
 * comments are stripped first (newlines kept, so line numbers stay true) — a guard must never read its own prose.
 *
 * @return list<string> "path:line"
 */
function vyStringStyleBindings(string $source, string $label): array
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

        if (preg_match('/\sstyle\s*=\s*"/', $tag) !== 1 || preg_match('/\s(?::|x-bind:)style\s*=\s*"([^"]*)"/', $tag, $bound) !== 1) {
            continue;
        }

        $value = trim($bound[1]);
        if (str_starts_with($value, '{') || preg_match('/\?\s*\{/', $value) === 1) {
            continue;   // an object, or a choice between objects — merged, not replaced
        }

        $hits[] = $label.':'.(substr_count(substr($source, 0, $lt), "\n") + 1);
    }

    return $hits;
}

/** @return list<string> */
function vyStringStyleCensus(): array
{
    $hits = [];
    $root = resource_path('views');

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
        if (! $file->isFile() || ! str_ends_with((string) $file->getFilename(), '.blade.php')) {
            continue;
        }

        $label = str_replace($root.'/', '', (string) $file->getRealPath());
        array_push($hits, ...vyStringStyleBindings((string) file_get_contents((string) $file->getRealPath()), $label));
    }

    sort($hits);

    return $hits;
}

it('⚠️⚠️ leaves no string :style beside a static style on the Maintenance pages', function () {
    $offenders = array_values(array_filter(
        vyStringStyleCensus(),
        fn (string $hit): bool => in_array(explode(':', $hit)[0], VY_STRING_STYLE_FIXED_VIEWS, true),
    ));

    expect($offenders)->toBe([]);
});

it('⚠️ does not let the pattern grow anywhere else', function () {
    $hits = vyStringStyleCensus();

    expect(count($hits))->toBeLessThanOrEqual(VY_STRING_STYLE_CEILING,
        "An element now carries a static style AND a string :style. Alpine replaces the whole style attribute with a string,\n"
        ."so every static property is lost. Bind an object instead ({ opacity: '.5' }). Sites:\n".implode("\n", $hits));
});

it('actually detects the shape it is guarding — and not the shapes that are fine', function () {
    // ⚠️ NON-VACUITY. A scanner that silently stopped matching would read exactly like a clean codebase.
    expect(vyStringStyleBindings("<button style=\"width:38px\" :style=\"on ? 'background:red' : ''\">", 'probe'))->toBe(['probe:1'])
        ->and(vyStringStyleBindings("<span :style=\"a > 0 ? 'left:19px' : 'left:3px'\"\n style=\"position:absolute\">", 'probe'))->toBe(['probe:1'])
        ->and(vyStringStyleBindings("<div style=\"gap: 4px\" x-bind:style=\"'color:red'\">", 'probe'))->toBe(['probe:1']);

    // an object · a choice between objects · no static style to lose · a comment about it
    expect(vyStringStyleBindings("<button style=\"width:38px\" :style=\"{ background: on ? 'red' : 'blue' }\">", 'probe'))->toBe([])
        ->and(vyStringStyleBindings("<button style=\"width:38px\" :style=\"on ? { opacity: '.5' } : {}\">", 'probe'))->toBe([])
        ->and(vyStringStyleBindings("<button :style=\"on ? 'opacity:.5' : ''\">", 'probe'))->toBe([])
        ->and(vyStringStyleBindings("{{-- <b style=\"x\" :style=\"'y:z'\"> --}}", 'probe'))->toBe([]);

    // and the census walks the real views — it finds the other screens' sites, so it is reading something
    expect(count(vyStringStyleCensus()))->toBeGreaterThan(0);
});
