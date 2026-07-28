<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner completeness sweep · Wave B · GATE 5 — the EXPORT DOWNLOAD audit-log.
 *
 * Every financial export (PDF/CSV) the owner or a sub-user downloads is recorded here: WHO (user), WHAT (kind +
 * label + optional subject id), and WHEN + from where. This makes the Team security note's original claim TRUE
 * ("financial exports are watermarked with who downloaded them") — the same identity is stamped onto the file and
 * recorded here, and the primary owner can review the log. Owner-scoped (organization_id + owner_id), append-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_downloads', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('owner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('downloader_name')->nullable();     // snapshot — the log survives a later rename
            $table->string('kind', 60);                        // statement_pdf | bookings_csv | remittance_pdf | …
            $table->string('label');                           // human description shown in the log
            $table->string('subject_id')->nullable();          // the statement/payout id, when applicable
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->index(['owner_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_downloads');
    }
};
