<?php

declare(strict_types=1);

namespace App\Support\Privacy;

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
 * ⚠️ `docs/` is not deployed, so on the production host M cannot be counted — the card says so, and shows only the
 * code-enforced windows. It never falls back to a remembered number.
 */
final class RetentionCoverage
{
    public const RULING = null;   // ⚠️ the founder's definition — NOT SET. Nothing here may pretend it is.

    public static function inventoryPath(): string
    {
        return base_path('docs/legal/DATA-INVENTORY.md');
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
            return ['measurable' => false, 'reason' => 'The inventory it counts (docs/legal/DATA-INVENTORY.md) is not deployed on this host, so the categories cannot be counted here.',
                'categories' => null, 'strict' => null, 'lenient' => null, 'enforced' => $enforced, 'ruling' => self::RULING];
        }

        $tables = array_map('strtolower', array_map(fn ($t) => is_array($t) ? (string) $t['name'] : (string) $t, Schema::getTables()));
        $tables = array_flip($tables);
        $rows = array_values(array_filter(preg_split('/\R/', (string) file_get_contents($path)) ?: [], fn (string $l): bool => str_starts_with($l, '| **')));

        $strict = 0;
        $lenient = 0;
        foreach ($rows as $row) {
            preg_match_all('/\b[a-z][a-z0-9_]{2,}\b/', strtolower($row), $m);
            $named = array_values(array_unique(array_filter($m[0], fn (string $w): bool => isset($tables[$w]))));
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
