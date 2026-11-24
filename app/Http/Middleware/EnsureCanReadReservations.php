<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Authorization\ReservationRead;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * VY-14 — the route gate on the reservations list and a reservation's page: 403 unless the person holds one of
 * {@see ReservationRead::PERMISSIONS}. A middleware of its own (not `can:`) because the rule is EITHER of two permissions and
 * `can:` takes one; the rule itself lives in {@see ReservationRead} so the links that lead here read the same thing.
 *
 * ⚠️ It runs where `can:` runs — after the tenant middleware has set the spatie team (a null team fails silently to false) — and is
 *    registered as a Livewire persistent middleware, so a page left open does not keep answering after the role is taken away.
 */
class EnsureCanReadReservations
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(ReservationRead::allowed($request->user()), 403);

        return $next($request);
    }
}
