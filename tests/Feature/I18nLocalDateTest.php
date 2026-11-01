<?php

declare(strict_types=1);

use App\Support\I18n\LocalDate;
use App\Support\I18n\Locales;
use Carbon\CarbonImmutable;

// I18N-01 · D-I18N-DATE-ORDER (2026-10-29) — a date's ORDER follows the page's language (ICU's best pattern), English unchanged.

afterEach(fn () => app()->setLocale('en'));

/** Every literal pattern the app passes to LocalDate::pattern(), read from the code. @return list<string> */
function ldPatternsInUse(): array
{
    $found = [];
    foreach ([app_path(), resource_path('views')] as $dir) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
            if (str_ends_with($f->getFilename(), '.php') && preg_match_all("/LocalDate::pattern\\('([^']+)'\\)/", (string) file_get_contents($f->getPathname()), $m)) {
                array_push($found, ...$m[1]);
            }
        }
    }

    return array_values(array_unique($found));
}

it('⚠️⚠️ leaves every English pattern exactly as it was — byte for byte what every page printed before', function () {
    $patterns = ldPatternsInUse();
    expect(count($patterns))->toBeGreaterThan(20);   // the scan really found the call sites
    $d = CarbonImmutable::parse('2026-10-27 15:05:00');
    foreach ($patterns as $p) {
        expect(LocalDate::pattern($p, 'en'))->toBe($p)
            ->and($d->locale('en')->translatedFormat(LocalDate::pattern($p, 'en')))->toBe($d->locale('en')->translatedFormat($p));
    }
});

it('writes a date in the language\'s own order — German "27. Okt", Japanese "2026年10月27日", Arabic with its own comma', function () {
    $d = CarbonImmutable::parse('2026-10-27 15:05:00');
    expect($d->locale('de')->translatedFormat(LocalDate::pattern('j M Y', 'de')))->toStartWith('27. ')
        ->and($d->locale('ja')->translatedFormat(LocalDate::pattern('j M Y', 'ja')))->toBe('2026年10月27日')
        ->and($d->locale('ar')->translatedFormat(LocalDate::pattern('D, j M Y', 'ar')))->toContain('،')
        ->and(LocalDate::pattern('M', 'ja'))->toBe('M');   // one field has no order to fix
    // every pattern in use converts to something Carbon can print, in every planned language — never a raw "?" or an empty string
    foreach (Locales::planned() as $l) {
        foreach (ldPatternsInUse() as $p) {
            $out = $d->locale(Locales::carbon($l))->translatedFormat(LocalDate::pattern($p, $l));
            expect($out)->not->toBe('', "$l: $p")
                ->and(str_contains($out, '?'))->toBeFalse("$l: $p");
        }
    }
});

it('⚠️ every date people read goes through LocalDate — no literal pattern is handed to translatedFormat() directly', function () {
    $bare = [];
    foreach ([app_path(), resource_path('views')] as $dir) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
            if (str_ends_with($f->getFilename(), '.php') && preg_match("/translatedFormat\\(\\s*['\"]/", (string) file_get_contents($f->getPathname()))) {
                $bare[] = str_replace(base_path().'/', '', $f->getPathname());
            }
        }
    }
    expect($bare)->toBe([]);
});
