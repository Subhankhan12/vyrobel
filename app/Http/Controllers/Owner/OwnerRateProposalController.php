<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\RateProposal;
use App\Services\RateProposalService;
use App\Support\Money\MoneyInput;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
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
        abort_unless(app(OwnerContext::class)->canOn((string) $model->owner_id, OwnerTeamRole::CAP_ACTIONS), 403);   // 🔒 P1-1 per-owner

        try {
            $this->proposals->approve($model, $request->user());
        } catch (\RuntimeException) {
            return back()->with('owner_notice', __('php/http/controllers/owner/owner_rate_proposal_controller.already_decided'));
        }

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_rate_proposal_controller.approved'));
    }

    public function decline(Request $request, string $proposal): RedirectResponse
    {
        $model = RateProposal::query()->findOrFail($proposal);
        abort_unless(app(OwnerContext::class)->canOn((string) $model->owner_id, OwnerTeamRole::CAP_ACTIONS), 403);   // 🔒 P1-1 per-owner

        try {
            $this->proposals->decline($model, $request->user());
        } catch (\RuntimeException) {
            return back()->with('owner_notice', __('php/http/controllers/owner/owner_rate_proposal_controller.already_decided'));
        }

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_rate_proposal_controller.declined'));
    }

    /**
     * OWNER COUNTERS with their own nightly rate (#81) → `countered`; the operator then accepts or withdraws.
     * Nothing is applied here. Money arrives as hand-typed text → integer minor units (junk / non-positive
     * rejected) in the proposal's own currency. Owner-scoped + CAP_ACTIONS (per-owner), audited via the service.
     */
    public function counter(Request $request, string $proposal): RedirectResponse
    {
        $model = RateProposal::query()->findOrFail($proposal);   // owner-scoped
        abort_unless(app(OwnerContext::class)->canOn((string) $model->owner_id, OwnerTeamRole::CAP_ACTIONS), 403);   // 🔒 P1-1 per-owner

        $validated = $request->validate([
            'counter_rate' => ['required', 'string', 'max:12'],
            'counter_note' => ['nullable', 'string', 'max:500'],
        ]);

        $minor = MoneyInput::toMinor($validated['counter_rate']);
        if ($minor === null) {
            return back()->with('owner_error', __('php/http/controllers/owner/owner_rate_proposal_controller.invalid_counter_rate'));
        }

        try {
            $this->proposals->counter($model, $request->user(), $minor, $validated['counter_note'] ?? null);
        } catch (\RuntimeException) {
            return back()->with('owner_notice', __('php/http/controllers/owner/owner_rate_proposal_controller.already_decided'));
        }

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_rate_proposal_controller.counter_sent'));
    }
}
