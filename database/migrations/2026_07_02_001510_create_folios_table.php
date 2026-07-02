<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `folios` — the financial ledger header for a reservation (one per reservation in Phase 1,
 * splittable later — hence an index, not a unique, on reservation_id). total/paid/balance are
 * DERIVED caches kept in sync by FolioService from the append-only ledger (folio_items + payments).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folios', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->constrained()->cascadeOnDelete();
            $table->char('currency', 3);
            $table->enum('status', ['open', 'closed', 'void'])->default('open');
            $table->bigInteger('total_minor')->default(0);
            $table->bigInteger('paid_minor')->default(0);
            $table->bigInteger('balance_minor')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folios');
    }
};
