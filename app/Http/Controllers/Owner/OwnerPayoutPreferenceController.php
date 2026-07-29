<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Models\OwnerPayoutPreference;
use App\Support\Money\MoneyInput;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Owner Payout PREFERENCES — the owner's STATED preference for how earnings settle (frequency · business-day ·
 * minimum · pause). A WRITE on a controller ROUTE (invariant #20): a sensitive owner write never rides a Livewire
 * action (that would bypass {@see SetOwnerScopeForPortal} + the capability gate).
 *
 * GATED `CAP_PAYOUT` (co-owner only, #28/#42 — the same money-security gate as the encrypted IBAN): an accountant
 * or view-only sub-user may SEE the preference but not change where/when the money moves. OWNER-SCOPED: the row is
 * written under the resolved {@see OwnerContext} owner id (never a request id); a non-linked user has no owner id
 * → nothing is written. Idempotent upsert on `owner_id`.
 *
 * ⚠️ HONESTY: this stores a PREFERENCE, not a schedule. It NEVER moves money or promises a date — the actual
 * cadence + execution are confirmed with the operator + the live payout rail (#39). Money is integer minor (#17).
 */
class OwnerPayoutPreferenceController extends Controller
{
    public function save(Request $request, OwnerContext $ownerContext): RedirectResponse
    {
        // 🔒 P1-1: payout preferences are a CAP_PAYOUT write — resolve the holder from the owners the acting user
        // has PAYOUT on (per-owner), never the union. Fail closed when none.
        $ownerIds = $ownerContext->ownerIdsWithCapability(OwnerTeamRole::CAP_PAYOUT);
        if ($ownerIds === []) {
            abort(404);
        }

        $validated = $request->validate([
            'frequency' => ['required', Rule::in(OwnerPayoutPreference::FREQUENCIES)],
            'payout_day' => ['required', 'integer', 'min:1', 'max:5'],   // Nth business day of the month
            'minimum' => ['required', 'string', 'max:20'],
        ]);

        // The same string-arithmetic money parse every typed amount uses (#17); 0/unparseable → no minimum.
        $minimumMinor = MoneyInput::toMinor($validated['minimum'], 100_000_000) ?? 0;

        OwnerPayoutPreference::query()->updateOrCreate(
            ['owner_id' => $ownerIds[0]],   // organization_id is auto-filled by BelongsToOrganization
            [
                'frequency' => $validated['frequency'],
                'payout_day' => (int) $validated['payout_day'],
                'minimum_minor' => $minimumMinor,
                'paused' => $request->boolean('paused'),
            ],
        );

        return back()->with('payout_pref_notice', 'Payout preferences updated.');
    }
}
