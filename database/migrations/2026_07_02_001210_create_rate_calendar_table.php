<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `rate_calendar` — nightly price per rate_plan per date. Money is integer minor units.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_calendar', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('rate_plan_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3);
            $table->timestamps();

            $table->unique(['rate_plan_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_calendar');
    }
};
