<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Support\Guest\GuestEmailMatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * THE WIZARD'S "IS THERE ALREADY A GUEST WITH THIS EMAIL?" (DEC-r, 2026-11-11). A POST with a JSON body — never a GET, so the email stays out of URLs and
 * access logs — behind `/app` (#26, tenant scope), `can:reservations.create` (#30) and a throttle. It answers with NAMES only ({@see GuestEmailMatch}); the
 * response is never cached. ⚠️ It is outside Livewire on purpose: a Livewire update does not run the middleware that applies the working property.
 */
class GuestMatchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'string', 'max:254']]);

        return response()
            ->json(['matches' => GuestEmailMatch::for($validated['email'])])
            ->header('Cache-Control', 'no-store, private');
    }
}
