<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\FrontDeskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Front-desk check-in / check-out — the REAL staff actions, driven from the styled Front Desk
 * (design pass · Operator Screen 2) via a POST → action → redirect (PRG) flow. This deliberately
 * does NOT use a Livewire wire-action: a POST to a normal route re-runs the full web middleware
 * stack (SetTenantForWebRequest sets the tenant context), so the mutation is genuinely tenant-scoped
 * — the owner-portal lesson that scope-dependent mutations must re-run the middleware, not ride a
 * /livewire/update re-render.
 *
 * `{reservation}` is a plain STRING route param (never a route-model-bound Model), resolved here via
 * the tenant-scoped global scope AFTER the middleware has set context — so a cross-org id 404s (the
 * standing RMB rule). The transition itself delegates to {@see FrontDeskService} (→ the Phase 2
 * Step 8 CheckIn/CheckOut actions); no inventory/folio/schema is touched.
 */
class FrontDeskActionController extends Controller
{
    public function checkIn(Request $request, FrontDeskService $desk, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        // Only a confirmed booking can be checked in — guard against nonsensical transitions
        // (e.g. re-opening a checked-out stay) without weakening the underlying idempotent action.
        if ($model->status !== 'confirmed') {
            return $this->backToBoard($request, "{$model->reference} can’t be checked in from “{$model->status}”.");
        }

        $desk->checkIn($model);

        return $this->backToBoard($request, "Checked in {$model->reference}.");
    }

    public function checkOut(Request $request, FrontDeskService $desk, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);

        if ($model->status !== 'checked_in') {
            return $this->backToBoard($request, "{$model->reference} can’t be checked out from “{$model->status}”.");
        }

        $desk->checkOut($model);

        return $this->backToBoard($request, "Checked out {$model->reference}.");
    }

    /** Redirect back to the board on the same day (preserves ?date), with a flash notice. */
    private function backToBoard(Request $request, string $notice): RedirectResponse
    {
        $date = $request->input('date');
        $target = route('app.front-desk').(is_string($date) && $date !== '' ? '?date='.urlencode($date) : '');

        return redirect()->to($target)->with('desk_notice', $notice);
    }
}
