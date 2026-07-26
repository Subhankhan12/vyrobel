<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tier 1 sweep · Step 3 — daily cleanup of genuinely expired, never-accepted invites + their
// never-activated artifacts (conservative + idempotent; see OwnerInviteService::pruneExpired()).
Schedule::command('invites:prune-expired')->daily();

// Phase 3 · Sub-gate 4 — daily guest payment reminders for balances due before arrival (the real trigger; the
// email is genuine via Mailpit in dev / a real provider in prod).
Schedule::command('emails:send-payment-reminders')->dailyAt('09:00');

// Super Admin · Build 2 · Gate 1 — PRIVACY RETENTION: purge login-activity security-signal rows (IP + all)
// older than 90 days, daily (GDPR — security-monitoring purpose, limited retention). See PrunePlatformActivity.
Schedule::command('platform:prune-activity')->daily();
