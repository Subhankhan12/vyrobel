<?php

declare(strict_types=1);

namespace App\Support\Platform\Health;

use App\Support\Ops\ConfigInventory;
use App\Support\Ops\Heartbeat;
use App\Support\Platform\MailDelivery;
use App\Support\Platform\PlatformSwitches;
use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * ADMIN 01 · PHASE 1 — THE "NOTHING IS SILENT" BOARD: the first screen after sign-in.
 *
 * ⚠️⚠️ CALIBRATED ON KNOWN TRUTHS, NOT ON HOPE. On the day it shipped, production had mail going to `log`
 * (discarded), no external heartbeat URL, and a live release readable from `.release`. A board that showed all
 * green that day would have been broken — because that day was not all green. The tests pin exactly that.
 *
 * ⚠️ EVERY CARD IS COMPUTED IN ISOLATION: one card's check throwing turns THAT card grey ("could not measure")
 *    and leaves the others standing. Grey is never green.
 * ⚠️ NO CARD SHOWS A SECRET OR A CONFIG VALUE — mailer NAMES and key PATHS only.
 * ⚠️ READ-ONLY: this computes; nothing on the board can deploy, roll back or park the site.
 */
final class HealthBoard
{
    /** A job waiting longer than this is "backed up". The worker cron runs every minute. */
    private const QUEUE_BACKLOG_SECONDS = 300;

    /** More server errors than this in 24h is red rather than amber. A number, stated, not a feeling. */
    private const ERRORS_RED_AT = 20;

    /** @return list<HealthCard> */
    public function cards(): array
    {
        return [
            $this->guard('mail', 'Mail delivery', fn () => $this->mail()),
            $this->guard('queue', 'Queue', fn () => $this->queue()),
            $this->guard('scheduler', 'Scheduler & heartbeat', fn () => $this->scheduler()),
            $this->guard('errors', 'Server errors (24h)', fn () => $this->errors()),
            $this->guard('release', 'Live release', fn () => $this->release()),
            $this->guard('config', 'Silent configuration', fn () => $this->config()),
            $this->guard('switches', 'Kill switches', fn () => $this->switches()),
            $this->exposure(),
        ];
    }

    /** @param Closure(): HealthCard $check */
    private function guard(string $key, string $title, Closure $check): HealthCard
    {
        try {
            return $check();
        } catch (Throwable $e) {
            return HealthCard::fromFailure($key, $title, $e);
        }
    }

    private function mail(): HealthCard
    {
        $mailer = MailDelivery::mailer();

        // ⚠️⚠️ `log` and `array` do not deliver. This is RED, in words, whatever else is true.
        if (MailDelivery::discards()) {
            return new HealthCard('mail', 'Mail delivery', HealthCard::RED, 'MAIL IS NOT BEING DELIVERED', [
                "Active mailer: {$mailer} — every email is discarded, not sent.",
                'Invites, password resets, verification and receipts reach nobody until a real mailer is configured.',
            ], 'Founder step: the Postmark DNS records and token (docs/EMAIL-PRODUCTION.md).');
        }

        $lines = ["Active mailer: {$mailer}."];
        if ($mailer === 'postmark' && blank(config('services.postmark.token')) && blank(config('mail.mailers.postmark.token'))) {
            return new HealthCard('mail', 'Mail delivery', HealthCard::RED, 'Mailer set, token missing', array_merge($lines, [
                'Postmark is selected but has no token — sends will fail.',
            ]));
        }

        return new HealthCard('mail', 'Mail delivery', HealthCard::GREEN, 'A delivering mailer is configured', array_merge($lines, [
            'Sent / bounced / failed: not measured yet — the provider\'s delivery events are not recorded here.',
        ]));
    }

    private function queue(): HealthCard
    {
        $waiting = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();
        $oldest = DB::table('jobs')->min('created_at');
        $oldestAge = $oldest === null ? null : max(0, Carbon::now()->getTimestamp() - (int) $oldest);
        $beat = Heartbeat::status(Heartbeat::QUEUE, Heartbeat::QUEUE_STALE_AFTER_SECONDS);

        $lines = [
            "Jobs waiting: {$waiting}".($oldestAge === null ? '' : ' · oldest '.$this->age($oldestAge)),
            "Failed jobs: {$failed}",
            'Worker last ran: '.($beat['age_seconds'] === null ? 'never recorded' : $this->age($beat['age_seconds']).' ago'),
        ];

        if ($failed > 0 || ! $beat['healthy']) {
            return new HealthCard('queue', 'Queue', HealthCard::RED, $failed > 0 ? "{$failed} failed job(s)" : 'Worker not seen', $lines);
        }

        if ($oldestAge !== null && $oldestAge > self::QUEUE_BACKLOG_SECONDS) {
            return new HealthCard('queue', 'Queue', HealthCard::AMBER, 'Jobs are backing up', $lines);
        }

        return new HealthCard('queue', 'Queue', HealthCard::GREEN, 'Draining', $lines);
    }

