<?php

use App\Jobs\Ops\QueueHeartbeatJob;
use App\Support\Ops\Heartbeat;
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

// Transactional-emails exact-match — daily guest PRE-ARRIVAL self check-in emails, sent the day the arrival
// window opens (48h before check-in), carrying the guest's OWN real door code + wifi (guest-only; the #33
// owner-perimeter is untouched). The previously-deferred pre-arrival trigger, now real.
Schedule::command('emails:send-pre-arrival')->dailyAt('09:00');

// Super Admin · Build 2 · Gate 1 — PRIVACY RETENTION: purge login-activity security-signal rows (IP + all)
// older than 90 days, daily (GDPR — security-monitoring purpose, limited retention). See PrunePlatformActivity.
Schedule::command('platform:prune-activity')->daily();

// Payout engine (Phase 2) — the automated owner payout run, daily. INERT unless PAYOUT_ENGINE_ENABLED is on
// (the command self-guards), and TEST-MODE unless live keys + production. The command selects owners due today
// per their payout preferences and disburses via the certified PayoutRunService. ⚠️ Real money only at go-live.
Schedule::command('payouts:run')->dailyAt('06:00');

// #98 — archive properties whose scheduled removal date has passed (soft-delete, records retained). Daily.
Schedule::command('properties:process-removals')->dailyAt('02:00');

// Guest Portal · GATE 3 (D2) — run guest-account deletions whose 14-day grace window has elapsed
// (cancel upcoming reservations per policy + GDPR-anonymize). Reversible until it runs. Daily.
Schedule::command('guests:process-deletions')->dailyAt('03:00');

// Guest Portal batch 2 · GATE 2 (D2) — DATA MINIMISATION: hard-delete guest ID documents (encrypted file
// bytes + rows) 30 days after the stay checks out. Makes the online-check-in "deleted 30 days after
// checkout" promise real. Daily.
Schedule::command('guest-documents:purge-expired')->dailyAt('03:30');

// ─────────────────────────────────────────────────────────────────────────────────────────────────
// DEPLOY §7.2 — LIVENESS. ⚠️ None of the eight commands above runs more often than daily, so none of
// them can prove the scheduler is alive at any useful resolution: a cron that died at 09:01 would look
// identical to a healthy one until tomorrow. These two entries exist ONLY to be observed.
//
// ⚠️ AND NOTE WHAT THIS FILE CANNOT DO FOR ITSELF. If cron stops, everything below stops too — a check
// living here could never report its own absence. That is why the alarm is an EXTERNAL monitor polling
// /up/liveness, and optionally a dead-man's-switch ping. This half only leaves the evidence.
// ─────────────────────────────────────────────────────────────────────────────────────────────────
Schedule::call(fn () => Heartbeat::beat(Heartbeat::SCHEDULER))
    ->everyMinute()
    ->name('ops-scheduler-heartbeat')
    ->withoutOverlapping()
    // ⚠️ THE DEAD-MAN'S-SWITCH, and it is the only detector that survives the whole box dying. An external
    // service expects this ping every minute and raises the alarm when it STOPS arriving — so it alerts on
    // power loss, a killed cron and a crashed host alike, none of which any in-app check can report.
    // ⛔ FOUNDER STEP: set OPS_HEARTBEAT_PING_URL (Healthchecks.io / Better Stack / Cronitor). Unset, this
    // is inert by design — no ping is attempted and nothing pretends one was.
    ->pingOnSuccessIf((bool) env('OPS_HEARTBEAT_PING_URL'), (string) env('OPS_HEARTBEAT_PING_URL', ''));

// ⚠️ Dispatched every minute, stamped only when a WORKER runs it — see QueueHeartbeatJob. A dispatch that
// is never processed is exactly the signal we want, so this must go through the real queue.
Schedule::job(new QueueHeartbeatJob)
    ->everyMinute()
    ->name('ops-queue-heartbeat')
    ->withoutOverlapping();
