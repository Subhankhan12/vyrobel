<?php

declare(strict_types=1);

/**
 * Reservation-desk operating thresholds (APPLY 01 #2, 2026-09-22).
 *
 * ⚠️ IN A CONFIG FILE, NOT AN `env()` CALL IN APPLICATION CODE — the same reason `config/security.php`
 * records: `php artisan config:cache` (which the deploy runs) makes Laravel skip loading `.env` entirely,
 * so an `env()` call outside `config/` returns NULL in production, silently. Larastan enforces it.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Early check-in warning threshold — DAYS
    |--------------------------------------------------------------------------
    |
    | ⚠️⚠️ THIS IS A WARNING, NOT A CONTROL, AND THE DISTINCTION IS THE RULING. Checking a guest in more than
    | this many days before their arrival date produces a CONFIRMATION STEP that the operator can always pass.
    | It refuses nothing, it grants nothing, and no permission is attached to it. A front desk faces real
    | situations this product cannot enumerate — an early arrival, a corrected date, a walk-up against a
    | future booking — and a rule that blocks them would be wrong more often than the mistake it prevents.
    |
    | ⚠️ WHAT IT IS FOR: `checked_in` is a status with real consequences (it is the only status a check-out
    | can follow, and since the 2026-09-22 ruling it can no longer be cancelled — see STATUS-LADDERS R7).
    | Reaching it by mis-click on the wrong row is therefore expensive and, today, not undoable. The
    | confirmation exists to make that mis-click visible BEFORE the press.
    |
    | ⚠️ DELIBERATELY GLOBAL — there is NO per-organisation setting and none should be added without a
    | ruling. A per-tenant threshold is a settings screen, a migration, a scope and a default; it would also
    | make the behaviour untestable as one rule. One number, one meaning, everywhere.
    |
    | "More than a day away" → 1. Arrival tomorrow does not warn; arrival in two days does.
    |
    */
    'early_check_in_warning_days' => (int) env('VY_EARLY_CHECKIN_WARN_DAYS', 1),

];
