<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Fee;
use App\Models\Folio;
use App\Models\FolioItem;
use App\Models\ManagementAgreement;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Unit;
use App\Support\Events\DomainEventRecorder;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * OwnerAttributionService (Phase 3 · Module 1 Step 2) — the crux. It attributes committed
 * folio/reservation revenue to owners, writing each component as its own immutable `owner_ledger`
 * line so a statement is explainable line-by-line.
 *
 * It is READ-ONLY over the operational data: it reads `folio_items` (room/fee/add_on lines) and
 * `reservation_units` (which owned unit was occupied over which nights, at what price). It NEVER
 * mutates folios, reservations, inventory, or `availability`.
 *
 * Design notes grounded in the real schema:
 *  - Room revenue is posted to the folio as a SINGLE lump `room` line (BookingEngineService), so the
 *    authoritative total is Σ folio_items(type=room). It is ALLOCATED across the active
 *    reservation_unit lines (each line = one unit over one date-range = one owner) using
 *    Money::allocate, weighting by each line's `price_minor` when all are priced, else by night count
 *    (a mid-stay move is `split()` + `assignUnit()`, and split() zeroes the moved-out segment's
 *    price — so night-weighting is the correct per-night fallback). This makes multi-unit and
 *    mid-stay-move attribution both per-night correct.
 *  - Owner + operator shares always sum back to the room revenue EXACTLY (Money::allocate invariant).
 *
 * Correct-by-reversal + WHOLE-TRANSACTION: a cancel/refund/modify reverses EVERY component of a
 * booking together at the same version (never partial, never edits originals). Re-attribution after a
 * modification reverses the live version wholesale and attributes a fresh version. Idempotency is
 * DB-enforced by `owner_ledger.idempotency_key`.
 */
class OwnerAttributionService
{
    /** Default rule set of record (see DECISIONS.md 2026-07-03 Step 0). Only these two are overridable. */
    private const DEFAULT_OTA_BEARER = 'owner';      // owner bears channel cost on their revenue

    private const DEFAULT_CLEANING_BEARER = 'owner';  // cleaning fee is owner revenue by default

    private const DEFAULT_ADDON_BEARER = 'operator';  // add-ons are operator revenue by default

    public function __construct(
        private DomainEventRecorder $domainEvents,
        private PeriodLockGuard $lockGuard,
    ) {}

    /**
     * First attribution for a booking (idempotent — safe to re-run). No-op if already attributed at
     * the live version; attributes at version 1 otherwise.
     */
    public function attributeReservation(Reservation $reservation): void
    {
        $live = $this->liveVersion($reservation);

        if ($live > 0) {
            return; // already attributed; idempotency keys would collide anyway
        }

        $this->attributeAtVersion($reservation, 1);
    }

    /**
     * Whole-transaction reversal: reverse EVERY component of the booking's live version together.
     * The originals are untouched; the ledger nets to zero for this booking. Idempotent.
     */
    public function reverseReservation(Reservation $reservation): void
    {
        $live = $this->liveVersion($reservation);

        if ($live === 0) {
            return;
        }

        $this->reverseVersion($reservation, $live);
    }

    /**
     * Re-attribute after a modification: reverse the live version wholesale, then attribute a fresh
     * version. The prior version's lines remain (reversed); numbers are reproducible per version.
     */
    public function reattributeReservation(Reservation $reservation): void
    {
        $live = $this->liveVersion($reservation);

        if ($live > 0) {
            $this->reverseVersion($reservation, $live);
        }

        $this->attributeAtVersion($reservation, $live + 1);
    }

    // ---------------------------------------------------------------------------------------------

