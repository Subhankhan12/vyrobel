<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Exceptions\Owner\PayoutsPausedException;
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
            // NEUTRAL: the payout left the failed state on its own. Nothing to retry, and nothing lost.
            return $this->notice('That payout is no longer in a failed state, so nothing was re-queued.', 'neutral');
        }

        if (PayoutRecovery::liveReplacement($row) !== null) {
            // NEUTRAL, and deliberately NOT a refusal: the owner's money IS on its way. Painting this red
            // would invent a problem — the honesty defect this pass exists to avoid, one layer over.
            return $this->notice('A replacement payout for that statement is already on its way — nothing further was queued.', 'neutral');
        }

        if (PayoutRecovery::blockers($owner) !== []) {
            // ⚠️⚠️ THE ONE TRUE REFUSAL, AND THE SHARPEST MESSAGE ON THIS SURFACE. It is an INSTRUCTION
            // rendered, until now, as a COMPLETION: `PayoutRecovery::blockers()` returns actionable items
            // with a CTA ("Connect your payout account" → owner.payout-method), so the retry did not happen
            // AND WILL NEVER HAPPEN until the owner acts. Under a green tick they do nothing and wait for
            // money that is not coming.
            // ⚠️ LATENT TODAY, LIVE AT CUTOVER: blockers() returns [] unless `PAYOUTS_RAIL=stripe_connect`,
            // which defaults to `manual` — so this branch becomes reachable exactly when real money starts
            // moving, which is the worst possible moment to discover it.
            return $this->notice('Please finish setting up your payout account first — another attempt would fail the same way.', 'refused');
        }

        try {
            $result = $payouts->retry($row);
        } catch (PayoutsPausedException) {
            // ⚠️ DEC-n (2026-10-02) — a pause stops every payout, a retry included. Said as what it is, with the way back;
            //    the catch below would have called it "no longer in a failed state".
            return $this->notice('Your payouts are paused in your payout preferences — resume them, then try again.', 'refused');
        } catch (RuntimeException) {
            // The row moved on between the read and the lock (a webhook landing mid-request) — the guard
            // did its job; say so plainly rather than reporting a queue that did not happen.
            return $this->notice('That payout is no longer in a failed state, so nothing was re-queued.', 'neutral');
        }

        return $result->wasRecentlyCreated
            ? $this->notice('We’ve queued this payout for another attempt. You’ll see it in your payout history.', 'ok')
            : $this->notice('A replacement payout for that statement is already on its way — nothing further was queued.', 'neutral');
    }

    /**
     * ⚠️ THE OUTCOME RIDES WITH THE MESSAGE — that was the whole defect, not the wording.
     *
     * `payout_retry_notice` carried only text, and the view rendered every one of them through
     * `class="vy-flash is-ok"` — a skin literally named for success, with a green tick. The messages were
     * already careful (the view's own comment says they report what actually happened rather than a blanket
     * success); the SKIN contradicted them.
     * ⚠️ THREE outcomes, because two is wrong here: see <x-owner.flash-notice> for why a no-op must not be
     * painted as a failure.
     * ⚠️ NO MESSAGE TEXT AND NO REFUSAL LOGIC CHANGES IN THIS PASS.
     *
     * @param  'ok'|'neutral'|'refused'  $outcome
     */
    private function notice(string $message, string $outcome): RedirectResponse
    {
        return back()->with('payout_retry_notice', $message)->with('payout_retry_outcome', $outcome);
    }
}
