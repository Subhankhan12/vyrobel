<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Console\Commands\CreatePlatformAdminCommand;
use App\Http\Controllers\Admin\AccountController;
use App\Support\Auth\PasswordPolicy;
use Illuminate\Validation\Rules\Password;

/**
 * ADMIN 01 · PHASE 1 — THE ONE password rule for a PLATFORM ADMIN, at every door that sets one:
 * {@see CreatePlatformAdminCommand} (creation), the invitation acceptance, and {@see AccountController} (change).
 *
 * ⚠️⚠️ WHY IT EXISTS: account creation required 12 characters with mixed case and a number, but the change-
 * password form used the app-wide default (8 characters) — so the highest-privilege account in the product could
 * be changed to something WEAKER than it was allowed to be created with.
 *
 * ⚠️⚠️ AND IT IS THE *UNION* OF THE TWO OLD BARS, NOT EITHER ONE. The first version of this class copied the
 * creation bar alone — and PasswordCompositionTest (path 6/9) caught that it had quietly DROPPED the symbol the
 * app-wide rule used to demand on the change form: `StrongPass12` passed where it had been refused the day before.
 * So: the creation bar's length and mixed case, PLUS the app-wide rule's symbol. No dimension is weaker than
 * either rule it replaces.
 *
 * ⚠️ Deliberately NOT {@see PasswordPolicy} (the tenant rule): a higher bar for the super-admin is a real
 * difference, not drift.
 */
final class PlatformAdminPasswordPolicy
{
    public const MIN_LENGTH = 12;

    public static function rule(): Password
    {
        return Password::min(self::MIN_LENGTH)->mixedCase()->numbers()->symbols();
    }

    public static function hint(): string
    {
        return 'At least '.self::MIN_LENGTH.' characters, with upper- and lower-case letters, a number and a symbol.';
    }
}
