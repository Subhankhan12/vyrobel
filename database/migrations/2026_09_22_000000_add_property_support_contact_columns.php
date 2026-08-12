<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The GUEST SUPPORT BACKEND (2026-08-12) — the other half of `DO-G13-1`, which the founder ruled must exist
 * BEFORE any support UI is drawn. Three per-property columns, and the honesty rule is the SCHEMA rather than a
 * discipline someone has to remember:
 *
 * ⚠️ ALL THREE ARE NULLABLE WITH NO DEFAULT, DELIBERATELY. NULL is not "unset yet", it is the product state
 * "this property offers no such channel", and the guest surface renders the affordance ONLY when the column is
 * populated — never a blank row, a placeholder or a "not provided". A default here would ship an operational
 * promise (a number to ring, an hour to expect a reply) that no operator ever made: the `DO-EMAIL-9` class of
 * false claim, in the one place a guest is most likely to act on it.
 *
 * ⚠️ PER-PROPERTY, never global. A support number is a real operational commitment by the people who run that
 * site; an org-wide number would route a guest of property B to property A's desk. `properties` already carries
 * `organization_id` (BelongsToOrganization), so these inherit the tenant perimeter unchanged — no new scope.
 *
 * `support_reply_norm` stores a KEY from a fixed catalogue, not free text — see App\Support\Guest\PropertySupport.
 * The operator picks a bucket; the sentence a guest reads ("usually within a few hours") is written in code, so
 * a guarantee ("replies within 1 hour, promised") is not expressible through this field at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->string('support_phone', 32)->nullable()->after('brand_powered_by');
            $table->string('support_reply_norm', 32)->nullable()->after('support_phone');
            $table->string('support_hours', 120)->nullable()->after('support_reply_norm');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn(['support_phone', 'support_reply_norm', 'support_hours']);
        });
    }
};
