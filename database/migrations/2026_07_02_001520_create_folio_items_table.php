<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `folio_items` — every charge line (room/tax/fee/add_on/product/manual/discount). Posted lines
 * are correct-by-reversal: FolioService NEVER deletes/edits a posted row — corrections are new
 * offsetting lines (e.g. a negative discount/credit). SoftDeletes is a safety net only.
 *
 * `folio.total_minor = SUM(folio_items.amount_minor)` — tax is posted as its own line (type=tax),
 * so the ledger sum never double-counts. `tax_minor` is per-line breakdown metadata (not summed).
 * `reference` is a nullable polymorphic pointer to the source definition (add_on/tax/fee).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folio_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('folio_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['room', 'tax', 'fee', 'add_on', 'product', 'manual', 'discount'])->default('manual');
            $table->string('description');
            $table->bigInteger('unit_amount_minor')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->bigInteger('amount_minor')->default(0); // unit_amount_minor * quantity
            $table->bigInteger('tax_minor')->nullable();
            $table->char('currency', 3);
            $table->nullableUlidMorphs('reference'); // reference_type/reference_id → add_on/tax/fee def
            $table->date('service_date')->nullable();
            $table->foreignUlid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('folio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_items');
    }
};