    private function attributeAtVersion(Reservation $reservation, int $version): void
    {
        $reservation->loadMissing('reservationUnits');

        /** @var Collection<int, ReservationUnit> $activeLines */
        $activeLines = $reservation->reservationUnits
            ->where('status', 'active')
            ->values();

        if ($activeLines->isEmpty()) {
            return;
        }

        $currency = (string) $reservation->currency;
        $roomTotal = $this->roomRevenueTotal($reservation, $activeLines, $currency);

        // Allocate the folio room total across ALL active lines (owned + operator-owned) so the
        // owner share is exact and operator inventory keeps its remainder.
        $allPriced = $activeLines->every(fn (ReservationUnit $l): bool => (int) $l->price_minor > 0);
        $weights = $activeLines
            ->map(fn (ReservationUnit $l): int => $allPriced ? (int) $l->price_minor : $this->nights($l))
            ->all();
        $roomParts = $roomTotal->allocate($weights);

        // Which lines are owner-attributable (owned unit + a governing agreement on that night)?
        $ownerLines = [];      // index => context
        $ownerRoomWeights = []; // index => weight for reservation-level distribution (OTA/cleaning/addon)
        foreach ($activeLines as $i => $ru) {
            $unit = $ru->unit_id !== null ? Unit::find($ru->unit_id) : null;
            if ($unit === null || $unit->owner_id === null) {
                continue; // operator inventory — not owner-attributed
            }

            $agreement = ManagementAgreement::resolveFor((string) $unit->owner_id, (string) $unit->id, $ru->check_in);
            $ownerLines[$i] = [
                'ru' => $ru,
                'unit' => $unit,
                'owner_id' => (string) $unit->owner_id,
                'agreement' => $agreement,
                'room' => $roomParts[$i],
            ];
            $ownerRoomWeights[$i] = max(1, $roomParts[$i]->amountMinor());
        }

        // Reservation-level fee/add-on totals (folio ledger), distributed across owner lines by room.
        $cleaningTotal = $this->cleaningFeeTotal($reservation, $currency);
        $addonTotal = $this->addonTotal($reservation, $currency);
        $otaContext = $this->otaContext($reservation, $currency);

        $indices = array_keys($ownerLines);
        $roomWeightList = array_values($ownerRoomWeights);
        $cleaningParts = $this->distribute($cleaningTotal, $roomWeightList);
        $addonParts = $this->distribute($addonTotal, $roomWeightList);
        $otaByAllocation = $otaContext['mode'] === 'total'
            ? $this->distribute($otaContext['total'], $roomWeightList)
            : null;

        DB::transaction(function () use (
            $reservation, $version, $currency, $ownerLines, $indices,
            $cleaningParts, $addonParts, $otaContext, $otaByAllocation
        ): void {
            foreach ($indices as $slot => $i) {
                $ctx = $ownerLines[$i];
                /** @var ReservationUnit $ru */
                $ru = $ctx['ru'];
                $unitId = (string) $ctx['unit']->id;
                $ownerId = $ctx['owner_id'];
                $serviceDate = CarbonImmutable::parse($ru->check_in)->toDateString();
                $agreement = $ctx['agreement'];

                // NULL governing agreement on a covered date → attribute NOTHING, flag explicitly.
                if ($agreement === null) {
                    $this->post($reservation, $ownerId, $unitId, $version, [
                        'entry_type' => 'adjustment',
                        'direction' => 'debit',
                        'amount' => Money::zero($currency),
                        'description' => 'Not attributed — no governing management agreement on '.$serviceDate,
                        'service_date' => $serviceDate,
                        'unverified' => true,
                        'note' => 'no_agreement',
                    ]);

                    continue;
                }

                /** @var Money $room */
                $room = $ctx['room'];

                // Room revenue → owner GROSS credit.
                $this->post($reservation, $ownerId, $unitId, $version, [
                    'entry_type' => 'room_revenue',
                    'direction' => 'credit',
                    'amount' => $room,
                    'description' => 'Room revenue · '.$this->nights($ru).' night(s)',
                    'service_date' => $serviceDate,
                    'meta' => ['room_minor' => $room->amountMinor()],
                ]);

                // Management commission → deduct from owner (operator revenue). Exact complement.
                $commissionPct = $agreement->commission_pct;
                if ($commissionPct !== null && (float) $commissionPct > 0.0) {
                    $bp = (int) round((float) $commissionPct * 100); // basis points
                    [$operatorShare] = $room->allocate([$bp, max(0, 10000 - $bp)]);
                    if (! $operatorShare->isZero()) {
                        $this->post($reservation, $ownerId, $unitId, $version, [
                            'entry_type' => 'commission',
                            'direction' => 'debit',
                            'amount' => $operatorShare,
                            'description' => 'Management commission '.rtrim(rtrim((string) $commissionPct, '0'), '.').'%',
                            'service_date' => $serviceDate,
                            'meta' => ['commission_pct' => (string) $commissionPct, 'room_minor' => $room->amountMinor()],
                        ]);
                    }
                }

                // OTA cost → deduct from owner by default (configurable to operator).
                $otaBearer = $agreement->rule('ota_cost_bearer', self::DEFAULT_OTA_BEARER);
                if ($otaBearer === 'owner') {
                    if ($otaContext['mode'] === 'total') {
                        $otaAmount = $otaByAllocation[$slot];
                        $unverified = false;
                    } elseif ($otaContext['mode'] === 'channel_pct') {
                        $otaAmount = $room->percentage($otaContext['pct']);
                        $unverified = false;
                    } else { // 'unverified' — no commission source anywhere
                        $otaAmount = Money::zero($currency);
                        $unverified = true;
                    }

                    $this->post($reservation, $ownerId, $unitId, $version, [
                        'entry_type' => 'ota_cost',
                        'direction' => 'debit',
                        'amount' => $otaAmount,
                        'description' => $unverified
                            ? 'OTA commission — unverified / pending channel reconciliation'
                            : 'OTA commission on booking',
                        'service_date' => $serviceDate,
                        'unverified' => $unverified,
                        'note' => $unverified ? 'ota_unverified' : null,
                        'meta' => ['ota_mode' => $otaContext['mode']],
                    ]);
                }

                // Cleaning fee → owner revenue by default (configurable to operator).
                $cleaningBearer = $agreement->rule('cleaning_fee_bearer', self::DEFAULT_CLEANING_BEARER);
                $cleaningAmount = $cleaningParts[$slot] ?? Money::zero($currency);
                if ($cleaningBearer === 'owner' && ! $cleaningAmount->isZero()) {
                    $this->post($reservation, $ownerId, $unitId, $version, [
                        'entry_type' => 'cleaning',
                        'direction' => 'credit',
                        'amount' => $cleaningAmount,
                        'description' => 'Cleaning fee',
                        'service_date' => $serviceDate,
                    ]);
                }

                // Add-ons → operator revenue by default; owner only when the agreement overrides.
                $addonBearer = $agreement->rule('addon_bearer', self::DEFAULT_ADDON_BEARER);
                $addonAmount = $addonParts[$slot] ?? Money::zero($currency);
                if ($addonBearer === 'owner' && ! $addonAmount->isZero()) {
                    $this->post($reservation, $ownerId, $unitId, $version, [
                        'entry_type' => 'addon',
                        'direction' => 'credit',
                        'amount' => $addonAmount,
                        'description' => 'Add-ons',
                        'service_date' => $serviceDate,
                    ]);
                }
            }
        });

        $this->domainEvents->record('owner.attributed', $reservation, [
            'attribution_version' => $version,
        ]);
    }

