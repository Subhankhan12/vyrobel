<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerLedgerEntry;
use App\Models\RateCalendar;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Restriction;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Services\OwnerRateService;
use App\Support\Guest\GuestName;
use App\Support\Owner\OwnerAttention;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Owner\OwnerOccupancy;
use App\Support\Owner\OwnerUnitAnalytics;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner My Units (design pass · Screen 4 — completes the owner portal) — a NEW owner-scoped page for
 * the owner's units + an occupancy calendar. Styled from the catalogued "Owner My Units" design, wired
 * to LIVE data via the read-only {@see OwnerPortalService} (called, never modified). Presentation +
 * thin page only: NO change to Unit/availability logic, schema, or guarantees. Owner isolation
 * ({@see OwnerScope}) makes the unit list the owner's own; a non-linked user fails
 * closed. No route-model-bound props; month navigation is via plain GET links (`?month=`) so every
 * request re-runs the owner middleware (a Livewire wire-action would bypass it → isolation leak).
 *
 * OCCUPANCY — built OWNER-SCOPED, not room-type-wide. {@see occupancy()} reads `reservation_units` for
 * the owner's OWN unit ids only (from the owner-scoped units()), active physical-unit lines — so a
 * non-owner unit in the SAME room type (a different unit_id, e.g. a hotel room or another owner's unit)
 * is excluded. This is the fix for the room-type-wide `availabilityRange` trap flagged on the dashboard:
 * the number shown is genuinely THIS owner's occupancy.
 *
 * Honesty triage (updated 2026-07-27 · My Units GATE 2 — the wireframe-exact rebuild wired the real actions):
 *   • REAL     — unit list + count, per-unit name/type/status/occupancy/nights/revenue/ADR, the occupancy
 *                calendar heatmap, the best performer, the "Needs your attention" items (OwnerAttention),
 *                the "Add unit" link (owner.add-unit), and the CARD/CALENDAR write actions: EDIT PRICING
 *                ({@see editableRates} → owner.rates.apply, per-ROOM-TYPE exclusive-owned only), MESSAGE
 *                GUEST (the owner↔operator relay, owner.messages.start — never owner→guest, #28), BLOCK
 *                DATES (owner.calendar), and EXPORT (owner.units.export CSV, counts only). All owner-scoped.
 *   • HONEST-EMPTY — a unit sharing its room type with foreign inventory is NOT rate-editable (no fake save);
 *                the action-center's approve/decline DECISIONS have no real owner entity → not fabricated (#39).
 *   • Guest PII — the occupancy calendar's day-detail shows the guest NAME-ONLY (#28, via the shared GuestName —
 *                the same name-only rule Unit Detail / Block Calendar / Bookings use; P2-3 back-port), NEVER
 *                contact (email/phone stays on the Guests CRM); the message relay is framed around the UNIT, to
 *                the team, never the guest.
 */
#[Layout('components.layouts.owner')]
class OwnerMyUnits extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $portal = app(OwnerPortalService::class);
        // ⚠️ THE INVENTORY PAGE — live properties only. `units()` is the attribution set and would list a
        // unit at an archived property beside the owner's real ones, with working editors on it.
        $units = $portal->unitsAtLiveProperties();   // owner-scoped, fail-closed when empty
        $currency = $this->currency($units);

        $month = $this->resolveMonth();
        $occupancy = $this->occupancy($units, $month);

        // Batch B — REAL per-unit month metrics (gross revenue · ADR · occupancy) + the best-performer
        // ranking, both through the SHARED OwnerUnitAnalytics so this screen and Unit Detail cannot drift.
        $unitIds = array_map('strval', $units->pluck('id')->all());
        $perUnit = OwnerUnitAnalytics::perUnitMonth($unitIds, $month, $currency);
        $bestUnitId = OwnerUnitAnalytics::bestUnitId($perUnit);
        $editableRates = $this->editableRates($currency);

        return view('livewire.owner.units', [
            'viewer' => $this->viewer(),
            'unitsCount' => $units->count(),
            'units' => $this->unitCards($units, $occupancy, $perUnit, $currency, $bestUnitId, $editableRates),
            'occupancy' => $occupancy,
            'dayDetails' => $this->dayDetails($units, $month, $currency),   // P2-3 — richer day-detail (name-only #28)
            'occDelta' => $this->occDelta($unitIds, $month, $occupancy['pct']),
            'earned' => $this->earnedThisMonth($month, $currency),
            'best' => $this->bestPerformer($units, $perUnit, $bestUnitId, $month, $currency),
            'attention' => $this->attentionItems(),
            'snoozedAttention' => OwnerAttention::snoozed(app(OwnerContext::class)),
            'currencySymbol' => $this->currencySymbol($currency),
            'month' => [
                'label' => $month->translatedFormat('F Y'),
                'label_short' => $month->translatedFormat('M'),
                'first_weekday' => $month->copy()->startOfMonth()->dayOfWeekIso,   // 1=Mon … 7=Sun
                'prev' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
                'next' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            ],
        ]);
    }

    /**
     * A REAL month-over-month occupancy delta for the stats band — this month's owner-unit occupancy % vs the
     * previous month's (both via the shared {@see OwnerOccupancy}). null when there is no prior signal (never a
     * fabricated trend, #39).
     *
     * @param  list<string>  $unitIds
     * @return array{label: string, positive: bool}|null
     */
    private function occDelta(array $unitIds, Carbon $month, int $currentPct): ?array
    {
        $prevPct = OwnerOccupancy::forMonth($unitIds, $month->copy()->subMonthNoOverflow())['pct'];
        $diff = $currentPct - $prevPct;

        return $diff === 0 ? null : ['label' => ($diff > 0 ? '+' : '−').abs($diff).'%', 'positive' => $diff > 0];
    }

    /** The display currency — the units' property currency (matches OwnerInsights); EUR fallback. */
    private function currency(Collection $units): string
    {
        // ⚠️⚠️ F-44 (FIX 46) — THE FALLBACK FIRES ON AN EMPTY COLLECTION, NOT A NULL COLUMN.
        //    A GBP owner with nothing yet to show was quoted EUROS. ⚠️ The amount is right and the
        //    symbol is wrong, which is the dangerous direction: zero looks the same in every currency.
        //    ⚠️ THE OWNER THEMSELVES STILL CARRIES ONE — `owners.payout_currency` is char(3) NOT NULL.
        //    Same answer F-46 gave for this class; the last resort is CONFIG, never a literal.
        return (string) ($units->first()?->property->currency
            ?? $this->ownerPayoutCurrency()
            ?? config('billing.default_currency', 'EUR'));
    }

    /** The calendar month: `?month=YYYY-MM` when valid, otherwise the CURRENT month. */
    private function resolveMonth(): Carbon
    {
        $param = request()->query('month');
        if (is_string($param) && preg_match('/^\d{4}-\d{2}$/', $param) === 1) {
            return Carbon::parse($param.'-01')->startOfMonth();
        }

        // ⚠️ THE CURRENT month (wireframe-parity WAVE 2 · #11). This used to default to the month of the
        // owner's LATEST booking (`max(check_in)`) while the caption said "This month" — so a forward booking
        // in December made the page report December's occupancy as though it were now, and the operator
        // Dashboard and My Units printed different occupancy for the same unit on the same day. A page that
        // says "this month" must mean this month; `?month=` still navigates, and the caption now prints the
        // real month it is showing rather than asserting "This month" whatever is on screen.
        return Carbon::now()->startOfMonth();
    }

    /**
     * OWNER-SCOPED occupancy for the month — delegated to the SHARED {@see OwnerOccupancy} derivation so
     * this screen and the Unit Detail screen can never disagree about the owner's occupancy. The rule it
     * enforces is unchanged: active physical-unit bookings on the owner's OWN units only, so a non-owner
     * unit in the same room type is excluded by construction (it carries a different unit_id).
     *
     * @param  Collection<int, Unit>  $units
     * @return array{days: int, unit_count: int, heat: array<int, int>, per_unit: array<string, int>, nights: int, pct: int}
     */
    private function occupancy(Collection $units, Carbon $month): array
    {
        return OwnerOccupancy::forMonth(array_map('strval', $units->pluck('id')->all()), $month);
    }

    /**
     * P2-3 — the RICHER day-detail for the occupancy calendar (back-ported from Unit Detail; the bare count was
     * the audit gap). For each day-of-month, the booked units that night — each with the guest NAME-ONLY (#28,
     * via the shared {@see GuestName}), the stay dates and the owner's attributed NET on that unit (via the ONE
     * shared {@see OwnerLedgerAttribution::netDisplays}, the SAME rule Unit Detail / Bookings use — no drift).
     * Owner-scoped (units come pre-scoped); NO guest email/phone ever (#28).
     *
     * @param  Collection<int, Unit>  $units
     * @return array<int, list<array{unit: string, guest: string, stay: string, nights: int, nights_label: string, net: list<string>}>>
     */
    private function dayDetails(Collection $units, Carbon $month, string $currency): array
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->addMonth();
        $unitIds = array_map('strval', $units->pluck('id')->all());

        if ($unitIds === []) {
            return [];
        }

        $lines = ReservationUnit::query()
            ->whereIn('unit_id', $unitIds)->where('status', 'active')
            ->whereDate('check_in', '<', $monthEnd->toDateString())
            ->whereDate('check_out', '>', $monthStart->toDateString())
            ->with('reservation.leadGuest')
            ->get();

        // Net per (unit, reservation) — grouped, then the ONE shared net rule per group (anti-drift with Unit Detail).
        $nets = OwnerLedgerEntry::query()
            ->whereIn('unit_id', $unitIds)
            ->whereIn('reservation_id', $lines->pluck('reservation_id')->unique()->values()->all())
            ->get()
            ->groupBy(fn (OwnerLedgerEntry $r): string => $r->unit_id.'|'.$r->reservation_id)
            ->map(fn (Collection $g): array => OwnerLedgerAttribution::netDisplays($g))
            ->all();

        $unitLabel = $units->mapWithKeys(fn (Unit $u): array => [(string) $u->id => (string) ($u->name ?? $u->code)])->all();

        $out = [];
        foreach ($lines as $line) {
            $reservation = $line->reservation;
            $checkIn = Carbon::parse($line->check_in);
            $checkOut = Carbon::parse($line->check_out);
            $nights = (int) $checkIn->diffInDays($checkOut);
            $detail = [
                'unit' => $unitLabel[(string) $line->unit_id] ?? __('php/livewire/owner/owner_my_units.unit_fallback'),
                'guest' => $reservation instanceof Reservation ? GuestName::display($reservation->leadGuest) : __('php/livewire/owner/owner_my_units.guest_fallback'),
                'stay' => __('php/livewire/owner/owner_my_units.stay_range', ['from' => $checkIn->translatedFormat('j M'), 'to' => $checkOut->translatedFormat('j M Y')]),
                'nights' => $nights,
                // the calendar's day detail prints this (Alpine) — the plural is chosen here, per language
                'nights_label' => trans_choice('php/livewire/owner/owner_my_units.nights', $nights, ['count' => $nights]),
                'net' => $nets[$line->unit_id.'|'.$line->reservation_id] ?? [],
            ];

            // Paint onto each in-month night of the stay (half-open), keyed by day-of-month for the calendar.
            $cursor = $checkIn->greaterThan($monthStart) ? $checkIn->copy() : $monthStart->copy();
            $stop = $checkOut->lessThan($monthEnd) ? $checkOut : $monthEnd;
            for (; $cursor->lt($stop); $cursor->addDay()) {
                $out[(int) $cursor->day][] = $detail;
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, Unit>  $units
     * @param  array{days: int, per_unit: array<string, int>}  $occupancy
     * @param  array<string, array{gross_minor: int, adr_minor: int, occupancy_pct: int, nights: int}>  $perUnit
     * @return list<array<string, mixed>>
     */
    private function unitCards(Collection $units, array $occupancy, array $perUnit, string $currency, ?string $bestUnitId, array $editableRates): array
    {
        $days = $occupancy['days'];

        return $units->map(function (Unit $u) use ($occupancy, $days, $perUnit, $currency, $bestUnitId, $editableRates): array {
            $id = (string) $u->id;
            $roomTypeId = (string) $u->room_type_id;
            $nights = $occupancy['per_unit'][$id] ?? 0;
            $m = $perUnit[$id] ?? ['gross_minor' => 0, 'adr_minor' => 0, 'nights' => 0];
            $rate = $editableRates[$roomTypeId] ?? null;   // present only for an EXCLUSIVELY-owned room type

            return [
                'id' => $u->id,
                'name' => (string) ($u->name ?? $u->code),
                'code' => (string) $u->code,
                'type' => (string) $u->roomType->name,   // units always belong to a room type
                'active' => $u->is_active !== false,
                'nights' => $nights,
                'occupancy_pct' => $days > 0 ? (int) round($nights / $days * 100) : 0,
                // Batch B — REAL per-unit revenue (GROSS room revenue, for comparability).
                'revenue' => $this->money($m['gross_minor'], $currency),
                // ⚠️ "Nightly rate" now means the CONFIGURED rate (wireframe-parity WAVE 2 · #12). It used to
                // be fed the achieved ADR, so a unit priced at €120 that sold two discounted nights displayed
                // "Nightly rate €95" — an owner reads that as the price on their calendar and it isn't. The
                // configured rate was already loaded (it fills the editor's "Reset to …"). The ADR is still
                // shown, under its own name, so nothing is lost — only the two stop wearing one label.
                'nightly_rate' => $rate['display'] ?? null,
                'adr' => $m['nights'] > 0 ? $this->money($m['adr_minor'], $currency) : null,
                'is_best' => $bestUnitId !== null && $id === $bestUnitId,
                // Cover derives from the room-type (the sellable entity); null → gradient fallback.
                'cover' => $u->roomType->coverPhoto()?->url,
                // Edit pricing (GATE 2) — REAL, per-room-type, exclusive-owned only (OwnerRateService). A unit
                // sharing its room type with foreign inventory is NOT editable → honest, no fake save.
                'rate_editable' => $rate !== null && $rate['rate_minor'] !== null,
                'room_type_id' => $roomTypeId,
                'rate_minor' => $rate['rate_minor'] ?? null,
                'rate_units' => $rate !== null && $rate['rate_minor'] !== null ? (int) round($rate['rate_minor'] / 100) : null,
                'base_display' => $rate['display'] ?? null,
                // Wave-B: the current weekend-uplift % + min-stay, to prefill the editor (null when unset).
                'weekend_pct' => $rate['weekend_pct'] ?? null,
                'min_stay' => $rate['min_stay'] ?? null,
            ];
        })->values()->all();
    }

    /**
     * The REAL current nightly rate (integer minor + display) for each room type the owner may EDIT (exclusive
     * ownership, via {@see OwnerRateService::editableRoomTypes()}). Keyed by room_type_id; a room type shared with
     * foreign inventory is absent → the card's Edit-pricing is honestly disabled. The rate is read from
     * `rate_calendar` for today on the room type's primary plan (the same source the Rates screen reads).
     *
     * @return array<string, array{rate_minor: int|null, display: string|null, weekend_pct: float|null, min_stay: int|null}>
     */
    private function editableRates(string $currency): array
    {
        $out = [];
        $today = Carbon::today()->toDateString();

        foreach (app(OwnerRateService::class)->editableRoomTypes() as $roomType) {
            $plan = $roomType->primaryRatePlan();
            $rateMinor = null;
            $display = null;

            if ($plan !== null) {
                $row = RateCalendar::query()
                    ->where('rate_plan_id', $plan->id)
                    ->whereDate('date', '>=', $today)
                    ->orderBy('date')
                    ->first();
                if ($row !== null) {
                    $rateMinor = (int) $row->price_minor;
                    $display = $this->money($rateMinor, (string) $row->currency);
                }
            }

            // Prefill the Wave-A weekend-uplift POLICY (on the plan) + the current min-stay (the soonest
            // upcoming restriction row) so the editor opens on the owner's real current values.
            $weekendPct = $plan?->weekend_uplift_pct !== null ? (float) $plan->weekend_uplift_pct : null;
            $minStay = Restriction::query()
                ->where('room_type_id', $roomType->id)
                ->whereDate('date', '>=', $today)
                ->whereNotNull('min_stay')
                ->orderBy('date')
                ->value('min_stay');

            $out[(string) $roomType->id] = [
                'rate_minor' => $rateMinor,
                'display' => $display,
                'weekend_pct' => $weekendPct,
                'min_stay' => $minStay !== null ? (int) $minStay : null,
            ];
        }

        return $out;
    }

    /**
     * "Earned this month" — the owner's REAL net over the month (Σ signedMinor of the owner-scoped ledger
     * rows dated in the month), in the display currency. A month with no rows shows a clean zero, never a
     * fabricated figure. Owner-scoped by OwnerLedgerEntry's BelongsToOwner (#28).
     *
     * @return array{display: string, minor: int}
     */
    private function earnedThisMonth(Carbon $month, string $currency): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $start->copy()->addMonth();

        $entries = OwnerLedgerEntry::query()
            ->whereDate('service_date', '>=', $start->toDateString())
            ->whereDate('service_date', '<', $end->toDateString())
            ->get();

        $net = OwnerLedgerAttribution::netPerCurrency($entries)[$currency] ?? 0;

        return ['display' => $this->money($net, $currency), 'minor' => $net];
    }

    /**
     * The best-performing unit this month, from the SHARED ranking (real gross revenue). null → an honest
     * "no best performer yet" when nothing has earned (#39 — never a fabricated winner).
     *
     * @param  Collection<int, Unit>  $units
     * @param  array<string, array{gross_minor: int, occupancy_pct: int, nights: int}>  $perUnit
     * @return array<string, mixed>|null
     */
    private function bestPerformer(Collection $units, array $perUnit, ?string $bestUnitId, Carbon $month, string $currency): ?array
    {
        if ($bestUnitId === null) {
            return null;
        }

        $unit = $units->firstWhere('id', $bestUnitId);
        if (! $unit instanceof Unit) {
            return null;
        }

        $m = $perUnit[$bestUnitId];

        return [
            'name' => (string) ($unit->name ?? $unit->code),
            'occupancy_pct' => $m['occupancy_pct'],
            'nights' => $m['nights'],
            'revenue' => $this->money($m['gross_minor'], $currency),
            'count' => $units->count(),
            'month' => $month->translatedFormat('F Y'),
        ];
    }

    /**
     * "Needs your attention" — REAL items only, via the ONE shared {@see OwnerAttention} feed (extracted so
     * this screen and the owner Overview cannot drift on what needs the owner). Owner-resolved via
     * OwnerContext (#28); no real item → the view shows an honest "caught up".
     *
     * @return list<array{tone: string, title: string, body: string, cta: array{label: string, route: string}}>
     */
    private function attentionItems(): array
    {
        return OwnerAttention::items(app(OwnerContext::class));
    }
}
