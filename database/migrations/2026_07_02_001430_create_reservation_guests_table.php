<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `reservation_guests` — additional guests attached to a reservation (optionally to a unit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_guests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('reservation_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('guest_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['reservation_id', 'reservation_unit_id', 'guest_id'], 'reservation_guests_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_guests');
    }
};
