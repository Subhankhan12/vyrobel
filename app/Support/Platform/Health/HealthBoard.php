<?php

declare(strict_types=1);

namespace App\Support\Platform\Health;

use App\Support\I18n\LocalDate;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Mail\MailSendLog;
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
            $this->guard('mail', $this->title('mail'), fn () => $this->mail()),
            $this->guard('queue', $this->title('queue'), fn () => $this->queue()),
            $this->guard('scheduler', $this->title('scheduler'), fn () => $this->scheduler()),
            $this->guard('errors', $this->title('errors'), fn () => $this->errors()),
            $this->guard('release', $this->title('release'), fn () => $this->release()),
            $this->guard('config', $this->title('config'), fn () => $this->config()),
            $this->guard('switches', $this->title('switches'), fn () => $this->switches()),
            $this->guard('channex', $this->title('channex'), fn () => $this->channex()),
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
            return new HealthCard('mail', $this->title('mail'), HealthCard::RED, __('php/support/platform/health/health_board.mail_not_delivered'), [
                __('php/support/platform/health/health_board.mail_discarded_line', ['mailer' => $mailer]),
                __('php/support/platform/health/health_board.mail_discarded_reach'),
            ], __('php/support/platform/health/health_board.mail_founder_step'));
        }

        $lines = [__('php/support/platform/health/health_board.mail_active_mailer', ['mailer' => $mailer])];
        if ($mailer === 'postmark' && blank(config('services.postmark.token')) && blank(config('mail.mailers.postmark.token'))) {
            return new HealthCard('mail', $this->title('mail'), HealthCard::RED, __('php/support/platform/health/health_board.mail_token_missing'), array_merge($lines, [
                __('php/support/platform/health/health_board.mail_postmark_no_token'),
            ]));
        }

        // ⚠️ MAIL 02 — SMTP (Hostinger's own mail, the founder's choice). Each check names a KEY, never a value.
        if ($mailer === 'smtp') {
            if (MailSafetyGuard::isLocalSmtpHost((string) config('mail.mailers.smtp.host'))) {
                return new HealthCard('mail', $this->title('mail'), HealthCard::RED, __('php/support/platform/health/health_board.mail_local_host'), array_merge($lines, [
                    __('php/support/platform/health/health_board.mail_local_host_line'),
                ]));
            }
            $missing = array_keys(array_filter([
                'MAIL_USERNAME' => config('mail.mailers.smtp.username'),
                'MAIL_PASSWORD' => config('mail.mailers.smtp.password'),
            ], static fn ($v): bool => blank($v)));
            if ($missing !== []) {
                return new HealthCard('mail', $this->title('mail'), HealthCard::RED, __('php/support/platform/health/health_board.mail_smtp_incomplete'), array_merge($lines, [
                    __('php/support/platform/health/health_board.mail_smtp_not_set', ['keys' => implode(', ', $missing)]),
                ]));
            }
            if (strcasecmp(trim((string) config('mail.from.address')), trim((string) config('mail.mailers.smtp.username'))) !== 0) {
                return new HealthCard('mail', $this->title('mail'), HealthCard::RED, __('php/support/platform/health/health_board.mail_sender_mismatch'), array_merge($lines, [
                    __('php/support/platform/health/health_board.mail_sender_mismatch_line'),
                ]));
            }
            $lines[] = __('php/support/platform/health/health_board.mail_smtp_ok');
        }

        $failed = $this->queuedMailFailures(now()->subDays(7));
        $lines[] = __('php/support/platform/health/health_board.mail_accepted', [
            'day' => MailSendLog::count(MailSendLog::ACCEPTED, now()->subDay()),
            'week' => MailSendLog::count(MailSendLog::ACCEPTED, now()->subDays(7)),
        ]);
        $lines[] = __('php/support/platform/health/health_board.mail_suppressed', ['week' => MailSendLog::count(MailSendLog::SUPPRESSED, now()->subDays(7))]);
        $lines[] = __('php/support/platform/health/health_board.mail_failed_queue', ['failed' => $failed]);
        $lines[] = __('php/support/platform/health/health_board.mail_bounced');

        if ($failed > 0) {
            return new HealthCard('mail', $this->title('mail'), HealthCard::AMBER, trans_choice('php/support/platform/health/health_board.mail_queued_failed', $failed, ['count' => $failed]), $lines);
        }

        return new HealthCard('mail', $this->title('mail'), HealthCard::GREEN, __('php/support/platform/health/health_board.mail_delivering'), $lines);
    }

    /** Queued email jobs that ended in `failed_jobs` since the given moment — read by the job's display name. */
    private function queuedMailFailures(\DateTimeInterface $since): int
    {
        return DB::table('failed_jobs')->where('failed_at', '>=', $since)->pluck('payload')
            ->filter(static function ($payload): bool {
                $name = (string) (json_decode((string) $payload, true)['displayName'] ?? '');

                return preg_match('/SendQueuedMailable|SendQueuedNotifications|Mail\\\\|Email/', $name) === 1;
            })->count();
    }

    /**
     * ⚠️ ADMIN 01 · PHASE 5 — CHANNEX. ⚠️⚠️ NOT LIVE, and its cutover (F-36/A10) is DEFERRED ON ITS TRIGGER: this card
     * says so and this console does not build, start or simulate the cutover. Read from config (SET / fake only —
     * never a value) and from nothing else: a tenant's channel rows are not read outside the one door.
     */
    private function channex(): HealthCard
    {
        $configured = ! (bool) config('services.channex.fake', true) && filled(config('services.channex.api_key'));

        if (! $configured) {
            return new HealthCard('channex', $this->title('channex'), HealthCard::OFF, __('php/support/platform/health/health_board.channex_not_connected'), [
                (bool) config('services.channex.fake', true) ? __('php/support/platform/health/health_board.channex_fake_client') : __('php/support/platform/health/health_board.channex_no_key'),
                __('php/support/platform/health/health_board.channex_deferred'),
            ], label: __('php/support/platform/health/health_board.channex_not_connected'));
        }

        return new HealthCard('channex', $this->title('channex'), HealthCard::AMBER, __('php/support/platform/health/health_board.channex_key_set'), [
            __('php/support/platform/health/health_board.channex_not_called'),
        ]);
    }

    private function queue(): HealthCard
    {
        $waiting = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();
        $oldest = DB::table('jobs')->min('created_at');
        $oldestAge = $oldest === null ? null : max(0, Carbon::now()->getTimestamp() - (int) $oldest);
        $beat = Heartbeat::status(Heartbeat::QUEUE, Heartbeat::QUEUE_STALE_AFTER_SECONDS);

        $lines = [
            $oldestAge === null
                ? __('php/support/platform/health/health_board.queue_jobs_waiting', ['waiting' => $waiting])
                : __('php/support/platform/health/health_board.queue_jobs_waiting_oldest', ['waiting' => $waiting, 'age' => $this->age($oldestAge)]),
            __('php/support/platform/health/health_board.queue_failed_jobs', ['failed' => $failed]),
            $beat['age_seconds'] === null
                ? __('php/support/platform/health/health_board.queue_worker_never')
                : __('php/support/platform/health/health_board.queue_worker_last_ran', ['age' => $this->age($beat['age_seconds'])]),
        ];

        if ($failed > 0 || ! $beat['healthy']) {
            return new HealthCard('queue', $this->title('queue'), HealthCard::RED, $failed > 0 ? trans_choice('php/support/platform/health/health_board.queue_failed_headline', $failed, ['count' => $failed]) : __('php/support/platform/health/health_board.queue_worker_not_seen'), $lines);
        }

        if ($oldestAge !== null && $oldestAge > self::QUEUE_BACKLOG_SECONDS) {
            return new HealthCard('queue', $this->title('queue'), HealthCard::AMBER, __('php/support/platform/health/health_board.queue_backing_up'), $lines);
        }

        return new HealthCard('queue', $this->title('queue'), HealthCard::GREEN, __('php/support/platform/health/health_board.queue_draining'), $lines);
    }

    private function scheduler(): HealthCard
    {
        $beat = Heartbeat::status(Heartbeat::SCHEDULER, Heartbeat::SCHEDULER_STALE_AFTER_SECONDS);
        $armed = filled(config('ops.heartbeat_ping_url'));

        $lines = [
            $beat['age_seconds'] === null
                ? __('php/support/platform/health/health_board.scheduler_never')
                : __('php/support/platform/health/health_board.scheduler_last_beat', ['age' => $this->age($beat['age_seconds'])]),
            $armed ? __('php/support/platform/health/health_board.scheduler_switch_armed') : __('php/support/platform/health/health_board.scheduler_switch_not_armed'),
        ];

        if (! $beat['healthy']) {
            return new HealthCard('scheduler', $this->title('scheduler'), HealthCard::RED, __('php/support/platform/health/health_board.scheduler_not_seen'), $lines,
                __('php/support/platform/health/health_board.scheduler_dead_cron_note'));
        }

        if (! $armed) {
            return new HealthCard('scheduler', $this->title('scheduler'), HealthCard::AMBER, __('php/support/platform/health/health_board.scheduler_unwatched'), $lines,
                __('php/support/platform/health/health_board.scheduler_founder_step'));
        }

        return new HealthCard('scheduler', $this->title('scheduler'), HealthCard::GREEN, __('php/support/platform/health/health_board.scheduler_watched'), $lines);
    }

    private function errors(): HealthCard
    {
        $counts = LogErrorCounter::lastDay();
        $note = __('php/support/platform/health/health_board.errors_note');

        if ($counts['found_files'] === 0) {
            return new HealthCard('errors', $this->title('errors'), HealthCard::GREY, __('php/support/platform/health/health_board.could_not_measure'), [__('php/support/platform/health/health_board.errors_no_log_file')], $note);
        }

        $lines = [__('php/support/platform/health/health_board.errors_counts', ['errors' => $counts['errors_24h'], 'warnings' => $counts['warnings_24h']])];
        foreach (array_slice($counts['by_hour'], -6, null, true) as $hour => $n) {
            // ADMIN 03 · F7 — the log's clock is the app's (UTC); say so, and say which hour.
            $at = Carbon::parse($hour, 'UTC');
            $lines[] = trans_choice('php/support/platform/health/health_board.errors_hour_line', $n, [
                'count' => $n,
                'from' => $at->translatedFormat(LocalDate::pattern('j M, H:i')),
                'to' => $at->copy()->addHour()->format('H:i'),
            ]);
        }

        $status = match (true) {
            $counts['errors_24h'] === 0 => HealthCard::GREEN,
            $counts['errors_24h'] >= self::ERRORS_RED_AT => HealthCard::RED,
            default => HealthCard::AMBER,
        };

        return new HealthCard('errors', $this->title('errors'), $status,
            $counts['errors_24h'] === 0
                ? __('php/support/platform/health/health_board.errors_none')
                : trans_choice('php/support/platform/health/health_board.errors_logged', $counts['errors_24h'], ['count' => $counts['errors_24h']]), $lines, $note);
    }

    private function release(): HealthCard
    {
        $r = ReleaseReader::read();

        if (! $r['in_layout']) {
            return HealthCard::couldNotMeasure('release', $this->title('release'), __('php/support/platform/health/health_board.release_not_in_layout'));
        }

        if ($r['commit'] === null) {
            return new HealthCard('release', $this->title('release'), HealthCard::AMBER, __('php/support/platform/health/health_board.release_commit_unreadable', ['release' => $r['current']]),
                [__('php/support/platform/health/health_board.release_no_file')]);
        }

        $lines = [__('php/support/platform/health/health_board.release_commit', ['commit' => $r['commit']])];
        foreach (array_slice($r['retained'], 0, 3) as $old) {
            $lines[] = $old['commit'] === null
                ? __('php/support/platform/health/health_board.release_rollback_unreadable', ['name' => $old['name']])
                : __('php/support/platform/health/health_board.release_rollback', ['name' => $old['name'], 'commit' => substr($old['commit'], 0, 8)]);
        }
        if (count($r['retained']) > 3) {
            $older = count($r['retained']) - 3;
            $lines[] = trans_choice('php/support/platform/health/health_board.release_older_retained', $older, ['count' => $older]);
        }

        return new HealthCard('release', $this->title('release'), HealthCard::GREEN, "{$r['current']} · ".substr($r['commit'], 0, 8), $lines,
            __('php/support/platform/health/health_board.release_note'));
    }

    private function config(): HealthCard
    {
        $inv = ConfigInventory::inventory();
        $silent = $inv['buckets']['SILENT'];
        $failedCalibration = array_filter(ConfigInventory::calibration($inv), fn (array $c): bool => ! $c[1]);

        if ($failedCalibration !== []) {
            return HealthCard::couldNotMeasure('config', $this->title('config'), __('php/support/platform/health/health_board.config_calibration_failed'));
        }

        $lines = [__('php/support/platform/health/health_board.config_counts', ['set' => $inv['set'], 'empty' => $inv['empty'], 'unclassified' => count($inv['buckets']['UNCLASSIFIED'])])];
        foreach ($silent as [$path]) {
            $lines[] = __('php/support/platform/health/health_board.config_empty_silent', ['path' => $path]);
        }

        return new HealthCard('config', $this->title('config'), $silent === [] ? HealthCard::GREEN : HealthCard::RED,
            $silent === [] ? __('php/support/platform/health/health_board.config_no_gaps') : trans_choice('php/support/platform/health/health_board.config_silent_settings', count($silent), ['count' => count($silent)]), $lines,
            __('php/support/platform/health/health_board.config_names_only'));
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
            ? new HealthCard('switches', $this->title('switches'), HealthCard::GREEN, __('php/support/platform/health/health_board.switches_all_on'), [__('php/support/platform/health/health_board.switches_open')])
            : new HealthCard('switches', $this->title('switches'), HealthCard::AMBER, trans_choice('php/support/platform/health/health_board.switches_off_count', count($off), ['count' => count($off)]),
                array_map(fn (string $l): string => __('php/support/platform/health/health_board.switches_off_line', ['switch' => $l]), $off));
    }

    /** ⚠️ GREY by design: the battery and drift check run from OUTSIDE the host, and store their results nowhere this page can read. */
    private function exposure(): HealthCard
    {
        return HealthCard::couldNotMeasure('exposure', $this->title('exposure'), __('php/support/platform/health/health_board.exposure_why'));
    }

    /** A card's title, by its code, in the request's language (the code itself never changes). */
    private function title(string $key): string
    {
        return match ($key) {
            'mail' => __('php/support/platform/health/health_board.title_mail'),
            'queue' => __('php/support/platform/health/health_board.title_queue'),
            'scheduler' => __('php/support/platform/health/health_board.title_scheduler'),
            'errors' => __('php/support/platform/health/health_board.title_errors'),
            'release' => __('php/support/platform/health/health_board.title_release'),
            'config' => __('php/support/platform/health/health_board.title_config'),
            'switches' => __('php/support/platform/health/health_board.title_switches'),
            'channex' => __('php/support/platform/health/health_board.title_channex'),
            'exposure' => __('php/support/platform/health/health_board.title_exposure'),
            default => $key,
        };
    }

    private function age(int $seconds): string
    {
        return match (true) {
            $seconds < 90 => __('php/support/platform/health/health_board.age_seconds', ['seconds' => $seconds]),
            $seconds < 5400 => __('php/support/platform/health/health_board.age_minutes', ['minutes' => intdiv($seconds, 60)]),
            $seconds < 172800 => __('php/support/platform/health/health_board.age_hours', ['hours' => intdiv($seconds, 3600)]),
            default => trans_choice('php/support/platform/health/health_board.age_days', intdiv($seconds, 86400), ['count' => intdiv($seconds, 86400)]),
        };
    }
}
