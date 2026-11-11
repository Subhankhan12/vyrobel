<?php

declare(strict_types=1);

use App\Support\I18n\Locales;

// ⚠️ D-RTL-TEXT-ARROWS (found 2026-11-11 by the Persian translator, then by a sweep; fixed 2026-11-11, his go).
// The Arabic/Persian chevron rule turns the app's SVG chevrons round, but a literal `→` a Blade file writes beside a translated label cannot
// be selected by CSS — it kept pointing right in a page that reads from the right. Each carries `class="vy-flip-rtl"` (the stylesheet turns it
// round); a text a CLASS or a script builds ("5 Oct → 8 Oct") takes its arrow from Locales::forward(). An arrow inside a TRANSLATION is the
// translator's to choose (ar and fa already write ←).

/** @return list<string> the view files that are in an application language (marketing pages and email are English-only for now) */
function rta_views(): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $path = str_replace('\\', '/', (string) $file);
        if (! str_ends_with($path, '.blade.php') || str_contains($path, '/views/marketing/') || str_contains($path, '/views/emails/')
            || str_contains($path, '/components/marketing/')) {
            continue;
        }
        $out[] = $path;
    }
    sort($out);

    return $out;
}

/** The file's markup and code with its comments taken out — a `→` in a comment is for a developer and never reaches a page. */
function rta_strip_comments(string $src): string
{
    $src = preg_replace('/\{\{--.*?--\}\}/s', '', $src) ?? $src;
    $src = preg_replace('/<!--.*?-->/s', '', $src) ?? $src;
    $src = preg_replace('#/\*.*?\*/#s', '', $src) ?? $src;

    return preg_replace('#(?<![:\w/])//\s.*$#m', '', $src) ?? $src;
}

it('⚠️ every literal text arrow in a view carries the class that turns it round in Arabic and Persian', function () {
    $bad = [];
    foreach (rta_views() as $path) {
        foreach (preg_split('/\R/', rta_strip_comments((string) file_get_contents($path))) ?: [] as $n => $line) {
            if (! str_contains($line, '→')) {
                continue;
            }
            // each arrow must sit in an element that carries the class: `<span … class="… vy-flip-rtl …" …>→`
            $without = preg_replace('/<[a-z]+[^>]*\bclass="[^"]*\bvy-flip-rtl\b[^"]*"[^>]*>→/i', '', $line) ?? $line;
            if (str_contains($without, '→')) {
                $bad[] = substr($path, strlen(resource_path('views')) + 1).':'.($n + 1).'  '.trim(mb_substr($line, 0, 140));
            }
        }
    }

    expect($bad)->toBe([], "a literal → in a view that does not turn round on a right-to-left page:\n".implode("\n", $bad));
});

it('CONTROL: the scanner sees an arrow with no class, ignores one in a comment, and accepts one with the class', function () {
    $lines = fn (string $src): int => substr_count(preg_replace('/<[a-z]+[^>]*\bclass="[^"]*\bvy-flip-rtl\b[^"]*"[^>]*>→/i', '', rta_strip_comments($src)) ?? '', '→');

    expect($lines('<span>→</span>'))->toBe(1)
        ->and($lines('{{-- → a note --}} <p>x</p>'))->toBe(0)
        ->and($lines("/* a → b */ .x{}\n// a → b\n"))->toBe(0)
        ->and($lines('<span class="x vy-flip-rtl" aria-hidden="true">→</span>'))->toBe(0)
        ->and($lines('<span class="x vy-flip-rtl">→</span> and a bare →'))->toBe(1);
});

it('⚠️ a text a class or a script builds takes its arrow from the page\'s direction', function () {
    expect(Locales::forward('en'))->toBe('→')
        ->and(Locales::forward('de'))->toBe('→')
        ->and(Locales::forward('ar'))->toBe('←')
        ->and(Locales::forward('fa'))->toBe('←');

    app()->setLocale('ar');
    try {
        expect(Locales::forward())->toBe('←');
    } finally {
        app()->setLocale('en');
    }
    expect(Locales::forward())->toBe('→');
});
