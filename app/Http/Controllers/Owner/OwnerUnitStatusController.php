<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Services\OwnerUnitStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * OWNER unit-status write (Wave C · GATE 3) — the owner PAUSES / RESUMES one of their OWN units. OWNER-SCOPED:
 * {@see Unit} is BelongsToOwner, so `findOrFail` under the active OwnerScope 404s a cross-owner/operator-owned
 * unit + fails closed for a non-linked user. CAP_ACTIONS-gated at the route (co-owner only — it changes what's
 * bookable). Controller POSTs only; the service recomputes availability + audits.
 */
class OwnerUnitStatusController extends Controller
{
    public function toggle(Request $request, OwnerUnitStatusService $status, string $unit): RedirectResponse
    {
        $model = Unit::query()->findOrFail($unit);   // owner-scoped
        $actorId = (string) $request->user()?->getKey();

        if ($model->is_active !== false) {
            $status->pause($model, $actorId);

            return back()->with('owner_notice', 'Unit paused — it won’t take new bookings until you resume it.');
        }

        $status->resume($model, $actorId);

        return back()->with('owner_notice', 'Unit resumed — it’s open for bookings again.');
    }
}
