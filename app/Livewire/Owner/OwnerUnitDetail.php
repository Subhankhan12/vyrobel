<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\HousekeepingTask;
use App\Models\InventoryBlock;
use App\Models\ManagementAgreement;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\RateCalendar;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Restriction;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\OwnerBlockService;
use App\Services\OwnerPortalService;
use App\Services\OwnerRateService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Guest\GuestName;
use App\Support\Owner\OwnerCommissionRate;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Owner\OwnerOccupancy;
use App\Support\Owner\OwnerUnitAnalytics;
use App\Support\Pricing\NightlyRate;
use App\Support\Reviews\ReviewList;
use App\Support\Reviews\ReviewRating;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Unit Detail (Wave 1 · Sub-gate 3) — ONE of the owner's units: its real facts, an owner-scoped
 * occupancy calendar for a month, what that unit attributed to the owner's ledger in that month, and the
 * stays behind those figures. Styled from the catalogued "Owner Unit Detail" design and wired to LIVE
 * data. Presentation only: NO change to any service, schema, or guarantee — this screen writes NOTHING.
 *
 * ISOLATION — {@see Unit} carries BelongsToOwner, so {@see OwnerScope} constrains the resolve: a
 * cross-owner or operator-owned unit id 404s, and a non-linked user (active scope, empty owner set)
 * fails closed to `1 = 0` → 404. The id is a plain STRING prop resolved via findOrFail in render(), never
 * a route-model-bound typed prop — a typed `public Unit $unit` would make Livewire's ImplicitRouteBinding
 * call resolveRouteBinding() for us, resolving the model outside this component's control. Month paging is
 * a plain GET link (`?month=YYYY-MM`), never a wire-action: a wire-action re-renders via /livewire/update
 * and would bypass the route middleware that activates the owner scope (invariant #20).
 *
 * ⚠️ THE OCCUPANCY TRAP — occupancy here is THIS UNIT's, via the shared {@see OwnerOccupancy} derivation
 * (keyed by physical unit id). {@see OwnerPortalService::occupancy()} / {@see AvailabilityService} are
 * ROOM-TYPE-wide, and a room type is shared with hotel rooms, operator-owned units and OTHER owners'
 * units — reading occupancy that way would show this owner a number driven by units they do not own.
 * OwnerUnitDetailViewTest locks this: it books a non-owner unit in the SAME room type and asserts the
 * figure excludes it, so swapping in availabilityRange() fails the suite.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL      — name / code / room type / unit_kind / max_occupancy ("Sleeps") / is_active /
 *                 condition_status; the owner_ledger rows for THIS unit (entry_type, direction +
 *                 amount_minor, currency, statement_id); the stays (reference, dates, source, status —
 *                 the REAL enums only); the effective agreement's commission_pct.
 *   • DERIVED   — occupancy nights + pct (OwnerOccupancy, this unit only); the per-entry_type nets and
 *                 the month net (Σ signedMinor); each stay's attributed net (Σ signedMinor over that
 *                 reservation's rows ON THIS UNIT). All from real figures, reconciling to the cent.
 *   • CURRENCY  — buckets, nets and per-stay figures accumulate PER CURRENCY and render per currency.
 *                 Nothing is ever summed or converted across currencies (invariant #17 — no silent FX).
 *   • NULLS     — no commission on the effective agreement → "Not set", NEVER "0%". No effective
 *                 agreement at all → the card is not rendered. A stay with no ledger rows yet → "Not yet
 *                 attributed", never a zero dressed up as a figure.
 *   • GUEST PII — NO guest name/email/phone anywhere. The guest relation is never loaded (locked owner
 *                precedent); the stays table identifies a booking by its REAL `reference` only.
 *
 * WIRED REAL in the exact-match pass (#65) — the Wave-1 refusals below were STALE; the backends shipped
 * later (My Units #55, Block Calendar #60, Phase-5 reviews, the owner↔operator relay #55), re-verified here:
 *   • Owner pricing — the REAL current nightly rate + minimum stay ({@see pricing()}), editable via
 *     {@see OwnerRateService} ONLY when the owner owns the room type exclusively (honest read-only when
 *     shared; a real controller write to `owner.rates.apply`). WEEKEND UPLIFT is now REAL (Wave B, below).
 *   • Day-blocking — the calendar now renders booked / blocked / open ({@see calendarDays()}) and blocking
 *     goes through the certified {@see OwnerBlockService} (`owner.calendar.block`), the SAME path as the
 *     Block Calendar; non-overlap enforced. Guest data name-only / counts-only (#28).
 *   • Reviews & rating — the derived overall rating + the real review list ({@see reviews()}; byline is
 *     first name + initial only, #28). Category bars omitted (not modelled).
 *   • Guest messages → the owner↔operator RELAY (`owner.messages.start`) — name-free (#28), NEVER a
 *     guest thread and NEVER owner→guest. Rendered in the blade as an honest "message your management team".
 *
 * WIRED REAL in Wave B (owner completeness sweep · GATE 2):
 *   • WEEKEND UPLIFT — now REAL ({@see pricing()} reads the plan's `weekend_uplift_pct`; the editor writes it via
 *     the Wave-A `OwnerRateService::setRateWeekendAware`), consistent with My Units. No longer "not available".
 *   • RICHER DAY-DETAIL — {@see dayDetails()} attaches the stay (guest NAME-only #28 via {@see GuestName}, dates,
 *     owner net) to a booked day + the reason to a blocked day, for the calendar popover.
 *   • CLEANING SCHEDULE — {@see cleanings()} shows the unit's real {@see HousekeepingTask} rows READ-ONLY (date /
 *     type / status); the owner sees but does not manage (honest); never a fabricated cleaner (#39).
 *
 * WIRED REAL in Wave C (owner completeness sweep · GATE 3):
 *   • PAUSE / RESUME TOGGLE — now REAL. The listing card writes `units.is_active` via the owner-scoped,
 *     CAP_ACTIONS-gated `owner.units.toggle-status` (App\Services\OwnerUnitStatusService); a pause removes
 *     the unit's capacity through the certified availability recompute, so it GENUINELY stops taking new
 *     bookings (mutation-proven in tests/Feature/OwnerUnitStatusTest.php), not a label.
 *
 * OMITTED / HONEST — still rendered as an honest state, never fabricated (#39):
 *   • Calendar SYNC / "Sync calendars" — no owner-facing sync backend (account-gated Channex); honest.
 *   • Per-unit PAYOUT TIMELINE — payouts are per-STATEMENT/per-OWNER, so a per-unit timeline is a fabricated
 *     attribution; the rail links the real Payouts instead.
 *   • "Direct bookings saved you €X in commission" — a fabricated directRev × 0.15; still omitted.
 *
 * BUILT REAL in Batch B (GATE 2) — with real aggregates, NEVER the design's fabricated prior years:
 *   • The 6-month NET revenue trend ({@see revenueTrend()}) — Σ signedMinor of this unit's ledger rows per
 *     month, in the display currency; an empty month is an honest zero.
 *   • The trailing-12-month occupancy trend ({@see occupancyTrend()}) — the SHARED {@see OwnerOccupancy}
 *     per month (unit-scoped), with the real average + peak. NOT a client-side ×0.9/×0.8 fabrication.
 *   • The "Best performer" badge ({@see bestBadge()}) — the real cross-unit ranking (shared
 *     {@see OwnerUnitAnalytics}); shown only when this unit genuinely leads and the owner has >1 unit.
 *   • "Channel mix" ({@see channelMix()}) — this month's room-nights by the REAL `reservations.source`
 *     enum (direct|walk_in|ota|corporate|group|agent|manual), never an OTA brand (the model has none).
 *   • Per-unit payout timeline — payouts are per-STATEMENT/per-OWNER, never per-unit; a per-unit payout
 *     timeline would be a fabricated attribution. The rail links to the real Payouts screen instead.
 *   • "Share listing" · breadcrumbs to unbuilt screens · "All bookings" (no owner bookings list exists —
 *     Wave 2).
 *
 * Honest compromises (documented rather than papered over):
 *   • A stay's "Your net" spans the WHOLE stay, not just the part inside the month, so it is a clean
 *     per-booking fact rather than a clipped one. It therefore does NOT tie to the month net (which also
 *     carries rows with no reservation — expenses, management fees). Both are labelled for what they are.
 *   • Occupancy nights CLIP to the month (OwnerOccupancy's rule); a stay's "Nights" is the stay's own
 *     full length. Different labels, both true — they need not agree for a straddling stay.
 *   • A stay whose reservation is soft-deleted cannot be described honestly (no reference/source/status),
 *     so those cells render "—" rather than an invented value. The line's real dates still render.
 */
#[Layout('components.layouts.owner')]
class OwnerUnitDetail extends Component
{
    use FormatsOwnerData;

    /**
     * The unit id, straight off the `{unit}` route param — a plain STRING, never a typed model prop.
     * Livewire fills it in mount(); the model is resolved owner+tenant-scoped in render() (see the class
     * docblock). Mirrors {@see OwnerStatementView}, the certified precedent for a scoped detail screen.
     *
     * NB the `Id` suffix is load-bearing, not styling: Livewire injects public props into the view
     * scope, so a prop named `$unit` would SHADOW the `'unit' => [...]` array this component passes
     * from render(), and the view's `$unit['name']` would fatal on a string offset.
     */
    public string $unitId;

    public function mount(string $unit): void
    {
        $this->unitId = $unit;
    }

    public function render(): View
    {
        // Owner + tenant scoped: a cross-owner / operator-owned / unknown id resolves to nothing → 404.
        // A non-linked user hits OwnerScope's fail-closed `1 = 0` and also 404s.
        $unit = Unit::query()->with('roomType')->findOrFail($this->unitId);

        $month = $this->resolveMonth($unit);
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->addMonth();   // exclusive upper bound, half-open [start, end)

        // THIS unit's occupancy — never the room-type-wide read (see the class docblock).
        $occupancy = OwnerOccupancy::forMonth([(string) $unit->id], $month);

        $entries = $this->ledgerForMonth($unit, $monthStart, $monthEnd);
        $currency = (string) ($unit->property->currency ?? 'EUR');

        return view('livewire.owner.unit-detail', [
            'viewer' => $this->viewer(),
            // ⚠️ The nightly-rate label and the weekend-uplift preview hardcoded '€' while every FIGURE on the
            // page was already built in `$currency` (the unit's property currency, resolved above).
            'currencySymbol' => $this->currencySymbol($currency),
            'unit' => $this->unitFacts($unit),
            'occupancy' => $occupancy,
            'month' => [
                'label' => $month->translatedFormat('F Y'),
                'first_weekday' => $month->copy()->startOfMonth()->dayOfWeekIso,   // 1=Mon … 7=Sun
                'prev' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
                'next' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            ],
            'attribution' => OwnerLedgerAttribution::breakdown($entries),
            'bookings' => $this->bookings($unit, $monthStart, $monthEnd),
            'commission' => $this->commission($unit),
            'statements' => $this->statements($entries),
            'unstatemented' => $entries->filter(fn (OwnerLedgerEntry $e): bool => $e->statement_id === null)->count(),
            // Batch B — REAL per-unit analytics: a 6-month NET revenue trend, a trailing-12-month occupancy
            // trend, this unit's channel mix, and a best-performer badge (all deterministic aggregates, #39).
            'revenueTrend' => $this->revenueTrend($unit, $month, $currency),
            'occupancyTrend' => $this->occupancyTrend($unit, $month),
            'channelMix' => $this->channelMix($unit, $month, $currency),
            'bestBadge' => $this->bestBadge($unit, $month, $currency),
            // Exact-match pass (#65) — stale refusals now REAL (the backends shipped after Wave 1):
            'calendar' => $this->calendarDays($unit, $monthStart, $monthEnd),   // booked/blocked/open + counts
            'dayDetails' => $this->dayDetails($unit, $monthStart, $monthEnd, $currency),   // Wave-B richer day-detail
            'pricing' => $this->pricing($unit, $currency),                       // nightly + min-stay + weekend uplift
            'reviews' => $this->reviews($unit),                                  // ReviewRating + ReviewList
            'cleanings' => $this->cleanings($unit),                              // Wave-B real HousekeepingTask (read-only)
            'blockReasons' => OwnerBlockService::REASONS,
            'blockReasonLabels' => OwnerBlockService::reasonLabels(),   // the ONE definition, keyed by the stored code
            'weekdays' => $this->weekdayHeads(),
        ]);
    }

    /**
     * The month's calendar day-states — the SAME derivation the Block Calendar uses (past → booked → blocked →
     * open), so the two surfaces can never disagree. Booked from active {@see ReservationUnit} lines, blocked
     * from {@see InventoryBlock} rows, both on THIS (owner-scoped) unit. Guest data name-only elsewhere; the
     * calendar carries COUNTS only (#28).
     *
     * @return array{first_weekday: int, days: list<array{day: int, date: string, state: string, block_id: ?string}>, booked: int, blocked: int, open: int}
     */
    private function calendarDays(Unit $unit, Carbon $monthStart, Carbon $monthEnd): array
    {
        $today = Carbon::today();

        // Booked nights (a night is [check_in, check_out)).
        $booked = [];
        foreach (ReservationUnit::query()
            ->where('unit_id', $unit->id)->where('status', 'active')
            ->whereDate('check_in', '<', $monthEnd->toDateString())
            ->whereDate('check_out', '>', $monthStart->toDateString())
            ->get(['check_in', 'check_out']) as $line) {
            $c = Carbon::parse($line->check_in);
            $end = Carbon::parse($line->check_out);
            for (; $c->lt($end); $c->addDay()) {
                $booked[$c->toDateString()] = true;
            }
        }

        // Blocked nights (half-open [start_date, end_date)); keep a block id for the release affordance.
        $blocked = [];
        foreach (InventoryBlock::query()
            ->where('unit_id', $unit->id)
            ->whereDate('start_date', '<', $monthEnd->toDateString())
            ->whereDate('end_date', '>', $monthStart->toDateString())
            ->get(['id', 'start_date', 'end_date']) as $block) {
            $c = Carbon::parse($block->start_date);
            $end = Carbon::parse($block->end_date);
            for (; $c->lt($end); $c->addDay()) {
                $blocked[$c->toDateString()] = (string) $block->id;
            }
        }

        $days = [];
        $bookedCount = $blockedCount = $openCount = 0;
        $cursor = $monthStart->copy();
        while ($cursor->lt($monthEnd)) {
            $key = $cursor->toDateString();
            $state = match (true) {
                $cursor->lt($today) => 'past',
                isset($booked[$key]) => 'booked',
                isset($blocked[$key]) => 'blocked',
                default => 'open',
            };
            match ($state) {
                'booked' => $bookedCount++,
                'blocked' => $blockedCount++,
                'open' => $openCount++,
                default => null,
            };
            $days[] = ['day' => (int) $cursor->day, 'date' => $key, 'state' => $state, 'block_id' => $blocked[$key] ?? null];
            $cursor->addDay();
        }

        return [
            'first_weekday' => $monthStart->copy()->dayOfWeekIso,
            'days' => $days,
            'booked' => $bookedCount,
            'blocked' => $blockedCount,
            'open' => $openCount,
        ];
    }

    /**
     * Wave-B RICHER DAY-DETAIL — a map `date → detail` for the calendar popover. A booked day carries the stay's
     * guest NAME-ONLY ({@see GuestName}, #28 — the same name-only rule the owner Block Calendar/Bookings already
     * use), reference, dates and the owner NET on this unit; a blocked day carries its reason. Open/past days
     * have no detail. Owner-scoped: reached only through this owner-scoped unit; NO guest email/phone ever (#28).
     *
     * @return array<string, array{type: string, guest?: string, reference?: ?string, stay?: string, nights?: int, nights_label?: string, net?: list<string>, reason?: string}>
     */
    private function dayDetails(Unit $unit, Carbon $monthStart, Carbon $monthEnd, string $currency): array
    {
        $out = [];

        $lines = ReservationUnit::query()
            ->where('unit_id', $unit->id)->where('status', 'active')
            ->whereDate('check_in', '<', $monthEnd->toDateString())
            ->whereDate('check_out', '>', $monthStart->toDateString())
            ->with('reservation.leadGuest')
            ->get();

        $nets = $this->netByReservation(
            $unit,
            $lines->map(fn (ReservationUnit $l): string => (string) $l->reservation_id)->unique()->values()->all(),
        );

        foreach ($lines as $line) {
            $reservation = $line->reservation;
            $checkIn = Carbon::parse($line->check_in);
            $checkOut = Carbon::parse($line->check_out);
            $detail = [
                'type' => 'booked',
                'guest' => $reservation instanceof Reservation ? GuestName::display($reservation->leadGuest) : __('php/livewire/owner/owner_unit_detail.guest_fallback'),
                'reference' => $reservation instanceof Reservation ? (string) $reservation->reference : null,
                'stay' => __('php/livewire/owner/owner_unit_detail.date_range', ['start' => $checkIn->translatedFormat('j M'), 'end' => $checkOut->translatedFormat('j M Y')]),
                'nights' => (int) $checkIn->diffInDays($checkOut),
                'nights_label' => trans_choice('php/livewire/owner/owner_unit_detail.nights', (int) $checkIn->diffInDays($checkOut), ['count' => (int) $checkIn->diffInDays($checkOut)]),
                'net' => $nets[(string) $line->reservation_id] ?? [],
            ];

            // Paint the detail onto each in-month night of the stay (half-open).
            $cursor = $checkIn->greaterThan($monthStart) ? $checkIn->copy() : $monthStart->copy();
            $stop = $checkOut->lessThan($monthEnd) ? $checkOut : $monthEnd;
            for (; $cursor->lt($stop); $cursor->addDay()) {
                $out[$cursor->toDateString()] = $detail;
            }
        }

        // Blocked days — the reason from the InventoryBlock (a real owner-set/managed block).
        foreach (InventoryBlock::query()
            ->where('unit_id', $unit->id)
            ->whereDate('start_date', '<', $monthEnd->toDateString())
            ->whereDate('end_date', '>', $monthStart->toDateString())
            ->get(['start_date', 'end_date', 'reason']) as $block) {
            $cursor = Carbon::parse($block->start_date);
            $end = Carbon::parse($block->end_date);
            for (; $cursor->lt($end); $cursor->addDay()) {
                $key = $cursor->toDateString();
                if (! isset($out[$key])) {
                    $out[$key] = ['type' => 'blocked', 'reason' => $this->blockReasonLabel((string) ($block->reason ?? ''))];
                }
            }
        }

        return $out;
    }

    /**
     * A human label for an InventoryBlock reason (the real OwnerBlockService reasons + a plain fallback). ⚠️ REASONS is a
     * numbered list, so the old `REASONS[$reason]` lookup never matched a code — it now asks the service by code.
     */
    private function blockReasonLabel(string $reason): string
    {
        if (in_array($reason, OwnerBlockService::REASONS, true)) {
            return OwnerBlockService::reasonLabel($reason);
        }

        return match ($reason) {
            '' => __('php/livewire/owner/owner_unit_detail.block_reason_none'),
            'maintenance' => __('php/livewire/owner/owner_unit_detail.block_reason_maintenance'),
            'owner' => __('php/livewire/owner/owner_unit_detail.block_reason_owner'),
            'manual' => __('php/livewire/owner/owner_unit_detail.block_reason_manual'),
            'overbooking_protection' => __('php/livewire/owner/owner_unit_detail.block_reason_overbooking_protection'),
            default => ucfirst(str_replace('_', ' ', $reason)),
        };
    }

    /**
     * The calendar's weekday heads, Monday first, in the reader's language and upper case ("MON" … "SUN" in English).
     *
     * @return list<string>
     */
    private function weekdayHeads(): array
    {
        $monday = Carbon::parse('2024-01-01');   // a Monday

        return array_map(fn (int $i): string => mb_strtoupper($monday->copy()->addDays($i)->translatedFormat('D')), range(0, 6));
    }

    /**
     * Wave-B CLEANING SCHEDULE (READ-ONLY) — the unit's real {@see HousekeepingTask} rows (departure/stayover/
     * deep/arrival_prep, generated operator-side on checkout). The owner SEES them (date + type + status) but does
     * NOT manage them — managing housekeeping is the operator's job (honest). Owner-scoped: the unit already passed
     * OwnerScope and the tasks are pinned to its unit_id; no guest data (a cleaning carries none, #28). Recent +
     * upcoming, newest-relevant first; an honest empty when the unit has no cleanings on record.
     *
     * @return array{has_data: bool, rows: list<array{date: string, type: string, status: string, tone: string, upcoming: bool}>}
     */
    private function cleanings(Unit $unit): array
    {
        $today = Carbon::today();

        $tasks = HousekeepingTask::query()
            ->where('unit_id', $unit->id)
            ->whereDate('date', '>=', $today->copy()->subDays(30)->toDateString())
            ->orderBy('date')
            ->limit(12)
            ->get(['date', 'type', 'status']);

        $rows = $tasks->map(fn (HousekeepingTask $t): array => [
            'date' => Carbon::parse($t->date)->translatedFormat('j M Y'),
            'type' => $this->cleaningTypeLabel((string) $t->type),
            'status' => $this->cleaningStatusMeta((string) $t->status)['label'],
            'tone' => $this->cleaningStatusMeta((string) $t->status)['tone'],
            'upcoming' => Carbon::parse($t->date)->greaterThanOrEqualTo($today),
        ])->values()->all();

        return ['has_data' => $rows !== [], 'rows' => $rows];
    }

    /** housekeeping_tasks.type enum ∈ departure|stayover|deep|arrival_prep. */
    private function cleaningTypeLabel(string $type): string
    {
        return match ($type) {
            'departure' => __('php/livewire/owner/owner_unit_detail.cleaning_departure'),
            'stayover' => __('php/livewire/owner/owner_unit_detail.cleaning_stayover'),
            'deep' => __('php/livewire/owner/owner_unit_detail.cleaning_deep'),
            'arrival_prep' => __('php/livewire/owner/owner_unit_detail.cleaning_arrival_prep'),
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    /**
     * housekeeping_tasks.status enum ∈ pending|in_progress|clean|inspected.
     *
     * @return array{label: string, tone: string}
     */
    private function cleaningStatusMeta(string $status): array
    {
        return match ($status) {
            'pending' => ['label' => __('php/livewire/owner/owner_unit_detail.cleaning_status_pending'), 'tone' => 'pending'],
            'in_progress' => ['label' => __('php/livewire/owner/owner_unit_detail.cleaning_status_in_progress'), 'tone' => 'pending'],
            'clean' => ['label' => __('php/livewire/owner/owner_unit_detail.cleaning_status_clean'), 'tone' => 'paid'],
            'inspected' => ['label' => __('php/livewire/owner/owner_unit_detail.cleaning_status_inspected'), 'tone' => 'paid'],
            default => ['label' => ucfirst(str_replace('_', ' ', $status)), 'tone' => 'draft'],
        };
    }

    /**
     * The unit's REAL pricing — the current nightly rate + minimum stay for its room type, editable ONLY when
     * the owner owns that room type EXCLUSIVELY (via {@see OwnerRateService::editableRoomTypes()} — the SAME
     * rule as My Units; a shared room type is honestly read-only, never a fake save). Weekend uplift is NOT
     * modelled (no column) → the caller shows an honest "not available", never a fabricated figure (#39).
     *
     * @return array{editable: bool, room_type_id: ?string, rate_display: ?string, rate_units: ?int, min_stay: ?int, weekend_pct: float|null}
     */
    private function pricing(Unit $unit, string $currency): array
    {
        $roomTypeId = (string) $unit->room_type_id;
        $editable = app(OwnerRateService::class)->editableRoomTypes()
            ->contains(fn ($rt): bool => (string) $rt->id === $roomTypeId);

        $rateDisplay = null;
        $rateUnits = null;
        $minStay = null;
        $weekendPct = null;

        $plan = $unit->roomType->primaryRatePlan();
        if ($plan !== null) {
            $today = Carbon::today()->toDateString();
            $row = RateCalendar::query()
                ->where('rate_plan_id', $plan->id)->whereDate('date', '>=', $today)
                ->orderBy('date')->first();
            if ($row !== null) {
                // ⚠️ D18-5 — THIS PAGE IS ABOUT ONE UNIT, so the rate it shows must be THAT unit's rate. The
                // row above finds the next priced night for the room TYPE; the resolver then answers for this
                // unit on that night — its own price if it carries one, the type price otherwise. Showing the
                // type figure for a unit charged its override would contradict the charge on the very page
                // that names the unit (§N.8f — one resolver, never a second read).
                $resolvedMinor = NightlyRate::forUnit(
                    (string) $plan->id,
                    (string) $unit->getKey(),
                    CarbonImmutable::parse($row->date)->toDateString(),
                ) ?? (int) $row->price_minor;

                $rateDisplay = $this->money($resolvedMinor, (string) $row->currency);
                $rateUnits = (int) round($resolvedMinor / 100);
            }

            // Owner-set restrictions are keyed by room_type_id (OwnerRateService::apply), rate_plan_id null.
            $restriction = Restriction::query()
                ->where('room_type_id', $unit->room_type_id)->whereDate('date', '>=', $today)
                ->whereNotNull('min_stay')->orderBy('date')->first();
            if ($restriction !== null) {
                $minStay = (int) $restriction->min_stay;
            }

            // Wave-B: the real weekend-uplift POLICY on the plan (Wave-A weekend_uplift_pct), for the editor prefill.
            $weekendPct = $plan->weekend_uplift_pct !== null ? (float) $plan->weekend_uplift_pct : null;
        }

        return [
            'editable' => $editable,
            'room_type_id' => $roomTypeId,
            'rate_display' => $rateDisplay,
            'rate_units' => $rateUnits,
            'min_stay' => $minStay,
            'weekend_pct' => $weekendPct,
        ];
    }

    /**
     * This unit's REAL reviews — the derived overall rating (published + public only, via the shared
     * {@see ReviewRating}) and the review list (via {@see ReviewList}, the guest byline is first name + initial
     * only, #28). Honest empty when the unit has no public reviews; category scores are NOT modelled → omitted.
     *
     * @return array{has_reviews: bool, average: ?float, count: int, list: list<array<string, mixed>>}
     */
    private function reviews(Unit $unit): array
    {
        $rating = ReviewRating::forUnit((string) $unit->id);

        return [
            'has_reviews' => (bool) $rating['has_reviews'],
            'average' => $rating['average'] ?? null,
            'count' => (int) $rating['count'],
            'list' => array_slice(ReviewList::forUnits([(string) $unit->id]), 0, 4),
        ];
    }

    /**
     * The unit's NET "to you" over the last 6 months (Batch B) — one bar per month, each = Σ signedMinor of
     * this unit's ledger rows dated in that month, in the display currency. REAL (the ledger is the source);
     * an empty month is an honest zero. Bar height is relative to the largest positive month.
     *
     * @return array{has_data: bool, bars: list<array{label: string, display: string, pct: int}>}
     */
    private function revenueTrend(Unit $unit, Carbon $month, string $currency): array
    {
        $start = $month->copy()->startOfMonth()->subMonths(5);
        $rows = [];
        $maxPositive = 0;

        for ($i = 0; $i < 6; $i++) {
            $mStart = $start->copy()->addMonths($i);
            $mEnd = $mStart->copy()->addMonth();

            $entries = OwnerLedgerEntry::query()
                ->where('unit_id', $unit->id)
                ->whereDate('service_date', '>=', $mStart->toDateString())
                ->whereDate('service_date', '<', $mEnd->toDateString())
                ->get();

            $net = OwnerLedgerAttribution::netPerCurrency($entries)[$currency] ?? 0;
            $maxPositive = max($maxPositive, $net);
            $rows[] = ['label' => $mStart->translatedFormat('M'), 'net' => $net];
        }

        $bars = array_map(fn (array $r): array => [
            'label' => $r['label'],
            'display' => $this->money($r['net'], $currency),
            'pct' => $maxPositive > 0 && $r['net'] > 0 ? (int) round($r['net'] / $maxPositive * 100) : 0,
        ], $rows);

        return ['has_data' => $maxPositive > 0, 'bars' => $bars];
    }

    /**
     * The unit's occupancy over the trailing 12 months (Batch B) — one bar per month via the SHARED
     * {@see OwnerOccupancy} (this unit only), plus the real average and peak. REAL; a month with no
     * bookings is an honest 0%.
     *
     * @return array{has_data: bool, avg: int, peak_label: string, peak_pct: int, bars: list<array{label: string, display: string, pct: int}>}
     */
    private function occupancyTrend(Unit $unit, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth()->subMonths(11);
        $bars = [];
        $sum = 0;
        $peakLabel = '';
        $peakPct = -1;

        for ($i = 0; $i < 12; $i++) {
            $mStart = $start->copy()->addMonths($i);
            $pct = OwnerOccupancy::forMonth([(string) $unit->id], $mStart)['pct'];
            $sum += $pct;
            if ($pct > $peakPct) {
                $peakPct = $pct;
                $peakLabel = $mStart->translatedFormat('M');
            }
            $bars[] = ['label' => $mStart->translatedFormat('M'), 'display' => $pct.'%', 'pct' => $pct];
        }

        return [
            'has_data' => $peakPct > 0,
            'avg' => (int) round($sum / 12),
            'peak_label' => $peakLabel,
            'peak_pct' => max(0, $peakPct),
            'bars' => $bars,
        ];
    }

    /**
     * The unit's channel mix this month by ROOM-NIGHTS (Batch B) — the real `reservations.source` enum via
     * the shared {@see PerformanceMetrics::forOwnerUnits} `sourceNights`. NOT an OTA brand (the model carries
     * no brand — #39): the honest source (Direct / OTA / Corporate …) with its real nights share.
     *
     * @return array{has_data: bool, total: int, rows: list<array{label: string, nights: int, pct: int}>}
     */
    private function channelMix(Unit $unit, Carbon $month, string $currency): array
    {
        $start = CarbonImmutable::parse($month->copy()->startOfMonth()->toDateString());
        $metrics = PerformanceMetrics::forOwnerUnits([(string) $unit->id], $start, $start->addMonth(), $currency);

        $sourceNights = $metrics->sourceNights;
        $total = array_sum($sourceNights);
        if ($total === 0) {
            return ['has_data' => false, 'total' => 0, 'rows' => []];
        }

        arsort($sourceNights);
        $rows = [];
        foreach ($sourceNights as $source => $nights) {
            $rows[] = [
                'label' => $this->sourceLabel((string) $source),
                'nights' => (int) $nights,
                'pct' => (int) round($nights / $total * 100),
            ];
        }

        return ['has_data' => true, 'total' => (int) $total, 'rows' => $rows];
    }

    /**
     * Whether THIS unit is the owner's best performer this month (Batch B) — via the SHARED
     * {@see OwnerUnitAnalytics} ranking across the owner's OWN units. null when the owner has a single unit
     * (a "best of 1" is meaningless) or when this unit is not the leader. No revenue anywhere → no badge.
     *
     * @return array{count: int, month: string}|null
     */
    private function bestBadge(Unit $unit, Carbon $month, string $currency): ?array
    {
        $units = app(OwnerPortalService::class)->units();
        if ($units->count() < 2) {
            return null;
        }

        $unitIds = array_map('strval', $units->pluck('id')->all());
        $perUnit = OwnerUnitAnalytics::perUnitMonth($unitIds, $month, $currency);

        if (OwnerUnitAnalytics::bestUnitId($perUnit) !== (string) $unit->id) {
            return null;
        }

        return ['count' => $units->count(), 'month' => $month->translatedFormat('F Y')];
    }

    /**
     * The calendar month: `?month=YYYY-MM` if valid, else the month of THIS unit's most recent active
     * booking, else the current month. Mirrors {@see OwnerMyUnits::resolveMonth()} exactly, narrowed to
     * one unit. A GET param (never a wire-action) so the owner middleware re-runs on every navigation.
     */
    private function resolveMonth(Unit $unit): Carbon
    {
        $param = request()->query('month');
        if (is_string($param) && preg_match('/^\d{4}-\d{2}$/', $param) === 1) {
            return Carbon::parse($param.'-01')->startOfMonth();
        }

        $latest = ReservationUnit::query()
            ->where('unit_id', $unit->id)
            ->where('status', 'active')
            ->max('check_in');

        return $latest !== null ? Carbon::parse($latest)->startOfMonth() : Carbon::now()->startOfMonth();
    }

    /**
     * THIS unit's ledger rows whose service_date falls in the month. `owner_ledger` is itself owner-scoped
     * (BelongsToOwner), and the unit_id filter pins it to a unit that already passed OwnerScope — so the
     * boundary holds twice over.
     *
     * @return Collection<int, OwnerLedgerEntry>
     */
    private function ledgerForMonth(Unit $unit, Carbon $monthStart, Carbon $monthEnd): Collection
    {
        return OwnerLedgerEntry::query()
            ->where('unit_id', $unit->id)
            ->whereDate('service_date', '>=', $monthStart->toDateString())
            ->whereDate('service_date', '<', $monthEnd->toDateString())
            ->orderBy('service_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * The unit's REAL columns. Nothing here is computed or defaulted: a unit with no `name` is titled by
     * its `code` (which is NOT NULL) rather than by an invented label.
     *
     * @return array<string, mixed>
     */
    private function unitFacts(Unit $unit): array
    {
        $name = (string) ($unit->name ?? '');
        $code = (string) $unit->code;

        return [
            'id' => (string) $unit->id,
            'name' => $name !== '' ? $name : $code,
            'code' => $code,
            'named' => $name !== '',   // false → the title IS the code; don't print it twice
            'type' => (string) $unit->roomType->name,
            'kind_label' => $this->kindLabel((string) $unit->unit_kind),
            'max_occupancy' => (int) $unit->max_occupancy,
            'active' => $unit->is_active !== false,
            'condition' => $this->conditionNotice((string) $unit->condition_status),
        ];
    }

    /** unit_kind enum ∈ room|apartment|villa|dorm_bed. The four arms ARE the enum. */
    private function kindLabel(string $kind): string
    {
        return match ($kind) {
            'room' => __('php/livewire/owner/owner_unit_detail.kind_room'),
            'apartment' => __('php/livewire/owner/owner_unit_detail.kind_apartment'),
            'villa' => __('php/livewire/owner/owner_unit_detail.kind_villa'),
            'dorm_bed' => __('php/livewire/owner/owner_unit_detail.kind_dorm_bed'),
            default => ucfirst(str_replace('_', ' ', $kind)),
        };
    }

    /**
     * condition_status enum ∈ in_service|out_of_order|out_of_service. `in_service` is the normal state and
     * says nothing worth a callout → null. The consequence stated is a CERTIFIED backend fact, not a
     * guess: {@see AvailabilityService::recompute()} counts BOTH out_of_order and out_of_service into the
     * availability `out_of_order` bucket (`available = total − sold − out_of_order − blocked`), so the
     * unit genuinely stops being offered. No cause, no ETA, no ticket link — none of that is on `units`.
     *
     * @return array{label: string}|null
     */
    private function conditionNotice(string $condition): ?array
    {
        return match ($condition) {
            'out_of_order' => ['label' => __('php/livewire/owner/owner_unit_detail.condition_out_of_order')],
            'out_of_service' => ['label' => __('php/livewire/owner/owner_unit_detail.condition_out_of_service')],
            default => null,
        };
    }

    /**
     * The stays on THIS unit that touch the month — active physical-unit lines only (an `overbooked` line
     * carries no unit_id and never occupied this unit), half-open [check_in, check_out) against the month.
     *
     * `reservation_units` / `reservations` carry the TENANT scope only, but every row here is reached
     * THROUGH the owner-scoped unit id, so the owner boundary holds. The guest relation is deliberately
     * never touched: owner surfaces show NO guest PII.
     *
     * @return list<array<string, mixed>>
     */
    private function bookings(Unit $unit, Carbon $monthStart, Carbon $monthEnd): array
    {
        $lines = ReservationUnit::query()
            ->where('unit_id', $unit->id)
            ->where('status', 'active')
            ->whereDate('check_in', '<', $monthEnd->toDateString())
            ->whereDate('check_out', '>', $monthStart->toDateString())
            ->with('reservation')
            ->orderBy('check_in')
            ->orderBy('id')
            ->get();

        $nets = $this->netByReservation(
            $unit,
            $lines->map(fn (ReservationUnit $line): string => (string) $line->reservation_id)->unique()->values()->all(),
        );

        return $lines->map(function (ReservationUnit $line) use ($nets): array {
            $reservation = $line->reservation;
            $checkIn = Carbon::parse($line->check_in);
            $checkOut = Carbon::parse($line->check_out);
            $real = $reservation instanceof Reservation;

            return [
                'reference' => $real ? (string) $reservation->reference : null,
                'stay' => __('php/livewire/owner/owner_unit_detail.date_range', ['start' => $checkIn->translatedFormat('j M'), 'end' => $checkOut->translatedFormat('j M Y')]),
                'nights' => (int) $checkIn->diffInDays($checkOut),
                'source_label' => $real ? $this->sourceLabel((string) $reservation->source) : null,
                'status_meta' => $real ? $this->reservationStatusMeta((string) $reservation->status) : null,
                'net_displays' => $nets[(string) $line->reservation_id] ?? [],
            ];
        })->values()->all();
    }

    /**
     * DERIVED: each stay's net attributed to the owner ON THIS UNIT = Σ signedMinor over the ledger rows
     * carrying that reservation_id AND this unit_id. Restricted to the unit so a multi-unit booking never
     * credits this unit with another unit's revenue. Kept per currency and returned as one display string
     * per currency — never a cross-currency sum (invariant #17).
     *
     * @param  list<string>  $reservationIds
     * @return array<string, list<string>>
     */
    private function netByReservation(Unit $unit, array $reservationIds): array
    {
        if ($reservationIds === []) {
            return [];
        }

        // Grouped by reservation, then handed to the ONE shared net rule per group so this per-stay figure
        // cannot drift from the owner Bookings list / Booking Detail (same discipline as OwnerOccupancy).
        return OwnerLedgerEntry::query()
            ->where('unit_id', $unit->id)
            ->whereIn('reservation_id', $reservationIds)
            ->get()
            ->groupBy(fn (OwnerLedgerEntry $row): string => (string) $row->reservation_id)
            ->map(fn (Collection $group): array => OwnerLedgerAttribution::netDisplays($group))
            ->all();
    }

    /**
     * reservations.status enum ∈ enquiry|tentative|confirmed|checked_in|checked_out|cancelled|no_show.
     * The seven arms ARE the enum — the design's Confirmed/Pending/Completed vocabulary does not exist and
     * is never rendered.
     *
     * @return array{label: string, tone: string}
     */
    private function reservationStatusMeta(string $status): array
    {
        return match ($status) {
            'enquiry' => ['label' => __('php/livewire/owner/owner_unit_detail.status_enquiry'), 'tone' => 'draft'],
            'tentative' => ['label' => __('php/livewire/owner/owner_unit_detail.status_tentative'), 'tone' => 'pending'],
            'confirmed' => ['label' => __('php/livewire/owner/owner_unit_detail.status_confirmed'), 'tone' => 'paid'],
            'checked_in' => ['label' => __('php/livewire/owner/owner_unit_detail.status_checked_in'), 'tone' => 'paid'],
            'checked_out' => ['label' => __('php/livewire/owner/owner_unit_detail.status_checked_out'), 'tone' => 'draft'],
            'cancelled' => ['label' => __('php/livewire/owner/owner_unit_detail.status_cancelled'), 'tone' => 'draft'],
            'no_show' => ['label' => __('php/livewire/owner/owner_unit_detail.status_no_show'), 'tone' => 'draft'],
            default => ['label' => ucfirst(str_replace('_', ' ', $status)), 'tone' => 'draft'],
        };
    }

    /**
     * The commission on the agreement governing THIS unit today.
     *
     * ⚠️ SECURITY — `management_agreements` does NOT carry BelongsToOwner, so there is no OwnerScope to
     * lean on (the model stays unscoped on purpose: OwnerAttributionService resolves agreements OUTSIDE
     * owner context, where a global scope would fail closed and break the certified backend). The boundary
     * is therefore applied EXPLICITLY, exactly as {@see OwnerAgreement} does it: an inactive context or an
     * empty owner set reads NOTHING, and the unit's owner must itself be one of the viewer's owners before
     * a single row is fetched.
     *
     * Effective-dating and most-specific-wins (a per-unit agreement beats the portfolio-wide one) come
     * from the model's CERTIFIED {@see ManagementAgreement::resolveFor()} — neither predicate is
     * re-implemented here.
     *
     * @return array{display: string|null, scope_label: string}|null null → render no card at all
     */
    private function commission(Unit $unit): ?array
    {
        $context = app(OwnerContext::class);

        if (! $context->active()) {
            return null;   // no owner boundary in force → do not read owner terms at all
        }

        $ownerIds = $context->ownerIds();

        if ($ownerIds === []) {
            return null;   // fail closed — a non-linked user reads nothing
        }

        $ownerId = (string) $unit->owner_id;

        if (! in_array($ownerId, $ownerIds, true)) {
            return null;   // belt-and-braces: never read terms for an owner this viewer is not linked to
        }

        $agreement = ManagementAgreement::resolveFor($ownerId, (string) $unit->id, Carbon::today());

        if (! $agreement instanceof ManagementAgreement) {
            return null;   // no agreement in effect today → no card, never an invented rate
        }

        $pct = $agreement->commission_pct;

        return [
            // ⚠️ Via the SHARED formatter (wireframe-parity WAVE 3) — the `decimal:2` cast renders "15.00",
            // so casting it straight to a string printed "15.00%" here while the Agreement page printed
            // "15%" for the same rate. Null → null → the view says "Not set", NEVER "0%".
            'display' => OwnerCommissionRate::display($pct),
            'scope_label' => $agreement->unit_id === null ? __('php/livewire/owner/owner_unit_detail.terms_portfolio') : __('php/livewire/owner/owner_unit_detail.terms_unit'),
        ];
    }

    /**
     * The REAL statements this month's rows for this unit landed on. `owner_statements` IS owner-scoped
     * (BelongsToOwner) and the ids come from the owner's own ledger rows, so this is doubly the owner's
     * own. Period + status are the model's real columns — a statement is never listed by an invented name.
     *
     * @param  Collection<int, OwnerLedgerEntry>  $entries
     * @return list<array{id: string, period: string, status_meta: array{label: string, tone: string}}>
     */
    private function statements(Collection $entries): array
    {
        $ids = $entries
            ->map(fn (OwnerLedgerEntry $entry): mixed => $entry->statement_id)
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return OwnerStatement::query()
            ->whereIn('id', $ids)
            ->orderBy('period_start')
            ->get()
            ->map(fn (OwnerStatement $statement): array => [
                'id' => (string) $statement->id,
                'period' => __('php/livewire/owner/owner_unit_detail.date_range', ['start' => $statement->period_start->translatedFormat('j M'), 'end' => $statement->period_end->translatedFormat('j M Y')]),
                'status_meta' => $this->statusMeta((string) $statement->status),
            ])
            ->values()
            ->all();
    }
}
