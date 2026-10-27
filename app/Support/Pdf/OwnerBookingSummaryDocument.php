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
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Countries;
use App\Support\Guest\GuestName;
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
 * REAL / DERIVED / OMITTED (honesty rule, invariant #19 — in a document there is no "Soon"). GATE 2 raised the
 * bar to a wireframe-exact match; the items the FIRST cut deferred are now REAL (their backends shipped later):
 *  - REAL     — the operator's registered identity (`billing_company`/`billing_vat_id`/`country` — VAT shown
 *               only when set; the wireframe's city is not modelled), the owner name, the period, `sequence_no`,
 *               every revenue figure (attributed `owner_ledger` room_revenue), `reservations.reference`, the
 *               `source` enum, the nights + dates from `reservation_units`, the GUEST NAME (name-only — #28, via
 *               the shared {@see GuestName}, the same name the owner Bookings page shows), OCCUPANCY (headline +
 *               per-unit — a real owner-scoped read via {@see PerformanceMetrics::forOwnerUnits()}, keyed by
 *               physical unit id so a non-owner unit in the same room type is excluded), and the per-source
 *               channel COMMISSION (Σ real `ota_cost`; €0 → honest "no commission").
 *  - DERIVED  — the Bookings / Nights / Occupancy / Gross KPIs, the per-unit + per-source groupings, the
 *               `BS-YYYY-MM` document reference (period-derived): all folded from the real figures above.
 *  - OMITTED  — **OTA brands**: the design's "Booking.com"/"Airbnb" split still cannot be rendered honestly —
 *               the brand is NOT modelled (`reservations.source` is a 7-value enum; `channels.provider` only has
 *               'channex'). Grouping stays by the REAL source enum. Also omitted: the design's "Showing 8 of 18"
 *               truncation (every row is listed) and the letterhead street/city (not modelled). The statement it
 *               accompanies is cross-referenced by its REAL `sequence_no`, never the design's "OS-YYYY-MM".
 *
 * CURRENCY (invariant #17 — never a silent FX): totals are stated in the statement's `source_currency`,
 * which is the real currency of `gross_minor`; the payout `currency` may differ and converting here would
 * invent a figure. Each booking row is formatted in its OWN ledger row's currency, so a divergence would
 * show as a fact rather than be silently summed away.
 *
 * @phpstan-type NettedBooking array{reservation_id: string, unit_id: string, currency: string, service_date: Carbon, net_minor: int}
 * @phpstan-type BookingRow array{reservation_id: string, unit_id: string, reference: string, guest: string, unit_code: string, unit_name: string|null, source: string|null, source_label: string, nights: int, dates: string|null, sort_key: int, net_minor: int, revenue_display: string}
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

        // The channel commission (ota_cost) this statement claimed, per reservation — the REAL channel cost the
        // owner bore (a direct stay has none). €0 when no channel fee was posted (→ honest "no commission").
        $otaByReservation = self::otaCostByReservation($statement);

        // OCCUPANCY over the statement period, for the owner's EARNING units only — a real owner-scoped read via
        // the shared PerformanceMetrics (keyed by physical unit id, never room-type-wide, so no non-owner leak).
        // Half-open [start, end): the period's last day is included. Occupancy is a separate metric from the
        // revenue reconciliation (Σ still === gross); it never touches the ledger totals.
        $occStart = CarbonImmutable::parse($periodStart);
        $occEnd = CarbonImmutable::parse($statement->period_end->toDateString())->addDay();
        $byUnit = self::byUnit($rows, $totalCurrency, $occStart, $occEnd);
        /** @var list<string> $unitIds */
        $unitIds = array_values(array_unique(array_column($rows, 'unit_id')));
        $headlineOcc = $unitIds === [] ? 0 : PerformanceMetrics::forOwnerUnits($unitIds, $occStart, $occEnd, $totalCurrency)->occupancyPct();

        // Letterhead — the operator's REAL registered identity from the org billing profile. VAT is shown ONLY
        // when set; the street/city (the wireframe's "Faro") is not modelled, so only the country is rendered.
        $org = Organization::query()->whereKey($statement->organization_id)->firstOrNew(['id' => $statement->organization_id]);
        $operator = (string) ($org->name ?? 'Vyrobel');
        $operatorCompany = (string) ($org->billing_company ?? $org->name ?? 'Vyrobel');
        $operatorVat = OperatorLetterhead::vatId($org);
        $operatorCountry = self::countryName(is_string($org->country) ? $org->country : null);

        $ownerName = (string) ($statement->owner->name ?? __('php/support/pdf/owner_booking_summary_document.owner_fallback'));
        $periodLabel = $statement->period_start->translatedFormat('F Y');
        $unitCount = count($byUnit);
        $grossDisplay = MoneyFormatter::display($totalMinor, $totalCurrency);
        $sequenceNo = $statement->sequence_no;

        // Composed presentation strings — built here (not in the Blade) so the PDF view stays logic-free and no
        // top-of-file @php block is needed. Only REAL parts (country / VAT) are appended to the letterhead.
        // The ONE letterhead rule (shared) — this document resolves the ISO country to its full name first,
        // so it passes that resolved form rather than the stored code.
        $addr = OperatorLetterhead::line($org, OperatorLetterhead::ROLE_MANAGING_AGENT, $operatorCountry);

        $reconcile = $sequenceNo
            ? __('php/support/pdf/owner_booking_summary_document.reconcile_with_statement', ['period' => $periodLabel, 'gross' => $grossDisplay, 'number' => $sequenceNo])
            : __('php/support/pdf/owner_booking_summary_document.reconcile_without_statement', ['period' => $periodLabel, 'gross' => $grossDisplay]);

        return [
            'operator' => $operator,
            'operator_company' => $operatorCompany,
            'operator_vat' => $operatorVat,
            'operator_country' => $operatorCountry,
            'letterhead_addr' => $addr,
            'owner_name' => $ownerName,
            'sequence_no' => $sequenceNo,
            // A period-derived DOCUMENT reference (this period's booking summary). The REAL statement it
            // accompanies is cross-referenced by its actual sequence_no below — never a fabricated number.
            'doc_ref' => 'BS-'.$statement->period_start->format('Y-m'),
            'period_label' => $periodLabel,
            'period_range' => $statement->period_start->translatedFormat('j M Y').' – '.$statement->period_end->translatedFormat('j M Y'),
            'subtitle' => trans_choice('php/support/pdf/owner_booking_summary_document.subtitle', $unitCount, ['period' => $periodLabel, 'owner' => $ownerName, 'count' => $unitCount]),
            'reconcile_note' => $reconcile,
            'footer_left' => $operatorVat !== null ? __('php/support/pdf/owner_booking_summary_document.footer_with_vat', ['company' => $operatorCompany, 'vat' => $operatorVat]) : $operatorCompany,
            'currency' => $totalCurrency,
            'unit_count' => $unitCount,

            // KPI band — REAL counts + DERIVED folds + REAL owner-scoped occupancy.
            'bookings_count' => self::countReservations($rows),
            'nights_total' => $totalNights,
            'occupancy_pct' => $headlineOcc,
            'gross_display' => $grossDisplay,

            'by_unit' => $byUnit,
            'by_source' => self::bySource($rows, $totalCurrency, $otaByReservation),
            'bookings' => $rows,

            'total_nights_display' => $totalNights,
            'total_display' => MoneyFormatter::display($totalMinor, $totalCurrency),
            'total_occupancy_pct' => $headlineOcc,
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
        $reservations = Reservation::query()->whereKey($reservationIds)->with('leadGuest')->get()->keyBy('id');
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
                // Guest NAME only (name-only — invariant #28; NO contact ever), via the ONE shared GuestName
                // rule — the same name the owner Bookings page + Booking Detail already show. An anonymized
                // (GDPR-erased) guest renders the neutral "Guest".
                'guest' => GuestName::display($reservation?->leadGuest),
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
            'dates' => $checkIn->translatedFormat('j M').' – '.$checkOut->translatedFormat('j M Y'),
            'sort_key' => $checkIn->getTimestamp(),
        ];
    }

    /**
     * Per-unit fold. Each (reservation, unit) pair is one stay in that unit, so the Bookings column
     * counts stays in that unit; the KPI counts DISTINCT reservations (a booking spanning two owned
     * units is one booking but two unit-stays). Revenue always reconciles to the gross. OCCUPANCY per unit is a
     * REAL owner-scoped read via {@see PerformanceMetrics::forOwnerUnits()} (keyed by THIS physical unit id, so
     * a non-owner unit in the same room type is excluded — no leak); it is independent of the revenue total.
     *
     * @param  list<BookingRow>  $rows
     * @return list<array<string, mixed>>
     */
    private static function byUnit(array $rows, string $currency, CarbonImmutable $occStart, CarbonImmutable $occEnd): array
    {
        /** @var array<string, list<BookingRow>> $groups */
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['unit_id']][] = $row;
        }

        $out = [];
        foreach ($groups as $unitId => $group) {
            $revenue = (int) array_sum(array_column($group, 'net_minor'));

            $out[] = [
                'unit_id' => (string) $unitId,
                'unit_code' => $group[0]['unit_code'],
                'unit_name' => $group[0]['unit_name'],
                'bookings' => count($group),
                'nights' => (int) array_sum(array_column($group, 'nights')),
                'occupancy_pct' => PerformanceMetrics::forOwnerUnits([(string) $unitId], $occStart, $occEnd, $currency)->occupancyPct(),
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
     * not attempted — grouping is by the real source. Bookings counts DISTINCT reservations per source. The
     * COMMISSION note is the REAL channel cost (Σ `ota_cost`) those reservations bore — €0 (a direct stay, or
     * no channel fee posted) reads as an honest "no commission", never an invented figure.
     *
     * @param  list<BookingRow>  $rows
     * @param  array<string, int>  $otaByReservation  reservation_id → absolute channel-cost minor
     * @return list<array<string, mixed>>
     */
    private static function bySource(array $rows, string $currency, array $otaByReservation): array
    {
        // Grouped by the source CODE, never by its (translated) label — two labels that read the same in some
        // language must not merge two sources (I18N rule 6). The label is the group's own, from the ONE map.
        /** @var array<string, list<BookingRow>> $groups */
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['source'] ?? ''][] = $row;
        }

        $out = [];
        foreach ($groups as $group) {
            $label = $group[0]['source_label'];
            $revenue = (int) array_sum(array_column($group, 'net_minor'));

            // Channel commission for this source = Σ ota_cost over its DISTINCT reservations.
            $commission = 0;
            foreach (array_values(array_unique(array_column($group, 'reservation_id'))) as $rid) {
                $commission += $otaByReservation[$rid] ?? 0;
            }

            $out[] = [
                'label' => $label,
                'bookings' => self::countReservations($group),
                'commission_minor' => $commission,
                'commission_note' => $commission > 0
                    ? __('php/support/pdf/owner_booking_summary_document.commission_amount', ['amount' => MoneyFormatter::display($commission, $currency)])
                    : __('php/support/pdf/owner_booking_summary_document.no_commission'),
                'revenue_minor' => $revenue,
                'revenue_display' => MoneyFormatter::display($revenue, $currency),
            ];
        }

        usort($out, fn (array $a, array $b): int => $b['revenue_minor'] <=> $a['revenue_minor']);

        return $out;
    }

    /**
     * The channel commission (`ota_cost`) this statement claimed, netted per reservation as an ABSOLUTE minor
     * (a cost is a debit; we show its magnitude). Currency-agnostic key: a reservation's channel cost is in its
     * own booking currency, matched to the same reservation's revenue elsewhere.
     *
     * @return array<string, int>
     */
    private static function otaCostByReservation(OwnerStatement $statement): array
    {
        /** @var Collection<int, OwnerLedgerEntry> $entries */
        $entries = OwnerLedgerEntry::query()
            ->where('statement_id', $statement->getKey())
            ->where('entry_type', 'ota_cost')
            ->get();

        $out = [];
        foreach ($entries->groupBy(fn (OwnerLedgerEntry $e): string => (string) $e->reservation_id) as $rid => $group) {
            $out[(string) $rid] = abs($group->reduce(fn (int $carry, OwnerLedgerEntry $e): int => $carry + $e->signedMinor(), 0));
        }

        return $out;
    }

    /**
     * ISO-3166 alpha-2 → a display country name for the letterhead (reference data, not fabrication). An
     * unmapped code renders as-is; null/empty → null (the line is omitted). The street/city is not modelled.
     */
    private static function countryName(?string $code): ?string
    {
        return Countries::name($code);   // the ONE country list (shared)
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
            'direct' => __('php/support/pdf/owner_booking_summary_document.source_direct'),
            'walk_in' => __('php/support/pdf/owner_booking_summary_document.source_walk_in'),
            'ota' => __('php/support/pdf/owner_booking_summary_document.source_ota'),
            'corporate' => __('php/support/pdf/owner_booking_summary_document.source_corporate'),
            'group' => __('php/support/pdf/owner_booking_summary_document.source_group'),
            'agent' => __('php/support/pdf/owner_booking_summary_document.source_agent'),
            'manual' => __('php/support/pdf/owner_booking_summary_document.source_manual'),
            default => ucwords(str_replace('_', ' ', $source)),
        };
    }
}
