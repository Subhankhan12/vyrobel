<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Livewire\Owner\OwnerBookings;
use App\Models\Guest;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\I18n\LocalDate;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The owner Overview's "Upcoming stays" feed — the owner's real ARRIVALS in the next N days across their OWN
 * units. Owner-scoped THROUGH the units exactly like {@see OwnerBookings} (reservations
 * carry no owner_id): the set is derived from `reservation_units` on the owner's OWN unit ids, and those ids
 * come only from the owner-scoped {@see OwnerPortalService::units()} — never a request. An empty
 * id set → no stays (fail-closed).
 *
 * The rows reuse the SAME rules as OwnerBookings so the two surfaces cannot drift:
 *   • guest NAME only (invariant #28 — never email/phone/contact); an anonymized guest → null.
 *   • "Net to you" per stay = Σ signed ledger through the ONE shared {@see OwnerLedgerAttribution}; a future
 *     stay with no ledger rows yet shows "Not yet attributed", never a fake €0 (#39). #17 integer money.
 *   • the REAL reservation `status` enum (confirmed → "Confirmed", enquiry/tentative → "Pending") + the REAL
 *     `source` enum — nothing invented.
 *
 * Rendered server-side ONCE (where the owner scope is active); the Overview then filters/sorts it client-side
 * with Alpine — a Livewire wire-action would bypass {@see SetOwnerScopeForPortal} and leak
 * (the same discipline OwnerBookings documents). A pure read: writes nothing.
 */
final class OwnerUpcomingStays
{
    /** Future arrivals with these statuses are shown; terminal + already-arrived stays are excluded. */
    private const UPCOMING_STATUSES = ['confirmed', 'tentative', 'enquiry'];

    /** A source's dot colour, keyed to the real `reservations.source` enum. */
    private const SOURCE_DOTS = [
        'direct' => '#C2410C',
        'corporate' => '#3B82A6',
        'group' => '#8B5CF6',
        'walk_in' => '#1F7A5A',
        'ota' => '#E0921E',
    ];

    /**
     * @param  list<string>  $unitIds  the owner's OWN unit ids (owner-scoped upstream); [] → empty result
     * @return array{rows: list<array<string, mixed>>, sources: list<array{key: string, label: string, count: int, dot: string}>, statuses: list<array{key: string, label: string, count: int}>, net_total_display: string, count: int}
     */
    public static function forOwnerUnits(array $unitIds, string $currency, int $withinDays = 30): array
    {
        $empty = ['rows' => [], 'sources' => [], 'statuses' => [], 'net_total_display' => MoneyFormatter::display(0, $currency), 'count' => 0];
        if ($unitIds === []) {
            return $empty;
        }

        $today = CarbonImmutable::today();
        $horizon = $today->addDays($withinDays);

        // Owned-unit lines → the reservations that touch this owner's units (+ a unit label per reservation).
        $lines = ReservationUnit::query()
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->get(['reservation_id', 'unit_id', 'check_in']);

        /** @var array<string, Unit> $unitById */
        $unitById = Unit::query()->whereIn('id', $unitIds)->get()->keyBy(fn (Unit $u): string => (string) $u->id)->all();

        // ⚠️ DEC-p — a stay that begins before the owner takes the unit over is not theirs (`Unit::ownerIdForStayStarting()`, the rule the earnings use). A unit
        //    with no `owner_since` — every unit before DEC-p — keeps every stay, exactly as before.
        $lines = $lines->filter(function (ReservationUnit $l) use ($unitById): bool {
            $unit = $unitById[(string) $l->unit_id] ?? null;

            return $unit === null || $unit->owner_since === null || $unit->ownerIdForStayStarting($l->check_in) !== null;
        })->values();

        /** @var list<string> $reservationIds */
        $reservationIds = $lines->pluck('reservation_id')->map(fn ($id): string => (string) $id)->unique()->values()->all();
        if ($reservationIds === []) {
            return $empty;
        }
        $unitLabelByReservation = self::unitLabels($lines, $unitById);

        // Only future arrivals within the horizon, non-terminal. (whereDate on the DB so a large book scales.)
        $reservations = Reservation::query()
            ->whereIn('id', $reservationIds)
            ->whereIn('status', self::UPCOMING_STATUSES)
            ->whereDate('check_in', '>', $today->toDateString())
            ->whereDate('check_in', '<=', $horizon->toDateString())
            ->with('leadGuest')
            ->orderBy('check_in')
            ->get();

        if ($reservations->isEmpty()) {
            return $empty;
        }

        // Per-stay owner net, batched, through the ONE shared rule (so it reconciles with OwnerBookings).
        $ledgerByReservation = OwnerLedgerEntry::query()
            ->whereIn('reservation_id', $reservations->pluck('id')->map(fn ($id): string => (string) $id)->all())
            ->get()
            ->groupBy(fn (OwnerLedgerEntry $e): string => (string) $e->reservation_id);

        $rows = [];
        $allLedger = collect();
        $sourceCounts = [];
        $statusCounts = [];

        foreach ($reservations as $r) {
            $resId = (string) $r->id;
            $checkIn = Carbon::parse($r->check_in);
            $checkOut = Carbon::parse($r->check_out);
            $nights = (int) $checkIn->diffInDays($checkOut);
            $source = (string) $r->source;
            $statusKey = self::statusKey((string) $r->status);

            $ledger = $ledgerByReservation->get($resId) ?? collect();
            $allLedger = $allLedger->concat($ledger);
            $netDisplays = OwnerLedgerAttribution::netDisplays($ledger);

            $sourceCounts[$source] = ($sourceCounts[$source] ?? 0) + 1;
            $statusCounts[$statusKey] = ($statusCounts[$statusKey] ?? 0) + 1;

            $rows[] = [
                'id' => $resId,
                'href' => route('owner.bookings.show', $resId),
                'guest' => self::guestName($r->leadGuest),
                'value_display' => $netDisplays === [] ? __('php/support/owner/owner_upcoming_stays.not_yet_attributed') : (string) $netDisplays[0],
                'attributed' => $netDisplays !== [],
                'detail' => trans_choice('php/support/owner/owner_upcoming_stays.detail_unit_nights', $nights, ['unit' => $unitLabelByReservation[$resId] ?? '—', 'count' => $nights]),
                'source_key' => $source,
                'source_label' => self::sourceLabel($source),
                'source_dot' => self::SOURCE_DOTS[$source] ?? '#A69A92',
                'status_key' => $statusKey,
                'status_label' => self::statusLabel($statusKey),
                'status_tone' => $statusKey === 'confirmed' ? 'confirmed' : 'pending',
                'date' => $checkIn->translatedFormat(LocalDate::pattern('j M')),
                'sort' => $checkIn->toDateString(),
            ];
        }

        // Net total (attributed only) across the shown stays, per currency → a single display in the owner's currency.
        $netTotalMinor = OwnerLedgerAttribution::netPerCurrency($allLedger)[$currency] ?? 0;

        $sources = array_map(
            fn (array $o): array => [...$o, 'dot' => self::SOURCE_DOTS[$o['key']] ?? '#A69A92'],
            self::options($sourceCounts, fn (string $k): string => self::sourceLabel($k)),
        );

        return [
            'rows' => $rows,
            'sources' => $sources,
            'statuses' => self::options($statusCounts, fn (string $k): string => self::statusLabel($k)),
            'net_total_display' => MoneyFormatter::display($netTotalMinor, $currency),
            'count' => count($rows),
        ];
    }

    /**
     * A unit-label per reservation (first owned unit + "+N"), from the owner's active lines.
     *
     * @param  Collection<int, ReservationUnit>  $lines
     * @param  array<string, Unit>  $unitById
     * @return array<string, string>
     */
    private static function unitLabels(Collection $lines, array $unitById): array
    {
        return $lines
            ->groupBy(fn (ReservationUnit $l): string => (string) $l->reservation_id)
            ->map(function (Collection $group) use ($unitById): string {
                $names = $group
                    ->map(fn (ReservationUnit $l): string => (string) $l->unit_id)
                    ->unique()
                    ->map(fn (string $id): ?string => isset($unitById[$id]) ? (string) ($unitById[$id]->name ?? $unitById[$id]->code) : null)
                    ->filter()
                    ->values();

                return match (true) {
                    $names->isEmpty() => '—',
                    $names->count() === 1 => (string) $names->first(),
                    default => $names->first().' +'.($names->count() - 1),
                };
            })
            ->all();
    }

    /**
     * The guest's NAME only — never email/phone/contact (#28). Anonymized (GDPR erased) → null.
     */
    private static function guestName(?Guest $guest): ?string
    {
        if (! $guest instanceof Guest) {
            return null;
        }

        $name = trim(((string) ($guest->first_name ?? '')).' '.((string) ($guest->last_name ?? '')));

        return $name !== '' ? $name : null;
    }

    /** enquiry/tentative → 'pending'; confirmed → 'confirmed'. */
    private static function statusKey(string $status): string
    {
        return $status === 'confirmed' ? 'confirmed' : 'pending';
    }

    private static function statusLabel(string $key): string
    {
        return $key === 'confirmed' ? __('php/support/owner/owner_upcoming_stays.status_confirmed') : __('php/support/owner/owner_upcoming_stays.status_pending');
    }

    /** The REAL source enum → a warm label (no OTA brand — an OTA stay is "OTA"), mirroring FormatsOwnerData. */
    private static function sourceLabel(string $source): string
    {
        return match ($source) {
            'direct' => __('php/support/owner/owner_upcoming_stays.source_direct'),
            'corporate' => __('php/support/owner/owner_upcoming_stays.source_corporate'),
            'group' => __('php/support/owner/owner_upcoming_stays.source_group'),
            'walk_in' => __('php/support/owner/owner_upcoming_stays.source_walk_in'),
            'ota' => __('php/support/owner/owner_upcoming_stays.source_ota'),
            default => ucfirst(str_replace('_', ' ', $source)),
        };
    }

    /**
     * @param  array<string, int>  $counts
     * @param  callable(string): string  $label
     * @return list<array{key: string, label: string, count: int}>
     */
    private static function options(array $counts, callable $label): array
    {
        ksort($counts);

        return array_map(
            fn (string $key): array => ['key' => $key, 'label' => $label($key), 'count' => $counts[$key]],
            array_keys($counts)
        );
    }
}
