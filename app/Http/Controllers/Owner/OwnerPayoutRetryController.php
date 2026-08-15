<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Models\Owner;
use App\Models\Payout;
use App\Services\OwnerPayoutService;
use App\Support\Owner\OwnerContext;
use App\Support\Owner\OwnerTeamRole;
use App\Support\Owner\PayoutRecovery;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * DO3-3 — the owner asks for another attempt at a FAILED payout. A WRITE on a controller ROUTE
 * (invariant #20): a money-adjacent owner write never rides a Livewire action, which would bypass
 * {@see SetOwnerScopeForPortal} and the capability gate.
 *
 * ⚠️ THIS RE-QUEUES; IT DOES NOT SEND. {@see OwnerPayoutService::retry()} writes a NEW `pending` payout
 * superseding the failed one. Disbursement stays exactly where it was — {@see OwnerPayoutService::pay()},
 * reached only by the operator (`payouts.manage`) or the scheduled engine, over the config-selected rail.
 * The owner gets no rail, no live Stripe call, and no ability to move money.
 *
 * 🔒 GATED `CAP_PAYOUT` (co-owner only, #28/#42 — the same money gate as the encrypted IBAN and the payout
 * preferences): an accountant or view-only sub-user may SEE a failed payout but not act on it.
 * 🔒 OWNER-SCOPED EXPLICITLY: the row is resolved with `whereIn('owner_id', …)` over the owners this user
 * holds CAP_PAYOUT on — the per-owner set, never the union, and never a request-supplied owner id. A
 * cross-owner payout id 404s (D24-2's lesson: assert the scope, do not lean on an ambient one).
 *
 * ⚠️ THE SERVICE IS THE AUTHORITY ON WHETHER THE RETRY HAPPENS. Everything this controller reads is a
 * snapshot; {@see OwnerPayoutService::retry()} re-decides under a row lock and refuses a second live payout
 * for one statement. The pre-flight below exists to give an honest MESSAGE, not to be the guard.
 */
class OwnerPayoutRetryController extends Controller
{
    public function retry(Request $request, OwnerContext $ownerContext, OwnerPayoutService $payouts, string $payout): RedirectResponse
    {
        // 🔒 Resolve the holder from the owners this user has PAYOUT on (per-owner). Fail closed when none.
        $ownerIds = $ownerContext->ownerIdsWithCapability(OwnerTeamRole::CAP_PAYOUT);
        if ($ownerIds === []) {
            abort(404);
        }

        try {
            /** @var Payout $row */
            $row = Payout::query()->whereIn('owner_id', $ownerIds)->findOrFail($payout);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        $owner = $row->owner;
        if (! $owner instanceof Owner) {
            abort(404);
        }

        // Pre-flight, for an honest message only (the service re-decides under its lock).
        if ((string) $row->status !== 'failed') {
            return back()->with('payout_retry_notice', 'That payout is no longer in a failed state, so nothing was re-queued.');
        }

        if (PayoutRecovery::liveReplacement($row) !== null) {
            return back()->with('payout_retry_notice', 'A replacement payout for that statement is already on its way — nothing further was queued.');
        }

        if (PayoutRecovery::blockers($owner) !== []) {
            return back()->with('payout_retry_notice', 'Please finish setting up your payout account first — another attempt would fail the same way.');
        }

        try {
            $result = $payouts->retry($row);
        } catch (RuntimeException) {
            // The row moved on between the read and the lock (a webhook landing mid-request) — the guard
            // did its job; say so plainly rather than reporting a queue that did not happen.
            return back()->with('payout_retry_notice', 'That payout is no longer in a failed state, so nothing was re-queued.');
        }

        return back()->with('payout_retry_notice', $result->wasRecentlyCreated
            ? 'We’ve queued this payout for another attempt. You’ll see it in your payout history.'
            : 'A replacement payout for that statement is already on its way — nothing further was queued.');
    }
}
