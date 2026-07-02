<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `guest_documents` — sensitive IDs/passports/registration cards. The domain record (type +
 * verification) referencing the actual file through the shared secure `documents` table
 * (which points at `media`) — NEVER an inline raw file. Signed-URL access, no public disk.
 *
 * `reservation_id` is a forward hook to `reservations` (Step 5): indexed ULID, no FK yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->ulid('reservation_id')->nullable(); // FK added in Step 5
            $table->enum('type', ['passport', 'id_card', 'visa', 'registration_card']);
            $table->foreignUlid('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_documents');
    }
};
