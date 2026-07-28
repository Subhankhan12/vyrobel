<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Tax Center exact-match (#63+) — a REGISTRATION FLAG for VAT.
 *
 * The Tax Center wireframe exposes a "VAT registered" toggle independent of the VAT number. The store already
 * holds `vat_number` (encrypted) but no boolean for the registration status itself, so an owner who is VAT
 * registered but hasn't yet typed the number couldn't be represented. This adds a plain boolean flag (NOT a VAT
 * computation — Vyrobel models no VAT); it feeds the letterhead/invoice wording only. Additive + default false,
 * so existing owners are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table): void {
            $table->boolean('vat_registered')->default(false)->after('vat_number');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table): void {
            $table->dropColumn('vat_registered');
        });
    }
};
