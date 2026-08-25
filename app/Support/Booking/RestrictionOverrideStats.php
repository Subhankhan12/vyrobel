<?php

declare(strict_types=1);

namespace App\Support\Booking;

use App\Models\AuditEvent;
use Illuminate\Support\Carbon;

/**
 * ⚠️⚠️ "HOW MANY BOOKINGS OVERRODE A RESTRICTION, AND WHICH ONES?"
 *
 * ⚠️ THE OVERRIDE RATE IS THE HONEST MEASURE OF ENFORCEMENT. If most bookings override, the RESTRICTIONS are
 * wrong rather than the bookings — and that is only visible if it is counted. ⚠️ It counts by RESTRICTION as
 * well as by reason, because a booking can waive several at once and a per-booking count would hide that.
 */
final class RestrictionOverrideStats
{
    /**
     * @return array{overrides: int, waivers: int, by_reason: array<string, int>, by_restriction: array<string, int>}
     */
    public static function since(?Carbon $from = null): array
    {
        $q = AuditEvent::query()->where('action', 'reservation.restriction_overridden');
        if ($from !== null) {
            $q->where('created_at', '>=', $from);
        }

        $byReason = [];
        $byRestriction = [];
        $waivers = 0;

        foreach ($q->get() as $row) {
            $after = is_array($row->after) ? $row->after : [];
            $reason = is_string($after['reason'] ?? null) ? $after['reason'] : 'unknown';
            $byReason[$reason] = ($byReason[$reason] ?? 0) + 1;

            foreach ((array) ($after['waived'] ?? []) as $w) {
                $name = is_array($w) && is_string($w['restriction'] ?? null) ? $w['restriction'] : 'unknown';
                $byRestriction[$name] = ($byRestriction[$name] ?? 0) + 1;
                $waivers++;
            }
        }

        return [
            'overrides' => array_sum($byReason),
            // ⚠️ MORE waivers than overrides is normal and is the point: one booking can waive several.
            'waivers' => $waivers,
            'by_reason' => $byReason,
            'by_restriction' => $byRestriction,
        ];
    }
}
