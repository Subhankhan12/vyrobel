<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `tags` — per-tenant tag catalog (guest segments, reservation flags, …).
 * `taggables` — polymorphic pivot; reached only via a tenant-scoped parent's morphToMany.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignUlid('tag_id')->constrained()->cascadeOnDelete();
            $table->ulidMorphs('taggable');

            $table->primary(['tag_id', 'taggable_id', 'taggable_type'], 'taggables_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
