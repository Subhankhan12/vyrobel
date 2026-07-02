<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes the Step 2 forward hook: `room_types.default_rate_plan_id` was created as a nullable
 * ULID column without a target because `rate_plans` did not exist yet. It exists now, so add
 * the constraint. (Circular room_types <-> rate_plans FK is fine on InnoDB via nullable ALTER.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->foreign('default_rate_plan_id')
                ->references('id')
                ->on('rate_plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropForeign(['default_rate_plan_id']);
        });
    }
};
