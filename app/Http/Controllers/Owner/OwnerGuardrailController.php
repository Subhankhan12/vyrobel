<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\RateGuardrail;
use App\Services\RateProposalService;
use App\Support\Money\MoneyInput;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner PRICING GUARDRAILS write (Owner Rates exact-match #81) — the owner saves the limits their manager
 * prices within. Owner-scoped + CAP_ACTIONS (co-owner only — a money-class control, not a view), audited via
 * the model events. A saved guardrail GENUINELY constrains operator proposals in
 * {@see RateProposalService::propose()} (reject out-of-band / auto-approve in-tolerance / else
 * pending) — never an inert limit. Floor/ceiling are hand-typed € → integer minor units.
 */
class OwnerGuardrailController extends Controller
{
    public function save(Request $request): RedirectResponse
    {
        $context = app(OwnerContext::class);

        // The one owner this portal user acts for with the actions capability (per-owner, P1-1).
        $ownerIds = $context->ownerIdsWithCapability(OwnerTeamRole::CAP_ACTIONS);
        abort_if($ownerIds === [], 403);
        $ownerId = (string) $ownerIds[0];

        $validated = $request->validate([
            'pricing_mode' => ['required', 'in:dynamic,fixed'],
            'auto_approve_pct' => ['required', 'integer', 'min:0', 'max:50'],
            'floor' => ['nullable', 'string', 'max:12'],
            'ceiling' => ['nullable', 'string', 'max:12'],
        ]);

        $floor = ($validated['floor'] ?? '') !== '' ? MoneyInput::toMinor($validated['floor']) : null;
        $ceiling = ($validated['ceiling'] ?? '') !== '' ? MoneyInput::toMinor($validated['ceiling']) : null;

        // A floor above the ceiling is nonsense — reject cleanly rather than storing a band nothing fits in.
        if ($floor !== null && $ceiling !== null && $floor > $ceiling) {
            return back()->with('owner_error', 'The floor can’t be higher than the ceiling.');
        }

        RateGuardrail::query()->updateOrCreate(
            ['owner_id' => $ownerId],
            [
                'pricing_mode' => $validated['pricing_mode'],
                'auto_approve_pct' => (int) $validated['auto_approve_pct'],
                'floor_minor' => $floor,
                'ceiling_minor' => $ceiling,
                'currency' => 'EUR',
            ],
        );

        return back()->with('owner_notice', 'Guardrails saved — your manager prices within these limits.');
    }
}
