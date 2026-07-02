<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `webhooks` / `webhook_deliveries` — tenant outbound webhooks (signed). Delivery attempts
 * are logged per event. Actual dispatching is wired later; this models the contract now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->json('events'); // subscribed domain_event types
            $table->string('secret'); // HMAC signing secret
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }
};
