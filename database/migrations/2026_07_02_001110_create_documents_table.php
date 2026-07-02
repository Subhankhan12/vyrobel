<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `documents` — polymorphic secure documents (stricter access than media; guest IDs live
 * here in Step 4). References a `media` row for the actual stored file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->ulidMorphs('documentable');
            $table->foreignUlid('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('category')->nullable();
            $table->boolean('signed_url_required')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
