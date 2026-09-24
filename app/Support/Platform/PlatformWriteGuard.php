<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Exceptions\PlatformWriteRefused;
use App\Models\PlatformAdminAction;
use Closure;
use Illuminate\Database\Connection;

/**
 * ADMIN 01 · PHASE 1 — two refusals enforced at the DATABASE CONNECTION, below every model and query builder.
 *
 *  1. ⚠️⚠️ THE ONE DOOR IS READ-ONLY. Inside {@see PlatformQuery::read()} any statement that is not a read is
 *     refused before it executes. It used to be read-only by convention only — `read()` ran whatever closure
 *     it was handed, writes included, with the tenant scope lifted.
 *  2. ⚠️⚠️ THE ADMIN LEDGER IS APPEND-ONLY. `platform_admin_actions` accepts INSERT and nothing else: no UPDATE,
 *     no DELETE, no TRUNCATE, ever — its immutability used to be a docblock.
 *
 * ⚠️ WHY THE CONNECTION AND NOT MODEL EVENTS: model events see `$model->save()` and `->delete()` only. A
 *    `Model::query()->update()`, a `DB::table()->delete()` or raw SQL fire no event at all and would walk
 *    straight past. `beforeExecuting` sees every statement this application sends. (Model events are ALSO
 *    wired on PlatformAdminAction, as the first, more readable line of defence.)
 *
 * ⚠️ WHAT IT STILL CANNOT SEE — WRITTEN DOWN SO IT IS NOT OVER-TRUSTED: a different process or connection (the
 *    MySQL client, another app), and PDO-level transaction verbs (SAVEPOINT/ROLLBACK), which Laravel sends
 *    without going through the statement path. ⚠️ The database GRANT is the layer that would catch those — a
 *    proposal recorded in KNOWN-LIMITS, deliberately not changed in this pass.
 */
final class PlatformWriteGuard
{
    /** Nesting depth of read-only sections (a read inside a read stays read-only until the outer one ends). */
    private static int $readOnlyDepth = 0;

    /** Statement verbs that only read. Everything else is a write. */
    private const READ_VERBS = ['select', 'show', 'describe', 'desc', 'explain'];

    /** Verbs refused against the append-only ledger. ⚠️ ALTER / DROP stay allowed — migrations need them. */
    private const LEDGER_FORBIDDEN_VERBS = ['update', 'delete', 'truncate', 'replace'];

    /**
     * Run a callback with every write refused at the connection.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function readOnly(Closure $callback): mixed
    {
        self::$readOnlyDepth++;

        try {
            return $callback();
        } finally {
            self::$readOnlyDepth--;
        }
    }

    public static function inReadOnly(): bool
    {
        return self::$readOnlyDepth > 0;
    }

    /** Wire both refusals onto a connection. Called once from AppServiceProvider::boot(). */
    public static function register(Connection $connection): void
    {
        $connection->beforeExecuting(static function (string $query): void {
            self::inspect($query);
        });
    }

    /** ⚠️ Public so the refusal logic itself is unit-testable without a database. */
    public static function inspect(string $sql): void
    {
        $verb = self::verb($sql);

        if (self::$readOnlyDepth > 0 && ! self::isRead($verb, $sql)) {
            throw PlatformWriteRefused::readOnlyDoor($verb);
        }

        if (in_array($verb, self::LEDGER_FORBIDDEN_VERBS, true) && self::targetsLedger($sql)) {
            throw PlatformWriteRefused::appendOnlyLedger($verb);
        }
    }

    /** The statement's first keyword, lower-cased, after comments and whitespace. */
    public static function verb(string $sql): string
    {
        $stripped = (string) preg_replace('~^\s*(?:(?:/\*.*?\*/|--[^\n]*\n|#[^\n]*\n)\s*)*~s', '', $sql);

        return preg_match('/^\s*\(?\s*([a-zA-Z]+)/', $stripped, $m) === 1 ? strtolower($m[1]) : '';
    }

    private static function isRead(string $verb, string $sql): bool
    {
        if (in_array($verb, self::READ_VERBS, true)) {
            return true;
        }

        // A CTE is a read unless it feeds a write (`WITH x AS (...) UPDATE ...` is legal MySQL 8).
        return $verb === 'with' && preg_match('/\b(insert|update|delete|replace)\b/i', $sql) !== 1;
    }

    private static function targetsLedger(string $sql): bool
    {
        $table = (new PlatformAdminAction)->getTable();

        return preg_match('/[`"\s.]'.preg_quote($table, '/').'[`"\s]/i', ' '.$sql.' ') === 1;
    }
}
