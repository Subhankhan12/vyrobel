<?php

declare(strict_types=1);

namespace App\Support\Ops;

use App\Console\Commands\RunBackup;
use Carbon\CarbonImmutable;

/**
 * VY-12 (audit of 2026-11-24) — THE ONE reading of "is there a recent backup", for the health board and for the schedule's own
 * "is it set up" filter.
 *
 * ⚠️ IT READS THE FOLDER, NOT THE JOB. {@see RunBackup} cannot report that it did not run, and a stamp it wrote yesterday says
 *    nothing about today — so the newest archive's own age is the fact. No cache entry, nothing a deploy can clear.
 * ⚠️ NO SECRET IS READ HERE BEYOND "is the passphrase file there and not empty": the passphrase is never returned, logged or shown.
 */
final class BackupStatus
{
    /** A daily job, plus slack for a slow night: older than this and there is no recent backup. */
    public const STALE_AFTER_SECONDS = 26 * 3600;

    /** The folder on the off-server disk the archives are copied into. */
    public const OFFSITE_FOLDER = 'vyrobel-backups';

    /** Written beside the archives after a successful off-server copy: `<ISO time>` — the health board's "copied" line. */
    private const OFFSITE_MARKER = '.offsite-last';

    private const ARCHIVE_GLOB = 'vyrobel-*.tar.gz.enc';

    /** Is the backup set up — a passphrase file that exists, is readable and is not empty? */
    public static function configured(): bool
    {
        return self::passphrase() !== null;
    }

    /**
     * The passphrase, for {@see RunBackup} ONLY (it goes into the script's process environment and nowhere else). NULL when the
     * file is not named, missing, unreadable or blank.
     */
    public static function passphrase(): ?string
    {
        $file = config('ops.backup.passphrase_file');
        if (! is_string($file) || $file === '' || ! is_file($file) || ! is_readable($file)) {
            return null;
        }

        $value = trim((string) file_get_contents($file));

        return $value === '' ? null : $value;
    }

    public static function dest(): string
    {
        $dest = config('ops.backup.dest');

        return is_string($dest) && $dest !== '' ? rtrim($dest, '/') : storage_path('backups');
    }

    public static function offsiteDisk(): ?string
    {
        $disk = config('ops.backup.offsite_disk');

        return is_string($disk) && $disk !== '' ? $disk : null;
    }

    public static function markOffsiteCopied(): void
    {
        @file_put_contents(self::dest().'/'.self::OFFSITE_MARKER, CarbonImmutable::now()->toIso8601String());
    }

    /**
     * @return array{configured: bool, last_name: ?string, last_at: ?string, age_seconds: ?int, bytes: ?int, healthy: bool,
     *               offsite_configured: bool, offsite_last_at: ?string, offsite_age_seconds: ?int, offsite_healthy: bool}
     */
    public static function report(): array
    {
        $now = CarbonImmutable::now()->getTimestamp();

        $newest = null;
        $newestAt = 0;
        foreach (glob(self::dest().'/'.self::ARCHIVE_GLOB) ?: [] as $file) {
            $at = (int) filemtime($file);
            if ($at >= $newestAt && (int) filesize($file) > 0) {
                $newest = $file;
                $newestAt = $at;
            }
        }
        $age = $newest === null ? null : max(0, $now - $newestAt);

        $offsiteAt = null;
        $marker = self::dest().'/'.self::OFFSITE_MARKER;
        if (is_file($marker)) {
            $raw = trim((string) file_get_contents($marker));
            try {
                $offsiteAt = $raw !== '' ? CarbonImmutable::parse($raw) : null;
            } catch (\Throwable) {
                $offsiteAt = null;
            }
        }
        $offsiteAge = $offsiteAt === null ? null : max(0, $now - $offsiteAt->getTimestamp());

        return [
            'configured' => self::configured(),
            'last_name' => $newest === null ? null : basename($newest),
            'last_at' => $newest === null ? null : CarbonImmutable::createFromTimestamp($newestAt)->toIso8601String(),
            'age_seconds' => $age,
            'bytes' => $newest === null ? null : (int) filesize($newest),
            'healthy' => $age !== null && $age <= self::STALE_AFTER_SECONDS,
            'offsite_configured' => self::offsiteDisk() !== null,
            'offsite_last_at' => $offsiteAt?->toIso8601String(),
            'offsite_age_seconds' => $offsiteAge,
            'offsite_healthy' => self::offsiteDisk() !== null && $offsiteAge !== null && $offsiteAge <= self::STALE_AFTER_SECONDS,
        ];
    }
}
