<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Livewire\Operator\OperatorOwners;
use App\Support\Unit\UnitListParams;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The Owners page's URL settings (2026-10-01) — filter, search, sort, direction, page size and page — read ONE way. The
 * page ({@see OperatorOwners}) and its export read their request through {@see clean()} and build every link through
 * {@see url()}; an action started from the page comes back through {@see returnUrl()}, so the page and the way back cannot
 * disagree, and the way back can only ever be this page. The same shape as {@see UnitListParams}.
 *
 * ⚠️ It lives HERE, not on the Livewire component: a public method on an operator component is callable over
 * /livewire/update, where no route middleware runs (OperatorLivewireMutationBoundaryTest).
 */
final class OwnerListParams
{
    public const PER_PAGE = 25;

    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [25, 50, 100];

    /**
     * The columns the table sorts by, each with the direction it starts in: owner A–Z, most units first, the largest net
     * payable first, and status with what needs someone first.
     *
     * @var array<string, 'asc'|'desc'>
     */
    public const SORTS = ['name' => 'asc', 'units' => 'desc', 'net' => 'desc', 'status' => 'asc'];

    /**
     * The chips: ready to pay, on hold, needs review, payouts paused, and invited (not yet activated).
     *
     * @var list<string>
     */
    public const FILTERS = ['ready', 'hold', 'review', 'paused', 'invited'];

    public const MAX_QUERY = 80;

    /**
     * The settings from a query string, each one checked; defaults are left out, so every link is the shortest URL for its
     * view and the keys always come out in the same order.
     *
     * @param  array<array-key, mixed>  $raw
     * @return array{filter?: string, q?: string, sort?: 'units'|'net'|'status', dir?: 'asc'|'desc', per?: int, page?: int}
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
     * The page's URL for these settings — the ONE place its links and its way back are built.
     *
     * @param  array<array-key, mixed>  $params
     */
    public static function url(array $params): string
    {
        $clean = self::clean($params);

        return route('app.owners').($clean === [] ? '' : '?'.http_build_query($clean));
    }

    /**
     * Where an action started from the page comes back to: the page as the operator had it. `$query` is the page's own
     * query string; it is PARSED and REBUILT through {@see clean()} — never used as a URL.
     */
    public static function returnUrl(mixed $query): string
    {
        $raw = [];
        if (is_string($query) && strlen($query) <= 2000) {
            parse_str($query, $raw);
        }

        return self::url($raw);
    }

    /**
     * Back to the page as it was when the form says it was started there (`return=list`); otherwise null, and the caller
     * keeps its own destination.
     */
    public static function redirect(Request $request): ?RedirectResponse
    {
        return $request->input('return') === 'list' ? redirect()->to(self::returnUrl($request->input('return_query'))) : null;
    }

    private static function wholeNumber(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && $value !== '' && strlen($value) <= 9 && ctype_digit($value) ? (int) $value : null;
    }
}
