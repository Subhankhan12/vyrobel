<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `folio_items.idempotency_key` — the money-path idempotency guard for the on-desk ADD-CHARGE action, mirroring
 * `payments.idempotency_key` (Batch C · GATE 3). A per-submit nonce keys the folio-item write so a duplicate
 * submission (a double-click racing the UI disable, a network retry, a bypassed UI) records the charge ONCE.
 * The UNIQUE index is the concurrent-race backstop (the loser catches the violation and returns the winner's row).
 * NULLABLE + unique so every existing caller (postAddOn / applyTax / applyFee, and the room/tax posting) — which
 * passes no key — keeps writing a distinct line exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folio_items', function (Blueprint $table): void {
            $table->string('idempotency_key')->nullable()->unique()->after('posted_at');
        });
    }

    public function down(): void
    {
        Schema::table('folio_items', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
