<?php

declare(strict_types=1);

namespace App\Support\Privacy;

use App\Console\Commands\ExportDataInventory;
use App\Models\GuestDocument;
use App\Models\LoginActivity;
use App\Support\Retention\RetentionPolicy;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️⚠️ ADMIN 01 · PHASE 4 — THE RETENTION COVERAGE METER — BUILT, AND WAITING FOR A RULING.
 *
 * "N of M data categories have a retention rule." ⚠️ WHAT COUNTS AS "HAS A RULE" IS THE FOUNDER'S AND HIS LAWYER'S
 * TO DEFINE (ADMIN 02 — a STOP item). So this class does NOT pick one. It computes, and the screen shows:
 *
 *   · M — the data categories, COUNTED from the same source the /privacy page was written from:
 *     `docs/legal/DATA-INVENTORY.md`, one category per table row whose first cell is bold. ⚠️ Read, never typed —
 *     the typed "96 of 142" that used to sit in a Blade comment was quoted four times before anyone checked it.
 *   · What the CODE enforces on a timer — read from the code itself: {@see RetentionPolicy::windows()}, the login
 *     activity window ({@see LoginActivity::RETENTION_DAYS}) and the identity-document purge. ⚠️ A guest's 14-day
 *     ERASURE is not listed: it answers a request, it is not a retention period.
 *   · Two CANDIDATE counts, side by side, neither of them the ruling — a category counts under the STRICT reading
 *     if every real table its row names is pruned by that code, and under the LENIENT one if any is. ⚠️ Both are
 *     approximate: a row's table names are the real tables it MENTIONS, including in passing.
 *
 * ⚠️⚠️ TONIGHT 01 (2026-09-25) — THE METER NOW COUNTS ON THE PRODUCTION HOST. `docs/` is not deployed, so it used to
 *    say it could not count there. The doc stays the ONE source; what ships is a data file GENERATED from it
 *    ({@see self::DATA_FILE}, by {@see ExportDataInventory}) holding only each category's name and the tables its row
 *    names — no prose, no addresses, no personal data. ⚠️ It cannot drift: a test regenerates it from the doc and
 *    fails on any difference. ⚠️ Which of those tables EXIST is still measured here, on the host's own database.
 *    A missing or unreadable file still says so — it never falls back to a remembered number.
 */
final class RetentionCoverage
{
    public const RULING = null;   // ⚠️ the founder's definition — NOT SET. Nothing here may pretend it is.

    /** The shipped data file the meter reads — inside every release (`ops/release.sh` ships it; it skips docs/). */
    public const DATA_FILE = 'resources/data/data-inventory.json';

    /** The audited doc the data file is generated from — in the repository, NOT on the production host. */
    public const SOURCE_DOC = 'docs/legal/DATA-INVENTORY.md';

    public static function inventoryPath(): string
    {
        return base_path(self::DATA_FILE);
    }

    public static function sourcePath(): string
    {
        return base_path(self::SOURCE_DOC);
    }

    /**
     * ⚠️ THE ONE PARSER — the doc's categories, each with the real tables its row names. Read by the generator and by
     * the drift test, so the shipped file can only ever be what this produces from the doc.
     *
     * @param  list<string>  $tables  the database's real table names
     * @return list<array{category: string, tables: list<string>}>
     */
    public static function categoriesFrom(string $markdown, array $tables): array
    {
        $known = array_flip(array_map('strtolower', $tables));
        $rows = array_values(array_filter(preg_split('/\R/', $markdown) ?: [], fn (string $l): bool => str_starts_with($l, '| **')));

        return array_map(function (string $row) use ($known): array {
            preg_match('/^\| \*\*(.+?)\*\*/', $row, $name);
            preg_match_all('/\b[a-z][a-z0-9_]{2,}\b/', strtolower($row), $m);
            $named = array_values(array_unique(array_filter($m[0], fn (string $w): bool => isset($known[$w]))));
            sort($named);

            return ['category' => trim($name[1] ?? ''), 'tables' => $named];
        }, $rows);
    }

    /**
     * The exact bytes of the shipped file for these categories — one renderer, so the generator and the drift test
     * cannot disagree about formatting.
     *
     * @param  list<array{category: string, tables: list<string>}>  $categories
     */
    public static function render(array $categories): string
    {
        return json_encode([
            'generated_from' => self::SOURCE_DOC,
            'regenerate' => 'php artisan privacy:inventory-data — edit the doc, regenerate, commit both; a test fails if they differ',
            'holds' => 'data CATEGORIES and the table names each one mentions — no personal data',
            'categories' => $categories,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * ⚠️ SCOPED TO THIS CONNECTION'S DATABASE. Laravel 13's `Schema::getTables()` with no argument lists EVERY database
     * the user can see — on the dev machine five, and the shipped inventory named six tables (`bookings`, `tickets`, …)
     * that only other projects' databases hold. The drift test could not see it: it regenerated through this same list.
     *
     * @return list<string> the connected database's real tables, lower-case
     */
    public static function liveTables(): array
    {
        return array_map('strtolower', array_column(Schema::getTables(Schema::getCurrentSchemaName()), 'name'));
    }

    /**
     * The tables the code prunes on a timer, with where each rule lives.
     *
     * @return array<string, string> table => the rule, in words
     */
    public static function enforced(): array
    {
        $out = [];
        foreach (RetentionPolicy::windows() as $table => $window) {
            $out[$table] = $window['days'].' days (RetentionPolicy, retention:prune)';
        }
        $out[(new LoginActivity)->getTable()] = LoginActivity::RETENTION_DAYS.' days (platform:prune-activity)';
        $out[(new GuestDocument)->getTable()] = '30 days after check-out (guest-documents:purge-expired)';
        ksort($out);

        return $out;
    }

    /**
     * @return array{measurable: bool, reason: ?string, categories: ?int, strict: ?int, lenient: ?int, enforced: array<string, string>, ruling: null}
     */
    public function compute(?string $path = null): array
    {
        $enforced = self::enforced();
        $path ??= self::inventoryPath();

        if (! is_readable($path)) {
            return ['measurable' => false, 'reason' => 'The inventory it counts ('.self::DATA_FILE.') is not deployed on this host, so the categories cannot be counted here.',
                'categories' => null, 'strict' => null, 'lenient' => null, 'enforced' => $enforced, 'ruling' => self::RULING];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data) || ! is_array($data['categories'] ?? null)) {
            return ['measurable' => false, 'reason' => 'The inventory it counts ('.self::DATA_FILE.') could not be read, so the categories cannot be counted here.',
                'categories' => null, 'strict' => null, 'lenient' => null, 'enforced' => $enforced, 'ruling' => self::RULING];
        }

        $tables = array_flip(self::liveTables());
        $rows = array_values($data['categories']);

        $strict = 0;
        $lenient = 0;
        foreach ($rows as $row) {
            // ⚠️ The file records what the doc NAMES; whether each table exists is measured HERE, on this database.
            $named = array_values(array_filter(array_map('strval', (array) ($row['tables'] ?? [])), fn (string $t): bool => isset($tables[$t])));
            if ($named === []) {
                continue;
            }
            $pruned = array_filter($named, fn (string $t): bool => isset($enforced[$t]));
            $lenient += $pruned !== [] ? 1 : 0;
            $strict += count($pruned) === count($named) ? 1 : 0;
        }

        return ['measurable' => true, 'reason' => null, 'categories' => count($rows), 'strict' => $strict, 'lenient' => $lenient, 'enforced' => $enforced, 'ruling' => self::RULING];
    }
}
