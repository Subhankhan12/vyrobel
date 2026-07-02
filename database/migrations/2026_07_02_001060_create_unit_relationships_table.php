<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `unit_relationships` — connected rooms / multi-bedroom composition.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_relationships', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('related_unit_id')->constrained('units')->cascadeOnDelete();
            $table->enum('relation', ['connects_to', 'part_of']);
            $table->timestamps();

            $table->unique(['unit_id', 'related_unit_id', 'relation'], 'unit_relationships_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_relationships');
    }
};
