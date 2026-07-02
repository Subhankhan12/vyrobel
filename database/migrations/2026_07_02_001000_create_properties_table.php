<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `properties` — a physical site (can be mixed-type: hotel + apartments under one roof).
 * Timezone lives here; all guest-facing "today" logic computes in property-local time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->enum('type', [
                'hotel', 'aparthotel', 'serviced_apartment', 'apartment_building', 'apartment',
                'villa', 'hostel', 'guest_house', 'resort', 'mixed', 'portfolio',
            ])->default('hotel');
            $table->json('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('timezone')->default('UTC');
            $table->unsignedTinyInteger('star_rating')->nullable();
            $table->char('currency', 3);
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
