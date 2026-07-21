<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff shift roster (Phase 7d · Team Schedule · SG1). A shift = a real staff member ({@see User}) on a
 * {@see ShiftType} for one date at one property. TENANT + PROPERTY scoped (`organization_id` +
 * `property_id`): a multi-property operator rosters PER property, so a shift always belongs to the SELECTED
 * property (resolved through `PropertyContext`, never `->first()`). `unique(property_id, user_id, date)` —
 * one shift per staff member per property per day.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();   // the REAL staff member
            $table->date('date');
            $table->enum('shift_type', ['early', 'day', 'late', 'off']);
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['property_id', 'user_id', 'date']);
            $table->index(['property_id', 'date']);
            $table->index(['organization_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
