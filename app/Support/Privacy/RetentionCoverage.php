<?php

declare(strict_types=1);

namespace App\Support\Privacy;

use App\Console\Commands\ExportDataInventory;
use App\Models\GuestDocument;
use App\Models\LoginActivity;
use App\Support\Retention\RetentionDecisions;
use App\Support\Retention\RetentionPolicy;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️⚠️ ADMIN 01 · PHASE 4 — THE RETENTION COVERAGE METER. ⚠️ DEC-4 (2026-11-13) RULED IT: THREE BUCKETS (see {@see compute()}).
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
    /** ⚠️ DEC-4 — his ruling (2026-11-13: "C"): three buckets, not one ratio. See {@see compute()} and {@see RetentionDecisions}. */
    public const RULING = 'three_buckets';

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
            $out[$table] = trans_choice('php/support/privacy/retention_coverage.rule_retention_policy', (int) $window['days'], ['count' => $window['days']]);
        }
        $out[(new LoginActivity)->getTable()] = trans_choice('php/support/privacy/retention_coverage.rule_login_activity', LoginActivity::RETENTION_DAYS, ['count' => LoginActivity::RETENTION_DAYS]);
        $out[(new GuestDocument)->getTable()] = __('php/support/privacy/retention_coverage.rule_guest_documents');
        ksort($out);

        return $out;
    }

    /**
     * ⚠️ DEC-4 — THE THREE BUCKETS (his ruling, 2026-11-13: "C"). Every category lands in exactly one, so they ALWAYS add up to
     * `categories`:
     *
     *   · `pruned` — every real store the category names is deleted by code on a timer ({@see enforced()});
     *   · `kept`   — every store is pruned OR has a COMPLETE written decision (reason + period + date, {@see RetentionDecisions});
     *   · `none`   — everything else: at least one store has neither. A category naming no real table at all is here too — its
     *                data is not in the database, and nothing on record says how long it is kept.
     *
     * `partly` is a note on `none`, not a fourth bucket: how many of those already have at least one store pruned.
     * `strict` / `lenient` are the two older readings, kept so the earlier tests and the data file's consumers keep working;
     * the card no longer shows them (`strict` is exactly `pruned`).
     *
     * @param  ?array<string, array{reason: string, period: string, decided_on: string}>  $decisions  the registry — null reads the real one
     * @return array{measurable: bool, reason: ?string, categories: ?int, strict: ?int, lenient: ?int, buckets: ?array{pruned: int, kept: int, none: int, partly: int}, enforced: array<string, string>, ruling: string}
     */
    public function compute(?string $path = null, ?array $decisions = null): array
    {
        $enforced = self::enforced();
        $path ??= self::inventoryPath();
        $decisions ??= RetentionDecisions::all();

        if (! is_readable($path)) {
            return ['measurable' => false, 'reason' => __('php/support/privacy/retention_coverage.reason_not_deployed', ['file' => self::DATA_FILE]),
                'categories' => null, 'strict' => null, 'lenient' => null, 'buckets' => null, 'enforced' => $enforced, 'ruling' => self::RULING];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data) || ! is_array($data['categories'] ?? null)) {
            return ['measurable' => false, 'reason' => __('php/support/privacy/retention_coverage.reason_unreadable', ['file' => self::DATA_FILE]),
                'categories' => null, 'strict' => null, 'lenient' => null, 'buckets' => null, 'enforced' => $enforced, 'ruling' => self::RULING];
        }

        $tables = array_flip(self::liveTables());
        $rows = array_values($data['categories']);

        $strict = 0;
        $lenient = 0;
        $kept = 0;
        $none = 0;
        $partly = 0;
        foreach ($rows as $row) {
            // ⚠️ The file records what the doc NAMES; whether each table exists is measured HERE, on this database.
            $named = array_values(array_filter(array_map('strval', (array) ($row['tables'] ?? [])), fn (string $t): bool => isset($tables[$t])));
            if ($named === []) {
                $none++;   // its data is not in a table, and nothing on record says how long it is kept

                continue;
            }
            $pruned = array_filter($named, fn (string $t): bool => isset($enforced[$t]));
            $lenient += $pruned !== [] ? 1 : 0;
            $strict += count($pruned) === count($named) ? 1 : 0;

            // the stores no timer prunes — each needs a COMPLETE written decision for the category to count as kept on purpose
            $unpruned = array_values(array_filter($named, fn (string $t): bool => ! isset($enforced[$t])));
            if ($unpruned === []) {
                continue;   // every store is pruned: counted in `strict`, which is the `pruned` bucket
            }
            if (array_all($unpruned, fn (string $t): bool => RetentionDecisions::isComplete($decisions[$t] ?? null))) {
                $kept++;
            } else {
                $none++;
                $partly += $pruned !== [] ? 1 : 0;
            }
        }

        return [
            'measurable' => true, 'reason' => null, 'categories' => count($rows), 'strict' => $strict, 'lenient' => $lenient,
            'buckets' => ['pruned' => $strict, 'kept' => $kept, 'none' => $none, 'partly' => $partly],
            'enforced' => $enforced, 'ruling' => self::RULING,
        ];
    }
}
