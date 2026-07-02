<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership pivots.
 *
 * `organization_user` — a user's membership + base role in an org (roles ride on spatie).
 * `property_user`     — scopes a staff member to specific properties (property-level roles).
 *
 * NOTE: `property_user.property_id` is created as an indexed ULID column WITHOUT a foreign
 * key constraint here, because `properties` is built in Step 2. The FK is added in the
 * Step 2 migration once the target table exists. Column-only forward hook for now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_user', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['invited', 'active', 'disabled'])->default('invited');
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('property_user', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->ulid('property_id'); // FK to properties added in Step 2
            $table->index('property_id');
            $table->timestamps();

            $table->unique(['organization_id', 'user_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_user');
        Schema::dropIfExists('organization_user');
    }
};
