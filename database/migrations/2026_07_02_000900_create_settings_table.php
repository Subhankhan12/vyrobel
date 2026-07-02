<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `settings` — typed config with a polymorphic owner (organization or property). A null
 * `organization_id` denotes a platform-global setting. Owner is polymorphic, so no FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('organization_id')->nullable();
            $table->string('owner_type'); // e.g. App\Models\Organization, App\Models\Property
            $table->ulid('owner_id');
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->index('organization_id');
            $table->unique(['owner_type', 'owner_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
