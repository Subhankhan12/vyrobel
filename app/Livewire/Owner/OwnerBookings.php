<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\OwnerLedgerEntry;
use App\Models\OwnerMonthlyTarget;
use App\Models\Unit;
use App\Services\OwnerPortalService;
use App\Support\Money\MoneyFormatter;
use App\Support\Owner\OwnerBookingsReader;
use App\Support\Owner\OwnerLedgerAttribution;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Bookings (Phase 2 · Sub-gate 1 → GATE 1 exact-match) — the owner's reservations ACROSS their own units,
 * owner-scoped and read-only. Styled to the decoded "Owner Bookings" wireframe, wired to LIVE owner-scoped data.
 *
 * ISOLATION — reservations carry NO owner_id (they are tenant-scoped). The owner boundary is applied THROUGH the
 * units by the ONE shared {@see OwnerBookingsReader} (the same builder the CSV export uses, so page + export can
 * never disagree): the set comes from the owner-scoped {@see OwnerPortalService::units()}, a non-linked user's set
 * is empty (fail-closed). Filters/sort/pagination ride GET (`?tab`/`source`/`status`/`unit`/`from`/`to`/`range`/
 * `q`/`sort`/`dir`/`page`) so the owner middleware re-runs on every navigation — a Livewire wire-action would
 * bypass {@see SetOwnerScopeForPortal} and leak (invariant #20). The two WRITES this page offers (Export CSV, set
 * the monthly Target) are CONTROLLER routes, never Livewire actions (#20/#30). Rows are handled as plain arrays
 * (list<BookingRow>) so PHPStan's invariant Collection<TValue> never bites at a method boundary.
 *
 * Honesty triage (every figure REAL — #39; guest NAME-only — #28):
 *   • REAL     — the reservations touching the owner's units (guest name-only, reference, unit, dates, nights,
 *                REAL source enum [OTA never a brand], REAL status enum + lifecycle stage) and their per-currency
 *                "Net to you" via the ONE shared {@see OwnerLedgerAttribution} (unattributed → "Not yet
 *                attributed", never a fake €0).
 *   • DERIVED  — the summary cards, the "Bookings over time" chart (per-check-in-month status counts + the real
 *                net line) and the "Monthly goal" ring: all folded from the real figures above. The goal % divides
 *                the REAL current-month net by the owner's OWN target ({@see OwnerMonthlyTarget}, a value they set
 *                — never a fabricated goal); with no target set, an honest "set a target" state, never a fake %.
 *
 * @phpstan-import-type BookingRow from OwnerBookingsReader
 */
#[Layout('components.layouts.owner')]
class OwnerBookings extends Component
{
    use FormatsOwnerData;

    /** The temporal tabs, mapped to the shared stage vocabulary. 'all' applies no stage filter. */
    private const TABS = ['upcoming', 'past', 'all'];

    /** Sortable columns ('net' sorts on the portfolio currency's minor). */
    private const SORTS = ['dates', 'guest', 'net'];

    /** Date-range presets offered by the "Any dates" control. */
    private const RANGES = ['this_month', 'last_month', 'next_30', 'this_year'];

    private const PER_PAGE = 10;

    public function render(): View
    {
        $today = CarbonImmutable::today();

        $portal = app(OwnerPortalService::class);
        $units = $portal->units();                             // owner-scoped, fail-closed when empty
        $ownerUnitIds = array_map('strval', $units->pluck('id')->all());

        $reader = app(OwnerBookingsReader::class);
        $bookings = $reader->rows($today);                     // list<BookingRow> — the FULL owner set (unsorted)
        $ledgerRows = $reader->ledgerRows();

        $currency = OwnerBookingsReader::primaryCurrency($ledgerRows);
        $filters = $this->filters($ownerUnitIds, $today);

        // The table set: filter → sort → paginate. Cards / chart / goal use the FULL set (not the filtered table).
        $sorted = $this->sortRows($this->applyFilters($bookings, $filters), $filters, $currency);
        $page = $this->pageOf(count($sorted));
        $rows = array_slice($sorted, ($page['page'] - 1) * self::PER_PAGE, self::PER_PAGE);

        return view('livewire.owner.bookings', [
            'viewer' => $this->viewer(),
            'unitsCount' => $units->count(),
            'reservationsCount' => count($bookings),
            'summary' => $this->summary($bookings, $ledgerRows),
            'chart' => $this->chart($bookings, $currency, $today),
            'goal' => $this->goal($bookings, $currency, $today),
            'rows' => $rows,
            'page' => $page,
            'filters' => $filters,
            'preserved' => $this->preservedQuery($filters),
            'sourceOptions' => $this->countedOptions($bookings, fn ($b): string => $b['source'], fn (string $v): string => OwnerBookingsReader::sourceLabel($v)),
            'statusOptions' => $this->countedOptions($bookings, fn ($b): string => $b['status'], fn (string $v): string => OwnerBookingsReader::statusBadge($v)['label']),
            'unitOptions' => $units->map(fn (Unit $u): array => [
                'id' => (string) $u->id,
                'label' => (string) ($u->name ?? $u->code),
            ])->values()->all(),
            'ranges' => $this->rangeLabels(),
        ]);
    }

    // ── Filters ───────────────────────────────────────────────────────────────────────────────────

    /**
     * The active filter selection, sanitised.
     *
     * @param  list<string>  $ownerUnitIds
     * @return array{tab: string, source: ?string, status: ?string, unit: ?string, from: ?string, to: ?string, range: ?string, q: ?string, sort: string, dir: string}
     */
    private function filters(array $ownerUnitIds, CarbonImmutable $today): array
    {
        $tab = $this->queryParam('tab');
        $unit = $this->queryParam('unit');
        $range = $this->queryParam('range');
        $sort = $this->queryParam('sort');
        $dir = $this->queryParam('dir');

        $from = $this->dateParam('from');
        $to = $this->dateParam('to');

        // A preset range wins over hand-typed from/to (the control sets exactly one).
        if ($range !== null && in_array($range, self::RANGES, true)) {
            [$from, $to] = $this->rangeBounds($range, $today);
        } else {
            $range = null;
        }

        $tab = in_array($tab, self::TABS, true) ? $tab : 'upcoming';

        return [
            'tab' => $tab,
            'source' => $this->queryParam('source'),
            'status' => $this->queryParam('status'),
            'unit' => ($unit !== null && in_array($unit, $ownerUnitIds, true)) ? $unit : null,
            'from' => $from,
            'to' => $to,
            'range' => $range,
            'q' => $this->queryParam('q'),
            'sort' => in_array((string) $sort, self::SORTS, true) ? (string) $sort : 'dates',
            'dir' => $dir === 'asc' || $dir === 'desc' ? $dir : ($tab === 'upcoming' ? 'asc' : 'desc'),
        ];
    }

    /**
     * The query params to carry on LINK navigation (tabs, sort headers, pagination, date presets). `sort`/`dir`
     * are carried ONLY when the user EXPLICITLY chose them (a raw request value), NEVER the tab-dependent DEFAULT
     * — so switching tabs correctly re-applies each tab's natural order (upcoming = soonest-first, past = most-
     * recent-first) instead of dragging the previous tab's defaulted direction along. A preset range is carried
     * as `range`; a custom span as `from`/`to` (never both, which would be redundant).
     *
     * @param  array{tab: string, source: ?string, status: ?string, unit: ?string, from: ?string, to: ?string, range: ?string, q: ?string, sort: string, dir: string}  $filters
     * @return array<string, string>
     */
    private function preservedQuery(array $filters): array
    {
        $out = ['tab' => $filters['tab']];
        if ($filters['source'] !== null) {
            $out['source'] = $filters['source'];
        }
        if ($filters['status'] !== null) {
            $out['status'] = $filters['status'];
        }
        if ($filters['unit'] !== null) {
            $out['unit'] = $filters['unit'];
        }
        if ($filters['q'] !== null) {
            $out['q'] = $filters['q'];
        }
        if ($filters['range'] !== null) {
            $out['range'] = $filters['range'];
        } else {
            if ($filters['from'] !== null) {
                $out['from'] = $filters['from'];
            }
            if ($filters['to'] !== null) {
                $out['to'] = $filters['to'];
            }
        }

        // Only an EXPLICIT sort/dir (raw request) persists — a defaulted direction must not become "sticky".
        $sortRaw = $this->queryParam('sort');
        $dirRaw = $this->queryParam('dir');
        if ($sortRaw !== null && in_array($sortRaw, self::SORTS, true)) {
            $out['sort'] = $sortRaw;
        }
        if ($dirRaw === 'asc' || $dirRaw === 'desc') {
            $out['dir'] = $dirRaw;
        }

        return $out;
    }

    /**
     * Filter the booking rows by the current selection. All in PHP over the already-owner-scoped set.
     *
     * @param  list<BookingRow>  $bookings
     * @param  array{tab: string, source: ?string, status: ?string, unit: ?string, from: ?string, to: ?string, range: ?string, q: ?string, sort: string, dir: string}  $filters
     * @return list<BookingRow>
     */
    private function applyFilters(array $bookings, array $filters): array
    {
        $q = $filters['q'] !== null ? mb_strtolower($filters['q']) : null;

        return array_values(array_filter($bookings, function (array $b) use ($filters, $q): bool {
            if ($filters['tab'] === 'upcoming' && $b['stage'] !== ReservationStage::UPCOMING) {
                return false;
            }
            if ($filters['tab'] === 'past' && $b['stage'] !== ReservationStage::PAST) {
                return false;
            }
            if ($filters['source'] !== null && $b['source'] !== $filters['source']) {
                return false;
            }
            if ($filters['status'] !== null && $b['status'] !== $filters['status']) {
                return false;
            }
            if ($filters['unit'] !== null && ! in_array($filters['unit'], $b['unit_ids'], true)) {
                return false;
            }
            if ($filters['from'] !== null && $b['check_in_sort'] < $filters['from']) {
                return false;
            }
            if ($filters['to'] !== null && $b['check_in_sort'] > $filters['to']) {
                return false;
            }
            if ($q !== null && $q !== '') {
                $hay = mb_strtolower(((string) $b['guest']).' '.$b['reference'].' '.$b['unit_label']);
                if (! str_contains($hay, $q)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Sort the filtered rows by the chosen column + direction. Net sorts on the portfolio currency's minor (an
     * unattributed booking sorts last, never as a fake 0).
     *
     * @param  list<BookingRow>  $rows
     * @param  array{sort: string, dir: string}  $filters
     * @return list<BookingRow>
     */
    private function sortRows(array $rows, array $filters, string $currency): array
    {
        $sign = $filters['dir'] === 'desc' ? -1 : 1;

        if ($filters['sort'] === 'net') {
            // A booking with NO net in the portfolio currency (unattributed, OR attributed only in another
            // currency) sinks to the bottom either direction — never intermixed as a literal €0.
            $net = static fn (array $b): int => array_key_exists($currency, $b['net_minor']) ? $b['net_minor'][$currency] : PHP_INT_MAX;

            usort($rows, function (array $a, array $b) use ($net, $sign): int {
                $an = $net($a);
                $bn = $net($b);

                // The sentinel always sinks, regardless of the requested direction.
                if ($an === PHP_INT_MAX || $bn === PHP_INT_MAX) {
                    return $an <=> $bn;
                }

                return $sign * ($an <=> $bn);
            });

            return $rows;
        }

        $key = $filters['sort'] === 'guest' ? 'guest' : 'check_in_sort';
        usort($rows, fn (array $a, array $b): int => $sign * strcasecmp((string) $a[$key], (string) $b[$key]));

        return $rows;
    }

    // ── Summary + options ───────────────────────────────────────────────────────────────────────────

    /**
     * Portfolio summary over the FULL owner set: upcoming/past counts, DISTINCT guests, total nights, and the
     * portfolio net per currency.
     *
     * @param  list<BookingRow>  $bookings
     * @param  Collection<int, OwnerLedgerEntry>  $ledgerRows
     * @return array{upcoming: int, past: int, guests: int, nights: int, net: list<array{display: string}>}
     */
    private function summary(array $bookings, Collection $ledgerRows): array
    {
        $net = [];
        foreach (OwnerLedgerAttribution::netPerCurrency($ledgerRows) as $ccy => $minor) {
            $net[] = ['display' => $this->money($minor, $ccy)];
        }

        $guestIds = array_filter(array_map(fn (array $b): ?string => $b['guest_id'], $bookings));

        return [
            'upcoming' => count(array_filter($bookings, fn (array $b): bool => $b['stage'] === ReservationStage::UPCOMING)),
            'past' => count(array_filter($bookings, fn (array $b): bool => $b['stage'] === ReservationStage::PAST)),
            'guests' => count(array_unique($guestIds)),
            'nights' => array_sum(array_map(fn (array $b): int => $b['nights'], $bookings)),
            'net' => $net,
        ];
    }

    /**
     * The distinct values present for a field, as {value,label,count}, so the filter never offers an option with
     * no bookings behind it and can show a count.
     *
     * @param  list<BookingRow>  $bookings
     * @param  callable(BookingRow): string  $value
     * @param  callable(string): string  $label
     * @return list<array{value: string, label: string, count: int}>
     */
    private function countedOptions(array $bookings, callable $value, callable $label): array
    {
        /** @var array<string, int> $counts */
        $counts = [];
        foreach ($bookings as $b) {
            $key = $value($b);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $out = [];
        foreach ($counts as $val => $count) {
            $out[] = ['value' => (string) $val, 'label' => $label((string) $val), 'count' => $count];
        }

        usort($out, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $out;
    }

    // ── Chart ("Bookings over time") ────────────────────────────────────────────────────────────────

    /**
     * The chart series: per check-in month, the status-segmented booking counts + the REAL net revenue (the line)
     * — plus the goal line level. Everything folds from the real owner set; the blade only scales + draws.
     *
     * @param  list<BookingRow>  $bookings
     * @return array{currency: string, months: list<array<string, mixed>>, max_count: int, max_money: int, goal_minor: ?int, goal_pct: ?float, legend: array{confirmed: int, pending: int, other: int}}
     */
    private function chart(array $bookings, string $currency, CarbonImmutable $today): array
    {
        $currentMonth = $today->format('Y-m');

        /** @var array<string, list<BookingRow>> $groups */
        $groups = [];
        foreach ($bookings as $b) {
            $groups[$b['month']][] = $b;
        }
        ksort($groups);

        $months = [];
        foreach ($groups as $month => $items) {
            $confirmed = count(array_filter($items, fn (array $b): bool => $b['status'] === 'confirmed'));
            $pending = count(array_filter($items, fn (array $b): bool => in_array($b['status'], ['tentative', 'enquiry'], true)));
            $total = count($items);
            $netMinor = array_sum(array_map(fn (array $b): int => $b['net_minor'][$currency] ?? 0, $items));

            $months[] = [
                'month' => (string) $month,
                'label' => Carbon::parse($month.'-01')->format('M ’y'),
                'confirmed' => $confirmed,
                'pending' => $pending,
                'other' => $total - $confirmed - $pending,
                'total_count' => $total,
                'net_minor' => $netMinor,
                'net_display' => MoneyFormatter::compact($netMinor, $currency),
                'is_current' => (string) $month === $currentMonth,
            ];
        }

        // Keep the chart legible: at most the 8 most-recent check-in months.
        if (count($months) > 8) {
            $months = array_slice($months, -8);
        }

        // The legend counts over the DISPLAYED months only, so it always reconciles with the bars it captions
        // (counting the full set here would over-report against a truncated chart — a false honesty signal).
        $legend = [
            'confirmed' => array_sum(array_column($months, 'confirmed')),
            'pending' => array_sum(array_column($months, 'pending')),
            'other' => array_sum(array_column($months, 'other')),
        ];

        $goalMinor = $this->targetMinor($currency);
        $maxCount = max(array_merge([1], array_column($months, 'total_count')));
        $maxMoney = max(array_merge([1, $goalMinor ?? 0], array_column($months, 'net_minor')));

        $months = array_map(function (array $m) use ($maxCount, $maxMoney): array {
            $m['count_pct'] = (int) round($m['total_count'] / $maxCount * 100);
            $m['net_pct'] = $maxMoney > 0 ? round(max(0, $m['net_minor']) / $maxMoney * 100, 1) : 0.0;

            return $m;
        }, $months);

        return [
            'currency' => $currency,
            'months' => $months,
            'max_count' => $maxCount,
            'max_money' => $maxMoney,
            'goal_minor' => $goalMinor,
            'goal_pct' => $goalMinor !== null && $maxMoney > 0 ? round($goalMinor / $maxMoney * 100, 1) : null,
            'legend' => $legend,
        ];
    }

    // ── Monthly goal ring ───────────────────────────────────────────────────────────────────────────

    /**
     * The "Monthly goal" ring: the REAL current-month net vs the owner's OWN target. No target set → an honest
     * prompt state, never a fabricated %.
     *
     * @param  list<BookingRow>  $bookings
     * @return array{currency: string, month_label: string, month_net_minor: int, month_net_display: string, has_target: bool, target_minor: ?int, target_display: ?string, target_input: string, pct: ?int, delta_display: ?string, over: bool}
     */
    private function goal(array $bookings, string $currency, CarbonImmutable $today): array
    {
        $currentMonth = $today->format('Y-m');
        $thisMonth = array_filter($bookings, fn (array $b): bool => $b['month'] === $currentMonth);
        $monthNet = array_sum(array_map(fn (array $b): int => $b['net_minor'][$currency] ?? 0, $thisMonth));

        $target = $this->targetMinor($currency);
        $hasTarget = $target !== null && $target > 0;

        $pct = $hasTarget ? (int) round($monthNet / $target * 100) : null;
        $delta = $hasTarget ? $monthNet - $target : 0;

        return [
            'currency' => $currency,
            'month_label' => $today->format('M ’y'),
            'month_net_minor' => $monthNet,
            'month_net_display' => MoneyFormatter::compact($monthNet, $currency),
            'has_target' => $hasTarget,
            'target_minor' => $target,
            'target_display' => $hasTarget ? MoneyFormatter::compact($target, $currency) : null,
            // Pre-fill the edit input with the real current target in plain units (e.g. "6000"), else blank.
            'target_input' => $hasTarget ? (string) (int) round($target / 100) : '',
            'pct' => $pct,
            'delta_display' => $hasTarget ? MoneyFormatter::compact(abs($delta), $currency) : null,
            'over' => $delta >= 0,
        ];
    }

    // ── Small helpers ───────────────────────────────────────────────────────────────────────────────

    /**
     * The owner's monthly target minor for a currency — summed across the scoped owner(s) (a portfolio may span
     * more than one owner). Owner-scoped read; null when none is set (→ honest "no target").
     */
    private function targetMinor(string $currency): ?int
    {
        $sum = (int) OwnerMonthlyTarget::query()
            ->where('currency', $currency)
            ->sum('target_minor');

        return $sum > 0 ? $sum : null;
    }

    /**
     * The effective [from, to] Y-m-d bounds for a preset range.
     *
     * @return array{0: string, 1: string}
     */
    private function rangeBounds(string $range, CarbonImmutable $today): array
    {
        // ⚠️ Start from the 1st before subtracting a month: `$today->subMonth()` OVERFLOWS on a 31st (e.g.
        // 2026-07-31 → 2026-07-01, landing back IN July), which would make "Last month" == "This month". The 1st
        // never overflows. CarbonImmutable, so each expression is independent (no mutation across the pair).
        $lastMonthStart = $today->startOfMonth()->subMonth();

        return match ($range) {
            'this_month' => [$today->startOfMonth()->toDateString(), $today->endOfMonth()->toDateString()],
            'last_month' => [$lastMonthStart->toDateString(), $lastMonthStart->endOfMonth()->toDateString()],
            'next_30' => [$today->toDateString(), $today->addDays(30)->toDateString()],
            'this_year' => [$today->startOfYear()->toDateString(), $today->endOfYear()->toDateString()],
            default => [$today->toDateString(), $today->toDateString()],
        };
    }

    /**
     * Human labels for the date-range presets (for the "Any dates" menu).
     *
     * @return array<string, string>
     */
    private function rangeLabels(): array
    {
        return [
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'next_30' => 'Next 30 days',
            'this_year' => 'This year',
        ];
    }

    /**
     * The current page + the "Showing X–Y of T" figures for a filtered total.
     *
     * @return array{page: int, per_page: int, total: int, pages: int, from: int, to: int, has_prev: bool, has_next: bool}
     */
    private function pageOf(int $total): array
    {
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $raw = request()->query('page');
        $page = is_string($raw) && ctype_digit($raw) ? (int) $raw : 1;
        $page = max(1, min($page, $pages));

        $from = $total === 0 ? 0 : ($page - 1) * self::PER_PAGE + 1;
        $to = min($page * self::PER_PAGE, $total);

        return [
            'page' => $page,
            'per_page' => self::PER_PAGE,
            'total' => $total,
            'pages' => $pages,
            'from' => $from,
            'to' => $to,
            'has_prev' => $page > 1,
            'has_next' => $page < $pages,
        ];
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

    /** A YYYY-MM-DD query value, or null. */
    private function dateParam(string $key): ?string
    {
        $value = $this->queryParam($key);

        return $value !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }
}
