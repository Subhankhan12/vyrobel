<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * #104b GATE 1 — the operator Security "IP allowlist": a REAL org-level admin-panel access control. When an org
 * has ≥1 active rule, ONLY a request whose IP matches a rule may reach /app (enforced by EnforceIpAllowlist). Zero
 * rules ⇒ no restriction (the honest "No IP restrictions" empty state) — the allowlist is opt-in, never a fake.
 * Tenant-scoped (BelongsToOrganization — it governs THIS org's admin panel). `ip_address` holds an exact IP or a
 * CIDR block (Symfony IpUtils matches both). `label` is the operator's note; `created_by` is the acting admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_allow_rules', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->index();
            $table->string('ip_address', 64);   // exact IPv4/IPv6 or a CIDR block
            $table->string('label', 80)->nullable();
            $table->foreignUlid('created_by')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_allow_rules');
    }
};
