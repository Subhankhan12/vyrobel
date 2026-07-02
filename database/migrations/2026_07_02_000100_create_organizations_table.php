<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `organizations` — the tenant (a company/operator account). This table IS the tenant,
 * so it is NOT itself organization-scoped. Every other business table carries a
 * `organization_id` that points here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('mode', ['single_property', 'chain', 'property_manager'])->default('single_property');
            $table->char('default_currency', 3);
            $table->string('default_locale', 10)->default('en');
            $table->char('country', 2)->nullable();
            $table->enum('status', ['trialing', 'active', 'suspended', 'cancelled'])->default('trialing');
            $table->string('billing_email')->nullable();
            $table->string('custom_domain')->nullable()->unique();
            $table->json('branding')->nullable(); // logo, colors — white-label
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
