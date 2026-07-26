<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUPER ADMIN · BUILD 2 · GATE 2 — the PLATFORM-LEVEL channel-integration credentials (Channex / OTAs).
 *
 * These are the FOUNDER'S master platform credentials per channel — NOT per-tenant, NOT in ENV (they are
 * UI-managed + rotatable). `credentials` is stored ENCRYPTED at rest (Laravel `encrypted:array` cast — the same
 * at-rest encryption the app already uses for the owner IBAN / guest PII, #28), so the ciphertext is what lands
 * on disk; the secret is never rendered back after saving (write-only / masked in the UI).
 *
 * 🔒 The actual channel SYNC is ACCOUNT-GATED (needs the real Channex/OTA accounts) — this stores + manages the
 * credentials + connection model; the real sync is deferred honestly. The customer-self-connect model (a
 * tenant later connecting THEIR own channel account on top) is documented, built later.
 *
 * ⚠️ ADDITIVE ONLY — a NEW table, no FK into existing tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_integrations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('channel')->unique();          // channex | booking_com | airbnb | expedia
            $table->text('credentials')->nullable();      // encrypted:array — the master credential bag (ciphertext)
            $table->boolean('is_enabled')->default(false);
            $table->string('status')->default('not_configured');   // not_configured | configured | tested
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_integrations');
    }
};
