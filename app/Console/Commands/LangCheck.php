<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\I18n\LangChecks;
use App\Support\I18n\Locales;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * THE translation gate (I18N-01 Phase E): every finished language against English, and every view and class for the
 * mistakes that break a translated page. Exit 1 on any error — the suite (LangCheckTest), GitHub's checks and the release
 * preflight all refuse on it. The checks themselves: App\Support\I18n\LangChecks (one definition).
 *
 *   php artisan lang:check                 every finished language (config('app.available_locales')) + the code scan
 *   php artisan lang:check --locale=de     one language, finished or not (a language being prepared)
 *   php artisan lang:check --self-test     plants one needle per check; refuses unless EVERY check fires (RULES Part 3)
 */
class LangCheck extends Command
{
    protected $signature = 'lang:check {--locale= : check this language (finished or not)} {--self-test : prove every check fires on a planted needle}';

    protected $description = 'The translation gate: placeholders, HTML, quotes, lost keys, glued keys, raw values, collapsed labels';

    public function handle(): int
    {
        ini_set('memory_limit', '1G');
        if ($this->option('self-test')) {
            return $this->selfTest();
        }

        $errors = [];
        $english = self::strings(lang_path('en'));
        $locales = $this->option('locale') ? [(string) $this->option('locale')] : array_values(array_diff(Locales::finished(), [Locales::SOURCE]));

        foreach ($locales as $locale) {
            $dir = lang_path($locale);
            foreach (self::files($dir) as $file) {
                if (! is_array(include $file)) {
                    $errors[] = ['PARSE', $locale, substr($file, strlen(lang_path()) + 1), 'does not return an array'];
                }
            }
            $applied = is_file(lang_path('.applied/'.$locale.'.json')) ? (array) json_decode((string) file_get_contents(lang_path('.applied/'.$locale.'.json')), true) : [];
            foreach (LangChecks::locale($english, self::strings($dir), $applied) as [$code, $key, $detail]) {
                $errors[] = [$code, $locale, $key, $detail];
            }
        }

        foreach ([resource_path('views'), app_path()] as $root) {
            foreach (self::files($root) as $file) {
                if (str_contains($file, '/marketing/')) {
                    continue;
                }
                $src = (string) file_get_contents($file);
                $checks = str_ends_with($file, '.blade.php') ? LangChecks::view($src) : LangChecks::code($src);
                foreach ($checks as [$code, $detail]) {
                    $errors[] = [$code, '-', substr($file, strlen(base_path()) + 1), $detail];
                }
            }
        }

        foreach ($errors as [$code, $locale, $where, $detail]) {
            $this->line(sprintf('%-11s %-6s %s  %s', $code, $locale, $where, $detail));
        }
        $this->line(($errors === [] ? 'OK' : 'FAILED').' — '.count($errors).' error(s) · languages checked: '.($locales === [] ? 'none finished yet (English only)' : implode(', ', $locales))
            .' · '.count($english).' English keys');

        return $errors === [] ? SymfonyCommand::SUCCESS : SymfonyCommand::FAILURE;
    }

