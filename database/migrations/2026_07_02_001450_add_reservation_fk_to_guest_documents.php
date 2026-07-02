<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Closes the Step 4 forward hook: `guest_documents.reservation_id` now that `reservations` exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_documents', function (Blueprint $table) {
            $table->foreign('reservation_id')
                ->references('id')
                ->on('reservations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guest_documents', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
        });
    }
};
