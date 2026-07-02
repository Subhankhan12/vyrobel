<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `guests` — guest profile (PII; handle per convention #5).
 *
 * `email` and `phone` are stored as ENCRYPTED ciphertext (Laravel `encrypted` cast, APP_KEY):
 * TEXT columns, never indexed by value. GDPR erase = `Guest::anonymize()` (nulls PII + sets
 * `anonymized_at`), NEVER soft-delete-and-retain-PII. `first_name`/`last_name` are nullable so
 * anonymize() can null them while preserving the row and its id (reservation/financial FKs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->text('email')->nullable();   // encrypted at rest
            $table->text('phone')->nullable();   // encrypted at rest
            $table->char('nationality', 2)->nullable();
            $table->date('dob')->nullable();
            $table->string('language', 10)->nullable();
            $table->boolean('is_vip')->default(false);
            $table->boolean('risk_flag')->default(false);
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('anonymized_at')->nullable();
            $table->json('external_refs')->nullable(); // OTA guest ids
            $table->timestamps();
            $table->softDeletes();

            $table->index('anonymized_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