    /** One needle per check, each must be caught — and a clean value/view must pass. A guard never seen to fire is not a guard. */
    private function selfTest(): int
    {
        $cases = [
            'PLACEHOLDER' => fn () => LangChecks::value('Paid :amount of :total', 'Bezahlt :amount'),
            'HTML' => fn () => LangChecks::value('New? <a href=":url">Sign up</a>', 'Neu? Registrieren'),
            'ATTR' => fn () => LangChecks::value('<a class="vy-x" href=":url">Go</a>', '<a class="other" href=":url">Los</a>'),
            'JSQUOTE' => fn () => LangChecks::value('Remove this unit?', 'Supprimer l\'unité ?'),
            // a plural form with markup no English form has (a six-form language repeats the markup per form — counted per form)
            'HTML (one plural form)' => fn () => array_map(fn ($p) => [$p[0] === 'HTML' ? 'HTML (one plural form)' : $p[0]],
                LangChecks::value('{1} <b>:count</b> night|[2,*] <b>:count</b> nights', '{0} <b>:count</b> ليلة|{1} :count ليلة|[2,*] <b>:count</b> ليال')),
            'LOST' => fn () => LangChecks::locale(['a.b' => 'Save'], [], ['a.b' => 'hash']),
            'GLUED' => fn () => LangChecks::view("<p>{{ __('a.add') }} {{ __('a.unit') }}</p>"),
            'RAWVAR' => fn () => LangChecks::view("{!! __('a.hi', ['name' => \$guest->name]) !!}"),
            'COLLAPSE' => fn () => LangChecks::code("\$o = [__('a.paid') => 1, __('a.due') => 2];"),
            // a `$…Html` variable is trusted only when built without raw data — one holding a guest's name unescaped is caught
            'RAWVAR (an Html variable with raw data)' => fn () => array_map(fn ($p) => [$p[0] === 'RAWVAR' ? 'RAWVAR (an Html variable with raw data)' : $p[0]],
                LangChecks::view("@php(\$nameHtml = '<b>'.\$guest->name.'</b>')\n{!! __('a.t', ['n' => \$nameHtml]) !!}")),
        ];
        $failed = 0;
        foreach ($cases as $code => $run) {
            $found = array_map(fn ($p) => $p[0], $run());
            $ok = in_array($code, $found, true);
            $failed += $ok ? 0 : 1;
            $this->line(($ok ? 'caught  ' : 'MISSED  ').$code);
        }
        $clean = array_merge(
            LangChecks::value('Paid :amount of :total', 'Bezahlt: :amount von :total'),
            LangChecks::value('{1} <b>:count</b> night|[2,*] <b>:count</b> nights', '{0} <b>:count</b> ليلة|{1} <b>:count</b> ليلة|{2} <b>:count</b> ليلتان|[3,*] <b>:count</b> ليال'),
            LangChecks::value('New? <a class="vy-x" href=":url">Sign up</a>', 'Neu? <a class="vy-x" href=":url">Registrieren</a>'),
            LangChecks::view("{!! __('a.hi', ['url' => e(route('x'))]) !!} <p>{{ __('a.one') }}</p>"),
            LangChecks::view("@php(\$whenHtml = '<time>'.e(\$at).'</time>')\n{!! __('a.t', ['w' => \$whenHtml]) !!}"),
            LangChecks::view("@php(\$priceHtml = '<b x-text=\"'.\\Illuminate\\Support\\Js::from(\$symbol)->toHtml().' + rate\"></b>')\n{!! __('a.t', ['p' => \$priceHtml]) !!}"),
        );
        $this->line(($clean === [] ? 'clean   ' : 'FALSE   ').'a correct value and view pass');

        return $failed === 0 && $clean === [] ? SymfonyCommand::SUCCESS : SymfonyCommand::FAILURE;
    }

    /** @return list<string> */
    private static function files(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }
        $out = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $f) {
            if (str_ends_with($f->getFilename(), '.php')) {
                $out[] = $f->getPathname();
            }
        }
        sort($out);

        return $out;
    }

    /** key => text for every group under a language directory. @return array<string, string> */
    public static function strings(string $dir): array
    {
        $out = [];
        foreach (self::files($dir) as $file) {
            $data = include $file;
            if (is_array($data)) {
                self::flatten($data, substr($file, strlen($dir) + 1, -4).'.', $out);
            }
        }

        return $out;
    }

    /** @param array<mixed> $a @param array<string, string> $out */
    private static function flatten(array $a, string $prefix, array &$out): void
    {
        foreach ($a as $k => $v) {
            if (is_array($v)) {
                self::flatten($v, $prefix.$k.'.', $out);
            } elseif (is_string($v)) {
                $out[$prefix.$k] = $v;
            }
        }
    }
}
