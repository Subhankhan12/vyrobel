<?php

declare(strict_types=1);

namespace App\Support\Rates;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The Rates page's own settings — the two-week window (`from`) and the room-type filter (`rt`) — and the way back to the page
 * as the operator had it after a write.
 *
 * ⚠️ REBUILT, NEVER TAKEN. A form started from the page stamps `return=list` and the page's own query string; the address is
 * rebuilt here from these two allow-listed keys, so nothing a request carries can become a redirect target (an off-site
 * value, a scheme, junk keys all fall away). Before, every write sent the operator to a window starting at the first night
 * they had edited — editing 5 Oct from the 30 Sep window moved the page — and dropped the filter.
 */
final class RatesListParams
{
    /**
     * @return array{from?: string, rt?: string}
     */
    public static function clean(mixed $query): array
    {
        if (! is_string($query) || $query === '' || strlen($query) > 500) {
            return [];
        }

        parse_str(ltrim($query, '?'), $raw);
        $out = [];

        $from = $raw['from'] ?? null;
        if (is_string($from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1) {
            [$y, $m, $d] = array_map('intval', explode('-', $from));
            if (checkdate($m, $d, $y)) {
                $out['from'] = $from;
            }
        }

        $rt = $raw['rt'] ?? null;
        if (is_string($rt) && preg_match('/^[0-9A-Za-z]{1,40}$/', $rt) === 1) {
            $out['rt'] = $rt;
        }

        return $out;
    }

    public static function returnUrl(mixed $query): string
    {
        return route('app.rates', self::clean($query));
    }

    /**
     * Back to the page: as the operator had it when the write was started there (`return=list`), otherwise the window
     * starting at `$fallbackFrom` — the old destination, kept for every caller that does not start from the page.
     */
    public static function redirect(Request $request, ?string $fallbackFrom = null): RedirectResponse
    {
        if ($request->input('return') === 'list') {
            return redirect()->to(self::returnUrl($request->input('return_query')));
        }

        return redirect()->to(route('app.rates', $fallbackFrom !== null ? ['from' => $fallbackFrom] : []));
    }
}
