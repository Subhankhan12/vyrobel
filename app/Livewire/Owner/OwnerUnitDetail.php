<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\ManagementAgreement;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Scopes\OwnerScope;
use App\Models\Unit;
use App\Services\AvailabilityService;
use App\Services\OwnerPortalService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Owner\OwnerOccupancy;
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
 * OMITTED ENTIRELY — rendered NOWHERE, not even disabled, because each would imply state with no schema
 * behind it (this gate's standing order; precedent: the owner approve/query workflow in Sub-gate 1):
 *   • Owner-editable pricing (Nightly rate / Weekend uplift / Minimum stay + Save) — there is no owner
 *     write path, and owner-editable rates are an explicit product-policy deferral. Verified: `units` has
 *     NO rate/fee column at all, so the design's €140 base rate + €45 cleaning fee are literals.
 *   • Day-blocking ("Tap a day to block") — AssignmentService::blockInventory exists but is NOT wired for
 *     owner use; an owner inventory-write path is a product decision, out of scope for a presentation
 *     gate. The calendar is READ-ONLY, and it renders Booked / No booking — never the design's "Blocked"
 *     split, which it fabricates from a seasonal blockShare constant.
 *   • Live/Snoozed toggle — a write to units.is_active with product implications. The REAL state renders
 *     read-only; the TOGGLE does not exist.
 *   • Calendar sync / "Sync calendars" / "Last synced" — no owner-facing sync backend.
 *   • Reviews & rating (score, stars, the Cleanliness/Accuracy/Check-in/Location/Value bars, review
 *     text) — NO schema at all.
 *   • Cleaning schedule / cleaner name / rating / "Schedule a cleaning" / "Message" — no owner-facing
 *     housekeeping surface and no cleaner ratings. `units.housekeeping_status` is a REAL column but is
 *     deliberately not surfaced: it is an operator-ops signal (clean/dirty/inspected), not owner-facing.
 *   • Guest messages panel + composer + quick replies — no owner messaging surface, and it would render
 *     guest PII.
 *   • 6-month revenue trend bars + the 12-month/multi-year occupancy heatmap with a year stepper —
 *     Tier-2 Analytics; worse, the design FABRICATES prior years client-side (×0.9 for 2024, ×0.8 for
 *     2023, plus a hard-coded jitter array). Fabricated history is never shipped.
 *   • Rank / "Best performer" / "#N of 3" — a cross-unit comparison already deferred on My Units.
 *   • "Direct bookings saved you €X in commission" — the design computes it as directRev × 0.15, a
 *     fabricated assumption about OTA commission.
 *   • "Channel mix" — the design splits it by Booking.com / Airbnb. The OTA BRAND IS NOT MODELLED:
 *     `reservations.source` is direct|walk_in|ota|corporate|group|agent|manual, so a brand can never be
 *     rendered. Only the real source enum appears, per stay.
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

        return view('livewire.owner.unit-detail', [
            'viewer' => $this->viewer(),
            'unit' => $this->unitFacts($unit),
            'occupancy' => $occupancy,
            'month' => [
                'label' => $month->format('F Y'),
                'first_weekday' => $month->copy()->startOfMonth()->dayOfWeekIso,   // 1=Mon … 7=Sun
                'prev' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
                'next' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            ],
            'attribution' => OwnerLedgerAttribution::breakdown($entries),
            'bookings' => $this->bookings($unit, $monthStart, $monthEnd),
            'commission' => $this->commission($unit),
            'statements' => $this->statements($entries),
            'unstatemented' => $entries->filter(fn (OwnerLedgerEntry $e): bool => $e->statement_id === null)->count(),
        ]);
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
            'room' => 'Room',
            'apartment' => 'Apartment',
            'villa' => 'Villa',
            'dorm_bed' => 'Dorm bed',
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
            'out_of_order' => ['label' => 'Out of order'],
            'out_of_service' => ['label' => 'Out of service'],
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
                'stay' => $checkIn->format('j M').' – '.$checkOut->format('j M Y'),
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
            'enquiry' => ['label' => 'Enquiry', 'tone' => 'draft'],
            'tentative' => ['label' => 'Tentative', 'tone' => 'pending'],
            'confirmed' => ['label' => 'Confirmed', 'tone' => 'paid'],
            'checked_in' => ['label' => 'Checked in', 'tone' => 'paid'],
            'checked_out' => ['label' => 'Checked out', 'tone' => 'draft'],
            'cancelled' => ['label' => 'Cancelled', 'tone' => 'draft'],
            'no_show' => ['label' => 'No-show', 'tone' => 'draft'],
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
            // The `decimal:2` cast already renders the stored scale, so the figure is never re-rounded.
            // Null → null → the view says "Not set", NEVER "0%".
            'display' => $pct === null ? null : ((string) $pct).'%',
            'scope_label' => $agreement->unit_id === null ? 'Portfolio-wide terms' : 'Terms for this unit',
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
                'period' => $statement->period_start->format('j M').' – '.$statement->period_end->format('j M Y'),
                'status_meta' => $this->statusMeta((string) $statement->status),
            ])
            ->values()
            ->all();
    }
}
