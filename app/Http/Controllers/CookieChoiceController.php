<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Privacy\CookieConsent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * RECORD THE VISITOR'S ANSWER TO THE COOKIE NOTICE (front-website Gate 6, ruling D11).
 *
 * ⚠️ THE ONLY WRITER OF {@see CookieConsent::COOKIE}, and it writes nothing else. Accept and decline are the
 * same code path with a different value — there is no route by which declining costs more clicks, more
 * time, or a different page than accepting.
 *
 * ⚠️ NOTHING NON-ESSENTIAL IS SET HERE OR ANYWHERE ELSE BEFORE THE ANSWER. There is nothing non-essential to
 * set: the product has no analytics, marketing or third-party cookie at all, which is what the notice says
 * and what CI Gate 7 keeps true.
 */
class CookieChoiceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $choice = $request->input('choice') === CookieConsent::ACCEPTED
            ? CookieConsent::ACCEPTED
            : CookieConsent::DECLINED;

        return redirect()
            ->to(self::safeReturnPath($request))
            ->withCookie(CookieConsent::record($choice, $request));
    }

    /**
     * Where to send the visitor back to.
     *
     * ⚠️ A PATH, NEVER A URL. The form carries the page it was shown on so the visitor lands back where they
     * were — but an attacker could post any value, and `back()` would follow a `Referer` just as blindly. So
     * the host, scheme and any protocol-relative prefix are refused outright and anything unrecognised falls
     * to "/". An open redirect on a consent form would be a poor first impression.
     */
    private static function safeReturnPath(Request $request): string
    {
        $raw = $request->input('return');

        if (! is_string($raw) || $raw === '' || ! str_starts_with($raw, '/') || str_starts_with($raw, '//')) {
            return '/';
        }

        // Strip anything that could carry a host, and keep only the path + query.
        $path = (string) parse_url($raw, PHP_URL_PATH);
        $query = parse_url($raw, PHP_URL_QUERY);
        $fragment = '#vy-cookie-notice';

        if ($path === '' || parse_url($raw, PHP_URL_HOST) !== null) {
            return '/';
        }

        return $path.(is_string($query) && $query !== '' ? '?'.$query : '').$fragment;
    }
}
