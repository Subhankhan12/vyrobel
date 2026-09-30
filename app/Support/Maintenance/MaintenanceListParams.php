<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Livewire\MaintenanceList;
use App\Support\Guest\GuestListParams;

/**
 * The Maintenance board's URL settings (2026-09-29) — filter, search, sort, direction, page size and page — read ONE
 * way. The board ({@see MaintenanceList}) reads its request through {@see clean()} and builds every link through
 * {@see url()}; an action started from the board comes back through {@see returnUrl()}, so the board and the way back
 * can never disagree, and the way back can only ever be the board.
 *
 * ⚠️ It lives HERE, not on the Livewire component: a public method on an operator component is callable over
 * /livewire/update, where no route middleware runs (OperatorLivewireMutationBoundaryTest). The same shape as
 * {@see GuestListParams}.
 */
final class MaintenanceListParams
{
    /** Rows per page by default — the same size across the operator lists. */
    public const PER_PAGE = 25;

    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [25, 50, 100];

    /**
     * The columns the table sorts by, each with the direction it starts in: priority urgent-first, time to target
     * soonest-first, reported newest-first, unit A–Z. Nothing else sorts; a `?sort=` outside this list falls back to
     * priority — the order the board always had.
     *
     * @var array<string, 'asc'|'desc'>
     */
    public const SORTS = ['priority' => 'asc', 'due' => 'asc', 'reported' => 'desc', 'unit' => 'asc'];

    /**
     * The board's filters (the chips) — the same keys and the same one matching rule the board has always used.
     *
     * @var list<string>
     */
    public const FILTERS = ['active', 'open', 'inprog', 'urgent', 'parts', 'ooo', 'done'];

    /** Bound the search term. */
    public const MAX_QUERY = 80;

    /**
     * The board's settings from a query string, each one checked; defaults are left out, so every link is the shortest
     * URL for its view and the keys always come out in the same order.
     *
     * @param  array<array-key, mixed>  $raw
     * @return array{filter?: string, q?: string, sort?: 'due'|'reported'|'unit', dir?: 'asc'|'desc', per?: int, page?: int}
     */
    public static function clean(array $raw): array
    {
        $clean = [];

        $filter = $raw['filter'] ?? null;
        if (is_string($filter) && in_array($filter, self::FILTERS, true)) {
            $clean['filter'] = $filter;
        }

        $q = $raw['q'] ?? null;
        $q = is_string($q) ? mb_substr(trim($q), 0, self::MAX_QUERY) : '';
        if ($q !== '') {
            $clean['q'] = $q;
        }

        $sort = $raw['sort'] ?? null;
        $sort = is_string($sort) && array_key_exists($sort, self::SORTS) ? $sort : 'priority';
        if ($sort !== 'priority') {
            $clean['sort'] = $sort;
        }

        $dir = $raw['dir'] ?? null;
        if (is_string($dir) && in_array($dir, ['asc', 'desc'], true) && $dir !== self::SORTS[$sort]) {
            $clean['dir'] = $dir;
        }

        $per = self::wholeNumber($raw['per'] ?? null);
        if ($per !== null && $per !== self::PER_PAGE && in_array($per, self::PER_PAGE_OPTIONS, true)) {
            $clean['per'] = $per;
        }

        $page = self::wholeNumber($raw['page'] ?? null);
        if ($page !== null && $page >= 2) {
            $clean['page'] = $page;
        }

        return $clean;
    }

    /**
     * The board's URL for these settings — the ONE place its links and its way back are built.
     *
     * @param  array<array-key, mixed>  $params
     */
    public static function url(array $params): string
    {
        $clean = self::clean($params);

        return route('app.maintenance').($clean === [] ? '' : '?'.http_build_query($clean));
    }

    /**
     * Where an action started from the board comes back to: the board as the operator had it. `$query` is the board's
     * own query string; it is PARSED and REBUILT through {@see clean()} — never used as a URL — so the answer is always
     * the Maintenance board, whatever was sent.
     */
    public static function returnUrl(mixed $query): string
    {
        $raw = [];
        if (is_string($query) && strlen($query) <= 2000) {
            parse_str($query, $raw);
        }

        return self::url($raw);
    }

    /** A whole number from the URL ("50", 50), or null — "50abc" and "-1" are not page sizes. */
    private static function wholeNumber(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && $value !== '' && strlen($value) <= 9 && ctype_digit($value) ? (int) $value : null;
    }
}
