<?php

declare(strict_types=1);

namespace App\Support\Http;

use Illuminate\Http\Request;

/**
 * THE JSON ENDPOINTS THAT LIVE OUTSIDE `/api` — and so need telling apart from a page (D-GM-EMPTY's sweep, 2026-11-12).
 *
 * ⚠️ This app renders an error as JSON only under `/api` (`bootstrap/app.php`, `shouldRenderJsonWhen`) — whatever Accept header a caller sends. So a JSON
 *    endpoint anywhere else that calls `$request->validate()` answers a bad call with a REDIRECT, and a missing record with the HTML 404 page. The guest
 *    lookup did exactly that to a blank email on the live site; the public booking engine's machine contract (`/book/{property}/search|quote|book|
 *    confirmation`, "JSON API (machine clients)" in `routes/web.php`) had the same flaw since it was built and no machine client had hit it yet.
 *
 * ⚠️ ONE LIST, TWO READERS: the exception renderer asks {@see self::matches()}, and `NonApiJsonEndpointsAnswerJsonTest` scans every route that returns a
 *    `JsonResponse` outside `/api` and fails unless it is here or never calls `validate()` — so a new JSON endpoint cannot be added and forgotten.
 *
 * ⚠️ Only an endpoint whose callers are MACHINES (or a script that reads the answer) belongs here. A page form on the same prefix — the booking checkout's
 *    POST — stays a redirect with its errors, because a person reads that one.
 */
final class JsonErrorRoutes
{
    /** @var list<string> route names whose errors (422, 404, 403 …) are answered as JSON */
    public const NAMES = [
        'booking.search',
        'booking.quote',
        'booking.book',
        'booking.confirmation',
    ];

    public static function matches(Request $request): bool
    {
        return $request->routeIs(...self::NAMES);
    }
}
