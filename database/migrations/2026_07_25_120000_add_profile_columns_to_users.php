<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-account area (Profile & account + Security).
 *
 * - `avatar_path` — the owner's OWN uploaded avatar (a path on the `public` disk; the file bytes live in
 *   storage, not the DB). Self-only, set by App\Http\Controllers\Owner\OwnerAccountController.
 * - `pending_email` — a login-email change is NEVER switched immediately. The new address is parked here and a
 *   signed verify link is mailed TO IT; the switch happens only when that link is clicked (the same
 *   "verify before it takes effect" discipline the guest claim flow uses). Not an encrypted column — a login
 *   email is a lookup key (like `email`), not contact PII rendered elsewhere; #28 is about OTHER people's
 *   contact details.
 *
 * `status` (active|disabled — used by Deactivate in Gate 2) and `locale` (Language) already exist on `users`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable()->after('locale');
            $table->string('pending_email')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_path', 'pending_email']);
        });
    }
};
