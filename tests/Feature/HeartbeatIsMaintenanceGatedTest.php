<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/**
 * ⚠️⚠️ THE ALARM GOES QUIET DURING MAINTENANCE, AND THAT IS THE RULING — NOT AN OVERSIGHT TO REPAIR.
 *
 * ⚠️⚠️ **READ THIS BEFORE "FIXING" A NOISY DEPLOY.** The obvious repair, the moment someone is paged by their
 * own deploy, is to add `->evenInMaintenanceMode()` to the two heartbeats so they keep beating while the site
 * is parked. **That repair is forbidden, and this test is what forbids it.** See `docs/KNOWN-LIMITS.md` L12i.
 *
 * ── ⚠️ THE MECHANISM, MEASURED RATHER THAN READ ─────────────────────────────────────────────────────────
 *
 * 🔬 `ScheduleRunCommand` skips every event whose `runsInMaintenanceMode()` is false while the application is
 * down. Both heartbeats are therefore silent during a park: the scheduler one because its task is skipped,
 * and the queue one because it is a `Schedule::job` — it is never even DISPATCHED, and the worker would
 * refuse it anyway (🔬 the same `queue:work` command processes nothing while down and drains the identical
 * job the moment maintenance lifts).
 *
 * 🔬 **PROVEN ON THE LIVE HOST BY A REAL down→up→down TOGGLE (2026-09-20), by a POSITIVE ARTEFACT rather than
 * an absent beat** — which is the only kind of proof that distinguishes "the cron is skipping" from "the cron
 * is dead". Both heartbeats were frozen (scheduler 09:53:02, queue 14:12:35); maintenance was lifted at
 * 15:20:04; **160 seconds later they read 15:22:01 and 15:21:03**; maintenance was then restored.
 *
 * ── ⚠️⚠️ WHY NOISY BEATS LYING, WHICH IS THE WHOLE DECISION ──────────────────────────────────────────────
 *
 * ⚠️⚠️ A heartbeat that beats under maintenance would report **healthy** while the scheduler is skipping
 * every daily task the product has: the **14-day GDPR erasure**, the **30-day ID purge**,
 * `billing:reconcile`, `payouts:run` and the reminder emails. That is not a quieter alarm — it is an alarm
 * asserting the opposite of the truth, **and erring toward inaction**.
 *
 * ⚠️ Maintenance is also **not a silent failure**. It is the loudest state this product has: every visitor
 * receives a 503. The alarm is the one signal that still reaches somebody who is NOT looking at the site —
 * which is precisely the person who leaves a park running. Silencing it removes the only channel that
 * survives inattention.
 *
 * ⚠️ **THE ACCEPTED COST, STATED PLAINLY:** a deploy or a park WILL page. 🔬 The livable-ness rests on a
 * number, not a hope — the command half of a real deploy on this host is under 15 seconds, so any
 * Healthchecks grace period ≥ 5 minutes absorbs a routine deploy while still catching a forgotten park.
 *
 * ⚠️ **IF THIS GOES RED, THE SYSTEM IS WORKING — STOP AND ASK.** A ruled behaviour whose pin fails is a
 * decision being reversed, never a test to adjust.
 */
/** @return array<string, Event> the two heartbeat events, keyed by name */
function heartbeatEvents(): array
{
    $found = [];

    foreach (app(Schedule::class)->events() as $event) {
        $name = (string) ($event->description ?? '');

        if (in_array($name, ['ops-scheduler-heartbeat', 'ops-queue-heartbeat'], true)) {
            $found[$name] = $event;
        }
    }

    return $found;
}

// ── 1. ⚠️ THE FIXTURE ITSELF MUST BE REAL — a lookup that silently finds nothing would pass every ────
//        assertion below by vacuum. Both events are named and both must be present.
it('⚠️ finds BOTH heartbeat tasks on the schedule — an empty lookup would pass every later check vacuously', function () {
    $events = heartbeatEvents();

    expect(array_keys($events))->toContain('ops-scheduler-heartbeat', 'ops-queue-heartbeat');
    expect(count($events))->toBe(2,
        '⚠️ one of the two heartbeats is no longer on the schedule under its expected name. This test '
        .'cannot pin a ruling about a task it cannot find — fix the lookup or the schedule, never delete '
        .'the assertion.');
});

// ── 2. ⚠️⚠️ THE RULING ITSELF ────────────────────────────────────────────────────────────────────────
it('⚠️⚠️ keeps BOTH heartbeats maintenance-GATED — the alarm may be noisy, it may never lie', function () {
    foreach (heartbeatEvents() as $name => $event) {
        expect($event->runsInMaintenanceMode())->toBeFalse(
            "⚠️⚠️ `{$name}` NOW RUNS IN MAINTENANCE MODE — the alarm has been taught to lie.\n"
            .'⚠️ While the app is parked the scheduler skips EVERY daily task: the 14-day GDPR erasure, the '
            ."30-day ID purge, billing:reconcile, payouts:run and the reminder emails.\n"
            .'⚠️ A heartbeat that keeps beating through that reports "healthy" while the product is doing '
            ."none of its work, and it errs toward inaction.\n"
            .'⚠️ The ruling (docs/KNOWN-LIMITS.md L12i) is that we live with a NOISY alarm and never a lying '
            .'one. If a deploy is paging you, RAISE THE HEALTHCHECKS GRACE PERIOD — the deploy window is '
            .'under 15 seconds of command time, so ≥5 minutes absorbs it. Do NOT ungate the heartbeat.');
    }
});

// ── 3. ⚠️ AND THE DEAD-MAN'S SWITCH MUST STAY A DEAD-MAN'S SWITCH ───────────────────────────────────
//
// ⚠️ The whole design depends on the ping being sent ONLY on success: the scheduler heartbeat deliberately
// THROWS when `failed_jobs` is non-empty so that the ping is suppressed and the external check's grace
// period lapses. A ping moved to "always" would keep reassuring the operator through exactly the failure it
// was built to surface.
it('⚠️ pings only on SUCCESS — a heartbeat that pings regardless is not a dead-man\'s switch', function () {
    $scheduler = heartbeatEvents()['ops-scheduler-heartbeat'] ?? null;

    expect($scheduler)->not->toBeNull();

    $source = file_get_contents(base_path('routes/console.php'));

    expect(str_contains((string) $source, 'pingOnSuccessIf'))->toBeTrue(
        '⚠️⚠️ THE SCHEDULER HEARTBEAT NO LONGER PINGS ON SUCCESS ONLY. The alarm works by the ping STOPPING: '
        .'the task throws when failed_jobs is non-empty, the ping is suppressed, and the external check '
        .'alerts by itself. An unconditional ping (pingOnCompletion / a ping in the callback) would keep '
        .'reassuring the operator through the exact failure this exists to surface.');

    expect(str_contains((string) $source, 'failed_jobs'))->toBeTrue(
        '⚠️ the scheduler heartbeat no longer reads failed_jobs — a dropped job is invisible again (L12c).');
});
