<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes the Step 1 forward hook: `property_user.property_id` was created as an indexed ULID
 * column without a foreign key because `properties` did not exist yet. It exists now, so add
 * the constraint (see DECISIONS.md, Step 1 close-out).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_user', function (Blueprint $table) {
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('property_user', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
        });
    }
};
