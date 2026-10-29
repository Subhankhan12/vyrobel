<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\I18n\Locales;
use App\Support\I18n\TextScanner;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Process\Process;

/**
 * What still needs translation (I18N-01 Phase E) — the founder's weekly report.
 *
 *   php artisan lang:todo                         everything, grouped realm › screen, for every language being translated
 *   php artisan lang:todo --since=<git ref>       only what changed since that commit (the files and keys it touched)
 *   php artisan lang:todo --since=live            …since the commit production runs (read from .release through ops/release.sh)
 *   php artisan lang:todo --locale=de --realm=owner
 *   php artisan lang:todo --self-test             plants one needle per kind; refuses unless each is reported (RULES Part 3)
 *
 * NEW       an English key a language has no translation for (English shows there until it does);
 * CHANGED   a key whose English changed after it was translated (lang/.applied/<l>.json keeps each English text's hash at apply
 *           time) — the translation is stale and may no longer say what the English says;
 * HARDCODED visible English that is not a key (ops/i18n/scan.php), minus the accepted baseline (lang/.hardcoded-baseline.json);
 * REMOVED   an English key no code uses any more (dynamic prefixes like __('x.status_'.$code) count as use).
 * Writes storage/app/i18n/todo.md and todo.json; prints one line per screen ("Owner portal › livewire/owner/statement: …").
 */
class LangTodo extends Command
{
    protected $signature = 'lang:todo {--since= : a git ref, or "live"} {--locale= : one language} {--realm= : part of a realm name} {--self-test}';

    protected $description = 'What still needs translation: NEW, CHANGED, HARDCODED and REMOVED, grouped by realm and screen';

    public function handle(): int
    {
        ini_set('memory_limit', '1G');
        if ($this->option('self-test')) {
            return $this->selfTest();
        }

        $since = $this->option('since') ? $this->resolveSince((string) $this->option('since')) : null;
        if ($this->option('since') && $since === null) {
            $this->error('Could not resolve --since='.$this->option('since'));

            return SymfonyCommand::FAILURE;
        }
        $changedFiles = $since !== null ? $this->changedFiles($since) : null;
        $locales = $this->locales();

        $english = LangCheck::strings(lang_path('en'));
        [$usage, $prefixes] = $this->usage();
        $items = [];   // realm => screen => list of [kind, key/text, where, detail]

        foreach ($english as $key => $text) {
            $where = $usage[$key] ?? null;
            $screenFile = $where !== null ? explode(':', $where)[0] : 'lang/en/'.substr($key, 0, (int) strrpos($key, '.')).'.php';
            if ($changedFiles !== null && ! in_array($screenFile, $changedFiles, true) && ! in_array('lang/en/'.self::groupOf($key).'.php', $changedFiles, true)) {
                continue;
            }
            $missing = [];
            $stale = [];
            foreach ($locales as $l) {
                $translated = $this->translations($l);
                $applied = $this->applied($l);
                if (! array_key_exists($key, $translated)) {
                    $missing[] = $l;
                } elseif (isset($applied[$key]) && $applied[$key] !== substr(sha1($text), 0, 16)) {
                    $stale[] = $l;
                }
            }
            if ($missing !== []) {
                $items[self::realmOf($screenFile)][$screenFile][] = ['NEW', $key, $where ?? $screenFile, count($missing).' languages: '.self::short($missing)];
            }
            if ($stale !== []) {
                $items[self::realmOf($screenFile)][$screenFile][] = ['CHANGED', $key, $where ?? $screenFile, 'stale in '.self::short($stale)];
            }
            if ($where === null && ! self::isFramework($key) && ! self::usedByPrefix($key, $prefixes) && ! self::usedAsWhole($key, $usage)) {
                $items[self::realmOf($screenFile)][$screenFile][] = ['REMOVED', $key, $screenFile, 'no code uses it'];
            }
        }

        $baseline = $this->baseline();
        foreach ($this->scanFiles() as $rel) {
            if ($changedFiles !== null && ! in_array($rel, $changedFiles, true)) {
                continue;
            }
            foreach (self::hardcodedIn($rel) as $s) {
                if (! isset($baseline[$rel][self::hash($s)])) {
                    $items[self::realmOf($rel)][$rel][] = ['HARDCODED', $s, $rel, ''];
                }
            }
        }

        if ($realm = $this->option('realm')) {
            $items = array_filter($items, fn ($r) => stripos($r, (string) $realm) !== false, ARRAY_FILTER_USE_KEY);
        }
        ksort($items);
        $this->report($items, $locales, $since);

        return SymfonyCommand::SUCCESS;
    }

