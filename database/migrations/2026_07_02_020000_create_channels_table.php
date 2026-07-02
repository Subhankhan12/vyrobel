<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `channels` — one connection from a Vyrobel property to a channel-manager provider (Channex at
 * launch). This is the OTA credential vault: `credentials` holds the provider API key / per-
 * property token, ENCRYPTED at rest (encrypted cast on the model, convention #5), never plaintext.
 *
 * Per-tenant AND per-property: a Channex property maps 1:1 to one of our properties, so the
 * unique key is (organization_id, property_id, provider).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->enum('provider', ['channex'])->default('channex'); // extensible; only channex in P2
            $table->string('name');
            $table->string('external_property_id')->nullable(); // Channex property id
            $table->text('credentials')->nullable();            // ENCRYPTED cast on the model — the vault
            $table->json('settings')->nullable();               // provider-specific options (no secrets)
            $table->enum('status', ['disconnected', 'connected', 'error', 'disabled'])->default('disconnected');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'property_id', 'provider']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
