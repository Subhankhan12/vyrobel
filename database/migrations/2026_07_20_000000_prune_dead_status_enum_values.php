<?php

declare(strict_types=1);

use App\Services\FolioService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 0.2 — align two status enums with what the code ACTUALLY writes (the invariant register's "dead enum
 * values": a status-keyed UI would render never, silently). Decided per enum:
 *   • `reservations.payment_status` — `'charged_back'` had NO writer (no chargeback/dispute flow; that needs a
 *     Stripe dispute webhook that does not exist) → REMOVED. `'refunded'` is now genuinely WRITTEN by
 *     {@see FolioService::paymentStatus()} for a fully-refunded stay → it STAYS.
 *   • `folios.status` — `'closed'`/`'void'` had NO writer and NO guard (every folio is `'open'`, forever), and
 *     a half-built `'closed'` without enforcement would be a worse lie than the dead value → REMOVED until a
 *     real folio close/void lifecycle (`FolioService::closeFolio()`) is built, which re-adds it WITH enforcement.
 *
 * Safe: zero rows carry any removed value — nothing in `app/` ever wrote them (verified by grep, 2026-07-20).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reservations MODIFY payment_status ENUM('unpaid', 'partial', 'paid', 'refunded') NOT NULL DEFAULT 'unpaid'");
        DB::statement("ALTER TABLE folios MODIFY status ENUM('open') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reservations MODIFY payment_status ENUM('unpaid', 'partial', 'paid', 'refunded', 'charged_back') NOT NULL DEFAULT 'unpaid'");
        DB::statement("ALTER TABLE folios MODIFY status ENUM('open', 'closed', 'void') NOT NULL DEFAULT 'open'");
    }
};
