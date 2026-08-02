<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\Reservation;
use Carbon\CarbonImmutable;

/**
 * The ONE definition of the operator report's by-channel + by-month revenue breakdown (#104 GATE 2), so the
 * Reports SCREEN and its CSV EXPORT compute the exact same figures — they cannot drift. Everything is raw integer
 * minor units + the real `source` string; the caller formats (money symbol, channel label). Revenue is the
 * report's OWN folio basis (room + ancillary), so both breakdowns reconcile to the report's gross (#17).
 */
final class OperatorRevenueBreakdown
{
    /**
     * Room + ancillary revenue per reservation, from the folio basis.
     *
     * @param  list<string>  $reservationIds
     * @return array<string, int> reservation id → revenue minor
     */
    public static function perReservation(array $reservationIds, string $currency): array
    {
        if ($reservationIds === []) {
            return [];
        }

        $folioToRes = Folio::query()->whereIn('reservation_id', $reservationIds)->pluck('reservation_id', 'id');
        if ($folioToRes->isEmpty()) {
            return [];
        }

        $revByFolio = FolioItem::query()
            ->whereIn('folio_id', $folioToRes->keys()->all())
            ->whereIn('type', ['room', 'add_on', 'product', 'fee', 'manual', 'discount'])
            ->where('currency', $currency)
            ->selectRaw('folio_id, SUM(amount_minor) AS total')
            ->groupBy('folio_id')
            ->pluck('total', 'folio_id');

        $out = [];
        foreach ($revByFolio as $folioId => $total) {
            $resId = (string) $folioToRes[$folioId];
            $out[$resId] = ($out[$resId] ?? 0) + (int) $total;
        }

        return $out;
    }

    /**
     * Revenue grouped by the reservation's real `source`, richest first, with each source's share of the total.
     *
     * @param  list<string>  $reservationIds
     * @return list<array{source: string, bookings: int, revenue_minor: int, share: int}>
     */
    /** THE operator booking-source label (direct/walk-in/corporate/group/agent/OTA) — one map, no fabricated brand (#39). */
    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            'direct' => 'Direct',
            'walk_in' => 'Walk-in',
            'corporate' => 'Corporate',
            'group' => 'Group',
            'agent' => 'Agent',
            'ota' => 'OTA',
            default => ucwords(str_replace('_', ' ', $source)),
        };
    }

    public static function byChannel(array $reservationIds, string $currency): array
    {
        if ($reservationIds === []) {
            return [];
        }

        $revByRes = self::perReservation($reservationIds, $currency);
        $agg = [];
        foreach (Reservation::query()->whereIn('id', $reservationIds)->get(['id', 'source']) as $r) {
            $src = (string) $r->source;
            $agg[$src] ??= ['bookings' => 0, 'revenue_minor' => 0];
            $agg[$src]['bookings']++;
            $agg[$src]['revenue_minor'] += $revByRes[(string) $r->id] ?? 0;
        }

        $total = array_sum(array_column($agg, 'revenue_minor'));
        $rows = [];
        foreach ($agg as $src => $a) {
            $rows[] = [
                'source' => $src,
                'bookings' => $a['bookings'],
                'revenue_minor' => $a['revenue_minor'],
                'share' => $total > 0 ? (int) round($a['revenue_minor'] * 100 / $total) : 0,
            ];
        }
        usort($rows, fn (array $x, array $y): int => $y['revenue_minor'] <=> $x['revenue_minor']);

        return $rows;
    }

    /**
     * One revenue bucket per calendar month across [start, end); an empty month is an honest zero, never dropped.
     *
     * @param  list<string>  $reservationIds
     * @return list<array{month: string, label: string, revenue_minor: int}>
     */
    public static function byMonth(array $reservationIds, string $currency, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $buckets = [];
        $cursor = $start->startOfMonth();
        $guard = 0;
        while ($cursor->lessThan($end) && $guard < 13) {
            $buckets[$cursor->format('Y-m')] = ['month' => $cursor->format('Y-m'), 'label' => $cursor->format('M'), 'revenue_minor' => 0];
            $cursor = $cursor->addMonth();
            $guard++;
        }

        if ($reservationIds !== []) {
            $revByRes = self::perReservation($reservationIds, $currency);
            foreach (Reservation::query()->whereIn('id', $reservationIds)->get(['id', 'check_in']) as $r) {
                $key = CarbonImmutable::parse($r->check_in)->format('Y-m');
                if (isset($buckets[$key])) {
                    $buckets[$key]['revenue_minor'] += $revByRes[(string) $r->id] ?? 0;
                }
            }
        }

        return array_values($buckets);
    }
}
