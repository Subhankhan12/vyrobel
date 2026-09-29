<?php

declare(strict_types=1);

namespace App\Support\Guest;

use App\Livewire\Operator\GuestsList;
use App\Support\Reservations\ReservationStage;

/**
 * The Guests list's URL settings (2026-09-29) — filter, tag, search, sort, direction, page size and page — read ONE way.
 * The page ({@see GuestsList}) reads its request through {@see clean()} and builds every link through {@see url()}, and a
 * row's Edit or Erase comes back through {@see returnUrl()}, so the list and the way back can never disagree about what
 * a setting means — and the way back can only ever be the Guests list.
 *
 * ⚠️ IT LIVES HERE, NOT ON THE LIVEWIRE COMPONENT, ON PURPOSE. A public method on an operator component is callable over
 * /livewire/update, where no route middleware runs (OperatorLivewireMutationBoundaryTest, which caught these two there on
 * the first full run). They write nothing, but the rule is structural, so that no ungated entry point can ever appear.
 */
final class GuestListParams
{
    /** Rows per page by default — the same size across the three operator lists, so the realm feels consistent. */
    public const PER_PAGE = 25;

    /**
     * The page sizes the list offers. Anything else in `?per=` falls back to PER_PAGE, so the URL can never ask for an
     * unbounded page.
     *
     * @var list<int>
     */
    public const PER_PAGE_OPTIONS = [25, 50, 100];

    /**
     * The columns the table sorts by, each with the direction it starts in: A–Z for the guest, most first for stays,
     * latest first for the last stay. NOTHING ELSE SORTS — a `?sort=` outside this list falls back to the guest's name,
     * so the URL can never order people by a column the list does not show (an encrypted email, say). Lifetime is not
     * sortable: it is per currency, and adding EUR to USD to rank guests would invent an exchange rate (invariant #17).
     *
     * @var array<string, 'asc'|'desc'>
     */
    public const SORTS = ['name' => 'asc', 'stays' => 'desc', 'last_stay' => 'desc'];

    /**
     * The segmented filters the list offers (#87, matching the wireframe): All · In-house · Upcoming · VIP.
     * `in_house`/`upcoming` are REAL stay-state filters via the shared {@see ReservationStage::scope()} (never a
     * re-derived predicate); `vip` is the real `is_vip` column.
     *
     * @var list<string>
     */
    public const FILTERS = ['in_house', 'upcoming', 'vip'];

    /** Bound the search term: a LIKE pattern is not a place to accept unbounded user input. */
    public const MAX_QUERY = 100;

    /**
     * The list's settings from a query string, each one checked. Defaults are left out, so every link the page builds is
     * the shortest URL for its view, and the keys always come out in the same order.
     *
     * @param  array<array-key, mixed>  $raw
     * @return array{filter?: string, tag?: string, q?: string, sort?: 'stays'|'last_stay', dir?: 'asc'|'desc', per?: int, page?: int}
     */
    public static function clean(array $raw): array
    {
        $clean = [];

        $filter = $raw['filter'] ?? null;
        if (is_string($filter) && in_array($filter, self::FILTERS, true)) {
            $clean['filter'] = $filter;
        }

        // A tag id is a ULID. Whether it names a tag in THIS tenant is the page's question (GuestsList::state()).
        $tag = $raw['tag'] ?? null;
        if (is_string($tag) && preg_match('/^[0-9A-Za-z]{26}$/', $tag) === 1) {
            $clean['tag'] = $tag;
        }

        $q = $raw['q'] ?? null;
        $q = is_string($q) ? mb_substr(trim($q), 0, self::MAX_QUERY) : '';
        if ($q !== '') {
            $clean['q'] = $q;
        }

        $sort = $raw['sort'] ?? null;
        $sort = is_string($sort) && array_key_exists($sort, self::SORTS) ? $sort : 'name';
        if ($sort !== 'name') {
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
     * The Guests list URL for these settings — the ONE place its links and its way back are built.
     *
     * @param  array<array-key, mixed>  $params
     */
    public static function url(array $params): string
    {
        $clean = self::clean($params);

        return route('app.guests').($clean === [] ? '' : '?'.http_build_query($clean));
    }

    /**
     * Where a row's Edit or Erase comes back to: the list the operator was on. `$query` is the list's own query string as
     * the browser had it. It is PARSED and REBUILT through {@see clean()} — never used as a URL — so the answer is always
     * the Guests list, whatever was sent.
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
