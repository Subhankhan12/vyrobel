<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DEC-1 (his ruling, 2026-11-13) — sending from TWO mailboxes. ⚠️ ADDITIVE: one nullable column.
 *
 * Which mailbox an accepted message left from, as the mailer's own name ("smtp", "smtp_second") — a label, NEVER an address. The
 * rotation counts "how many has this mailbox sent in the last 24 hours" from this column, so the limit is read from the same
 * record the health board shows. NULL = sent before the rotation existed, or by a mailer that is not the rotating one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_send_log', function (Blueprint $table): void {
            $table->string('mailbox', 24)->nullable()->after('mailable');
            $table->index(['mailbox', 'outcome', 'created_at'], 'mail_send_log_mailbox_idx');
        });
    }

    public function down(): void
    {
        Schema::table('mail_send_log', function (Blueprint $table): void {
            $table->dropIndex('mail_send_log_mailbox_idx');
            $table->dropColumn('mailbox');
        });
    }
};