    /**
     * Reverse EVERY original line at $version together (whole-transaction). Idempotent: a reversal
     * that already exists (keyed on the original id) is skipped.
     */
    private function reverseVersion(Reservation $reservation, int $version): void
    {
        $originals = OwnerLedgerEntry::query()
            ->where('reservation_id', $reservation->getKey())
            ->where('attribution_version', $version)
            ->whereNull('reverses_id')
            ->get();

        if ($originals->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($originals, $version): void {
            foreach ($originals as $original) {
                OwnerLedgerEntry::firstOrCreate(
                    ['idempotency_key' => 'rev:'.$original->getKey()],
                    [
                        'organization_id' => $original->organization_id,
                        'owner_id' => $original->owner_id,
                        'unit_id' => $original->unit_id,
                        'reservation_id' => $original->reservation_id,
                        'entry_type' => $original->entry_type,
                        'direction' => $original->direction === 'credit' ? 'debit' : 'credit',
                        'amount_minor' => $original->amount_minor,
                        'currency' => $original->currency,
                        'description' => 'Reversal — '.$original->description,
                        'service_date' => $original->service_date,
                        'attribution_version' => $version,
                        'reverses_id' => $original->getKey(),
                        'unverified' => $original->unverified,
                        'prior_period_adjustment' => $this->lockGuard->isSealed((string) $original->owner_id, $original->service_date),
                        'note' => 'reversal',
                        'posted_at' => now(),
                    ],
                );
            }
        });

        $this->domainEvents->record('owner.attribution_reversed', $reservation, [
            'attribution_version' => $version,
        ]);
    }

    /**
     * Fixed management fee, pro-rated to the fraction of the fee interval covered by the period.
     * Posted once per (agreement, period) as an owner debit. Idempotent on the period key.
     */
    public function attributeManagementFee(ManagementAgreement $agreement, string $periodStart, string $periodEnd): ?OwnerLedgerEntry
    {
        if ($agreement->management_fee_minor === null || (int) $agreement->management_fee_minor === 0) {
            return null;
        }

        $currency = (string) ($agreement->management_fee_currency ?? $agreement->payout_currency ?? 'EUR');
        $fee = Money::of((int) $agreement->management_fee_minor, $currency);

        $intervalDays = match ($agreement->management_fee_interval) {
            'yearly' => 365,
            'quarterly' => 91,
            default => 30, // monthly
        };
        $periodDays = (int) CarbonImmutable::parse($periodStart)->diffInDays(CarbonImmutable::parse($periodEnd)) + 1;

        // Pro-rate: (feeIntervalPortion) of the fixed fee. allocate keeps it exact vs. the interval.
        [$prorated] = $fee->allocate([min($periodDays, $intervalDays), max(0, $intervalDays - min($periodDays, $intervalDays))]);

        return OwnerLedgerEntry::firstOrCreate(
            ['idempotency_key' => 'fee:'.$agreement->getKey().':'.$periodStart.':'.$periodEnd],
            [
                'organization_id' => $agreement->organization_id,
                'owner_id' => $agreement->owner_id,
                'unit_id' => $agreement->unit_id,
                'entry_type' => 'management_fee',
                'direction' => 'debit',
                'amount_minor' => $prorated->amountMinor(),
                'currency' => $currency,
                'description' => 'Management fee ('.$agreement->management_fee_interval.', pro-rated)',
                'service_date' => $periodEnd,
                'attribution_version' => 1,
                'meta' => ['interval_days' => $intervalDays, 'period_days' => $periodDays],
                'posted_at' => now(),
            ],
        );
    }

