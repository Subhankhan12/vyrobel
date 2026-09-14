<?php

use App\Support\Owner\OwnerContext;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// ══ AUDIT 2026-08-29 · M-8 — EVERY SCHEDULED COMMAND, IN THE SCHEDULER'S OWN SHAPE ═══════════════════
//
// ⚠️ `properties:process-removals` threw TenantContextMissingException every night at 02:00 and archived
// nothing — SILENTLY. It survived because the only test calling it did so INSIDE a tenant context, which
// the scheduler never has. One command was the symptom; the absence of a class-level guard was the disease.
//
// ⚠️ THE SCHEDULER'S SHAPE IS: no tenant context, no owner context, no request, no authenticated user.
// ⚠️ AND SINCE THE E-1 FIX, `OwnerScope` FAILS CLOSED TOO — so scheduled work touching an owner-scoped
// model is newly exposed to the same shape. This runs with BOTH contexts cleared for that reason.
//
// ⚠️ IT DISCOVERS THE COMMANDS FROM THE SCHEDULE ITSELF. A hand-written list is how H-5 happened (a suite
// naming five of ninety-seven models), and a list cannot notice a command added tomorrow.

/**
 * Every artisan command the scheduler runs, discovered from the registered schedule.
 *
 * @return list<string>
 */
function scheduledCommandNames(): array
{
    $names = [];

    foreach (app(Schedule::class)->events() as $event) {
        // ⚠️ EXCLUSIONS ARE EXPLICIT AND REASONED, NEVER BY OMISSION:
        //   · Schedule::call() (the scheduler heartbeat) is a CallbackEvent with no command to invoke —
        //     it has no `command` property at all, so there is nothing for this guard to run.
        //   · Schedule::job() (the queue heartbeat) dispatches onto the queue rather than running here;
        //     its whole purpose is to be processed by a WORKER, so running it inline would prove nothing.
        // Both are covered by the §7.2 liveness drill instead.
        if (! $event instanceof Event || ! is_string($event->command ?? null)) {
            continue;
        }

        // The stored command is a full CLI invocation: '<php>' 'artisan' name --opts
        if (preg_match("~'artisan'\s+(?:'([^']+)'|(\S+))~", $event->command, $m) === 1) {
            $names[] = $m[1] !== '' ? $m[1] : $m[2];
        }
    }

    return array_values(array_unique($names));
}

// ── 1. the discovery is not vacuous ─────────────────────────────────────────────────────────────────
// An empty list would make test 2 pass having run nothing — the defect class this file exists for.
it('discovers the scheduled commands from the schedule itself', function () {
    expect(count(scheduledCommandNames()))->toBeGreaterThanOrEqual(8);
});

// ── 2. ⛔ EVERY scheduled command completes with NO tenant and NO owner context ──────────────────────
it('runs every scheduled command in the scheduler shape without throwing', function () {
    Mail::fake();   // two of them send guest mail; the scheduler shape is the subject, not delivery

    $failed = [];

    foreach (scheduledCommandNames() as $name) {
        // The scheduler has neither. Cleared before EACH command so one cannot leak context to the next.
        app(TenantContext::class)->forget();
        app(OwnerContext::class)->deactivate();

        try {
            $this->artisan($name)->run();
        } catch (Throwable $e) {
            $failed[] = $name.' → '.class_basename($e).': '.$e->getMessage();
        }
    }

    expect($failed)->toBe([],
        'These scheduled commands THROW when run as the scheduler runs them (no tenant/owner context), '
        ."which is silent in production — no error, no failed job, no symptom:\n  ".implode("\n  ", $failed));
});
