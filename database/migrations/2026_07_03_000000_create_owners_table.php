<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `owners` — the external owner/investor party (Phase 3 · Module 1). A person or company that owns
 * one or more `units` and receives periodic payouts. Org-scoped (`BelongsToOrganization`), optionally
 * linked to a `User` for owner-portal login via the Phase 1 External Owner role.
 *
 * PII (`email`/`phone`/`tax_id`) is ENCRYPTED at rest (convention #5) — same discipline as guests.
 * `payout_currency` is the currency the owner is PAID in and may differ from the booking currency;
 * cross-currency conversion happens (with a recorded rate) only at statement time, never as a float.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owners', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->text('email');                 // ENCRYPTED cast (PII)
            $table->text('phone')->nullable();     // ENCRYPTED cast (PII)
            $table->text('tax_id')->nullable();    // ENCRYPTED cast (PII)
            $table->json('address')->nullable();
            $table->char('payout_currency', 3);
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
