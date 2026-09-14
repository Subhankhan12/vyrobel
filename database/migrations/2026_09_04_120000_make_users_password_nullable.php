<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️⚠️ THE PRECONDITION FOR ANY SOCIAL SIGN-IN — the schema could not hold a passwordless user at all.
 *
 * `users.password` was `varchar(255) NOT NULL`, so a Google signup had nowhere to put "no password". Ship
 * the provider first and the very first such account is locked out of every re-authenticated action with no
 * way to fix itself: `current_password` verifies against the stored hash, and there would be none.
 *
 * ⚠️ NULL, NOT A SENTINEL — AND THE DIFFERENCE IS MEASURED, NOT PREFERRED.
 *   · `Hash::check('anything', null)` → **false**   (fails closed)
 *   · `Hash::check('anything', '')`   → **false**   (fails closed — today's behaviour, preserved)
 *   · `Hash::check('anything', 'x')`  → **THROWS** "This password does not use the Bcrypt algorithm."
 * A sentinel would therefore turn a denial into a 500 on twenty-four sensitive routes. Null denies exactly
 * as the empty string already does, which is the property this migration must not disturb.
 *
 * ⚠️ THE GUEST TABLE ALREADY WORKS THIS WAY. `guests.password` has been nullable since the claim flow was
 * built, and `Guest::isClaimed()` reads `password !== null`. This brings `users` into line with a shape the
 * product already relies on, rather than inventing one.
 *
 * ⚠️ `platform_admins.password` IS DELIBERATELY LEFT NOT NULL. A platform admin is created by console
 * command with a password and mandatory 2FA; there is no provider path to that table and none is planned.
 * Excluded explicitly rather than silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        // ⚠️ Any passwordless row must be resolved before reversing — this cannot invent a hash, and
        //    writing one would be a credential nobody chose.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable(false)->change();
        });
    }
};