    /** @param array<string, array<string, list<array{0: string, 1: string, 2: string, 3: string}>>> $items @param list<string> $locales */
    private function report(array $items, array $locales, ?string $since): void
    {
        $totals = ['NEW' => 0, 'CHANGED' => 0, 'HARDCODED' => 0, 'REMOVED' => 0];
        $md = ['# What still needs translation', '', 'Generated '.now()->toDateTimeString().' · languages: '.($locales === [] ? 'none started' : implode(', ', $locales))
            .($since !== null ? " · since `$since`" : '').'.', ''];
        foreach ($items as $realm => $screens) {
            ksort($screens);
            $md[] = "## $realm";
            foreach ($screens as $screen => $rows) {
                $c = array_count_values(array_column($rows, 0));
                foreach ($c as $k => $n) {
                    $totals[$k] += $n;
                }
                $parts = [];
                foreach (['NEW' => 'new', 'CHANGED' => 'changed', 'HARDCODED' => 'hardcoded', 'REMOVED' => 'removed'] as $k => $label) {
                    if (isset($c[$k])) {
                        $parts[] = $c[$k].' '.$label;
                    }
                }
                $this->line("$realm › $screen: ".implode(', ', $parts));
                $md[] = "### $screen — ".implode(', ', $parts);
                foreach ($rows as [$kind, $what, $where, $detail]) {
                    $md[] = "- **$kind** `".str_replace('`', "'", mb_strimwidth($what, 0, 120, '…'))."` — $where".($detail !== '' ? " — $detail" : '');
                }
            }
            $md[] = '';
        }
        $summary = sprintf('%d new · %d changed · %d hardcoded · %d removed', $totals['NEW'], $totals['CHANGED'], $totals['HARDCODED'], $totals['REMOVED']);
        $this->line('TOTAL — '.$summary.' · languages: '.($locales === [] ? 'none started' : count($locales)));
        @mkdir(storage_path('app/i18n'), 0755, true);
        file_put_contents(storage_path('app/i18n/todo.md'), implode("\n", $md)."\n\n**Total:** $summary\n");
        file_put_contents(storage_path('app/i18n/todo.json'), json_encode(['generated' => now()->toIso8601String(), 'since' => $since, 'locales' => $locales,
            'totals' => $totals, 'items' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function selfTest(): int
    {
        $hard = self::hardcodedInSource('<p>Plant a hardcoded needle</p>', 'x.blade.php');
        $ok = in_array('Plant a hardcoded needle', $hard, true);
        $this->line(($ok ? 'caught  ' : 'MISSED  ').'HARDCODED');
        $prefix = self::usedByPrefix('a/b.status_paid', ['a/b.status_']);
        $this->line(($prefix ? 'caught  ' : 'MISSED  ').'a dynamic prefix counts as use (no false REMOVED)');
        $ok = $ok && $prefix;
        [$u, $p] = [[], []];
        self::scanLine("\$key = 'a/b.'.\$this->line_type;", 'x', $u, $p);
        self::scanLine("StoredEnglish::say('a/c', \$text);", 'x', $u, $p);
        $joined = self::usedByPrefix('a/b.net', $p) && self::usedByPrefix('a/c.reversal', $p) && ! self::usedByPrefix('a/d.x', $p);
        $this->line(($joined ? 'caught  ' : 'MISSED  ').'a joined literal prefix and a whole group count as use (and nothing else does)');
        $ok = $ok && $joined;
        $stale = substr(sha1('Old English'), 0, 16) !== substr(sha1('New English'), 0, 16);
        $this->line(($stale ? 'caught  ' : 'MISSED  ').'CHANGED (a different English text has a different hash)');
        $ok = $ok && $stale;
        $clean = self::hardcodedInSource("<p>{{ __('a.b') }}</p>", 'x.blade.php') === [];
        $this->line(($clean ? 'clean   ' : 'FALSE   ').'a converted view reports nothing');

        return $ok && $clean ? SymfonyCommand::SUCCESS : SymfonyCommand::FAILURE;
    }

    /**
     * The uses one line of code makes of keys: a full key (a call, or a plain literal — FeedNotification::t('title', 'php/…key'),
     * a key held in a constant); a dynamic prefix (__('x.status_'.$code), or a literal 'group.' joined to a code); a whole GROUP
     * handed over as a literal ('php/models/owner_ledger_entry' — App\Support\I18n\StoredEnglish reads every key in it).
     *
     * @param  array<string, string>  $usage
     * @param  list<string>  $prefixes
     */
    private static function scanLine(string $line, string $where, array &$usage, array &$prefixes): void
    {
        if (preg_match_all('~[\'"]([a-z0-9_\-]+(?:/[a-z0-9_\-]+)*\.[a-z0-9_\-]+(?:\.[a-z0-9_\-]+)*)[\'"]~i', $line, $lit)) {
            foreach ($lit[1] as $k) {
                $usage[$k] ??= $where;
            }
        }
        // a literal ending in "." or "_" with a value joined on: 'php/models/owner_statement_line.'.$type
        if (preg_match_all('~[\'"]((?:[a-z0-9_\-]+/)*[a-z0-9_\-]+\.(?:[a-z0-9_\-]+\.)*[a-z0-9_\-]*)[\'"]\s*\.(?!\.)~', $line, $pre)) {
            foreach ($pre[1] as $p) {
                $prefixes[] = $p;
            }
        }
        // a whole group as a literal (a path with at least one "/" and no dot)
        if (preg_match_all('~[\'"]((?:[a-z0-9_\-]+/)+[a-z0-9_\-]+)[\'"]~', $line, $grp)) {
            foreach ($grp[1] as $g) {
                $prefixes[] = $g.'.';
            }
        }
        if (preg_match_all('/(?:__|trans_choice|trans|@lang|@choice)\s*\(\s*[\'"]([^\'"$]+)[\'"]\s*(\.)?/', $line, $m, PREG_SET_ORDER)) {
            foreach ($m as $mm) {
                if (($mm[2] ?? '') === '.') {
                    $prefixes[] = $mm[1];
                } else {
                    $usage[$mm[1]] ??= $where;
                }
            }
        }
    }

    /** @return list<string> */
    private function locales(): array
    {
        if ($this->option('locale')) {
            return [(string) $this->option('locale')];
        }
        $started = array_values(array_filter(array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR) ?: []), fn ($l) => $l !== 'en' && Locales::isPlanned($l)));

        return array_values(array_unique([...array_diff(Locales::finished(), ['en']), ...$started]));
    }

    /** @var array<string, array<string, string>> */
    private array $translationCache = [];

    /** @return array<string, string> */
    private function translations(string $locale): array
    {
        return $this->translationCache[$locale] ??= LangCheck::strings(lang_path($locale));
    }

    /** @return array<string, string> */
    private function applied(string $locale): array
    {
        $f = lang_path('.applied/'.$locale.'.json');

        return is_file($f) ? (array) json_decode((string) file_get_contents($f), true) : [];
    }

    /** @return array<string, array<string, true>> */
    private function baseline(): array
    {
        $f = lang_path('.hardcoded-baseline.json');
        $out = [];
        // file => {hash: the accepted text} (readable in a diff); a plain list of hashes is read too
        foreach (is_file($f) ? (array) json_decode((string) file_get_contents($f), true) : [] as $file => $hashes) {
            foreach ((array) $hashes as $k => $h) {
                $out[$file][is_string($k) ? $k : (string) $h] = true;
            }
        }

        return $out;
    }

    /** key => first "file:line" using it, and the dynamic prefixes. @return array{0: array<string, string>, 1: list<string>} */
    private function usage(): array
    {
        $usage = [];
        $prefixes = [];
        foreach ($this->scanFiles(includeAll: true) as $rel) {
            foreach (file(base_path($rel)) ?: [] as $n => $line) {
                self::scanLine($line, $rel.':'.($n + 1), $usage, $prefixes);
            }
        }

        return [$usage, array_values(array_unique($prefixes))];
    }

    /** @return list<string> */
    private function scanFiles(bool $includeAll = false): array
    {
        $out = [];
        foreach (['resources/views', 'app'] as $dir) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dir), RecursiveDirectoryIterator::SKIP_DOTS)) as $f) {
                $rel = substr($f->getPathname(), strlen(base_path()) + 1);
                if (! str_ends_with($rel, '.php') || (! $includeAll && self::outOfScope($rel))) {
                    continue;
                }
                $out[] = $rel;
            }
        }
        sort($out);

        return $out;
    }

    private static function outOfScope(string $rel): bool
    {
        return (bool) preg_match('#/marketing/|Marketing|welcome\.blade|pdfs/owner-agreement|pdfs/owner-annual-tax|app/Console/|app/Support/I18n/|app/Providers/#', $rel);
    }

    /** @return list<string> */
    public static function hardcodedIn(string $rel): array
    {
        return self::hardcodedInSource((string) file_get_contents(base_path($rel)), $rel);
    }

    /** @return list<string> */
    public static function hardcodedInSource(string $src, string $name): array
    {
        if (str_ends_with($name, '.blade.php')) {
            $r = TextScanner::scanBlade($src);

            return array_values(array_unique(array_merge($r['text'], $r['attr'], $r['code'], $r['js'])));
        }

        return array_values(array_unique(TextScanner::scanPhp($src)['visible']));
    }

    public static function hash(string $s): string
    {
        return substr(sha1(trim($s)), 0, 12);
    }

    private static function groupOf(string $key): string
    {
        $slash = strrpos(substr($key, 0, (int) strpos($key.'.', '.', (int) strrpos($key, '/'))), '/');

        return substr($key, 0, (int) strpos($key, '.', $slash === false ? 0 : $slash + 1));
    }

    private static function isFramework(string $key): bool
    {
        return (bool) preg_match('/^(validation|auth|passwords|pagination)\./', $key);
    }

    /**
     * A key inside an array that code reads WHOLE — __('components/notifications/bell.categories') — is used through it.
     *
     * @param  array<string, string>  $usage
     */
    private static function usedAsWhole(string $key, array $usage): bool
    {
        for ($k = $key; ($i = strrpos($k, '.')) !== false && ! str_ends_with(substr($k, 0, $i), '/');) {
            $k = substr($k, 0, $i);
            if (isset($usage[$k]) && str_contains($k, '.')) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $prefixes */
    private static function usedByPrefix(string $key, array $prefixes): bool
    {
        foreach ($prefixes as $p) {
            if (str_starts_with($key, $p)) {
                return true;
            }
        }

        return false;
    }

    public static function realmOf(string $file): string
    {
        $f = preg_replace('#^(resources/views/|lang/en/)#', '', $file) ?? $file;

        return match (true) {
            (bool) preg_match('#^(livewire/owner|owner/|app/Livewire/Owner|app/Http/Controllers/Owner|php/livewire/owner|php/http/controllers/owner)#', $f) => 'Owner portal',
            (bool) preg_match('#^(guest/|app/Http/Controllers/Guest|php/http/controllers/guest)#', $f) => 'Guest portal',
            (bool) preg_match('#^(booking/|app/Http/Controllers/Booking|php/http/controllers/booking)#', $f) => 'Public booking engine',
            (bool) preg_match('#^(admin/|components/admin|app/Http/Controllers/Admin|php/http/controllers/admin)#', $f) => 'Platform admin console',
            (bool) preg_match('#^(auth/|billing/|oauth/|status|errors/|app/Http/Controllers/Auth|php/http/controllers/auth)#', $f) => 'Auth, signup and billing',
            (bool) preg_match('#^(emails/|app/Mail|app/Notifications|php/mail|php/notifications)#', $f) => 'Emails and notifications',
            (bool) preg_match('#^(pdfs/)#', $f) => 'PDFs',
            (bool) preg_match('#^(livewire/|operator/|app/Livewire|app/Http/Controllers/Operator|php/livewire|php/http/controllers/operator)#', $f) => 'Operator back office',
            default => 'Shared',
        };
    }

    /** @param list<string> $l */
    private static function short(array $l): string
    {
        return count($l) > 6 ? implode(', ', array_slice($l, 0, 6)).' …' : implode(', ', $l);
    }

    private function resolveSince(string $since): ?string
    {
        if ($since === 'live') {
            $p = Process::fromShellCommandline('bash ops/release.sh status 2>/dev/null', base_path());
            $p->run();
            if (! preg_match('/live commit\s*:\s*([0-9a-f]{7,40})/', $p->getOutput(), $m)) {
                return null;
            }
            $since = $m[1];
        }
        $p = new Process(['git', 'rev-parse', '--verify', '--quiet', $since.'^{commit}'], base_path());
        $p->run();

        return $p->isSuccessful() ? trim($p->getOutput()) : null;
    }

    /** @return list<string> */
    private function changedFiles(string $since): array
    {
        $p = new Process(['git', 'diff', '--name-only', $since], base_path());
        $p->run();
        $tracked = array_filter(explode("\n", trim($p->getOutput())));
        $u = new Process(['git', 'ls-files', '--others', '--exclude-standard'], base_path());
        $u->run();

        return array_values(array_unique([...$tracked, ...array_filter(explode("\n", trim($u->getOutput())))]));
    }
}
