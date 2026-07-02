<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `payment_authorizations` — deposit / incidental holds (authorize vs capture lifecycle).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_authorizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('payment_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('authorized_minor')->default(0);
            $table->bigInteger('captured_minor')->nullable();
            $table->char('currency', 3);
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['authorized', 'captured', 'released', 'expired', 'voided'])->default('authorized');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_authorizations');
    }
};
