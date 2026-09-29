<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\FrontDesk;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Tag;
use App\Support\Guest\GuestHistory;
use App\Support\Guest\GuestListParams;
use App\Support\Guest\GuestSpend;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator GUESTS LIST — the CRM (Wave 2 · Sub-gate 4). Every guest profile in the tenant, one row each,
 * with what they have been billed, where they are in their stay, and how to contact them. READ-ONLY: this
 * component writes nothing — Add guest, a row's Edit and Erase, and the bulk actions all POST to controllers
 * (GuestActionController, GuestBulkActionController), the single write paths through GuestService.
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
 *     Wave 1 prop/view-key shadowing bug.) The row's Edit data (2026-09-29) is the same contact detail the
 *     row already shows, handed to that row's Edit dialog — and only to a viewer who may edit.
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
 *         contain 'paused' but nothing reads or writes it.
 *       – The "+4%" delta on the repeat-guests tile, and the design's 248 / 38 / 31% constants. There is no
 *         historical snapshot table anywhere, so a delta cannot be computed without inventing a baseline
 *         (the Wave 1 fabricated-prior-year trap). The tiles below are recomputed from real rows.
 *     The first build also omitted the bulk bar, Add guest, the export, the profile link, pagination and
 *     sorting. All of them have since been built for real: the bulk actions and Add guest (#87, the erase as
 *     `Guest::anonymize()`, never a delete), pagination (WAVE 3), and sorting, page sizes and each row's
 *     Edit and Erase (2026-09-29).
 *
 * DECISIONS TAKEN HERE (each was ambiguous in the design, so it is stated rather than guessed):
 *   1. SEARCH MATCHES NAME, CITY AND AN EXACT EMAIL — and the placeholder says so. Email/phone are
 *      encrypted TEXT, never indexed by value, so a LIKE over them silently matches nothing; an exact email
 *      is found through the deterministic `email_hash` blind index. The page therefore SHOWS contact detail
 *      it cannot fully SEARCH, and says that outright rather than letting an operator conclude the data is
 *      missing.
 *   2. "NATIONALITY", not "Country". `guests.nationality` is `char(2)` — a nationality. The column is
 *      labelled for the data that backs it.
 *   3. STAYS EXCLUDE CANCELLED. A cancelled booking is not a stay. The rule keys on
 *      ReservationStage::CANCELLED_STATUSES so the count, the badge, the tile and the Stays sort cannot drift
 *      apart. Note this is a BOOKING count (`reservations.lead_guest_id`), which is deliberately NOT the same
 *      number as GuestSpend's per-currency `stays` (folios only) — see {@see Guest::reservations()}.
 *   4. ORDERING — by default surname, then first name, then `id`: a CRM is browsed alphabetically, and `id`
 *      (a unique ULID) makes the order TOTAL, so paging is deterministic. Anonymized guests have NULL names
 *      and sort first under MySQL's nulls-first ASC — visible rather than buried. Since 2026-09-29 the
 *      table also sorts by Stays and by Last stay ({@see GuestListParams::SORTS}); every sort ends on the same total order.
 *
 * "Today" is CarbonImmutable::today() — SERVER time, matching {@see ReservationStage} and {@see FrontDesk}.
 * CLAUDE.md puts guest-facing day logic in the property's timezone; none of the three honour it yet.
 * Deliberately consistent with what ships rather than silently divergent — fix all three together.
 *
 * Every setting rides the URL (`?filter=`, `?tag=`, `?q=`, `?sort=`, `?dir=`, `?per=`, `?page=`), never a
 * wire-action: a wire-action re-renders over /livewire/update and bypasses the route middleware that
 * establishes the tenant (invariant #20). The page's own script swaps the results in place, but it does so by
 * fetching the same GET URL — every change is still an ordinary request through every middleware.
 *
 * @phpstan-type ListState array{filter: ?string, tag: ?string, q: string, sort: 'name'|'stays'|'last_stay', dir: 'asc'|'desc', per: int}
 */
#[Layout('components.layouts.operator')]
class GuestsList extends Component
{
    use FormatsOperatorData;

    /** Rows per page by default — defined with the rest of the list's settings in {@see GuestListParams}. */
    public const PER_PAGE = GuestListParams::PER_PAGE;

    public function render(): View
    {
        $today = CarbonImmutable::today();
        $state = $this->state();
        $canManage = (bool) auth()->user()?->can('guests.manage');

        $rows = $this->rows($state, $today, $canManage);

        // The tenant's TRUE total, ignoring filter and search — it distinguishes "no guests yet" from "your
        // filter matched none", which are different empty states with different advice.
        $total = Guest::query()->count();

        return view('livewire.operator.guests-list', [
            'viewer' => $this->viewer(),
            'subtitle' => $total.' '.($total === 1 ? 'guest profile' : 'guest profiles'),
            'query' => $state['q'],
            // What a search keeps when submitted without script — every other setting, and page 1.
            'keep' => array_diff_key(GuestListParams::clean($this->params($state)), ['q' => true]),
            'chips' => $this->chips($state),
            'sorts' => $this->sortLinks($state),
            'sizes' => $this->sizeLinks($state),
            'pages' => $this->pageLinks($state, $rows),
            'sortedBy' => $this->sortedBy($state),
            'rows' => $rows,
            'total' => $total,
            'stats' => $this->stats($today),
            'canErase' => (bool) auth()->user()?->can('guests.erase'),
            // The page script's start: where a row's Edit and Erase post (the id fills `__ID__`), the search as
            // loaded, and an Edit the server just refused, to reopen as it was typed.
            'boot' => [
                'urls' => [
                    'edit' => route('app.guests.update', ['guest' => '__ID__']),
                    'erase' => route('app.guests.anonymize', ['guest' => '__ID__']),
                    'profile' => route('app.guests.show', ['guest' => '__ID__']),
                ],
                'q' => $state['q'],
                'retry' => $canManage ? $this->editRetry() : null,
            ],
        ]);
    }

    // ── The list's settings — ONE reading, for the page AND for the way back ─────────────────────

    /**
     * This request's settings, defaults filled in. The tag is resolved against the TENANT'S catalog (Tag is
     * BelongsToOrganization), so a foreign org's tag id filters nothing and simply falls back to All — it can
     * neither match rows nor confirm that the id exists.
     *
     * @return ListState
     */
    private function state(): array
    {
        $clean = GuestListParams::clean((array) request()->query());
        $sort = $clean['sort'] ?? 'name';

        return [
            'filter' => $clean['filter'] ?? null,
            'tag' => isset($clean['tag']) && Tag::query()->whereKey($clean['tag'])->exists() ? $clean['tag'] : null,
            'q' => $clean['q'] ?? '',
            'sort' => $sort,
            'dir' => $clean['dir'] ?? GuestListParams::SORTS[$sort],
            'per' => $clean['per'] ?? self::PER_PAGE,
        ];
    }

    /**
     * A link to this list with some settings changed and every other one kept — and back to page 1, because a
     * different view of the list has different pages.
     *
     * @param  ListState  $state
     * @param  array<string, string|int|null>  $changes
     */
    private function href(array $state, array $changes): string
    {
        return GuestListParams::url(array_merge($this->params($state), $changes));
    }

    /**
     * @param  ListState  $state
     * @return array<string, string|int|null>
     */
    private function params(array $state): array
    {
        return ['filter' => $state['filter'], 'tag' => $state['tag'], 'q' => $state['q'],
            'sort' => $state['sort'], 'dir' => $state['dir'], 'per' => $state['per']];
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

        $today = CarbonImmutable::today();
        if ($filter === 'vip') {
            $query->where('is_vip', true);   // a REAL boolean column
        } elseif ($filter === 'in_house') {
            // The SHARED stay-state resolver's own SQL — a guest with a checked-in stay right now.
            $query->whereIn('id', ReservationStage::scope(Reservation::query(), ReservationStage::IN_HOUSE, $today)->select('lead_guest_id'));
        } elseif ($filter === 'upcoming') {
            $query->whereIn('id', ReservationStage::scope(Reservation::query(), ReservationStage::UPCOMING, $today)->select('lead_guest_id'));
        }

        if ($tag !== null) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($tag));
        }

        if ($search !== '') {
            // `%` and `_` are escaped so a term containing them searches for the literal character.
            $term = '%'.addcslashes($search, '%_\\').'%';

            $query->where(function (Builder $q) use ($term, $search): void {
                // CONCAT_WS over the two plaintext name columns so "Ada", "Lovelace" and "Ada Lovelace" all
                // match one typed term. CONCAT_WS skips NULLs, so an anonymized guest never matches.
                $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term])
                    ->orWhere('city', 'like', $term);

                // ⚠️ email/phone are ENCRYPTED — not searchable by value. But the deterministic `email_hash`
                // blind index lets an EXACT email match work: if the term is a full address, match its hash.
                if (str_contains($search, '@')) {
                    $q->orWhere('email_hash', Guest::emailHash(trim($search)));
                }
            });
        }

        return $query;
    }

    /**
     * One PAGE of guests (WAVE 3 pagination gate) — this list was unbounded.
     *
     * ⚠️ GET-PAGED, not wire-paged: Laravel's paginator reads `?page` from the request, and the page links
     * carry every other setting, so a page change is an ordinary request that re-runs EVERY middleware.
     * Livewire's WithPagination would change pages over /livewire/update, where SetCurrentPropertyForWebRequest
     * does not run (invariant #20) — the same reason this component's filters ride GET links.
     *
     * @param  ListState  $state
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function rows(array $state, CarbonImmutable $today, bool $canManage): LengthAwarePaginator
    {
        $query = $this->baseQuery($state['filter'], $state['tag'], $state['q'])
            ->with([
                'tags',
                // The stage badge + the stay count are computed from these in PHP via the SHARED resolver —
                // one query for the page, and no second SQL predicate that could drift from the badge.
                'reservations' => fn ($reservations) => $reservations->select([
                    'id', 'lead_guest_id', 'status', 'check_in', 'check_out',
                ]),
            ]);

        $this->applySort($query, $state['sort'], $state['dir'], $today);

        $guests = $query->paginate($state['per'])->appends(GuestListParams::clean($this->params($state)));

        // ONE batched call for the page — not a per-row query (GuestSpend::forGuestIds exists for exactly
        // this), and the same definition the profile reads for a single guest.
        $spend = GuestSpend::forGuestIds(
            collect($guests->items())->map(fn (Guest $guest): string => (string) $guest->getKey())->all(),
        );

        return $guests->through(fn (Guest $guest): array => $this->row($guest, $spend[(string) $guest->getKey()] ?? [], $today, $canManage));
    }

    /**
     * Order the page (2026-09-29). Each sort ends on surname, first name and `id`, so the order is TOTAL and a
     * row can never appear on two pages or on none. The `match` has no default on purpose: `$sort` is already
     * checked against {@see GuestListParams::SORTS}, and a sort nobody wrote must fail loudly rather than quietly pick one.
     *
     * @param  Builder<Guest>  $query
     * @param  'name'|'stays'|'last_stay'  $sort
     * @param  'asc'|'desc'  $dir
     */
    private function applySort(Builder $query, string $sort, string $dir, CarbonImmutable $today): void
    {
        match ($sort) {
            'name' => $query->orderBy('last_name', $dir)->orderBy('first_name', $dir)->orderBy('id', $dir),
            // The SAME count the Stays column shows — GuestHistory's rule: every lead booking that is not cancelled
            // or a no-show (ReservationStage::CANCELLED_STATUSES), through the same tenant-scoped relation.
            'stays' => $query
                ->withCount(['reservations as stays_count' => fn (Builder $reservations) => $reservations
                    ->whereNotIn('status', ReservationStage::CANCELLED_STATUSES)])
                ->orderBy('stays_count', $dir)
                ->orderBy('last_name')->orderBy('first_name')->orderBy('id'),
            'last_stay' => $this->orderByLastStay($query, $dir, $today)
                ->orderBy('last_name')->orderBy('first_name')->orderBy('id'),
        };
    }

    /**
     * ⚠️ "Last stay" sorts by the date the column SHOWS — computed here in SQL exactly as {@see lastStay()}
     * computes it in PHP, so a sorted column never reads out of order:
     *   • a guest checked in right now shows "In house" — they sort at TODAY;
     *   • otherwise the reservation is their latest non-cancelled one by check-in (the id breaks a tie, in both
     *     places), and it sorts by its ARRIVAL if that is still ahead ("Arrives …") and by its DEPARTURE if not.
     * Sorting by the check-in alone would be simpler and WRONG: a long stay that began first can end last, and
     * the column shows when it ended.
     *
     * A guest who has never stayed has no date. A sentinel puts them LAST in both directions — never at the top
     * of an oldest-first list — and it is only ever a sort key: the cell still shows "—".
     *
     * @param  Builder<Guest>  $query
     * @param  'asc'|'desc'  $dir
     * @return Builder<Guest>
     */
    private function orderByLastStay(Builder $query, string $dir, CarbonImmutable $today): Builder
    {
        $t = $today->toDateString();

        $inHouse = ReservationStage::scope(Reservation::query(), ReservationStage::IN_HOUSE, $today)
            ->select('lead_guest_id')
            ->toBase();

        $latest = Reservation::query()
            ->selectRaw('CASE WHEN reservations.check_in > ? THEN reservations.check_in ELSE reservations.check_out END', [$t])
            ->whereColumn('reservations.lead_guest_id', 'guests.id')
            ->whereNotIn('reservations.status', ReservationStage::CANCELLED_STATUSES)
            ->orderByDesc('reservations.check_in')
            ->orderBy('reservations.id')
            ->limit(1)
            ->toBase();

        $never = $dir === 'asc' ? '9999-12-31' : '0001-01-01';

        return $query->select('guests.*')->selectRaw(
            'COALESCE(CASE WHEN guests.id IN ('.$inHouse->toSql().') THEN ? ELSE ('.$latest->toSql().') END, ?) AS last_stay_key',
            [...$inHouse->getBindings(), $t, ...$latest->getBindings(), $never],
        )->orderBy('last_stay_key', $dir);
    }

    /**
     * One row — REAL columns + the two shared derivations.
     *
     * @param  array<string, array{billed_minor: int, balance_minor: int, stays: int}>  $spend
     * @return array<string, mixed>
     */
    private function row(Guest $guest, array $spend, CarbonImmutable $today, bool $canManage): array
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
            // One of five warm tints for the initials, fixed by the id so a guest keeps theirs. Decoration only.
            'tone' => $anonymized ? 'erased' : (string) (crc32((string) $guest->getKey()) % 5),
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
            // The wireframe's "Last stay" cell — a live state (In house / Arrives …) or the real date.
            'last_stay' => $this->lastStay($guest->reservations, $today),
            // Decision 1 (locked) — BILLED, with the open balance BESIDE it, never netted into it.
            // Per currency, never summed across (invariant #17).
            'billed' => $this->moneyLines($spend, 'billed_minor'),
            'balance' => $this->moneyLines($spend, 'balance_minor'),
            // The row's Edit dialog (2026-09-29) — the same fields as the profile's, as they are now. Only for a
            // viewer who may edit, and never for an erased guest: writing a name back would un-erase them.
            'edit' => $canManage && ! $anonymized ? [
                'id' => (string) $guest->getKey(),
                'name' => $label,
                'first_name' => (string) $guest->first_name,
                'last_name' => (string) $guest->last_name,
                'email' => $this->contact($guest->email) ?? '',
                'phone' => $this->contact($guest->phone) ?? '',
                'city' => (string) $guest->city,
                'nationality' => (string) $guest->nationality,
                'is_vip' => (bool) $guest->is_vip,
                'risk_flag' => (bool) $guest->risk_flag,
                'prefers_email' => (bool) $guest->prefers_email,
                'marketing_consent' => (bool) $guest->marketing_consent,
            ] : null,
        ];
    }

    /**
     * The "Last stay" cell (#87) — the most relevant real state of this guest's most significant reservation:
     * "In house" now, an arrival ahead, the departure of the latest stay, or null for a guest who has never
     * booked. Derived from the SAME reservations + shared resolver as the badge, so the two cells never
     * disagree — and {@see orderByLastStay()} sorts by exactly this, so the sorted column never does either.
     *
     * @param  Collection<int, Reservation>|iterable<Reservation>  $reservations
     */
    private function lastStay($reservations, CarbonImmutable $today): ?string
    {
        $stage = ReservationStage::mostSignificant($reservations, $today);
        if ($stage === null) {
            return null;
        }
        if ($stage === ReservationStage::IN_HOUSE) {
            return 'In house';
        }

        // The latest non-cancelled reservation by check-in → its arrival (ahead) or its departure (past). A
        // cancelled booking is not a stay, so it never sources the label. The id breaks a tie on the check-in,
        // exactly as the Last stay sort does.
        $relevant = collect($reservations)
            ->reject(fn (Reservation $r): bool => in_array((string) $r->status, ReservationStage::CANCELLED_STATUSES, true))
            ->sort(fn (Reservation $a, Reservation $b): int => [(string) $b->check_in, (string) $a->getKey()] <=> [(string) $a->check_in, (string) $b->getKey()])
            ->first();
        if ($relevant === null) {
            return null;
        }

        $checkIn = CarbonImmutable::parse($relevant->check_in)->startOfDay();
        if ($stage === ReservationStage::UPCOMING || $checkIn->greaterThan($today)) {
            return $checkIn->isSameDay($today) ? 'Arrives today' : 'Arrives '.$checkIn->format('j M Y');
        }

        return CarbonImmutable::parse($relevant->check_out)->format('j M Y');
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

    /**
     * The Edit dialog, reopened with what was typed when the server refused it (2026-09-29) — a bad email
     * address comes back to the list with its reason, and the operator should not have to type it all again.
     * Only the list's own Edit form carries `_guest`, so the profile's refusals never open this.
     *
     * @return array<string, string|bool>|null
     */
    private function editRetry(): ?array
    {
        $id = old('_guest');
        if (! is_string($id) || preg_match('/^[0-9A-Za-z]{26}$/', $id) !== 1 || ! session()->has('errors')) {
            return null;
        }

        $text = fn (string $key): string => is_string(old($key)) ? (string) old($key) : '';

        return [
            'id' => $id,
            'name' => trim($text('first_name').' '.$text('last_name')),
            'first_name' => $text('first_name'), 'last_name' => $text('last_name'),
            'email' => $text('email'), 'phone' => $text('phone'), 'city' => $text('city'), 'nationality' => $text('nationality'),
            'is_vip' => (bool) old('is_vip'), 'risk_flag' => (bool) old('risk_flag'),
            'prefers_email' => (bool) old('prefers_email'), 'marketing_consent' => (bool) old('marketing_consent'),
        ];
    }

    // ── The controls — each link keeps every other setting ───────────────────────────────────────

    /**
     * All · In-house · Upcoming · VIP, each with the REAL count of what clicking it shows.
     *
     * The counts include the ACTIVE SEARCH: a chip that promised "VIP 5" and then showed 2 once clicked
     * would be lying about what the click delivers.
     *
     * @param  ListState  $state
     * @return list<array{key: string, label: string, count: int, active: bool, href: string}>
     */
    private function chips(array $state): array
    {
        $chips = [];

        foreach ([[null, 'All'], ['in_house', 'In-house'], ['upcoming', 'Upcoming'], ['vip', 'VIP']] as [$filter, $label]) {
            $chips[] = [
                'key' => $filter ?? 'all',
                'label' => $label,
                'count' => $this->baseQuery($filter, null, $state['q'])->count(),
                'active' => $state['filter'] === $filter,
                'href' => $this->href($state, ['filter' => $filter, 'tag' => null]),
            ];
        }

        return $chips;
    }

    /**
     * The sortable headers. A column's link turns its own order round; another column's starts in that
     * column's direction ({@see GuestListParams::SORTS}).
     *
     * @param  ListState  $state
     * @return array<string, array{href: string, active: bool, dir: string, aria: string}>
     */
    private function sortLinks(array $state): array
    {
        $links = [];

        foreach (GuestListParams::SORTS as $column => $start) {
            $active = $state['sort'] === $column;
            $next = $active ? ($state['dir'] === 'asc' ? 'desc' : 'asc') : $start;

            $links[$column] = [
                'href' => $this->href($state, ['sort' => $column, 'dir' => $next]),
                'active' => $active,
                'dir' => $active ? $state['dir'] : '',
                'aria' => $active ? ($state['dir'] === 'asc' ? 'ascending' : 'descending') : 'none',
            ];
        }

        return $links;
    }

    /**
     * @param  ListState  $state
     * @return list<array{size: int, active: bool, href: string}>
     */
    private function sizeLinks(array $state): array
    {
        return array_map(fn (int $size): array => [
            'size' => $size,
            'active' => $state['per'] === $size,
            'href' => $this->href($state, ['per' => $size]),
        ], GuestListParams::PER_PAGE_OPTIONS);
    }

    /**
     * Previous / next, built like every other link (the paginator's own URLs would carry whatever else was in
     * the query string).
     *
     * @param  ListState  $state
     * @param  LengthAwarePaginator<int, array<string, mixed>>  $rows
     * @return array{prev: ?string, next: ?string}
     */
    private function pageLinks(array $state, LengthAwarePaginator $rows): array
    {
        $page = $rows->currentPage();

        return [
            'prev' => $page > 1 ? GuestListParams::url($this->params($state) + ['page' => $page - 1]) : null,
            'next' => $rows->hasMorePages() ? GuestListParams::url($this->params($state) + ['page' => $page + 1]) : null,
        ];
    }

    /** @param  ListState  $state */
    private function sortedBy(array $state): string
    {
        return match ($state['sort'].'.'.$state['dir']) {
            'name.asc' => 'A–Z by surname',
            'name.desc' => 'Z–A by surname',
            'stays.desc' => 'most stays first',
            'stays.asc' => 'fewest stays first',
            'last_stay.desc' => 'latest stay first',
            'last_stay.asc' => 'earliest stay first',
        };
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
}
