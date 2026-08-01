<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest-side "delete my account" as a REVERSIBLE, SCHEDULED erase (Guest Portal · GATE 2/3, founder decision D2).
 * When the guest confirms deletion, `scheduled_deletion_at` is stamped 14 days out; the account stays usable and
 * the guest can UNDO (clearing it) within the window. A daily command runs the actual erase on/after that
 * timestamp — per-policy cancellation of upcoming reservations + Guest::anonymize() — never immediate/destructive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->timestamp('scheduled_deletion_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table): void {
            $table->dropColumn('scheduled_deletion_at');
        });
    }
};
