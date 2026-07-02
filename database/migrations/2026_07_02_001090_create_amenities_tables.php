<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `amenities` — a GLOBAL shared catalog (name/icon/category), reused across tenants like a
 * lookup table (matches the schema, which lists no organization_id here).
 * `amenitiables` — polymorphic pivot linking a catalog amenity to a tenant-scoped
 * property / room_type / unit. It is reached only via the parent's (scoped) morphToMany.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('icon')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });

        Schema::create('amenitiables', function (Blueprint $table) {
            $table->foreignUlid('amenity_id')->constrained()->cascadeOnDelete();
            $table->ulidMorphs('amenitiable'); // amenitiable_id char(26) + amenitiable_type + index

            $table->primary(['amenity_id', 'amenitiable_id', 'amenitiable_type'], 'amenitiables_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenitiables');
        Schema::dropIfExists('amenities');
    }
};
