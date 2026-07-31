<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\FrontDesk;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Reservation;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Reservations LIST (Wave 2 · Sub-gate 1) — every booking in the tenant, one row each, filterable
 * by lifecycle stage and searchable by guest or reference. This component READS ONLY: it opens no folio,
 * assigns no unit and changes no status. Every row links to the already-built {@see ReservationDetail},
 * which is where the real writes live.
 *
 * ⚠️ TENANT-scoped, and that is the isolation-critical fact. {@see Reservation} is BelongsToOrganization, so
 * its global scope + SetTenantForWebRequest mean a foreign org's booking is not in the collection at all —
 * this file therefore adds no `->where('organization_id', …)` of its own (adding one would imply the scope
 * were optional). `/app` additionally sits behind EnsureBackOfficeAccess, so an owner-role user never
 * reaches this page at all (invariant #26). Both facts are proven by OperatorReservationsListTest.
 *
 * THE STAGE VOCABULARY IS NOT THIS FILE'S TO INVENT. Filtering, badging and the chip labels all come from
 * {@see ReservationStage} — `scope()` for the SQL, `for()`+`meta()` for the badge. That class exists
 * precisely so a chip cannot drift from the badge it filters on (its own test proves the two classify every
 * row identically), so no date/status predicate is re-written here. The design's "Completed" and "In house"
 * are NOT enum values — "Completed" is `checked_out`, "In house" is the STAGE of a `checked_in` row — and
 * they appear nowhere on this page.
 *
 * Honesty triage (invariant #19; standing order = OMIT AGGRESSIVELY):
 *   • REAL     — guest name (via `lead_guest_id`), `reference`, `check_in`/`check_out`, the unit assignment
 *                (or a genuinely-null one → "Unassigned"), `source` via the shared `sourceLabel()`, the real
 *                7-value `status` enum behind the stage, `total_minor` and `balance_minor` (both kept in
 *                sync with the append-only ledger by FolioService::recompute(), so the tiles, the rows and
 *                the folio reconcile to the cent for free).
 *   • DERIVED  — the stage badge; the chip counts (each one the REAL count of what clicking it shows, search
 *                included — a chip that promised a number the click could not deliver would be a lie);
 *                nights (`check_in`→`check_out`, half-open); the three stat tiles (below).
 *   • OMITTED (no backend — absent, not greyed out):
 *       – The design's PAUSED-BILLING BANNER. Always rendered, every word hard-coded, and its sub-line
 *         contains a literally empty `<span data-vyc-zero>`. `subscriptions.status` does contain 'paused'
 *         but NOTHING reads or writes it — no billing module, no gate. Shipping a banner that always says
 *         "paused" on a workspace that isn't would be a fabricated status. Already omitted by name in
 *         Wave 1 (OperatorOwnerDetail:101-102); not reopened here.
 *       – The BULK MESSAGE COMPOSER + "Send". The composer is real UI whose Send button calls NOTHING. A
 *         real send needs InboxService per recipient AND a channel — and every external channel is
 *         account-gated (ManualChannel is the only one that works). The whole modal is omitted rather than
 *         shipped as a button that fires a toast and sends no message.
 *       – The BULK SELECT + bulk bar. "Send messages" was the ONLY bulk action on the design; with the
 *         composer omitted there is no bulk backend left to reach, so a select mode would select rows for
 *         nothing.
 *       – The MONTH PICKER. Zero handlers in the design, and its "Sep 2025" label contradicts the tiles it
 *         sits beside ("THIS MONTH"). The tiles here say "this month" and mean it; a picker that scoped
 *         nothing would reintroduce exactly that contradiction.
 *       – SORT CONTROLS. The design has none (its headers are plain spans); the ordering below is stated in
 *         the footer instead of implied by chevrons that would not work.
 *       – The "Refunded" row badge. The design reads it from localStorage written by a sibling design. As of
 *         Phase 0.2 `payment_status='refunded'` IS a real, written state (a fully-refunded stay —
 *         FolioService::paymentStatus()), so the value is no longer dead; wiring the list badge to it is a
 *         follow-up UI gate, so no badge is shown HERE yet rather than one this screen does not yet compute.
 *   • SOON     — "New reservation": a separate Wave 4 design with no route. Rendered disabled, matching the
 *                shipped ReservationRack / UnitsList precedent — never a link to nowhere.
 *   • PII      — the guest NAME only. `guests.email`/`phone` are encrypted casts on TEXT columns and this is
 *                NOT the contact surface (the FrontDesk precedent shows names alone; Guests CRM is the only
 *                contact-visible surface). They are never rendered here, and — see search() — never queried:
 *                encrypted text is not indexed by value, so a LIKE over it would silently match nothing.
 *
 * DECISIONS TAKEN HERE (both were ambiguous in the design, so they are stated rather than guessed):
 *   1. ORDERING — `check_in` DESC, then `id` DESC. The design's footer claims "most recent first" and never
 *      implements it; "recent" by `booked_at` is unusable as a sole key because that column is NULLABLE, so
 *      null-booked rows would scatter. `check_in` is NOT NULL on every row and is the same column the stage
 *      filter and the tiles key on — so the order, the chips and the tiles all agree about which date this
 *      page is about. `id` is a ULID (lexicographically time-ordered) and unique, so it breaks same-day ties
 *      by newest-created and gives a TOTAL order — the render is deterministic. The footer states this claim
 *      literally, because it is the one this code actually keeps.
 *   2. STAT TILES (locked decision 3) — the CURRENT CALENDAR MONTH, keyed on `check_in`, EXCLUDING
 *      cancelled/no_show, PER CURRENCY, and labelled so the scope is on the face of the tile. Each choice is
 *      load-bearing: `booked_at` would answer a different question (booked vs staying) and is nullable;
 *      cancelReservation() → terminate() never zeroes `total_minor`, so a naive SUM counts cancelled
 *      bookings as booked value; and `currency` is per-ROW, so summing minor units across currencies is
 *      arithmetically meaningless (invariant #17 — never a cross-currency sum). The tiles are deliberately
 *      NOT re-scoped by the chips or the search: their labels state their own scope, and a tile that
 *      silently tracked the filter would make its label false. The design's figures (64 / €18,420 / €1,161)
 *      are hard-coded constants that do not reconcile to its own rows — not one was ported.
 *
 * "Today" is CarbonImmutable::today() — SERVER time, matching {@see ReservationStage} and {@see FrontDesk}.
 * CLAUDE.md's convention puts guest-facing day logic in the property's timezone; none of these three honour
 * it yet. Deliberately consistent with what ships rather than silently divergent — when it is fixed, fix it
 * in all three together.
 *
 * Scope-dependent reads ride GET links (`?stage=`, `?q=`), never wire-actions: a wire-action re-renders over
 * /livewire/update and bypasses the route middleware that establishes the tenant (invariant #20). There are
 * no public props, so the Wave 1 prop/view-data shadowing bug (a `$reservation` prop colliding with a
 * 'reservation' view key) cannot arise.
 */
#[Layout('components.layouts.operator')]
class ReservationsList extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    /** Bound the search term: a LIKE pattern is not a place to accept unbounded user input. */
    private const MAX_QUERY = 100;

    public function render(): View
    {
        $today = CarbonImmutable::today();
        $stage = $this->resolveStage();
        $query = $this->resolveQuery();

        $rows = $this->rows($stage, $query, $today);

        // The property's TRUE total, ignoring both filter and search — it distinguishes "this property has
        // no reservations" from "your filter matched none", which are different empty states with different
        // advice. It must never be a count of the filtered set. Scoped to the current property (or all).
        $total = Reservation::query()->whereIn('property_id', $this->currentReadIds)->count();

        return view('livewire.operator.reservations-list', [
            'viewer' => $this->viewer(),
            'subtitle' => $this->subtitle($total),
            'stage' => $stage,
            'query' => $query,
            'chips' => $this->chips($stage, $query, $today),
            'rows' => $rows,
            'total' => $total,
            'stats' => $this->monthStats($today),
            'footer' => $this->footer(count($rows), $total, $stage, $query),
        ]);
    }

    // ── Request state ────────────────────────────────────────────────────────────────────────────

    /**
     * The active stage, or null for "All". An unknown `?stage=` value falls back to All rather than
     * silently rendering everything under a chip that looks active — only ReservationStage::FILTERABLE
     * values are honoured, so the URL can never invent a stage this app does not have.
     */
    private function resolveStage(): ?string
    {
        $param = request()->query('stage');

        return is_string($param) && in_array($param, ReservationStage::FILTERABLE, true) ? $param : null;
    }

    private function resolveQuery(): string
    {
        $param = request()->query('q');

        return is_string($param) ? mb_substr(trim($param), 0, self::MAX_QUERY) : '';
    }

    // ── The query ────────────────────────────────────────────────────────────────────────────────

    /**
     * The tenant's reservations, optionally narrowed by the search. TENANT-scoped by
     * BelongsToOrganization's global scope — see the class docblock for why no explicit org predicate
     * appears here. Returns a FRESH builder every call: ReservationStage::scope() MUTATES the builder it is
     * given, so the chip counts and the row query must not share one.
     *
     * @return Builder<Reservation>
     */
    private function baseQuery(string $search): Builder
    {
        // Phase 6a · multi-property: scope to the current property (or every accessible property when
        // "all"). Pre-6a this listed reservations org-wide, blending properties in a multi-property org.
        $query = Reservation::query()->whereIn('property_id', $this->currentReadIds);

        if ($search === '') {
            return $query;
        }

        // REAL plaintext columns ONLY: `reservations.reference` and the guest's name. `guests.email`/`phone`
        // are encrypted casts — the ciphertext is not indexed by value, so a LIKE over them would match
        // nothing while LOOKING like a working search (the standing blind-index deferral). They are neither
        // searched nor rendered.
        // `%` and `_` are escaped so a term containing them searches for the literal character rather than
        // silently becoming a wildcard.
        $term = '%'.addcslashes($search, '%_\\').'%';

        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where('reference', 'like', $term)
                ->orWhereHas('leadGuest', function (Builder $guest) use ($term): void {
                    // CONCAT_WS over the two plaintext name columns so "Ada", "Lovelace" and "Ada Lovelace"
                    // all match one typed term. CONCAT_WS skips NULLs, so an anonymized guest (whose names
                    // are nulled by Guest::anonymize()) simply never matches — it does not error.
                    $guest->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term]);
                })
                // #104 GATE 3c — search the assigned UNIT code too (plaintext, not PII). The encrypted email/phone
                // stay un-searchable (a LIKE would match nothing) — that deferral is unchanged.
                ->orWhereHas('reservationUnits.unit', function (Builder $unit) use ($term): void {
                    $unit->where('code', 'like', $term);
                });
        });
    }

    /**
     * The table's rows. Ordering per decision 1 (see the class docblock).
     *
     * @return list<array<string, mixed>>
     */
    private function rows(?string $stage, string $search, CarbonImmutable $today): array
    {
        $query = $this->baseQuery($search);

        if ($stage !== null) {
            // The SHARED resolver's own SQL — never a predicate re-written here.
            $query = ReservationStage::scope($query, $stage, $today);
        }

        $reservations = $query
            ->with([
                'leadGuest',
                // Only ACTIVE lines describe the current assignment; a released line must not resurrect a
                // unit code. Eager-loaded to avoid an N+1 across the table (the FrontDesk precedent).
                'reservationUnits' => fn ($lines) => $lines->where('status', 'active'),
                'reservationUnits.unit',
            ])
            ->orderByDesc('check_in')
            ->orderByDesc('id')
            ->get();

        return $reservations->map(fn (Reservation $reservation): array => $this->row($reservation, $today))->all();
    }

    /**
     * One row — REAL columns only.
     *
     * @return array<string, mixed>
     */
    private function row(Reservation $reservation, CarbonImmutable $today): array
    {
        $guest = $reservation->leadGuest;
        $name = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';
        // first_name/last_name are nullable and Guest::anonymize() NULLS them (GDPR). Fall back to the
        // reference — a real identifier for the row — rather than inventing a placeholder name.
        $name = $name !== '' ? $name : (string) $reservation->reference;

        $lines = $reservation->reservationUnits;
        $assigned = $lines
            ->filter(fn ($line): bool => $line->unit_id !== null && $line->unit !== null)
            ->map(fn ($line): string => (string) ($line->unit->name ?? $line->unit->code))
            ->unique()->values();
        // `reservation_units.unit_id` is nullable BY DESIGN (e.g. a fresh OTA import lands unassigned), so
        // "Unassigned" reports a real state — it is not a missing-data placeholder.
        $unassigned = $lines->isNotEmpty() && $lines->contains(fn ($line): bool => $line->unit_id === null);

        $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
        $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();

        $currency = (string) ($reservation->currency ?? 'EUR');
        $balanceMinor = (int) $reservation->balance_minor;

        $stage = ReservationStage::for($reservation, $today);

        return [
            'id' => (string) $reservation->getKey(),
            'reference' => (string) $reservation->reference,
            'guest' => $name,
            'initials' => $this->initials($name),
            'check_in' => $checkIn->format('j M Y'),
            'check_out' => $checkOut->format('j M Y'),
            // Half-open [check_in, check_out) — the same arithmetic as the folio's room nights.
            'nights' => (int) $checkIn->diffInDays($checkOut),
            'guests' => (int) $reservation->adults + (int) $reservation->children,
            'unit' => $assigned->isNotEmpty() ? $assigned->implode(', ') : null,
            'unassigned' => $unassigned,
            // The OTA BRAND IS NOT MODELLED: `source` is exactly direct/walk_in/ota/corporate/group/agent/
            // manual, and no brand column exists on any table. An OTA booking is "OTA" — never "Booking.com".
            'source' => $this->sourceLabel((string) $reservation->source),
            'stage' => $stage,
            'stage_meta' => ReservationStage::meta($stage),
            'total' => $this->money((int) $reservation->total_minor, $currency),
            'balance' => $this->money($balanceMinor, $currency),
            'has_balance' => $balanceMinor > 0,
            // #104 GATE 3c — the wireframe's "Refunded" pill. `payment_status='refunded'` is a REAL written
            // state (a fully-refunded stay); render it (no figure — the amount lives on the folio, #39).
            'refunded' => (string) $reservation->payment_status === 'refunded',
        ];
    }

    // ── Filter chips — DERIVED, each count the REAL count of what clicking it shows ───────────────

    /**
     * "All" + every filterable stage, each with the count it would actually render.
     *
     * The counts include the ACTIVE SEARCH. If they did not, a chip could promise "Upcoming 5" and then
     * show 2 once clicked — the chip must state what the click delivers.
     *
     * @return list<array{key: ?string, label: string, count: int, active: bool, href: string}>
     */
    private function chips(?string $active, string $search, CarbonImmutable $today): array
    {
        $chips = [[
            'key' => null,
            'label' => 'All',
            'count' => $this->baseQuery($search)->count(),
            'active' => $active === null,
            'href' => $this->href(null, $search),
        ]];

        foreach (ReservationStage::FILTERABLE as $stage) {
            $chips[] = [
                'key' => $stage,
                // The resolver's OWN label — the chip and the badge it filters on cannot use different words.
                'label' => ReservationStage::meta($stage)['label'],
                // A fresh builder per stage: scope() mutates.
                'count' => ReservationStage::scope($this->baseQuery($search), $stage, $today)->count(),
                'active' => $active === $stage,
                'href' => $this->href($stage, $search),
            ];
        }

        return $chips;
    }

    /** A chip link that PRESERVES the active search — switching stage must not silently drop the term. */
    private function href(?string $stage, string $search): string
    {
        $params = array_filter([
            'stage' => $stage,
            'q' => $search !== '' ? $search : null,
        ], fn (?string $value): bool => $value !== null);

        return route('app.reservations').($params === [] ? '' : '?'.http_build_query($params));
    }

    // ── Stat tiles — DERIVED, current calendar month, per currency (decision 2) ───────────────────

    /**
     * Arrivals THIS CALENDAR MONTH by `check_in`, EXCLUDING cancelled/no_show, PER CURRENCY.
     *
     * One grouped aggregate rather than a fetch-and-sum: the tile is a count/Σ, not a list. `toBase()`
     * APPLIES the global scopes before dropping to the query builder (Eloquent's toBase() is
     * `applyScopes()->getQuery()`), so this aggregate is tenant-scoped exactly like every other read on the
     * page — it is not an unscoped raw query.
     *
     * @return array{count: int, booked: list<string>, due: list<string>, due_count: int, multi_currency: bool}
     */
    private function monthStats(CarbonImmutable $today): array
    {
        $rows = Reservation::query()
            ->whereIn('property_id', $this->currentReadIds)
            ->whereBetween('check_in', [
                $today->startOfMonth()->toDateString(),
                $today->endOfMonth()->toDateString(),
            ])
            // A cancellation frees inventory and sets the status but NEVER zeroes total_minor, so counting
            // cancelled/no_show rows would report money the operator will not bill as "booked value".
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->toBase()
            ->selectRaw(
                'currency,'
                .' COUNT(*) as res_count,'
                .' SUM(total_minor) as booked_minor,'
                .' SUM(CASE WHEN balance_minor > 0 THEN balance_minor ELSE 0 END) as due_minor,'
                .' SUM(CASE WHEN balance_minor > 0 THEN 1 ELSE 0 END) as due_count'
            )
            ->groupBy('currency')
            ->orderBy('currency')
            ->get();

        $count = 0;
        $dueCount = 0;
        $booked = [];
        $due = [];

        foreach ($rows as $row) {
            $currency = (string) $row->currency;

            $count += (int) $row->res_count;
            $dueCount += (int) $row->due_count;

            // Never a cross-currency sum (invariant #17): one line per currency.
            $booked[] = $this->money((int) $row->booked_minor, $currency);

            if ((int) $row->due_minor > 0) {
                $due[] = $this->money((int) $row->due_minor, $currency);
            }
        }

        return [
            'count' => $count,
            'booked' => $booked,
            'due' => $due,
            'due_count' => $dueCount,
            'multi_currency' => count($booked) > 1,
        ];
    }

    // ── Copy — DERIVED from real counts only ─────────────────────────────────────────────────────

    private function subtitle(int $total): string
    {
        return $total.' '.($total === 1 ? 'reservation' : 'reservations');
    }

    /**
     * "Showing N of M reservations · newest arrivals first" + a real echo of what is narrowing the view.
     * The ordering claim is the one decision 1 actually keeps — the design claimed an order it never
     * implemented.
     */
    private function footer(int $shown, int $total, ?string $stage, string $search): string
    {
        $line = 'Showing '.$shown.' of '.$total.' '.($total === 1 ? 'reservation' : 'reservations').' · newest arrivals first';

        $parts = [];

        if ($stage !== null) {
            $parts[] = mb_strtolower(ReservationStage::meta($stage)['label']);
        }

        if ($search !== '') {
            $parts[] = '“'.$search.'”';
        }

        return $parts === [] ? $line : $line.' · '.implode(' · ', $parts);
    }
}
