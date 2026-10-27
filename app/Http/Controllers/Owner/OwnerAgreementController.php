<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AgreementAmendment;
use App\Models\User;
use App\Services\AgreementAmendmentService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Owner Agreement — the REAL amendment ACKNOWLEDGMENT write (Owner Agreement exact-match #82). The owner
 * reviews an operator-published amendment and acknowledges it: a real, audited record of consent that
 * applies the new terms via the certified effective-dating ({@see AgreementAmendmentService}).
 *
 * 🔒 Owner-scoped EXPLICITLY — `agreement_amendments` carries no OwnerScope, so the amendment's owner is
 * re-verified against the ids this portal user holds CAP_ACTIONS on (co-owner only — a money-class
 * control). A cross-owner amendment 404s. #20 audited in the service. NOT a legally-binding e-signature —
 * the record is a portal acknowledgment (honest framing throughout the UI).
 */
class OwnerAgreementController extends Controller
{
    public function acknowledge(Request $request, string $amendment, AgreementAmendmentService $service): RedirectResponse
    {
        $context = app(OwnerContext::class);

        // The owners this user may take money-class actions for (per-owner, P1-1). Fail-closed.
        $ownerIds = $context->ownerIdsWithCapability(OwnerTeamRole::CAP_ACTIONS);
        abort_if($ownerIds === [], 403);

        // Explicit owner scoping — the amendment MUST belong to one of those owners, else 404 (never a
        // cross-owner acknowledgment). Only a published amendment can be acknowledged.
        $model = AgreementAmendment::query()
            ->whereIn('owner_id', $ownerIds)
            ->where('status', AgreementAmendment::STATUS_PUBLISHED)
            ->findOrFail($amendment);

        /** @var User $actor */
        $actor = $request->user();

        $service->acknowledge($model, $actor);

        return back()->with('owner_notice', __('php/http/controllers/owner/owner_agreement_controller.amendment_acknowledged', ['date' => $model->effective_date->translatedFormat('j M Y')]));
    }
}
