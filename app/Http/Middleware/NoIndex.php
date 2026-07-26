<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SUPER ADMIN · GATE 0 — mark a response non-indexable. The /admin realm is behind auth, non-public, and must
 * never be indexed. It lives on a PATH (not a subdomain), so the main domain's SEO/GEO is unaffected; this
 * header (plus the `<meta robots>` in the admin shell and the robots.txt disallow) belts-and-braces it out of
 * every crawler.
 */
class NoIndex
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }
}
