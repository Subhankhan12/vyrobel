<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The ONE maintenance-SLA computation (#89) — a REAL breach measurement, never a fabricated indicator. Each
 * priority is measured against the org's configured resolution target ({@see MaintenanceSetting} `sla_*_hours`);
 * a ticket that is still ACTIVE (open/in_progress) past `opened_at + target` is genuinely breached. Read by the
 * board's SLA bar and the ticket detail, so the two can't disagree. A resolved/cancelled ticket never breaches
 * (the work is done); a ticket without a configured target for its priority is honestly "no target".
 */
final class MaintenanceSla
{
    /** priority → the settings column holding its target hours. */
    private const HOURS_COLUMN = [
        'urgent' => 'sla_urgent_hours',
        'high' => 'sla_high_hours',
        'normal' => 'sla_normal_hours',
        'low' => 'sla_low_hours',
    ];

    /** The configured target hours for a priority, or null when unset. */
    public static function targetHours(MaintenanceSetting $setting, string $priority): ?int
    {
        $column = self::HOURS_COLUMN[$priority] ?? null;
        if ($column === null) {
            return null;
        }
        $hours = (int) $setting->{$column};

        return $hours > 0 ? $hours : null;
    }

    /**
     * Evaluate a ticket against its SLA target.
     *
     * @return array{
     *   has_target: bool, target_hours: ?int, deadline: ?CarbonImmutable,
     *   breached: bool, due_soon: bool, over_hours: int, remaining_hours: int, state: string
     * }
     */
    public static function evaluate(MaintenanceTicket $ticket, MaintenanceSetting $setting, ?CarbonInterface $now = null): array
    {
        $now = $now !== null ? CarbonImmutable::parse($now) : CarbonImmutable::now();
        $target = self::targetHours($setting, (string) $ticket->priority);

        $base = [
            'has_target' => $target !== null,
            'target_hours' => $target,
            'deadline' => null,
            'breached' => false,
            'due_soon' => false,
            'over_hours' => 0,
            'remaining_hours' => 0,
            'state' => 'none',
        ];

        // Only an ACTIVE ticket is on the clock; a closed one is done, and a missing target can't be measured.
        if ($target === null || ! $ticket->isActive()) {
            $base['state'] = $ticket->isActive() ? 'no_target' : 'closed';

            return $base;
        }

        $deadline = CarbonImmutable::parse($ticket->opened_at)->addHours($target);
        $base['deadline'] = $deadline;

        if ($now->greaterThan($deadline)) {
            $base['breached'] = true;
            $base['over_hours'] = (int) $deadline->diffInHours($now);
            $base['state'] = 'breached';

            return $base;
        }

        $remaining = (int) $now->diffInHours($deadline);
        $base['remaining_hours'] = $remaining;
        // "Due soon" = inside the last quarter of the window (a real derived warning band).
        $base['due_soon'] = $remaining <= max(1, (int) floor($target / 4));
        $base['state'] = $base['due_soon'] ? 'due_soon' : 'on_track';

        return $base;
    }
}
