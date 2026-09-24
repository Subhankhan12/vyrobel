<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\Platform\PlatformWriteGuard;
use RuntimeException;

/**
 * A write the platform refused before it reached the database — see {@see PlatformWriteGuard}.
 *
 * ⚠️ Deliberately an exception, never a silent no-op: a write that quietly vanishes looks exactly like a write
 * that succeeded, which is the failure this guard exists to make impossible.
 */
final class PlatformWriteRefused extends RuntimeException
{
    public static function readOnlyDoor(string $verb): self
    {
        return new self("Refused: a {$verb} statement inside PlatformQuery::read(). The cross-tenant read path is read-only; an admin action must go through PlatformQuery::mutate() with a reason.");
    }

    public static function appendOnlyLedger(string $verb): self
    {
        return new self("Refused: a {$verb} statement against platform_admin_actions. The admin audit ledger is append-only — its rows are never edited or deleted.");
    }
}