    // ---------------------------------------------------------------------------------------------

    private function liveVersion(Reservation $reservation): int
    {
        return (int) OwnerLedgerEntry::query()
            ->where('reservation_id', $reservation->getKey())
            ->whereNull('reverses_id')
            ->max('attribution_version');
    }

    private function roomRevenueTotal(Reservation $reservation, Collection $activeLines, string $currency): Money
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();

        $roomMinor = $folio !== null
            ? (int) FolioItem::query()->where('folio_id', $folio->getKey())->where('type', 'room')->sum('amount_minor')
            : 0;

        // Fallback to the price snapshot when no folio room line has been posted yet.
        if ($roomMinor === 0) {
            $roomMinor = (int) $activeLines->sum('price_minor');
        }

        return Money::of($roomMinor, $currency);
    }

    private function cleaningFeeTotal(Reservation $reservation, string $currency): Money
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();
        if ($folio === null) {
            return Money::zero($currency);
        }

        $cleaningFeeIds = Fee::query()->where('kind', 'cleaning')->pluck('id');

        $minor = (int) FolioItem::query()
            ->where('folio_id', $folio->getKey())
            ->where('type', 'fee')
            ->where('reference_type', 'fee')
            ->whereIn('reference_id', $cleaningFeeIds)
            ->sum('amount_minor');

        return Money::of($minor, $currency);
    }

    private function addonTotal(Reservation $reservation, string $currency): Money
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();
        if ($folio === null) {
            return Money::zero($currency);
        }

        $minor = (int) FolioItem::query()
            ->where('folio_id', $folio->getKey())
            ->where('type', 'add_on')
            ->sum('amount_minor');

        return Money::of($minor, $currency);
    }

    /**
     * OTA cost source resolution (per Step 0 lock): reservation override → channel default → 0 flagged
     * unverified.
     *
     * @return array{mode: string, total?: Money, pct?: float}
     */
    private function otaContext(Reservation $reservation, string $currency): array
    {
        if ($reservation->ota_commission_minor !== null) {
            return ['mode' => 'total', 'total' => Money::of((int) $reservation->ota_commission_minor, $currency)];
        }

        if ($reservation->channel_id !== null) {
            $channel = Channel::query()->find($reservation->channel_id);
            if ($channel !== null && $channel->commission_pct !== null) {
                return ['mode' => 'channel_pct', 'pct' => (float) $channel->commission_pct];
            }
        }

        return ['mode' => 'unverified'];
    }

    /**
     * Distribute a total across owner lines by their room weights (exact). Empty owner set → [].
     *
     * @param  array<int, int>  $weights
     * @return array<int, Money>
     */
    private function distribute(Money $total, array $weights): array
    {
        if ($weights === [] || $total->isZero()) {
            return array_fill(0, count($weights), Money::zero($total->currency()));
        }

        return $total->allocate($weights);
    }

    private function nights(ReservationUnit $ru): int
    {
        return (int) CarbonImmutable::parse($ru->check_in)->diffInDays(CarbonImmutable::parse($ru->check_out));
    }

    /**
     * Insert one immutable ledger line, DB-idempotent on its component key.
     *
     * @param  array<string, mixed>  $attrs
     */
    private function post(Reservation $reservation, string $ownerId, string $unitId, int $version, array $attrs): OwnerLedgerEntry
    {
        /** @var Money $amount */
        $amount = $attrs['amount'];
        $key = $attrs['idempotency_key']
            ?? $reservation->getKey().':'.$unitId.':'.$attrs['entry_type'].':v'.$version;

        return OwnerLedgerEntry::firstOrCreate(
            ['idempotency_key' => $key],
            [
                'organization_id' => $reservation->organization_id,
                'owner_id' => $ownerId,
                'unit_id' => $unitId,
                'reservation_id' => $reservation->getKey(),
                'entry_type' => $attrs['entry_type'],
                'direction' => $attrs['direction'],
                'amount_minor' => $amount->amountMinor(),
                'currency' => $amount->currency(),
                'description' => $attrs['description'],
                'service_date' => $attrs['service_date'],
                'attribution_version' => $version,
                'unverified' => $attrs['unverified'] ?? false,
                'prior_period_adjustment' => $this->lockGuard->isSealed($ownerId, $attrs['service_date']),
                'note' => $attrs['note'] ?? null,
                'meta' => $attrs['meta'] ?? null,
                'posted_at' => now(),
            ],
        );
    }
}
