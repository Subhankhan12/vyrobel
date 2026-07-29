<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Exceptions\Booking\UnitUnavailableException;
use App\Exceptions\Owner\OwnerBlockException;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\OwnerBlockService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
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
            'note' => ['nullable', 'string', 'max:200'],
            // Optional REPEAT: expand into real individual blocks (never a fake series). Both must arrive together.
            'repeat_freq' => ['nullable', Rule::in(OwnerBlockService::FREQUENCIES)],
            'repeat_count' => ['nullable', 'integer', 'min:2', 'max:'.OwnerBlockService::MAX_OCCURRENCES],
        ]);

        // 🔒 P1-1: holding dates is a CAP_ACTIONS write — the unit's OWNER must be one the acting user has actions
        // on (per-owner, not the union). If the unit is IN the owner scope but on an owner the user lacks actions
        // on (e.g. a co-owner of A + view-only of B blocking B's unit), 403 here. A TRULY foreign unit resolves to
        // null under the scope and falls through to the service's own OwnerBlockException → 403 (unchanged).
        $unit = Unit::query()->find($data['unit_id']);   // owner-scoped; null when foreign
        if ($unit !== null) {
            abort_unless(app(OwnerContext::class)->canOn((string) $unit->owner_id, OwnerTeamRole::CAP_ACTIONS), 403);
        }

        // The stored reason note = the picked reason, plus the optional free note (split back out for display).
        $note = trim((string) ($data['note'] ?? ''));
        $reasonNote = $note === '' ? $data['reason'] : $data['reason'].' — '.$note;

        $back = route('owner.calendar', [
            'unit' => $data['unit_id'],
            'month' => Carbon::parse($data['first_night'])->format('Y-m'),
        ]);

        try {
            if (! empty($data['repeat_freq'])) {
                $result = $blocks->blockSeries(
                    $request->user(), $data['unit_id'], $data['first_night'], $data['last_night'],
                    $reasonNote, $data['repeat_freq'], (int) ($data['repeat_count'] ?? 2),
                );

                if ($result['created'] === 0) {
                    return redirect()->to($back)->with('block_error', 'Every date in that series is already booked or blocked — nothing to hold.');
                }

                $total = $result['created'] + $result['skipped'];
                $msg = $result['skipped'] === 0
                    ? $result['created'].' blocks created across the series. Guests can no longer book them.'
                    : $result['created'].' of '.$total.' blocks created — the rest were already booked or blocked.';

                return redirect()->to($back)->with('block_notice', $msg)->with('block_undo_series', $result['series_id']);
            }

            $block = $blocks->block($request->user(), $data['unit_id'], $data['first_night'], $data['last_night'], $reasonNote);
        } catch (OwnerBlockException) {
            abort(403);   // a foreign unit id — tampering, not a normal outcome
        } catch (UnitUnavailableException) {
            return redirect()->to($back)->with('block_error', 'Those dates include a guest booking or an existing block — pick open dates.');
        }

        return redirect()->to($back)->with('block_notice', 'Dates blocked. Guests can no longer book them.')->with('block_undo_block', (string) $block->id);
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

    /** Release a whole repeat series (the Undo of a just-created series, and the series row's Remove). */
    public function destroySeries(Request $request, OwnerBlockService $blocks, string $series): RedirectResponse
    {
        try {
            $blocks->releaseSeries($series);
        } catch (OwnerBlockException) {
            abort(403);
        }

        return redirect()->back()->with('block_notice', 'Series removed. Those dates are open again.');
    }
}
