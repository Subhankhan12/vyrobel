<?php

declare(strict_types=1);

namespace App\Support\Platform\Health;

use Illuminate\Support\Carbon;

/**
 * Counts SERVER ERRORS from the application's own daily log files.
 *
 * ⚠️⚠️ SAID ON THE CARD, BECAUSE IT IS THE WHOLE TRUTH ABOUT THIS NUMBER:
 *   · ORIGIN ERRORS ONLY. The CDN in front of production answers some requests itself; an error there never
 *     reaches PHP and never reaches this log. The CDN is not visible from here.
 *   · ONLY WHAT IS LOGGED. Production logs at `warning` and above; an error swallowed before it is logged, or a
 *     fatal that kills PHP before the logger runs, is not counted.
 *   · NO ROUTE GROUP. The log line does not record which route failed, so "by route group" cannot be measured
 *     from this source. Rather than guess one from the message, the card says it cannot.
 *
 * Reads at most the last 2 MB of each of the two newest daily files — a bounded read on a page load.
 */
final class LogErrorCounter
{
    private const TAIL_BYTES = 2_000_000;

    /** Levels counted as an error. WARNING is counted separately (the refusal log writes there by design). */
    private const ERROR_LEVELS = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    /**
     * @return array{found_files: int, errors_24h: int, warnings_24h: int, by_hour: array<string, int>}
     */
    public static function lastDay(?string $logDir = null, ?Carbon $now = null): array
    {
        $dir = $logDir ?? storage_path('logs');
        $now ??= Carbon::now();
        $since = $now->copy()->subDay();

        $files = glob($dir.'/laravel*.log') ?: [];
        rsort($files);          // laravel-YYYY-MM-DD.log sorts by date; newest first
        $files = array_slice($files, 0, 2);

        $errors = $warnings = 0;
        $byHour = [];

        foreach ($files as $file) {
            foreach (self::tail($file) as $line) {
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\]\s+\S+\.([A-Z]+):/', $line, $m) !== 1) {
                    continue;
                }
                $at = Carbon::parse($m[1]);
                if ($at->lt($since) || $at->gt($now)) {
                    continue;
                }
                if (in_array($m[2], self::ERROR_LEVELS, true)) {
                    $errors++;
                    $hour = $at->format('Y-m-d H:00');
                    $byHour[$hour] = ($byHour[$hour] ?? 0) + 1;
                } elseif ($m[2] === 'WARNING') {
                    $warnings++;
                }
            }
        }

        ksort($byHour);

        return ['found_files' => count($files), 'errors_24h' => $errors, 'warnings_24h' => $warnings, 'by_hour' => $byHour];
    }

    /** @return list<string> */
    private static function tail(string $file): array
    {
        $size = @filesize($file);
        if ($size === false || $size === 0) {
            return [];
        }

        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            return [];
        }

        $offset = max(0, $size - self::TAIL_BYTES);
        fseek($handle, $offset);
        $chunk = (string) stream_get_contents($handle);
        fclose($handle);

        $lines = explode("\n", $chunk);
        if ($offset > 0) {
            array_shift($lines);   // the first line of a tail is almost certainly cut in half
        }

        return $lines;
    }
}
