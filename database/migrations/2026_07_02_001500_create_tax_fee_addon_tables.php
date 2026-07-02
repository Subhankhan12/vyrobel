<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Property-level pricing definitions: taxes, fees, add-ons.
 *
 * `rate` (decimal) carries percentage modes; `amount_minor` (+ currency) carries fixed-amount
 * modes — money is always integer minor units + ISO currency (convention #3). The schema lists
 * only `rate` on taxes; we add `amount_minor`/`currency` so fixed taxes are proper Money.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('kind', ['vat', 'city_tax', 'tourism_tax', 'other'])->default('vat');
            $table->enum('mode', ['percentage', 'per_night', 'per_person_night', 'flat'])->default('percentage');
            $table->decimal('rate', 8, 4)->nullable();       // e.g. 20.0000 for 20% (mode=percentage)
            $table->bigInteger('amount_minor')->nullable();  // fixed amount for non-percentage modes
            $table->char('currency', 3)->nullable();
            $table->json('applies_to')->nullable();
            $table->boolean('inclusive')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('mode', ['flat', 'per_night', 'per_person', 'percentage'])->default('flat');
            $table->decimal('rate', 8, 4)->nullable();
            $table->bigInteger('amount_minor')->nullable();
            $table->char('currency', 3)->nullable();
            $table->boolean('taxable')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('add_ons', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->bigInteger('price_minor')->default(0);
            $table->char('currency', 3);
            $table->boolean('taxable')->default(false);
            $table->integer('inventory_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_ons');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('taxes');
    }
};
