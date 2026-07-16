<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Organization;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Services\OwnerStatementService;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the OWNER BOOKING SUMMARY PDF view data — the REAL bookings behind ONE finalized statement's
 * gross room revenue. Pure + read-only: it never touches the owner-accounting services/schema, and it
 * renders only what the certified engine actually computed.
 *
 * ANCHOR + RECONCILIATION IDENTITY
 *   Σ (the room_revenue rows rendered here) === $statement->gross_minor  — and Σ per-unit revenue is the
 *   same total. Because the money strings come from the shared {@see MoneyFormatter}, the document
 *   reconciles to the on-screen statement to the cent (and to the character).
 *
 * HOW THE ROWS ARE SELECTED — mirrors {@see OwnerStatementService::buckets()} EXACTLY:
 *  - Source rows: `owner_ledger` lines stamped with this statement's id (`statement_id`) whose
 *    `entry_type` is `room_revenue`. Stamping happens at finalize, so the set is immutable.
 *  - PRIOR-PERIOD lines are EXCLUDED from the gross tables. A room_revenue line flagged
 *    `prior_period_adjustment`, or dated before `period_start`, is bucketed by the engine into
 *    ADJUSTMENTS — *not* into gross. Including it here would break the reconciliation and misstate what
 *    the engine did. When any exist, the document says so as a fact (real count), naming the statement
 *    line their revenue actually sits on. It never silently drops them.
 *  - REVERSALS are handled by NETTING per (reservation, unit) via signedMinor() — a reversal is a mirror
 *    row with the direction flipped, and re-attribution posts a fresh version alongside a reversal of the
 *    old one, so netting collapses every version + reversal to the live figure. A booking that nets to
 *    ZERO (fully reversed — i.e. cancelled) contributed nothing to this period's gross: it is excluded
 *    from the tables and the KPIs (counting its nights would inflate Nights against zero revenue), and
 *    disclosed as a real count instead. Either way the total still reconciles, since it adds zero.
 *
 * REAL / DERIVED / OMITTED (honesty rule, invariant #19 — in a document there is no "Soon"):
 *  - REAL     — the operator + owner names, the period, `sequence_no`, every revenue figure (attributed
 *               `owner_ledger` room_revenue), `reservations.reference`, the `source` enum, and the nights
 *               + dates read from `reservation_units`.
 *  - DERIVED  — the Bookings / Nights / Gross KPIs, and the per-unit + per-source groupings: all folded
 *               from the real figures above, never estimated.
 *  - OMITTED  — **Occupancy**: the design shows a per-unit 78%/82%, but occupancy is NOT an attributed
 *               value, and the only real occupancy read (OwnerPortalService::occupancy) is ROOM-TYPE-wide
 *               — rendering it on an owner document would leak non-owner units' numbers, exactly what the
 *               My Units precedent forbids. There is no honest owner-scoped occupancy to show, so it
 *               appears nowhere.
 *             — **The Guest column**: owner surfaces show NO guest names (locked precedent — the owner
 *               calendar shows booking COUNTS only). No guest field is loaded or rendered; the footer
 *               states this as a fact.
 *             — **OTA brands**: the design's "Booking.com"/"Airbnb" split cannot be rendered honestly —
 *               the brand is NOT modelled (`reservations.source` is a 7-value enum, and
 *               `channels.provider` only has 'channex'). Grouping is by the REAL source enum only.
 *             — the design's "BS-2025-09"/"OS-2025-09" reference formats (the real reference is
 *               `sequence_no`), its "Seaside Villas Lda"/VAT/postal address, its "Showing 8 of 18"
 *               truncation (every row is listed), and any per-booking commission split the engine did not
 *               compute.
 *
 * CURRENCY (invariant #17 — never a silent FX): totals are stated in the statement's `source_currency`,
 * which is the real currency of `gross_minor`; the payout `currency` may differ and converting here would
 * invent a figure. Each booking row is formatted in its OWN ledger row's currency, so a divergence would
 * show as a fact rather than be silently summed away.
 *
 * @phpstan-type NettedBooking array{reservation_id: string, unit_id: string, currency: string, service_date: Carbon, net_minor: int}
 * @phpstan-type BookingRow array{reservation_id: string, unit_id: string, reference: string, unit_code: string, unit_name: string|null, source: string|null, source_label: string, nights: int, dates: string|null, sort_key: int, net_minor: int, revenue_display: string}
 */
final class OwnerBookingSummaryDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function data(OwnerStatement $statement): array
    {
        $periodStart = $statement->period_start->toDateString();
        $totalCurrency = (string) $statement->source_currency;

        /** @var Collection<int, OwnerLedgerEntry> $entries */
        $entries = OwnerLedgerEntry::query()
            ->where('statement_id', $statement->getKey())
            ->where('entry_type', 'room_revenue')
            ->orderBy('service_date')
            ->get();

        // Mirror the engine's bucketing: prior-period room revenue is ADJUSTMENTS, not gross.
        [$prior, $current] = $entries->partition(
            fn (OwnerLedgerEntry $e): bool => $e->prior_period_adjustment === true
                || $e->service_date->toDateString() < $periodStart
        );

        $netted = self::netPerBooking($current);
        $rows = self::rows(self::whereNetIs($netted, false));

        $totalMinor = (int) array_sum(array_column($rows, 'net_minor'));
        $totalNights = (int) array_sum(array_column($rows, 'nights'));

        return [
            'operator' => (string) (Organization::query()->whereKey($statement->organization_id)->value('name') ?? 'Vyrobel'),
            'owner_name' => (string) ($statement->owner->name ?? 'Owner'),
            'sequence_no' => $statement->sequence_no,
            'period_label' => $statement->period_start->format('F Y'),
            'period_range' => $statement->period_start->format('j M Y').' – '.$statement->period_end->format('j M Y'),
            'currency' => $totalCurrency,

            // KPI band — REAL counts + DERIVED folds. No occupancy: there is no honest owner-scoped read.
            'bookings_count' => self::countReservations($rows),
            'nights_total' => $totalNights,
            'gross_display' => MoneyFormatter::display($totalMinor, $totalCurrency),

            'by_unit' => self::byUnit($rows, $totalCurrency),
            'by_source' => self::bySource($rows, $totalCurrency),
            'bookings' => $rows,

            'total_nights_display' => $totalNights,
            'total_display' => MoneyFormatter::display($totalMinor, $totalCurrency),
            'total_bookings_in_units' => count($rows),

            // Bookings this statement claimed whose room revenue nets to zero and which therefore appear
            // NOWHERE above. A reservation still live on another owned unit is excluded — it IS listed,
            // so calling it reversed would be false.
            'reversed_count' => count(array_diff(
                array_unique(array_column(self::whereNetIs($netted, true), 'reservation_id')),
                array_column($rows, 'reservation_id'),
            )),
            // Bookings with room revenue dated BEFORE this period — the engine reports that revenue within
            // the statement's prior-period adjustments line, never in this gross.
            'prior_count' => self::countReservations(self::whereNetIs(self::netPerBooking($prior), false)),
        ];
    }

    /**
     * Net the signed room-revenue minor per (reservation, unit) — collapsing reversals and
     * re-attribution versions to the live figure. Amounts stay integer minor units throughout.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return list<NettedBooking>
     */
    private static function netPerBooking(Collection $entries): array
    {
        return $entries
            ->groupBy(fn (OwnerLedgerEntry $e): string => $e->reservation_id.'|'.$e->unit_id)
            ->map(function (Collection $group): array {
                /** @var OwnerLedgerEntry $first */
                $first = $group->first();

                return [
                    'reservation_id' => (string) $first->reservation_id,
                    'unit_id' => (string) $first->unit_id,
                    'currency' => (string) $first->currency,
                    'service_date' => $first->service_date,
                    'net_minor' => $group->reduce(
                        fn (int $carry, OwnerLedgerEntry $e): int => $carry + $e->signedMinor(),
                        0
                    ),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * The netted bookings that DID (or, when $zero is true, did NOT) contribute revenue.
     *
     * @param  list<NettedBooking>  $netted
     * @return list<NettedBooking>
     */
    private static function whereNetIs(array $netted, bool $zero): array
    {
        return array_values(array_filter(
            $netted,
            fn (array $b): bool => ($b['net_minor'] === 0) === $zero
        ));
    }

    /**
     * DISTINCT reservations across a set of rows — a booking spanning two owned units is one booking.
     *
     * @param  list<NettedBooking>|list<BookingRow>  $rows
     */
    private static function countReservations(array $rows): int
    {
        return count(array_unique(array_column($rows, 'reservation_id')));
    }

    /**
     * Hydrate each netted booking with its REAL reference / source / unit / nights / dates.
     *
     * @param  list<NettedBooking>  $live
     * @return list<BookingRow>
     */
    private static function rows(array $live): array
    {
        $reservationIds = array_values(array_unique(array_column($live, 'reservation_id')));
        $unitIds = array_values(array_unique(array_column($live, 'unit_id')));

        if ($reservationIds === []) {
            return [];
        }

        // NOTE: `reservations` / `reservation_units` are tenant-scoped but not owner-scoped — they are
        // reached ONLY by ids taken from the owner-scoped ledger rows above, so no other owner's data
        // is reachable through them.
        $reservations = Reservation::query()->whereKey($reservationIds)->get()->keyBy('id');
        $units = Unit::query()->whereKey($unitIds)->get()->keyBy('id');

        $stays = ReservationUnit::query()
            ->whereIn('reservation_id', $reservationIds)
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->groupBy(fn (ReservationUnit $ru): string => $ru->reservation_id.'|'.$ru->unit_id);

        $rows = array_map(function (array $b) use ($reservations, $units, $stays): array {
            /** @var Reservation|null $reservation */
            $reservation = $reservations->get($b['reservation_id']);
            /** @var Unit|null $unit */
            $unit = $units->get($b['unit_id']);

            /** @var Collection<int, ReservationUnit>|null $lines */
            $lines = $stays->get($b['reservation_id'].'|'.$b['unit_id']);
            $stay = self::stay($lines);
            $source = $reservation !== null ? (string) $reservation->source : null;
            $name = $unit !== null ? (string) $unit->name : '';

            return [
                'reservation_id' => $b['reservation_id'],
                'unit_id' => $b['unit_id'],
                // The REAL human booking code. A row whose reservation is unreachable keeps its real
                // revenue and shows an em-dash — never a fabricated reference.
                'reference' => $reservation !== null ? (string) $reservation->reference : '—',
                'unit_code' => $unit !== null ? (string) $unit->code : '—',
                'unit_name' => $name !== '' ? $name : null,
                'source' => $source,
                'source_label' => $source !== null ? self::sourceLabel($source) : '—',
                'nights' => $stay['nights'],
                'dates' => $stay['dates'],
                'sort_key' => $stay['sort_key'] ?? $b['service_date']->getTimestamp(),
                'net_minor' => $b['net_minor'],
                'revenue_display' => MoneyFormatter::display($b['net_minor'], $b['currency']),
            ];
        }, $live);

        usort(
            $rows,
            fn (array $a, array $b): int => [$a['sort_key'], $a['reference']] <=> [$b['sort_key'], $b['reference']]
        );

        return $rows;
    }

    /**
     * The REAL stay for one (reservation, unit) pair, read from `reservation_units` — the same rows the
     * attribution engine measured nights from. Active lines win; a mid-stay move can leave more than one
     * line for a pair, so nights are summed and the dates shown as the real span.
     *
     * @param  Collection<int, ReservationUnit>|null  $lines
     * @return array{nights: int, dates: string|null, sort_key: int|null}
     */
    private static function stay(?Collection $lines): array
    {
        if ($lines === null || $lines->isEmpty()) {
            return ['nights' => 0, 'dates' => null, 'sort_key' => null];
        }

        $active = $lines->where('status', 'active');
        $use = $active->isNotEmpty() ? $active : $lines;

        $nights = $use->reduce(
            fn (int $carry, ReservationUnit $ru): int => $carry
                + (int) CarbonImmutable::parse($ru->check_in)->diffInDays(CarbonImmutable::parse($ru->check_out)),
            0
        );

        /** @var CarbonImmutable $checkIn */
        $checkIn = $use->map(fn (ReservationUnit $ru): CarbonImmutable => CarbonImmutable::parse($ru->check_in))->sort()->first();
        /** @var CarbonImmutable $checkOut */
        $checkOut = $use->map(fn (ReservationUnit $ru): CarbonImmutable => CarbonImmutable::parse($ru->check_out))->sort()->last();

        return [
            'nights' => $nights,
            'dates' => $checkIn->format('j M').' – '.$checkOut->format('j M Y'),
            'sort_key' => $checkIn->getTimestamp(),
        ];
    }

    /**
     * Per-unit fold. Each (reservation, unit) pair is one stay in that unit, so the Bookings column
     * counts stays in that unit; the KPI counts DISTINCT reservations (a booking spanning two owned
     * units is one booking but two unit-stays). Revenue always reconciles to the gross.
     *
     * @param  list<BookingRow>  $rows
     * @return list<array<string, mixed>>
     */
    private static function byUnit(array $rows, string $currency): array
    {
        /** @var array<string, list<BookingRow>> $groups */
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['unit_id']][] = $row;
        }

        $out = [];
        foreach ($groups as $group) {
            $revenue = (int) array_sum(array_column($group, 'net_minor'));

            $out[] = [
                'unit_code' => $group[0]['unit_code'],
                'unit_name' => $group[0]['unit_name'],
                'bookings' => count($group),
                'nights' => (int) array_sum(array_column($group, 'nights')),
                'revenue_minor' => $revenue,
                'revenue_display' => MoneyFormatter::display($revenue, $currency),
            ];
        }

        usort($out, fn (array $a, array $b): int => strcmp((string) $a['unit_code'], (string) $b['unit_code']));

        return $out;
    }

    /**
     * Per-source fold over the REAL `reservations.source` enum. The OTA BRAND IS NOT MODELLED, so a
     * channel-brand split (the design's Booking.com / Airbnb) is impossible to render honestly and is
     * not attempted. Bookings counts DISTINCT reservations per source.
     *
     * @param  list<BookingRow>  $rows
     * @return list<array<string, mixed>>
     */
    private static function bySource(array $rows, string $currency): array
    {
        /** @var array<string, list<BookingRow>> $groups */
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['source_label']][] = $row;
        }

        $out = [];
        foreach ($groups as $label => $group) {
            $revenue = (int) array_sum(array_column($group, 'net_minor'));

            $out[] = [
                'label' => $label,
                'bookings' => self::countReservations($group),
                'revenue_minor' => $revenue,
                'revenue_display' => MoneyFormatter::display($revenue, $currency),
            ];
        }

        usort($out, fn (array $a, array $b): int => $b['revenue_minor'] <=> $a['revenue_minor']);

        return $out;
    }

    /**
     * Booking source → a short human label. Keys are EXACTLY the real `reservations.source` enum
     * (direct/walk_in/ota/corporate/group/agent/manual); an unknown value is title-cased rather than
     * dropped. Mirrors the operator-side FormatsOperatorData::sourceLabel convention, widened for an
     * owner audience ('ota' names the channel rather than the acronym alone).
     */
    private static function sourceLabel(string $source): string
    {
        return match ($source) {
            'direct' => 'Direct',
            'walk_in' => 'Walk-in',
            'ota' => 'OTA / channel',
            'corporate' => 'Corporate',
            'group' => 'Group',
            'agent' => 'Agent',
            'manual' => 'Manual',
            default => ucwords(str_replace('_', ' ', $source)),
        };
    }
}
