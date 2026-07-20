<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Guest;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Bookings (Phase 2 · Sub-gate 1) — the owner's reservations ACROSS their own units: guest, unit, dates,
 * nights, source, stage and the owner-attributed "Net to you". Styled from the catalogued "Owner Bookings"
 * design, wired to LIVE owner-scoped data. Read-only: writes NOTHING.
 *
 * ISOLATION — reservations carry NO owner_id (they are tenant-scoped, not {@see OwnerScope}-scoped). So the
 * owner boundary is applied THROUGH the units: the set is derived from `reservation_units` on the owner's OWN
 * unit ids, and those ids come only from the owner-scoped {@see OwnerPortalService::units()} — never from a
 * request. A non-linked user's unit set is empty → no bookings (fail-closed, like every owner surface). Filters
 * ride GET (`?tab=`, `?source=`, `?unit=`, `?month=`, `?q=`) so the owner middleware re-runs on every
 * navigation; a Livewire wire-action would bypass {@see SetOwnerScopeForPortal} and leak
 * (invariant #20).
 *
 * Honesty triage:
 *   • REAL     — the reservations touching the owner's units: guest NAME (name-only — invariant #28; NO email /
 *                phone / contact ever on an owner surface), reference, the owned unit(s), dates, nights, the
 *                REAL `source` enum (via {@see FormatsOwnerData::sourceLabel()} — Booking.com/Airbnb are NOT
 *                modelled, an OTA stay is "OTA"), and the lifecycle stage via the shared {@see ReservationStage}.
 *   • DERIVED  — "Net to you" per booking = Σ {@see OwnerLedgerEntry::signedMinor()} over that reservation's
 *                owner-scoped ledger rows, PER CURRENCY, through the ONE shared {@see OwnerLedgerAttribution}
 *                rule (so it cannot drift from Unit Detail / Booking Detail / the statement). An unattributed
 *                booking (no ledger rows yet — e.g. a future stay) shows "Not yet attributed", never a fake €0.
 *                The summary cards (upcoming / past counts, nights, portfolio net) derive from the full set.
 *   • OMITTED  — the design's "Bookings over time" chart + "Monthly goal / €6k target / 123% of goal" (a
 *                fabricated goal with no backing column) and "Export" (no export backend) — rendered NOWHERE,
 *                not faked.
 */
#[Layout('components.layouts.owner')]
class OwnerBookings extends Component
{
    use FormatsOwnerData;

    /** The tabs, mapped to the shared stage vocabulary. 'all' applies no stage filter. */
    private const TABS = ['upcoming', 'past', 'all'];

    public function render(): View
    {
        $units = app(OwnerPortalService::class)->units();   // owner-scoped, fail-closed when empty
        /** @var array<string, Unit> $unitById */
        $unitById = $units->keyBy(fn (Unit $u): string => (string) $u->id)->all();
        $ownerUnitIds = array_map('strval', $units->pluck('id')->all());

        // The owner's booking set — reservations reached ONLY through their own active unit lines.
        $lines = $ownerUnitIds === [] ? collect() : ReservationUnit::query()
            ->whereIn('unit_id', $ownerUnitIds)
            ->where('status', 'active')
            ->get(['reservation_id', 'unit_id']);

        /** @var list<string> $reservationIds */
        $reservationIds = $lines->pluck('reservation_id')->map(fn ($id): string => (string) $id)->unique()->values()->all();

        // owned-unit ids + label per reservation (a booking may touch more than one of the owner's units)
        $unitsByReservation = $this->unitsByReservation($lines, $unitById);

        $reservations = $reservationIds === [] ? collect() : Reservation::query()
            ->whereIn('id', $reservationIds)
            ->with('leadGuest')
            ->get();

        // Per-booking net, batched: owner-scoped ledger rows grouped by reservation → the ONE shared net rule.
        $ledgerRows = $reservationIds === [] ? collect() : OwnerLedgerEntry::query()
            ->whereIn('reservation_id', $reservationIds)
            ->get();
        $netByReservation = $ledgerRows
            ->groupBy(fn (OwnerLedgerEntry $e): string => (string) $e->reservation_id)
            ->map(fn (Collection $g): array => OwnerLedgerAttribution::netDisplays($g))
            ->all();

        $today = CarbonImmutable::today();

        // One row per booking, stage-annotated, from the full owner set.
        $bookings = $reservations
            ->map(fn (Reservation $r): array => $this->buildRow($r, $unitsByReservation, $netByReservation, $today))
            ->values();

        $filters = $this->filters($ownerUnitIds, $unitById);
        $rows = $this->applyFilters($bookings, $filters)
            ->sortBy(fn (array $b): string => $b['check_in_sort'], SORT_REGULAR, $filters['tab'] !== 'upcoming')
            ->values()
            ->all();

        return view('livewire.owner.bookings', [
            'viewer' => $this->viewer(),
            'unitsCount' => $units->count(),
            'summary' => $this->summary($bookings, $ledgerRows),
            'rows' => $rows,
            'filters' => $filters,
            'sourceOptions' => $this->sourceOptions($bookings),
            'monthOptions' => $this->monthOptions($bookings),
            'unitOptions' => $units->map(fn (Unit $u): array => [
                'id' => (string) $u->id,
                'label' => (string) ($u->name ?? $u->code),
            ])->values()->all(),
        ]);
    }

