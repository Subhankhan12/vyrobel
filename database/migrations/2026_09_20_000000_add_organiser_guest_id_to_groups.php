<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist the block ORGANISER as a real FK on `groups`. The organiser's name was already copied into the
 * plaintext `groups.contact` json for display, but with no link back to the Guest there was no way to clear
 * that copy on GDPR erase — so the organiser's name survived anonymize() in plaintext (an adversarial review
 * caught it). The FK lets Guest::anonymize() redact the contact copy, closing the erase gap.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->foreignUlid('organiser_guest_id')->nullable()->after('billing_mode')
                ->constrained('guests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organiser_guest_id');
        });
    }
};