    private function scheduler(): HealthCard
    {
        $beat = Heartbeat::status(Heartbeat::SCHEDULER, Heartbeat::SCHEDULER_STALE_AFTER_SECONDS);
        $armed = filled(config('ops.heartbeat_ping_url'));

        $lines = [
            'Scheduler last beat: '.($beat['age_seconds'] === null ? 'never recorded' : $this->age($beat['age_seconds']).' ago'),
            'External dead-man\'s switch: '.($armed ? 'armed' : 'NOT ARMED — no ping URL is configured'),
        ];

        if (! $beat['healthy']) {
            return new HealthCard('scheduler', 'Scheduler & heartbeat', HealthCard::RED, 'Scheduler not seen', $lines,
                'A dead cron stops the GDPR deletion and the retention prune without any error.');
        }

        if (! $armed) {
            return new HealthCard('scheduler', 'Scheduler & heartbeat', HealthCard::AMBER, 'Running — but nobody is told if it stops', $lines,
                'Founder step: an external monitor URL in OPS_HEARTBEAT_PING_URL, then a config rebuild.');
        }

        return new HealthCard('scheduler', 'Scheduler & heartbeat', HealthCard::GREEN, 'Running, and watched', $lines);
    }

    private function errors(): HealthCard
    {
        $counts = LogErrorCounter::lastDay();
        $note = 'Origin errors only — the CDN in front of the site is not visible from here. Logged at warning+; the route group is not recorded in the log, so errors cannot be split by route.';

        if ($counts['found_files'] === 0) {
            return new HealthCard('errors', 'Server errors (24h)', HealthCard::GREY, 'Could not measure', ['No application log file was found.'], $note);
        }

        $lines = ["Errors: {$counts['errors_24h']} · warnings: {$counts['warnings_24h']}"];
        foreach (array_slice($counts['by_hour'], -6, null, true) as $hour => $n) {
            $lines[] = "{$hour} — {$n}";
        }

        $status = match (true) {
            $counts['errors_24h'] === 0 => HealthCard::GREEN,
            $counts['errors_24h'] >= self::ERRORS_RED_AT => HealthCard::RED,
            default => HealthCard::AMBER,
        };

        return new HealthCard('errors', 'Server errors (24h)', $status,
            $counts['errors_24h'] === 0 ? 'No server errors logged' : "{$counts['errors_24h']} server error(s) logged", $lines, $note);
    }

    private function release(): HealthCard
    {
        $r = ReleaseReader::read();

        if (! $r['in_layout']) {
            return HealthCard::couldNotMeasure('release', 'Live release', 'Not running from a release directory (this is a development checkout).');
        }

        if ($r['commit'] === null) {
            return new HealthCard('release', 'Live release', HealthCard::AMBER, "{$r['current']} — commit unreadable",
                ['The live release has no readable .release file.']);
        }

        $lines = ["Commit {$r['commit']}"];
        foreach (array_slice($r['retained'], 0, 3) as $old) {
            $lines[] = "Rollback candidate {$old['name']}".($old['commit'] === null ? ' — commit unreadable' : ' · '.substr($old['commit'], 0, 8));
        }
        if (count($r['retained']) > 3) {
            $lines[] = '… and '.(count($r['retained']) - 3).' older release(s) retained';
        }

        return new HealthCard('release', 'Live release', HealthCard::GREEN, "{$r['current']} · ".substr($r['commit'], 0, 8), $lines,
            'Read from the release layout on disk. There is no deploy or rollback button, by design.');
    }

    private function config(): HealthCard
    {
        $inv = ConfigInventory::inventory();
        $silent = $inv['buckets']['SILENT'];
        $failedCalibration = array_filter(ConfigInventory::calibration($inv), fn (array $c): bool => ! $c[1]);

        if ($failedCalibration !== []) {
            return HealthCard::couldNotMeasure('config', 'Silent configuration', 'The inventory failed its own calibration, so its answer is not trusted.');
        }

        $lines = ["{$inv['set']} set · {$inv['empty']} empty · ".count($inv['buckets']['UNCLASSIFIED']).' empty and unclassified'];
        foreach ($silent as [$path]) {
            $lines[] = "EMPTY and SILENT: {$path}";
        }

        return new HealthCard('config', 'Silent configuration', $silent === [] ? HealthCard::GREEN : HealthCard::RED,
            $silent === [] ? 'No silent gaps' : count($silent).' setting(s) empty that fail silently', $lines,
            'Names only — no value is ever shown.');
    }

    private function switches(): HealthCard
    {
        $off = [];
        foreach (PlatformSwitches::states() as $key => $state) {
            if (! $state['on']) {
                $off[] = PlatformSwitches::catalogue()[$key]['label'];
            }
        }

        return $off === []
            ? new HealthCard('switches', 'Kill switches', HealthCard::GREEN, 'All on — normal service', ['Sign-up and social sign-in are open.'])
            : new HealthCard('switches', 'Kill switches', HealthCard::AMBER, count($off).' switched off', array_map(fn (string $l): string => "OFF: {$l}", $off));
    }

    /** ⚠️ GREY by design: the battery and drift check run from OUTSIDE the host, and store their results nowhere this page can read. */
    private function exposure(): HealthCard
    {
        return HealthCard::couldNotMeasure('exposure', 'Exposure battery & drift check',
            'Both run from outside the host (ops/exposure-battery.sh, ops/htaccess-drift-check.sh) and their results are not stored anywhere this page can read. Running them from a web request would be the wrong place to run them.');
    }

    private function age(int $seconds): string
    {
        return match (true) {
            $seconds < 90 => "{$seconds}s",
            $seconds < 5400 => intdiv($seconds, 60).' min',
            $seconds < 172800 => intdiv($seconds, 3600).' h',
            default => intdiv($seconds, 86400).' days',
        };
    }
}