    /**
     * One booking row, stage-annotated. Declared `array<string, mixed>` so the row collection stays generally
     * typed for the filter/summary helpers (a precise inferred shape would not be assignable — Collection's
     * value type is invariant).
     *
     * @param  array<string, array{ids: list<string>, label: string}>  $unitsByReservation
     * @param  array<string, list<string>>  $netByReservation
     * @return array<string, mixed>
     */
    private function buildRow(Reservation $r, array $unitsByReservation, array $netByReservation, CarbonImmutable $today): array
    {
        $checkIn = Carbon::parse($r->check_in);
        $checkOut = Carbon::parse($r->check_out);
        $stage = ReservationStage::for($r, $today);
        $owned = $unitsByReservation[(string) $r->id] ?? ['ids' => [], 'label' => '—'];

        return [
            'id' => (string) $r->id,
            'reference' => (string) $r->reference,
            'guest' => $this->guestName($r->leadGuest),
            'unit_ids' => $owned['ids'],
            'unit_label' => $owned['label'],
            'check_in_sort' => $checkIn->toDateString(),
            'stay' => $checkIn->format('j M').' – '.$checkOut->format('j M Y'),
            'nights' => (int) $checkIn->diffInDays($checkOut),
            'month' => $checkIn->format('Y-m'),
            'source' => (string) $r->source,
            'source_label' => $this->sourceLabel((string) $r->source),
            'stage' => $stage,
            'stage_badge' => $this->stageBadge($stage),
            'net_displays' => $netByReservation[(string) $r->id] ?? [],
        ];
    }

    /**
     * Owned-unit ids + a display label per reservation, from the owner's active unit lines. A booking on more
     * than one of the owner's units shows the first + "+N".
     *
     * @param  Collection<int, ReservationUnit>  $lines
     * @param  array<string, Unit>  $unitById
     * @return array<string, array{ids: list<string>, label: string}>
     */
    private function unitsByReservation(Collection $lines, array $unitById): array
    {
        return $lines
            ->groupBy(fn (ReservationUnit $l): string => (string) $l->reservation_id)
            ->map(function (Collection $group) use ($unitById): array {
                $ids = $group->map(fn (ReservationUnit $l): string => (string) $l->unit_id)->unique()->values();
                $names = $ids
                    ->map(fn (string $id): ?string => isset($unitById[$id]) ? (string) ($unitById[$id]->name ?? $unitById[$id]->code) : null)
                    ->filter()
                    ->values();
                $label = match (true) {
                    $names->isEmpty() => '—',
                    $names->count() === 1 => (string) $names->first(),
                    default => $names->first().' +'.($names->count() - 1),
                };

                return ['ids' => $ids->all(), 'label' => $label];
            })
            ->all();
    }

    /**
     * The guest's NAME only — never email / phone / contact (invariant #28; the owner surface is name-only).
     * An anonymized guest (GDPR erased → name nulled) returns null; the row still identifies by its reference.
     */
    private function guestName(?Guest $guest): ?string
    {
        if (! $guest instanceof Guest) {
            return null;
        }

        $name = trim(((string) ($guest->first_name ?? '')).' '.((string) ($guest->last_name ?? '')));

        return $name !== '' ? $name : null;
    }

