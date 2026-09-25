<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MAIL 02 — additive: one row per email the mail server ACCEPTED or the app SUPPRESSED, for the health board's real
 * counts. ⚠️ No address, subject or body is stored — only the email type, the server's message id, and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_send_log', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('outcome', 20);                       // accepted | suppressed
            $table->string('mailable', 120);                     // the email TYPE (class basename), never content
            $table->string('message_id', 250)->nullable();       // the mail server's own id (accepted only)
            $table->unsignedSmallInteger('suppressed_recipients')->default(0);
            $table->timestamp('created_at')->index();
            $table->index(['outcome', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_send_log');
    }
};
