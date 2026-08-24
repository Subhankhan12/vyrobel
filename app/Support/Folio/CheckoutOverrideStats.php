<?php

declare(strict_types=1);

namespace App\Support\Folio;

use App\Models\AuditEvent;
use Illuminate\Support\Carbon;

/**
 * ⚠️⚠️ "HOW OFTEN WAS THE CHECKOUT OVERRIDDEN, AND WHY?" — the question that decides whether the block is
 * real or theatre.
 *
 * ⚠️ An override easier than taking payment becomes the default action. The only way to know whether that
 * has happened is to COUNT it — and if the rate runs at forty percent, the block is doing nothing and that
 * should be visible rather than assumed otherwise. ⚠️ This is why the reason is a keyed value: free text
 * would make this query impossible to write.
 *
 * ⚠️ A query, not a dashboard. The question has an answer; presenting it is a separate decision.
 */
final class CheckoutOverrideStats
{
    /**
     * @return array{overrides: int, checkouts: int, rate: float, by_reason: array<string, int>, total_minor: int}
     */
    public static function since(?Carbon $from = null): array
    {
        $overrides = AuditEvent::query()->where('action', 'reservation.checkout_overridden');
        $checkouts = AuditEvent::query()->where('action', 'reservation.checked_out');

        if ($from !== null) {
            $overrides->where('created_at', '>=', $from);
            $checkouts->where('created_at', '>=', $from);
        }

        $rows = $overrides->get();
        $checkoutCount = (int) $checkouts->count();

        $byReason = [];
        $totalMinor = 0;
        foreach ($rows as $row) {
            $after = is_array($row->after) ? $row->after : [];
            $reason = is_string($after['reason'] ?? null) ? $after['reason'] : 'unknown';
            $byReason[$reason] = ($byReason[$reason] ?? 0) + 1;
            $totalMinor += (int) ($after['balance_minor'] ?? 0);
        }

        return [
            'overrides' => $rows->count(),
            'checkouts' => $checkoutCount,
            // ⚠️ The rate is what matters. A high number of overrides against a high number of checkouts is
            //    a different story from the same number against a handful.
            'rate' => $checkoutCount > 0 ? round($rows->count() / $checkoutCount, 4) : 0.0,
            'by_reason' => $byReason,
            'total_minor' => $totalMinor,
        ];
    }
}
