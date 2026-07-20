<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4c · Sub-gate 3 — the GUEST's read position on a message thread, mirroring `owner_last_read_at`. The
 * guest portal must track its own read state separately from `unread_count`, which is the OPERATOR's badge — a
 * guest-side read must never zero the operator's counter (the same trap the owner inbox avoided in Wave 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_threads', function (Blueprint $table): void {
            $table->timestamp('guest_last_read_at')->nullable()->after('owner_last_read_at');
        });
    }

    public function down(): void
    {
        Schema::table('message_threads', function (Blueprint $table): void {
            $table->dropColumn('guest_last_read_at');
        });
    }
};
