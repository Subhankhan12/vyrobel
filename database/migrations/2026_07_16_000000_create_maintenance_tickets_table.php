<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `maintenance_tickets` — repair/maintenance tickets on a unit. Tenant-scoped (BelongsToOrganization).
 * A ticket can take its unit OUT OF SERVICE (Step 2 — the `condition_status` transition + availability
 * recompute), and a resolved ticket with a cost on an OWNED unit posts a deductible owner expense via
 * the certified `OwnerExpenseService` path (Step 3, linked by the polymorphic `owner_expenses.source` —
 * no denormalised FK here). Cost is integer minor units + ISO-4217 currency (filled at resolve).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('unit_id')->constrained('units')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['plumbing', 'electrical', 'appliance', 'structural', 'other'])->default('other');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'cancelled'])->default('open');

            $table->boolean('takes_out_of_service')->default(false);
            $table->unsignedBigInteger('cost_minor')->nullable();   // integer minor units, filled at resolve
            $table->string('currency', 3)->nullable();

            $table->foreignUlid('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'unit_id', 'status']);
            $table->index(['unit_id', 'takes_out_of_service', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};
