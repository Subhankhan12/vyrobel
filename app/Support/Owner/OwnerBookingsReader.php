<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Http\Controllers\Owner\OwnerBookingsExportController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Livewire\Owner\OwnerBookings;
use App\Models\Guest;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\I18n\LocalDate;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * THE ONE owner-scoped booking-set builder (GATE 1) — shared by the owner Bookings PAGE ({@see OwnerBookings})
 * and the owner Bookings CSV EXPORT ({@see OwnerBookingsExportController}), so the two
 * surfaces can never disagree on what the owner's bookings are, or on their "Net to you". Same anti-drift
 * discipline as {@see OwnerLedgerAttribution} / {@see OwnerOccupancy}.
 *
 * ISOLATION (unchanged from the original component): reservations carry NO owner_id — the owner boundary is
 * applied THROUGH the units. The set is derived from `reservation_units` on the owner's OWN unit ids, and those
 * ids come ONLY from the owner-scoped {@see OwnerPortalService::units()} — never from a request. A non-linked
 * user's unit set is empty → no bookings (fail-closed, like every owner surface).
 *
 * HONESTY: guest NAME only (name-only — invariant #28; NO email/phone/contact); the REAL `source` enum (an OTA
 * stay is "OTA", never a brand — the brand is not modelled); the REAL `status` enum; the lifecycle STAGE via the
 * shared {@see ReservationStage}; and "Net to you" per currency via the ONE shared {@see OwnerLedgerAttribution}
 * rule (an unattributed booking yields an empty net → the caller renders "Not yet attributed", never a fake €0).
 *
 * @phpstan-type BookingRow array{id: string, reference: string, guest: string|null, guest_id: string|null, unit_ids: list<string>, unit_label: string, check_in_sort: string, check_out_sort: string, stay: string, nights: int, month: string, source: string, source_label: string, status: string, status_badge: array{label: string, tone: string}, stage: string, stage_badge: array{label: string, tone: string}, net_displays: list<string>, net_minor: array<string, int>}
 */
final class OwnerBookingsReader
{
    public function __construct(private OwnerPortalService $portal) {}

    /**
     * The owner's full booking set — one row per reservation touching one of their OWN units, unsorted.
     *
     * @return list<BookingRow>
     */
    public function rows(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();

        $units = $this->portal->units();   // owner-scoped, fail-closed when empty
        /** @var array<string, Unit> $unitById */
        $unitById = $units->keyBy(fn (Unit $u): string => (string) $u->id)->all();
        $ownerUnitIds = array_map('strval', $units->pluck('id')->all());

        $lines = $ownerUnitIds === [] ? collect() : ReservationUnit::query()
            ->whereIn('unit_id', $ownerUnitIds)
            ->where('status', 'active')
            ->get(['reservation_id', 'unit_id']);

        /** @var list<string> $reservationIds */
        $reservationIds = $lines->pluck('reservation_id')->map(fn ($id): string => (string) $id)->unique()->values()->all();

        if ($reservationIds === []) {
            return [];
        }

        $unitsByReservation = $this->unitsByReservation($lines, $unitById);

        $reservations = Reservation::query()
            ->whereIn('id', $reservationIds)
            ->with('leadGuest')
            ->get();

        // Per-booking net, batched: owner-scoped ledger rows grouped by reservation → the ONE shared net rule.
        $ledgerRows = OwnerLedgerEntry::query()->whereIn('reservation_id', $reservationIds)->get();
        /** @var array<string, array<string, int>> $netMinorByReservation */
        $netMinorByReservation = [];
        /** @var array<string, list<string>> $netDisplaysByReservation */
        $netDisplaysByReservation = [];
        foreach ($ledgerRows->groupBy(fn (OwnerLedgerEntry $e): string => (string) $e->reservation_id) as $rid => $group) {
            $netMinorByReservation[(string) $rid] = OwnerLedgerAttribution::netPerCurrency($group);
            $netDisplaysByReservation[(string) $rid] = OwnerLedgerAttribution::netDisplays($group);
        }

        return $reservations
            ->map(fn (Reservation $r): array => $this->buildRow($r, $unitsByReservation, $netMinorByReservation, $netDisplaysByReservation, $today))
            ->values()
            ->all();
    }

