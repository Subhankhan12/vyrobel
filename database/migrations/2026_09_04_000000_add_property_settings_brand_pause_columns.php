<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task #98 — operator Property Settings. Adds the per-property GUEST-FACING BRAND store (accent/logo/email
 * from-name/reply-to/powered-by), the property-level BOOKING PAUSE flag, and the self-check-in policy toggle.
 *
 * Brand columns fail OPEN: null everywhere ⇒ the shared email layout + guest shell render byte-identically to
 * today's global Vyrobel branding (see App\Support\Branding\PropertyBrand). `bookings_paused_at` is the ONE
 * property-level lever the certified AvailabilityService reads (a paused property recomputes to total=0, so new
 * bookings are refused while existing `sold` stays run — orthogonal to per-unit is_active).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->string('brand_accent', 9)->nullable()->after('brand');            // hex e.g. #C2410C
            $table->string('brand_logo_path')->nullable()->after('brand_accent');     // path on the `public` disk
            $table->string('brand_email_from_name')->nullable()->after('brand_logo_path');
            $table->string('brand_reply_to')->nullable()->after('brand_email_from_name');
            $table->boolean('brand_powered_by')->default(true)->after('brand_reply_to');
            $table->timestamp('bookings_paused_at')->nullable()->after('status');
        });

        Schema::table('property_policies', function (Blueprint $table): void {
            $table->boolean('self_check_in')->default(false)->after('smoking');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn([
                'brand_accent', 'brand_logo_path', 'brand_email_from_name',
                'brand_reply_to', 'brand_powered_by', 'bookings_paused_at',
            ]);
        });

        Schema::table('property_policies', function (Blueprint $table): void {
            $table->dropColumn('self_check_in');
        });
    }
};
