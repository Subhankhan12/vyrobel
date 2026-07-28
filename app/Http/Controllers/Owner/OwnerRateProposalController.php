<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\RateProposal;
use App\Services\RateProposalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * OWNER rate-proposal decisions (Wave C · GATE 1 · ⚠️ REAL MONEY). The owner APPROVES (the proposed rate is
 * applied via the certified path — the rate really changes) or DECLINES (nothing changes) a pending proposal.
 *
 * OWNER-SCOPED: {@see RateProposal} is BelongsToOwner, so `findOrFail` under the active OwnerScope 404s a
 * cross-owner proposal + fails closed for a non-linked user. CAP_ACTIONS-gated at the route (co-owner only — a
 * money-class decision, not a view). Controller POSTs only (never a Livewire action); the service audits.
 */
class OwnerRateProposalController extends Controller
{
    public function __construct(private readonly RateProposalService $proposals) {}

    public function approve(Request $request, string $proposal): RedirectResponse
    {
        $model = RateProposal::query()->findOrFail($proposal);   // owner-scoped

        try {
            $this->proposals->approve($model, $request->user());
        } catch (\RuntimeException) {
            return back()->with('owner_notice', 'That proposal was already decided.');
        }

        return back()->with('owner_notice', 'Rate change approved — it’s live now.');
    }

    public function decline(Request $request, string $proposal): RedirectResponse
    {
        $model = RateProposal::query()->findOrFail($proposal);

        try {
            $this->proposals->decline($model, $request->user());
        } catch (\RuntimeException) {
            return back()->with('owner_notice', 'That proposal was already decided.');
        }

        return back()->with('owner_notice', 'Proposal declined — your rate is unchanged.');
    }
}
