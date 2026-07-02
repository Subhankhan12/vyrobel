<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `invoices` — issued proforma/tax invoices. `number` is sequential per property (hence
 * `property_id` + a unique index — the schema notes "sequential per property"). The PDF is
 * referenced later via `media_id` (shared media infra) — never an inline file. Correct-by-reversal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('folio_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->enum('type', ['proforma', 'tax'])->default('proforma');
            $table->timestamp('issued_at')->nullable();
            $table->json('tax_breakdown')->nullable();
            $table->foreignUlid('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->enum('status', ['draft', 'issued', 'void'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['property_id', 'number']);
            $table->index('folio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
