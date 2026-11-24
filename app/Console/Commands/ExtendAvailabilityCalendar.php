<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Inventory\CalendarHorizon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * VY-07 (audit of 2026-11-24) — keep the bookable calendar a year ahead. Runs daily; every room type of every organisation is published
 * through today + {@see CalendarHorizon::DAYS} via the certified availability recompute (it only adds the missing nights; `sold` and the
 * nights already published are untouched). Before this existed nothing added a day, so the window closed a year after the last
 * inventory change.
 *
 * ⚠️ IT FAILS LOUDLY IF ANYTHING IS STILL SHORT AFTER IT RAN: a non-zero exit and one error line naming how many room types and the
 * worst gap (ids and dates only). A job that "succeeds" over a calendar it did not extend would be the same silence as before.
 */
class ExtendAvailabilityCalendar extends Command
{
    protected $signature = 'availability:extend';

    protected $description = 'Publish every room type\'s availability calendar through today + 365 days (adds the missing nights only).';

    public function handle(CalendarHorizon $horizon): int
    {
        $result = $horizon->extend();

        $this->info(sprintf(
            'Room types checked: %d · extended: %d · nights added: %d · target: %d days ahead.',
            $result['room_types'], $result['extended'], $result['nights'], CalendarHorizon::DAYS,
        ));

        $short = $horizon->shortfalls();
        if ($short === []) {
            return self::SUCCESS;
        }

        $worst = $short[0];
        $message = sprintf(
            'availability:extend — %d room type(s) are STILL short of the %d-day calendar after the run; the worst is %d days short (room type %s, last night %s).',
            count($short), CalendarHorizon::DAYS, $worst['short_by'], $worst['room_type_id'], $worst['last_night'] ?? 'none published',
        );

        Log::error($message);
        $this->error($message);

        return self::FAILURE;
    }
}
