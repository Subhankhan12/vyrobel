<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner completeness sweep · Wave A · GATE 3 — model the expense VENDOR.
 *
 * The owner Expenses page once REFUSED a Vendor column as unmodelled (#39: no column → no fake supplier name).
 * This adds a real, nullable `vendor` to `owner_expenses` — the supplier/contractor a cost was paid to, captured
 * when known (e.g. a demo cleaning company) and honestly "—" when not (the maintenance→expense path has no vendor
 * on the ticket, so those stay null). Additive + nullable; never a fabricated value. NOT PII (a business name,
 * not a guest's contact), so it is a plain column — but it is descriptive of a cost, not of a guest (#28 N/A).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_expenses', function (Blueprint $table): void {
            $table->string('vendor', 160)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('owner_expenses', function (Blueprint $table): void {
            $table->dropColumn('vendor');
        });
    }
};