    /**
     * The active filter selection, sanitised. `tab` ∈ TABS; `source` must be a REAL enum value present;
     * `unit` must be one the owner actually owns (a foreign/unknown id is ignored, never trusted); `month`
     * matches YYYY-MM; `q` is a free search string.
     *
     * @param  list<string>  $ownerUnitIds
     * @param  array<string, Unit>  $unitById
     * @return array{tab: string, source: ?string, unit: ?string, month: ?string, q: ?string}
     */
    private function filters(array $ownerUnitIds, array $unitById): array
    {
        $tab = $this->queryParam('tab');
        $unit = $this->queryParam('unit');
        $month = $this->queryParam('month');

        return [
            'tab' => in_array($tab, self::TABS, true) ? $tab : 'upcoming',
            'source' => $this->queryParam('source'),
            'unit' => ($unit !== null && in_array($unit, $ownerUnitIds, true)) ? $unit : null,
            'month' => ($month !== null && preg_match('/^\d{4}-\d{2}$/', $month) === 1) ? $month : null,
            'q' => $this->queryParam('q'),
        ];
    }

    /**
     * Filter the booking rows by the current selection. All in PHP over the already-owner-scoped set (owner
     * booking volumes are modest); the stage filter reuses {@see ReservationStage} so a tab can never drift.
     *
     * @param  Collection<int, array<string, mixed>>  $bookings
     * @param  array{tab: string, source: ?string, unit: ?string, month: ?string, q: ?string}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function applyFilters(Collection $bookings, array $filters): Collection
    {
        $q = $filters['q'] !== null ? mb_strtolower($filters['q']) : null;

        return $bookings->filter(function (array $b) use ($filters, $q): bool {
            if ($filters['tab'] === 'upcoming' && $b['stage'] !== ReservationStage::UPCOMING) {
                return false;
            }
            if ($filters['tab'] === 'past' && $b['stage'] !== ReservationStage::PAST) {
                return false;
            }
            if ($filters['source'] !== null && $b['source'] !== $filters['source']) {
                return false;
            }
            if ($filters['unit'] !== null && ! in_array($filters['unit'], $b['unit_ids'], true)) {
                return false;
            }
            if ($filters['month'] !== null && $b['month'] !== $filters['month']) {
                return false;
            }
            if ($q !== null && $q !== '') {
                $hay = mb_strtolower(((string) $b['guest']).' '.$b['reference'].' '.$b['unit_label']);
                if (! str_contains($hay, $q)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Portfolio summary over the FULL owner set (not the filtered table): upcoming / past counts, total nights,
     * and the portfolio net (Σ signedMinor over all these bookings' ledger rows, PER currency).
     *
     * @param  Collection<int, array<string, mixed>>  $bookings
     * @param  Collection<int, OwnerLedgerEntry>  $ledgerRows
     * @return array{total: int, upcoming: int, past: int, nights: int, net: list<array{display: string}>}
     */
    private function summary(Collection $bookings, Collection $ledgerRows): array
    {
        $net = [];
        foreach (OwnerLedgerAttribution::netPerCurrency($ledgerRows) as $currency => $minor) {
            $net[] = ['display' => $this->money($minor, $currency)];
        }

        return [
            'total' => $bookings->count(),
            'upcoming' => $bookings->where('stage', ReservationStage::UPCOMING)->count(),
            'past' => $bookings->where('stage', ReservationStage::PAST)->count(),
            'nights' => (int) $bookings->sum(fn (array $b): int => $b['nights']),
            'net' => $net,
        ];
    }

    /**
     * The REAL sources present in the owner's set, as {value,label} — so the filter never offers an OTA brand
     * or a source with no bookings behind it.
     *
     * @param  Collection<int, array<string, mixed>>  $bookings
     * @return list<array{value: string, label: string}>
     */
    private function sourceOptions(Collection $bookings): array
    {
        return $bookings
            ->map(fn (array $b): string => (string) $b['source'])
            ->unique()
            ->sort()
            ->map(fn (string $s): array => ['value' => $s, 'label' => $this->sourceLabel($s)])
            ->values()
            ->all();
    }

    /**
     * The check-in months present, most recent first, as {value: YYYY-MM, label}.
     *
     * @param  Collection<int, array<string, mixed>>  $bookings
     * @return list<array{value: string, label: string}>
     */
    private function monthOptions(Collection $bookings): array
    {
        return $bookings
            ->map(fn (array $b): string => (string) $b['month'])
            ->unique()
            ->sortDesc()
            ->map(fn (string $m): array => ['value' => $m, 'label' => Carbon::parse($m.'-01')->format('M Y')])
            ->values()
            ->all();
    }

    /** A trimmed non-empty query-string value, or null. */
    private function queryParam(string $key): ?string
    {
        $value = request()->query($key);
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
