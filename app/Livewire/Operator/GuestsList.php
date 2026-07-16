<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\FrontDesk;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Tag;
use App\Support\Guest\GuestHistory;
use App\Support\Guest\GuestSpend;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator GUESTS LIST — the CRM (Wave 2 · Sub-gate 4). Every guest profile in the tenant, one row each,
 * with what they have been billed, where they are in their stay, and how to contact them. READ-ONLY: this
 * component creates no guest, edits no contact detail and toggles no flag — those writes land on the guest
 * PROFILE (Sub-gate 5).
 *
 * ⚠️ THIS IS THE PII SURFACE, AND IT IS THE ONLY ONE (locked decision 4, docs/DECISIONS.md 2026-07-16).
 * `guests.email`/`phone` are `encrypted` casts; the cast decrypts on attribute access, so rendering them is
 * a deliberate act, not an accident of a `select *`. The CRM is where an operator legitimately needs to
 * reach a guest, so contact detail is shown HERE and only here — the front desk, the reservations list and
 * the inbox stay NAME-ONLY, and OperatorGuestsListTest asserts that they still are. Two structural facts
 * keep the blast radius small:
 *   • `Guest::$hidden = ['email','phone']` excludes both from any array/JSON serialization, so a future
 *     `toArray()` path cannot leak them by default — this file opts IN, per attribute, per row.
 *   • This component has NO public props. Livewire serializes public props into the page payload and back
 *     over /livewire/update; view data passed through `view(...)` is server-rendered and never round-trips.
 *     Contact PII therefore reaches the HTML and stops there. Keep it that way — a `public array $rows`
 *     holding these rows would put every guest's email into a wire payload. (The same rule also dodges the
 *     Wave 1 prop/view-key shadowing bug.)
 *
 * TENANT-scoped. {@see Guest} is BelongsToOrganization, so its global scope + SetTenantForWebRequest mean a
 * foreign org's guest is not in the collection at all — no `->where('organization_id', …)` appears here,
 * because adding one would imply the scope were optional. `/app` additionally sits behind
 * EnsureBackOfficeAccess, so an owner-role user never reaches this page (invariant #26). Both are proven by
 * OperatorGuestsListTest. `Guest` also uses SoftDeletes, so a deleted profile is already excluded.
 *
 * NOTHING IS DERIVED TWICE. Two shared definitions do the arithmetic, and this file re-implements neither:
 *   • {@see GuestSpend} — "Total billed" + the open balance beside it (locked decision 1), reconciling to
 *     the append-only ledger. The profile reads the SAME helper, so the two screens cannot disagree.
 *   • {@see ReservationStage} — the status badge, via `mostSignificant()` over the guest's real
 *     reservations, and the stay count via its `CANCELLED_STATUSES`.
 *
 * Honesty triage (invariant #19; standing order = OMIT AGGRESSIVELY):
 *   • REAL     — name; `is_vip` and `risk_flag` (real boolean columns, not invented flags); `tags` via the
 *                shared polymorphic `taggables` pivot; `email`/`phone` (decrypted, see above); `nationality`.
 *   • DERIVED  — "Total billed" + "Open balance" (GuestSpend); the status badge (ReservationStage); the stay
 *                count; the chip counts (each the REAL count of what clicking it shows, search included);
 *                the three tiles (below).
 *   • OMITTED (no backend — absent, not greyed out):
 *       – The PAUSED-BILLING BANNER. Hard-coded in every design of this wave; `subscriptions.status` does
 *         contain 'paused' but nothing reads or writes it. Omitted by name in Wave 1
 *         (OperatorOwnerDetail:101-102) and in Sub-gate 1; not reopened here.
 *       – The BULK BAR + its two modals. "Send message" needs a channel (every external one is
 *         account-gated); "Delete" has no backend and MUST NOT be built as one — a guest is erased via
 *         `Guest::anonymize()` (GDPR: strip PII, keep the row), never deleted, and wiring a "Delete" button
 *         to a soft delete would keep the PII while looking like an erase. The design's Delete fires a
 *         toast and nothing else; the honest version is the anonymize trigger, which is logged as an
 *         outstanding GDPR item rather than smuggled in here.
 *       – The bulk CSV EXPORT. It is the one design handler that produces a real artifact — a client-side
 *         Blob over the fabricated rows. CSV export is an explicitly deferred Wave 1 stub; exporting every
 *         guest's decrypted email to a file is also a materially bigger PII decision than displaying it.
 *       – "ADD GUEST". Inert in the design (no handler) and it supplies NO form — the field set is
 *         undefined, so building one would be inventing the product. `Guest::create()` works, but the real
 *         version needs a controller + audit/domain events. Rendered disabled, matching the shipped
 *         "New reservation" precedent.
 *       – PAGINATION. The design's arrows have no handler. Every matching row is rendered and the footer
 *         states the true count — a silent cap would be worse than a long page.
 *       – SORT CONTROLS. The design has none (plain `<span>` headers); the order is stated in the footer.
 *       – The "+4%" delta on the repeat-guests tile, and the design's 248 / 38 / 31% constants. There is no
 *         historical snapshot table anywhere, so a delta cannot be computed without inventing a baseline
 *         (the Wave 1 fabricated-prior-year trap). The tiles below are recomputed from real rows; not one
 *         design figure was ported.
 *   • SOON     — the row link to the guest PROFILE. It is Sub-gate 5 and has no route yet, so the name is
 *                plain text this gate and becomes a link next — never a link to nowhere.
 *
 * DECISIONS TAKEN HERE (each was ambiguous in the design, so it is stated rather than guessed):
 *   1. SEARCH IS NAME-ONLY, and the placeholder says so. The design's field promises "name, email or phone"
 *      — and is a `<div>`, not an input. Email/phone are encrypted TEXT, never indexed by value, so a LIKE
 *      over them silently matches nothing (the standing blind-index deferral). This page therefore SHOWS
 *      contact detail it cannot SEARCH; the footer says that outright rather than letting an operator
 *      conclude the data is missing.
 *   2. "NATIONALITY", not "Country". `guests.nationality` is `char(2)` — a nationality. The design renders
 *      it as a residence ("Dublin, Ireland", editable); conflating the two is a data-modelling error, and
 *      no city/residence column exists. The column is labelled for the data that backs it.
 *   3. STAYS EXCLUDE CANCELLED. A cancelled booking is not a stay. The rule keys on
 *      ReservationStage::CANCELLED_STATUSES so the count, the badge and the tile cannot drift apart.
 *      Note this is a BOOKING count (`reservations.lead_guest_id`), which is deliberately NOT the same
 *      number as GuestSpend's per-currency `stays` (folios only) — see {@see Guest::reservations()}.
 *   4. ORDERING — surname, then first name, then `id`. A CRM is browsed alphabetically; `id` is a ULID and
 *      unique, so it breaks ties and gives a TOTAL order (the render is deterministic). Anonymized guests
 *      have NULL names and therefore sort first under MySQL's nulls-first ASC — visible rather than buried,
 *      which is the honest place for a row that has been erased.
 *
 * "Today" is CarbonImmutable::today() — SERVER time, matching {@see ReservationStage} and {@see FrontDesk}.
 * CLAUDE.md puts guest-facing day logic in the property's timezone; none of the three honour it yet.
 * Deliberately consistent with what ships rather than silently divergent — fix all three together.
 *
 * Scope-dependent reads ride GET links (`?filter=`, `?tag=`, `?q=`), never wire-actions: a wire-action
 * re-renders over /livewire/update and bypasses the route middleware that establishes the tenant
 * (invariant #20).
 */
#[Layout('components.layouts.operator')]
class GuestsList extends Component
{
    use FormatsOperatorData;

    /** Bound the search term: a LIKE pattern is not a place to accept unbounded user input. */
    private const MAX_QUERY = 100;

    /** The flag filters this page offers, each backed by a REAL boolean column on `guests`. */
    private const FLAG_FILTERS = ['vip' => 'is_vip', 'risk' => 'risk_flag'];

    public function render(): View
    {
        $today = CarbonImmutable::today();
        $filter = $this->resolveFilter();
        $tag = $this->resolveTag();
        $search = $this->resolveQuery();

        $rows = $this->rows($filter, $tag, $search, $today);

        // The tenant's TRUE total, ignoring filter and search — it distinguishes "no guests yet" from "your
        // filter matched none", which are different empty states with different advice.
        $total = Guest::query()->count();

        return view('livewire.operator.guests-list', [
            'viewer' => $this->viewer(),
            'subtitle' => $total.' '.($total === 1 ? 'guest profile' : 'guest profiles'),
            'query' => $search,
            'chips' => $this->chips($filter, $tag, $search),
            'rows' => $rows,
            'total' => $total,
            'stats' => $this->stats($today),
            'footer' => $this->footer(count($rows), $total, $filter, $tag, $search),
        ]);
    }

    // ── Request state ────────────────────────────────────────────────────────────────────────────

    /**
     * The active flag filter, or null for "All". An unknown `?filter=` falls back to All rather than
     * rendering everything under a chip that looks active — only FLAG_FILTERS keys are honoured, so the URL
     * can never invent a filter this page does not have.
     */
    private function resolveFilter(): ?string
    {
        $param = request()->query('filter');

        return is_string($param) && array_key_exists($param, self::FLAG_FILTERS) ? $param : null;
    }

    /**
     * The active tag id, or null. Resolved against the TENANT'S catalog (Tag is BelongsToOrganization), so a
     * foreign org's tag id filters nothing and simply falls back to All — it can neither match rows nor
     * confirm that the id exists.
     */
    private function resolveTag(): ?string
    {
        $param = request()->query('tag');

        if (! is_string($param) || $param === '') {
            return null;
        }

        return Tag::query()->whereKey($param)->exists() ? $param : null;
    }

    private function resolveQuery(): string
    {
        $param = request()->query('q');

        return is_string($param) ? mb_substr(trim($param), 0, self::MAX_QUERY) : '';
    }

    // ── The query ────────────────────────────────────────────────────────────────────────────────

    /**
     * The tenant's guests, narrowed by the flag filter, the tag and the search. TENANT-scoped by
     * BelongsToOrganization's global scope — see the class docblock for why no org predicate appears here.
     * Returns a FRESH builder every call so the chip counts and the row query never share one.
     *
     * @return Builder<Guest>
     */
    private function baseQuery(?string $filter, ?string $tag, string $search): Builder
    {
        $query = Guest::query();

        if ($filter !== null) {
            // A REAL boolean column, named in FLAG_FILTERS — never a user-supplied column name.
            $query->where(self::FLAG_FILTERS[$filter], true);
        }

        if ($tag !== null) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($tag));
        }

        if ($search !== '') {
            // PLAINTEXT COLUMNS ONLY (decision 1). `email`/`phone` are encrypted casts — the ciphertext is
            // not indexed by value, so a LIKE over them would match nothing while LOOKING like a working
            // search. They are rendered on this page but never queried.
            // `%` and `_` are escaped so a term containing them searches for the literal character.
            $term = '%'.addcslashes($search, '%_\\').'%';

            // CONCAT_WS over the two plaintext name columns so "Ada", "Lovelace" and "Ada Lovelace" all
            // match one typed term. CONCAT_WS skips NULLs, so an anonymized guest (names nulled by
            // Guest::anonymize()) never matches — it does not error.
            $query->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term]);
        }

        return $query;
    }

    /**
     * The table's rows. Ordering per decision 4 (see the class docblock).
     *
     * @return list<array<string, mixed>>
     */
    private function rows(?string $filter, ?string $tag, string $search, CarbonImmutable $today): array
    {
        $guests = $this->baseQuery($filter, $tag, $search)
            ->with([
                'tags',
                // The stage badge + the stay count are computed from these in PHP via the SHARED resolver —
                // one query for the page, and no second SQL predicate that could drift from the badge.
                'reservations' => fn ($reservations) => $reservations->select([
                    'id', 'lead_guest_id', 'status', 'check_in', 'check_out',
                ]),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->get();

        // ONE batched call for the whole page — not a per-row query (GuestSpend::forGuestIds exists for
        // exactly this), and the same definition the profile reads for a single guest.
        $spend = GuestSpend::forGuestIds($guests->map(fn (Guest $guest): string => (string) $guest->getKey())->all());

        return $guests->map(fn (Guest $guest): array => $this->row($guest, $spend[(string) $guest->getKey()] ?? [], $today))->all();
    }

    /**
     * One row — REAL columns + the two shared derivations.
     *
     * @param  array<string, array{billed_minor: int, balance_minor: int, stays: int}>  $spend
     * @return array<string, mixed>
     */
    private function row(Guest $guest, array $spend, CarbonImmutable $today): array
    {
        $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));
        $anonymized = $guest->isAnonymized();

        // An anonymized guest is a REAL state, not missing data: anonymize() nulls the PII and keeps the row
        // so the financial history that references it stays intact. Say so, rather than rendering a blank
        // row or inventing a placeholder name.
        $label = match (true) {
            $name !== '' => $name,
            $anonymized => 'Anonymized guest',
            default => 'Unnamed guest',
        };

        $stage = ReservationStage::mostSignificant($guest->reservations, $today);

        return [
            'id' => (string) $guest->getKey(),
            'name' => $label,
            'initials' => $name !== '' ? $this->initials($name) : '—',
            'anonymized' => $anonymized,
            // REAL boolean columns.
            'is_vip' => (bool) $guest->is_vip,
            'risk_flag' => (bool) $guest->risk_flag,
            // The real tenant tag catalog via the shared polymorphic pivot.
            'tags' => $guest->tags->map(fn (Tag $tag): string => (string) $tag->name)->all(),
            // ⚠️ PII — decrypted HERE, on this surface only (decision 4). Null on an anonymized guest.
            'email' => $this->contact($guest->email),
            'phone' => $this->contact($guest->phone),
            // Decision 2 — a nationality, not a residence.
            'nationality' => $this->countryName($guest->nationality),
            // Decision 3 — a cancelled booking is not a stay. The rule is the SHARED GuestHistory's, so this
            // count, the profile's and the inbox guest card's are the same number by construction.
            'stays' => GuestHistory::for($guest->reservations)['stays'],
            'stage' => $stage,
            'stage_meta' => $stage !== null ? ReservationStage::meta($stage) : null,
            // Decision 1 (locked) — BILLED, with the open balance BESIDE it, never netted into it.
            // Per currency, never summed across (invariant #17).
            'billed' => $this->moneyLines($spend, 'billed_minor'),
            'balance' => $this->moneyLines($spend, 'balance_minor'),
        ];
    }

    /**
     * A contact field for display: the decrypted value, or null when there is genuinely none.
     *
     * Guarded because `email`/`phone` are NULLABLE (an OTA import or a walk-in may carry neither) and
     * `Guest::anonymize()` nulls both — the row must degrade to "—", never to an empty box that reads like
     * a rendering bug.
     */
    private function contact(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    /**
     * One formatted line PER CURRENCY for a money cell — never a cross-currency sum (invariant #17), since
     * `folios.currency` is per row and adding EUR to USD would invent an FX rate.
     *
     * Zero-valued lines are dropped, so a settled guest's balance cell renders "—" (the caller's empty case)
     * rather than "€0.00" — and a guest with no billing history at all returns [] for both, per GuestSpend's
     * no-history rule.
     *
     * @param  array<string, array{billed_minor: int, balance_minor: int, stays: int}>  $spend
     * @param  'billed_minor'|'balance_minor'  $key
     * @return list<string>
     */
    private function moneyLines(array $spend, string $key): array
    {
        $lines = [];

        foreach ($spend as $currency => $figures) {
            if ($figures[$key] !== 0) {
                $lines[] = $this->money($figures[$key], $currency);
            }
        }

        return $lines;
    }

    // ── Filter chips — DERIVED, each count the REAL count of what clicking it shows ───────────────

    /**
     * "All" + the two REAL flag columns + one chip per tag in the tenant's catalog.
     *
     * The counts include the ACTIVE SEARCH: a chip that promised "VIP 5" and then showed 2 once clicked
     * would be lying about what the click delivers.
     *
     * Tag chips are driven by the tenant's real `tags` rows, so a workspace with no catalog renders no tag
     * chips — an empty filter row rather than chips that filter nothing. (Nothing in the app writes tags
     * yet; `DemoSeeder` seeds a small real catalog so the demo shows the true feature, and the guest PROFILE
     * is where they will be attached.)
     *
     * @return list<array{label: string, count: int, active: bool, href: string, tone: ?string}>
     */
    private function chips(?string $filter, ?string $tag, string $search): array
    {
        $chips = [[
            'label' => 'All',
            'count' => $this->baseQuery(null, null, $search)->count(),
            'active' => $filter === null && $tag === null,
            'href' => $this->href(null, null, $search),
            'tone' => null,
        ], [
            'label' => 'VIP',
            'count' => $this->baseQuery('vip', null, $search)->count(),
            'active' => $filter === 'vip',
            'href' => $this->href('vip', null, $search),
            'tone' => 'vip',
        ], [
            'label' => 'At risk',
            'count' => $this->baseQuery('risk', null, $search)->count(),
            'active' => $filter === 'risk',
            'href' => $this->href('risk', null, $search),
            'tone' => 'risk',
        ]];

        foreach (Tag::query()->orderBy('name')->get() as $catalogTag) {
            $id = (string) $catalogTag->getKey();

            $chips[] = [
                'label' => (string) $catalogTag->name,
                'count' => $this->baseQuery(null, $id, $search)->count(),
                'active' => $tag === $id,
                'href' => $this->href(null, $id, $search),
                'tone' => 'tag',
            ];
        }

        return $chips;
    }

    /** A chip link that PRESERVES the active search — switching filter must not silently drop the term. */
    private function href(?string $filter, ?string $tag, string $search): string
    {
        $params = array_filter([
            'filter' => $filter,
            'tag' => $tag,
            'q' => $search !== '' ? $search : null,
        ], fn (?string $value): bool => $value !== null);

        return route('app.guests').($params === [] ? '' : '?'.http_build_query($params));
    }

    // ── Stat tiles — DERIVED from real rows; every design constant discarded ──────────────────────

    /**
     * Three tiles, each recomputed and each labelled with its own scope.
     *
     * They are deliberately NOT re-scoped by the chips or the search: their labels state what they measure,
     * and a tile that silently tracked the filter would make its own label false (the Sub-gate 1 rule).
     *
     * @return array{profiles: int, anonymized: int, in_house: int, repeat: int, with_stays: int, repeat_pct: ?int}
     */
    private function stats(CarbonImmutable $today): array
    {
        // Tenant-scoped + non-trashed (SoftDeletes) for free.
        $profiles = Guest::query()->count();

        // Counted and SHOWN separately rather than filtered out: an anonymized guest is still a real profile
        // carrying real financial history, so excluding it would under-count the workspace — but folding it
        // in silently would over-state how many people we can actually identify.
        $anonymized = Guest::query()->whereNotNull('anonymized_at')->count();

        // DISTINCT GUESTS, not reservations — the design's "38" was a count of neither, against 8 rows.
        // The stage predicate is the SHARED resolver's own SQL, so this tile and the badges agree.
        //
        // Expressed as a subquery over an explicit `Reservation::query()` rather than a `whereHas` closure
        // because the resolver takes a Builder<Reservation> and whereHas hands its closure a Builder<Model>
        // — it cannot know the relation's model from a string. Same rows either way (Reservation is
        // BelongsToOrganization, so the subquery is tenant-scoped too); this way the type is honest.
        $inHouse = Guest::query()
            ->whereIn('id', ReservationStage::scope(Reservation::query(), ReservationStage::IN_HOUSE, $today)
                ->select('lead_guest_id'))
            ->count();

        // "Repeat" needs a stated denominator or the percentage means nothing. It is: of the guests who have
        // ever actually stayed (≥1 non-cancelled booking), how many came back (≥2). Guests who have never
        // booked are in NEITHER side — they are not "one-time" guests, they are not guests yet.
        $withStays = $this->withStaysQuery(1)->count();
        $repeat = $this->withStaysQuery(2)->count();

        return [
            'profiles' => $profiles,
            'anonymized' => $anonymized,
            'in_house' => $inHouse,
            'repeat' => $repeat,
            'with_stays' => $withStays,
            // No denominator → no percentage. "0%" would claim we measured something we cannot.
            'repeat_pct' => $withStays > 0 ? (int) round($repeat / $withStays * 100) : null,
        ];
    }

    /**
     * Guests with at least $atLeast non-cancelled bookings — the tile's mirror of decision 3, keyed on the
     * SHARED CANCELLED_STATUSES so the tile cannot drift from the row's stay count.
     *
     * @return Builder<Guest>
     */
    private function withStaysQuery(int $atLeast): Builder
    {
        return Guest::query()->whereHas(
            'reservations',
            fn (Builder $reservations) => $reservations->whereNotIn('status', ReservationStage::CANCELLED_STATUSES),
            '>=',
            $atLeast
        );
    }

    // ── Copy — DERIVED from real counts only ─────────────────────────────────────────────────────

    /**
     * "Showing N of M guests · A–Z by surname" + a real echo of what is narrowing the view, and the honest
     * note that the contact detail on this page is not searchable (decision 1).
     */
    private function footer(int $shown, int $total, ?string $filter, ?string $tag, string $search): string
    {
        $line = 'Showing '.$shown.' of '.$total.' '.($total === 1 ? 'guest' : 'guests').' · A–Z by surname';

        $parts = [];

        if ($filter !== null) {
            $parts[] = $filter === 'vip' ? 'VIP only' : 'flagged at risk';
        }

        if ($tag !== null) {
            $parts[] = 'tagged';
        }

        if ($search !== '') {
            $parts[] = '“'.$search.'”';
        }

        $line = $parts === [] ? $line : $line.' · '.implode(' · ', $parts);

        return $line.' · Search covers names only — email and phone are encrypted at rest, so they cannot be searched.';
    }
}
