<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `housekeeping_tasks` — cleaning jobs (drives the staff PWA board later). A departure-clean is
 * auto-generated when a reservation checks out (via a domain-event listener). Photo proof
 * attaches through the shared polymorphic `media`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('unit_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('type', ['departure', 'stayover', 'deep', 'arrival_prep'])->default('departure');
            $table->enum('status', ['pending', 'in_progress', 'clean', 'inspected'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->foreignUlid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->json('checklist')->nullable();
            $table->foreignUlid('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_id', 'date']);
            $table->index(['property_id', 'status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_tasks');
    }
};
