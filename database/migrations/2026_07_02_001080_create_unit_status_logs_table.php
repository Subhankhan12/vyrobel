<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `unit_status_logs` — append-only history of housekeeping/condition status changes
 * (audit + agent context). Write-once: only `created_at`, no updates, no soft delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_status_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('unit_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->enum('type', ['housekeeping', 'condition']);
            $table->foreignUlid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['unit_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_status_logs');
    }
};
