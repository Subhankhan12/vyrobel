<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Exceptions\Booking\UnitUnavailableException;
use App\Exceptions\Owner\OwnerBlockException;
use App\Http\Controllers\Controller;
use App\Services\OwnerBlockService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Owner Block-Calendar writes (Phase 2 · Sub-gate 2) — POST → {@see OwnerBlockService} → PRG. The owner holds /
 * releases dates on their OWN units. Owner isolation is enforced in the service (the unit is resolved from the
 * owner's own units, never the request id); a tampering attempt surfaces here as a 403. An ordinary date
 * conflict (a booked / already-blocked night) is the REAL rule from `assertUnitFree` and is shown as a friendly
 * message, never a crash.
 */
class OwnerBlockController extends Controller
{
    public function store(Request $request, OwnerBlockService $blocks): RedirectResponse
    {
        $data = $request->validate([
            'unit_id' => ['required', 'string'],
            'first_night' => ['required', 'date'],
            'last_night' => ['required', 'date', 'after_or_equal:first_night'],
            'reason' => ['required', 'string', Rule::in(OwnerBlockService::REASONS)],
        ]);

        $back = route('owner.calendar', [
            'unit' => $data['unit_id'],
            'month' => Carbon::parse($data['first_night'])->format('Y-m'),
        ]);

        try {
            $blocks->block($request->user(), $data['unit_id'], $data['first_night'], $data['last_night'], $data['reason']);
        } catch (OwnerBlockException) {
            abort(403);   // a foreign unit id — tampering, not a normal outcome
        } catch (UnitUnavailableException) {
            return redirect()->to($back)->with('block_error', 'Those dates include a guest booking or an existing block — pick open dates.');
        }

        return redirect()->to($back)->with('block_notice', 'Dates blocked. Guests can no longer book them.');
    }

    public function destroy(Request $request, OwnerBlockService $blocks, string $block): RedirectResponse
    {
        try {
            $blocks->release($block);
        } catch (OwnerBlockException) {
            abort(403);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        return redirect()->back()->with('block_notice', 'Block removed. Those dates are open again.');
    }
}
