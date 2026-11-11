<?php

use App\Jobs\Ops\QueueHeartbeatJob;
use App\Support\Ops\Heartbeat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tier 1 sweep · Step 3 — daily cleanup of genuinely expired, never-accepted invites + their
// never-activated artifacts (conservative + idempotent; see OwnerInviteService::pruneExpired()).
Schedule::command('invites:prune-expired')->daily();

// Team chat — D-TC-TYPING-SWEEP: the "is typing…" rows are read for a few seconds; one a person left behind an hour ago is dead weight
// (one row per person per conversation, so it never piled up — this just keeps it from lingering). Idempotent, tenant-free.
Schedule::command('team-chat:prune-typing')->hourly();

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

// ADMIN 01 · PHASE 3 — end and announce break-glass sessions nobody touched again. Expiry is enforced on every request
// regardless; this only guarantees the end is recorded and the organisation is told.
Schedule::command('break-glass:expire')->everyMinute();

// ⚠️⚠️ THE RECONCILIATION — it REPORTS drift between this product and Stripe, and never repairs it.
//    Every defect in the billing sequence was silent; this is the standing version of "somebody looked".
//    ⚠️ Its own last-successful-run time is printed in its header, so a run that stops happening is
//    visible from the command itself rather than only from a log nobody reads.
Schedule::command('billing:reconcile')->dailyAt('04:00');

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

// ⚠️ RETENTION SWEEP (audit 2026-08-29 · M-6) — delete rows past their declared window from the log-like
// stores. Windows + reasoning live in ONE place: App\Support\Retention\RetentionPolicy.
// ⚠️ `audit_events` and `domain_events` are DELIBERATELY NOT swept — they are evidence for reconstructing a
// disputed owner figure, and a dispute surfaces late. That refusal is guarded by RetentionPolicyTest.
Schedule::command('retention:prune')->dailyAt('04:00');

// ─────────────────────────────────────────────────────────────────────────────────────────────────
// DEPLOY §7.2 — LIVENESS. ⚠️ None of the nine commands above runs more often than daily, so none of
// them can prove the scheduler is alive at any useful resolution: a cron that died at 09:01 would look
// identical to a healthy one until tomorrow. These two entries exist ONLY to be observed.
//
// ⚠️ AND NOTE WHAT THIS FILE CANNOT DO FOR ITSELF. If cron stops, everything below stops too — a check
// living here could never report its own absence. That is why the alarm is an EXTERNAL monitor polling
// /up/liveness, and optionally a dead-man's-switch ping. This half only leaves the evidence.
//
// ⚠️⚠️ WHAT THESE TWO CERTIFY, AND WHAT THEY DO NOT — VERIFIED 2026-08-31, because the deploy script's
// note used to read as though they covered more. They prove CRON IS ALIVE and A WORKER IS CONSUMING.
// They say NOTHING about whether the NINE commands above SUCCEEDED, and cannot: they are separate
// scheduled entries, and Laravel runs each event independently.
//
// ⚠️ MEASURED: when a scheduled command throws, `ScheduleRunCommand` catches it, dispatches
// `ScheduledTaskFailed` (nothing in app/ listens) and calls `$handler->report($e)` — so exactly ONE
// error log line is written. `schedule:run` still exits 0, this heartbeat still beats, the dead-man's
// ping still fires and /up/liveness still returns 200. A heartbeat that beats through failure certifies
// only that the beater ran.
//
// ⛔ CLOSING THAT IS A FOUNDER DECISION, deliberately not taken here: an `->onFailure()` hook on each of
// the nine (pinging the same dead-man's-switch on a /fail endpoint), or log-based alerting.
// ─────────────────────────────────────────────────────────────────────────────────────────────────
Schedule::call(function (): void {
    // ⚠️⚠️ A FAILED JOB RAISES THE ALARM — THROUGH THE CHANNEL THAT ALREADY RINGS, NOT A NEW SCREEN.
    //
    // ⚠️ With no Redis there is no Horizon, so a failed job lands in `failed_jobs` and NOTHING surfaces it.
    // 🔬 Measured: `horizon:status` returns "Connection refused" and no route lists failed jobs. ⚠️ Payments
    // fulfil on a webhook that queues work — so a failed payment webhook would sit in a table nobody opens.
    //
    // ⚠️⚠️ A DASHBOARD COUNT ONLY WORKS IF SOMEBODY OPENS THE DASHBOARD. Throwing here means the success-ping
    // below does NOT fire, the external check's grace period lapses, and the alert arrives by itself. One
    // mechanism, already proven to ring, and nobody has to remember to look.
    //
    // ⚠️ THRESHOLD: ANY failed job. With zero customers every failure is worth waking up for. ⚠️ If that ever
    // becomes noisy, raising it is a decision WITH A NUMBER, not a default — and the number belongs here.
    $failed = DB::table('failed_jobs')->count();

    if ($failed > 0) {
        throw new RuntimeException(
            "{$failed} job(s) in failed_jobs — the queue is dropping work. Inspect with `php artisan "
            .'queue:failed`, fix the cause, then `queue:retry all` (or `queue:flush` once understood). '
            .'This deliberately suppresses the heartbeat ping so the external check alerts.'
        );
    }

    Heartbeat::beat(Heartbeat::SCHEDULER);
})
    ->everyMinute()
    ->name('ops-scheduler-heartbeat')
    ->withoutOverlapping()
    // ⚠️ THE DEAD-MAN'S-SWITCH, and it is the only detector that survives the whole box dying. An external
    // service expects this ping every minute and raises the alarm when it STOPS arriving — so it alerts on
    // power loss, a killed cron and a crashed host alike, none of which any in-app check can report.
    // ⛔ FOUNDER STEP: set OPS_HEARTBEAT_PING_URL (Healthchecks.io / Better Stack / Cronitor). Unset, this
    // is inert by design — no ping is attempted and nothing pretends one was.
    // ⚠️⚠️ L14, FIXED (ADMIN 01 · Phase 1): read through config('ops.heartbeat_ping_url'), NOT env(). With a cached
    //    config (every production deploy) env() here returned NULL no matter what .env said, so this switch could
    //    never arm. Proven by behaviour on a cached-config run, not by the code reading right — see KNOWN-LIMITS.
    ->pingOnSuccessIf(filled(config('ops.heartbeat_ping_url')), (string) config('ops.heartbeat_ping_url', ''));

// ⚠️ Dispatched every minute, stamped only when a WORKER runs it — see QueueHeartbeatJob. A dispatch that
// is never processed is exactly the signal we want, so this must go through the real queue.
Schedule::job(new QueueHeartbeatJob)
    ->everyMinute()
    ->name('ops-queue-heartbeat')
    ->withoutOverlapping();
