<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Auth Phase B · Step 2 — staff invites (Hotel Manager / Accountant) reuse the owner-invite machinery
 * but have NO Owner record, so `owner_invites.owner_id` must be nullable. A raw MODIFY changes only the
 * column's nullability and leaves the existing FK constraint (owner_invites_owner_id_foreign) intact —
 * owner invites still set owner_id; staff invites leave it null. MySQL-only project (see CLAUDE.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE owner_invites MODIFY owner_id CHAR(26) NULL');
    }

    public function down(): void
    {
        // Reversible only if no staff (null-owner) invites exist.
        DB::statement('ALTER TABLE owner_invites MODIFY owner_id CHAR(26) NOT NULL');
    }
};