    /**
     * The owner-scoped ledger rows behind the booking set — the raw material for the "Net to you" summary +
     * the chart's net line. Same owner boundary (through the units) as {@see rows()}.
     *
     * @return Collection<int, OwnerLedgerEntry>
     */
    public function ledgerRows(): Collection
    {
        $ownerUnitIds = array_map('strval', $this->portal->units()->pluck('id')->all());

        $reservationIds = $ownerUnitIds === [] ? [] : ReservationUnit::query()
            ->whereIn('unit_id', $ownerUnitIds)
            ->where('status', 'active')
            ->pluck('reservation_id')
            ->map(fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();

        return $reservationIds === [] ? collect() : OwnerLedgerEntry::query()->whereIn('reservation_id', $reservationIds)->get();
    }

    /**
     * @param  array<string, array{ids: list<string>, label: string}>  $unitsByReservation
     * @param  array<string, array<string, int>>  $netMinorByReservation
     * @param  array<string, list<string>>  $netDisplaysByReservation
     * @return BookingRow
     */
    private function buildRow(Reservation $r, array $unitsByReservation, array $netMinorByReservation, array $netDisplaysByReservation, CarbonImmutable $today): array
    {
        $checkIn = Carbon::parse($r->check_in);
        $checkOut = Carbon::parse($r->check_out);
        $stage = ReservationStage::for($r, $today);
        $owned = $unitsByReservation[(string) $r->id] ?? ['ids' => [], 'label' => '—'];
        $guest = $r->leadGuest instanceof Guest ? $r->leadGuest : null;

        return [
            'id' => (string) $r->id,
            'reference' => (string) $r->reference,
            'guest' => $this->guestName($guest),
            'guest_id' => $guest !== null ? (string) $guest->id : null,
            'unit_ids' => $owned['ids'],
            'unit_label' => $owned['label'],
            'check_in_sort' => $checkIn->toDateString(),
            'check_out_sort' => $checkOut->toDateString(),
            'stay' => $checkIn->translatedFormat(LocalDate::pattern('j M')).' – '.$checkOut->translatedFormat(LocalDate::pattern('j M Y')),
            'nights' => (int) $checkIn->diffInDays($checkOut),
            'month' => $checkIn->format('Y-m'),
            'source' => (string) $r->source,
            'source_label' => self::sourceLabel((string) $r->source),
            'status' => (string) $r->status,
            'status_badge' => self::statusBadge((string) $r->status),
            'stage' => $stage,
            'stage_badge' => [
                'label' => ReservationStage::meta($stage)['label'],
                'tone' => match ($stage) {
                    ReservationStage::IN_HOUSE => 'paid',
                    ReservationStage::UPCOMING, ReservationStage::DUE, ReservationStage::TENTATIVE => 'pending',
                    default => 'draft',
                },
            ],
            'net_displays' => $netDisplaysByReservation[(string) $r->id] ?? [],
            'net_minor' => $netMinorByReservation[(string) $r->id] ?? [],
        ];
    }

    /**
     * Owned-unit ids + a display label per reservation. A booking on more than one owned unit shows the first
     * + "+N".
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

    /** The guest's NAME only — never email/phone/contact (invariant #28). An anonymized guest returns null. */
    private function guestName(?Guest $guest): ?string
    {
        if (! $guest instanceof Guest) {
            return null;
        }

        $name = trim(((string) ($guest->first_name ?? '')).' '.((string) ($guest->last_name ?? '')));

        return $name !== '' ? $name : null;
    }

    /**
     * The owner's PORTFOLIO display currency — the currency carrying the largest absolute net across the given
     * owner-scoped ledger rows (deterministic), else EUR. The single owner-bookings source-of-truth for "which
     * currency the single-axis chart / ring / target speak in", so the page and the target-write controller can
     * never pick a different one. Money is never combined across currencies (#17); this only PICKS one.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $ledgerRows
     */
    public static function primaryCurrency(Collection $ledgerRows): string
    {
        $best = 'EUR';
        $bestAbs = -1;
        foreach (OwnerLedgerAttribution::netPerCurrency($ledgerRows) as $ccy => $minor) {
            if (abs($minor) > $bestAbs) {
                $best = $ccy;
                $bestAbs = abs($minor);
            }
        }

        return $best;
    }

    /**
     * reservations.source enum → owner-facing label — the OWNER source-of-truth (the {@see FormatsOwnerData}
     * trait delegates here so the page + export can never disagree). The seven arms ARE the enum; there is NO
     * OTA brand (not modelled — an OTA stay is "OTA").
     */
    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            'direct' => __('php/support/owner/owner_bookings_reader.source_direct'),
            'walk_in' => __('php/support/owner/owner_bookings_reader.source_walk_in'),
            'ota' => __('php/support/owner/owner_bookings_reader.source_ota'),
            'corporate' => __('php/support/owner/owner_bookings_reader.source_corporate'),
            'group' => __('php/support/owner/owner_bookings_reader.source_group'),
            'agent' => __('php/support/owner/owner_bookings_reader.source_agent'),
            'manual' => __('php/support/owner/owner_bookings_reader.source_manual'),
            default => ucfirst(str_replace('_', ' ', $source)),
        };
    }

    /**
     * reservations.status enum → owner badge (label + tone) — the OWNER source-of-truth (the trait delegates
     * here). The seven arms ARE the enum; nothing is invented.
     *
     * @return array{label: string, tone: string}
     */
    public static function statusBadge(string $status): array
    {
        return match ($status) {
            'confirmed' => ['label' => __('php/support/owner/owner_bookings_reader.status_confirmed'), 'tone' => 'confirmed'],
            'tentative' => ['label' => __('php/support/owner/owner_bookings_reader.status_tentative'), 'tone' => 'pending'],
            'enquiry' => ['label' => __('php/support/owner/owner_bookings_reader.status_enquiry'), 'tone' => 'pending'],
            'checked_in' => ['label' => __('php/support/owner/owner_bookings_reader.status_checked_in'), 'tone' => 'paid'],
            'checked_out' => ['label' => __('php/support/owner/owner_bookings_reader.status_checked_out'), 'tone' => 'draft'],
            'cancelled' => ['label' => __('php/support/owner/owner_bookings_reader.status_cancelled'), 'tone' => 'draft'],
            'no_show' => ['label' => __('php/support/owner/owner_bookings_reader.status_no_show'), 'tone' => 'draft'],
            default => ['label' => ucfirst(str_replace('_', ' ', $status)), 'tone' => 'draft'],
        };
    }
}
