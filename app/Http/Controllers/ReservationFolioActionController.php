<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\Folio\RefundExceedsPaymentException;
use App\Models\CompanyAccount;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\CompanyAccountService;
use App\Services\FolioService;
use App\Support\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Reservation folio money actions — REAL, append-only, driven from the styled Reservation Detail
 * (design pass · Operator Screen 4) via the proven POST → action → redirect (PRG) pattern. A normal
 * POST re-runs the web middleware (SetTenantForWebRequest sets context), so every mutation is
 * tenant-scoped; `{reservation}` is a plain STRING param resolved via the tenant-scoped global scope
 * AFTER the middleware (never RMB) → a cross-org id 404s.
 *
 * Both actions delegate to {@see FolioService} — the single append-only ledger writer. A payment is a
 * NEW `payment` row; a refund is a NEW offsetting `refund` row (the original is NEVER edited). No
 * ledger row is ever mutated or deleted here. Money is integer minor units end-to-end.
 */
class ReservationFolioActionController extends Controller
{
    /** Record a payment against the reservation's folio (through the configured gateway; manual = offline). */
    public function recordPayment(Request $request, FolioService $folios, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        $validated = $request->validate([
            'amount' => ['required'],
            // Batch C · GATE 3 — the method selector: cash / bank transfer record NOW (manual gateway);
            // card is charged via Stripe (test-mode) and is deferred to the live-key flip.
            'method' => ['required', Rule::in(['cash', 'bank_transfer', 'card'])],
            // The per-submit idempotency nonce — a double-click / re-submit records ONCE.
            'idempotency_key' => ['required', 'string', 'max:64'],
        ]);

        if ($validated['method'] === 'card') {
            // 🔒 NOT a live charge in the build: on-desk card is the account-gated Stripe seam.
            return $this->afterPayment($request, $model->id, __('php/http/controllers/reservation_folio_action_controller.card_not_live'));
        }

        $minor = $this->parseAmountMinor($validated['amount']);
        if ($minor <= 0) {
            return $this->afterPayment($request, $model->id, __('php/http/controllers/reservation_folio_action_controller.payment_amount_invalid'));
        }

        $folio = $folios->openFolio($model);
        // Manual/offline payment (cash or bank transfer) — the method is recorded on the payment metadata.
        $folios->recordPayment(
            $folio,
            new Money($minor, (string) $model->currency),
            metadata: ['method' => (string) $validated['method']],
            idempotencyKey: (string) $validated['idempotency_key'],
        );

        return $this->afterPayment($request, $model->id, __('php/http/controllers/reservation_folio_action_controller.payment_recorded'));
    }

    /**
     * Redirect after an on-desk payment: back to the FRONT-DESK BOARD when the collect originated there
     * (`return_to=front-desk`, preserving the viewed `?date`), otherwise to the reservation detail. The board
     * is where the operator is standing when they collect at check-in — landing them back there matches the
     * wireframe flow. Only recognises the one known origin; anything else falls through to the detail.
     */
    private function afterPayment(Request $request, string $reservationId, string $notice): RedirectResponse
    {
        if ($request->input('return_to') === 'front-desk') {
            $date = $request->input('return_date');
            $target = route('app.front-desk').(is_string($date) && $date !== '' ? '?date='.urlencode($date) : '');

            return redirect()->to($target)->with('desk_notice', $notice);
        }

        return $this->back($reservationId, $notice);
    }

    /**
     * ADD a charge to the reservation's folio (Batch C · GATE 2) — through the SINGLE append-only ledger
     * writer {@see FolioService::postCharge} (integer minor #17; a NEW folio_item, never an edit). The `type`
     * is restricted to the real `folio_items.type` enum values offered on the screen; a ≤0 amount is refused
     * before the service is called. Gated `folios.manage` on the route; tenant-scoped (cross-org 404).
     */
    public function addCharge(Request $request, FolioService $folios, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        $validated = $request->validate([
            'type' => ['required', Rule::in(['add_on', 'product', 'fee', 'manual'])],
            'description' => ['required', 'string', 'max:120'],
            'amount' => ['required'],
            // The per-submit idempotency nonce — a double-click / re-submit / raced retry records the charge ONCE
            // (mirrors the on-desk payment idempotency; the money guard holds even if the UI disable is bypassed).
            'idempotency_key' => ['required', 'string', 'max:64'],
        ]);

        $minor = $this->parseAmountMinor($validated['amount']);
        if ($minor <= 0) {
            return $this->back($model->id, __('php/http/controllers/reservation_folio_action_controller.charge_amount_invalid'), refused: true);
        }

        $folio = $folios->openFolio($model);
        $folios->postCharge(
            $folio,
            (string) $validated['type'],
            (string) $validated['description'],
            new Money($minor, (string) $model->currency),
            idempotencyKey: (string) $validated['idempotency_key'],
        );

        return $this->back($model->id, __('php/http/controllers/reservation_folio_action_controller.charge_added'));
    }

    /**
     * Refund a specific prior payment — a NEW offsetting entry; the original is untouched.
     *
     * AMOUNT-AWARE (Wave 2 · sub-gate 3). `FolioService::refundPayment()` has always taken an arbitrary
     * Money, so PARTIAL refunds were a CONTROLLER gap, not a service gap (docs/WAVE2-INVENTORY.md §4).
     * Two callers now share this action and both must keep working:
     *   • the inline per-payment button on the reservation detail — posts NO `amount` → the full remaining,
     *     exactly as before (RefundGuardTest asserts its flash verbatim);
     *   • the dedicated refund screen — posts a partial `amount` in major units.
     *
     * ⚠️ THE ZERO HOLE. `refundPayment()` rejects `amount <= 0` with an **InvalidArgumentException**, which
     * does NOT extend RuntimeException and is therefore NOT caught below — a €0 submission would 500. That
     * was unreachable while this controller only ever sent the full remaining; an operator-entered amount
     * makes it reachable, so 0 is refused HERE, before the service is called.
     */
    public function refund(Request $request, FolioService $folios, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);

        // The payment must belong to THIS reservation (and is tenant-scoped itself) — cross-org 404.
        $payment = Payment::query()
            ->where('reservation_id', $model->id)
            ->findOrFail((string) $request->input('payment'));

        if (! in_array($payment->status, ['succeeded', 'captured'], true) || ! in_array($payment->type, ['payment', 'capture'], true)) {
            return $this->back($model->id, __('php/http/controllers/reservation_folio_action_controller.refund_only_settled'), refused: true);
        }

        // Refund what is genuinely LEFT, not blindly the original: FolioService bounds the CUMULATIVE total
        // against a payment (docs/DECISIONS.md 2026-07-16). Sending the full amount at an already-refunded
        // payment is exactly the over-refund the guard rejects.
        $remaining = $folios->remainingRefundableMinor($payment);

        if ($remaining <= 0) {
            return $this->back($model->id, __('php/http/controllers/reservation_folio_action_controller.already_fully_refunded'), refused: true);
        }

        $raw = $request->input('amount');
        $requested = is_scalar($raw) && trim((string) $raw) !== '';

        // No amount asked for → the full REMAINING (never the original). Preserves the inline button.
        $minor = $requested ? $this->parseAmountMinor($raw) : $remaining;

        if ($minor <= 0) {
            return $this->back($model->id, __('php/http/controllers/reservation_folio_action_controller.refund_amount_invalid'), refused: true);
        }

        // #100 — the operator's reason (a chip label + optional note) + the destination, recorded on the refund
        // metadata via the service. NOT read-only-guarded: returning money to a guest is allowed even when the
        // workspace is paused. The refund gateway call is TEST-MODE (StripeSafetyGuard) until go-live.
        $reason = $this->composeReason($request);

        // ⚠️ WHO BEARS IT — a DECISIVE value, so it is validated against the model's CLOSED set at the
        //    boundary and stored in a typed column, never alongside the descriptive reason in metadata.
        //    ⚠️ Anything absent, empty or unrecognised resolves to NULL = OWNER-BORNE, which is today's
        //    behaviour exactly. Absorbing a refund can only ever be an EXPLICIT, valid 'operator'.
        $borneRaw = $request->input('borne_by');
        $borneBy = (is_string($borneRaw) && in_array($borneRaw, Payment::BORNE_BY, true)) ? $borneRaw : null;

        try {
            $folios->refundPayment($payment, new Money($minor, (string) $payment->currency), $reason, null, $borneBy);
        } catch (RefundExceedsPaymentException) {
            // The service is the authority. It ROW-LOCKS the original payment and re-reads the cumulative
            // total INSIDE that lock (FolioService::refundPayment), so a concurrent refund landing between
            // the read above and this one blocks, then loses the bound check and lands here — and because
            // the throw rolls its transaction back, the refused attempt writes nothing.
            // ⚠️ Until 2026-08-08 this comment claimed that safety while the service had NO lock at all
            //    (audit H1 / L1-01 / F4-2); the mechanism named above is what now makes the claim true.
            // Surfaced cleanly, never a 500 and NEVER silently clamped down to what would fit: refunding
            // less than was asked for, without saying so, is its own money bug.
            $left = $folios->remainingRefundableMinor($payment->fresh() ?? $payment);

            return $this->back($model->id, $left > 0
                ? __('php/http/controllers/reservation_folio_action_controller.refund_exceeds', ['amount' => $this->majorUnits($left), 'currency' => (string) $payment->currency])
                : __('php/http/controllers/reservation_folio_action_controller.already_fully_refunded'), refused: true);
        }

        return $this->back($model->id, __('php/http/controllers/reservation_folio_action_controller.refund_issued'));
    }

    /**
     * Compose the refund reason from the chip label + an optional free-text note + the destination (task #100).
     * Returns null when nothing meaningful was entered (the metadata `reason` is then omitted).
     */
    private function composeReason(Request $request): ?string
    {
        $allowed = ['duplicate', 'overcharge', 'service_issue', 'cancellation', 'goodwill', 'other'];
        $label = (string) $request->input('reason', '');
        $note = trim((string) $request->input('note', ''));
        $destination = $request->input('destination') === 'manual_bank' ? 'manual bank transfer' : 'original method';

        $parts = [];
        if (in_array($label, $allowed, true)) {
            $parts[] = ucfirst(str_replace('_', ' ', $label));
        }
        if ($note !== '') {
            $parts[] = $note;
        }
        if ($parts === []) {
            return null;   // no reason picked + no note — record no reason (just the amount)
        }
        $parts[] = 'to '.$destination;

        return implode(' · ', $parts);
    }

    /** Parse an operator-entered major-unit amount (e.g. "50.00") to integer minor units. */
    private function parseAmountMinor(mixed $raw): int
    {
        if (! is_string($raw) && ! is_numeric($raw)) {
            return 0;
        }

        $value = (float) $raw;

        return $value > 0 ? (int) round($value * 100) : 0;
    }

    /**
     * Integer minor units → a plain major-unit string for a flash message ("20000" → "200.00").
     *
     * Deliberately NOT symbolised: the currency-symbol map lives in the Livewire presentation trait, and a
     * controller flash is not a presentation surface. The ISO code is appended by the caller instead —
     * unambiguous, and it invents nothing.
     */
    private function majorUnits(int $minor): string
    {
        return number_format($minor / 100, 2, '.', '');
    }

    /**
     * ⚠️⚠️ ONE FLASH KEY CARRIED BOTH OUTCOMES — that was the D4 defect, not the wording.
     *
     * `folio_notice` is read by three views, and every one of them hardcoded a CHECKMARK. So invariant
     * #27 refusing an over-refund ("That refund was refused — only €X is still refundable") arrived under
     * a green tick: a desk clerk reads the tick, tells the guest the money is on its way, or retries.
     * ⚠️ THE MESSAGE WAS ALWAYS RIGHT. The outcome was simply never carried alongside it, so the view had
     * nothing to render it from and guessed — always optimistically.
     *
     * ⚠️ `$refused` DEFAULTS TO FALSE so no existing success site changes meaning, and every refusal now
     * passes it explicitly. Rendering happens in ONE place, `<x-operator.flash-notice>`; a second copy of
     * "which icon means what" is how two screens come to disagree (§N.8f).
     * ⚠️ NOTHING ABOUT THE REFUSAL LOGIC MOVES. This is presentation over a guard that already works.
     */
    private function back(string $reservationId, string $notice, bool $refused = false): RedirectResponse
    {
        return redirect()->to(route('app.reservations.show', $reservationId))
            ->with('folio_notice', $notice)
            ->with('folio_refused', $refused);
    }

    /**
     * ⚠️ BILL THIS FOLIO'S OUTSTANDING BALANCE TO A COMPANY ACCOUNT — the debt moves to the account; it is
     * NOT settled and NOT collected. `CompanyAccountService` posts nothing, so the folio balance is
     * unchanged and `OwnerCollection::reservationIsCollected()` (literally `balance_minor <= 0`) keeps
     * reading UNCOLLECTED — which is what stops an owner being paid for money nobody has received.
     *
     * ⚠️ Gated `can:folios.manage` on the route (Super Admin · Hotel Manager · Front Desk · Accountant —
     * verified against the matrix): billing to an account is a folio settlement decision, made by the same
     * people who take payments. ⚠️ The SERVICE still enforces org isolation and refuses a settled folio, so
     * the route is not the only thing between a caller and the state.
     */
    public function billToAccount(Request $request, CompanyAccountService $accounts, FolioService $folios, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        $validated = $request->validate([
            // ⚠️ A KEYED reference to a real account row — never a free-text company name.
            'company_account_id' => ['required', 'string', 'exists:company_accounts,id'],
        ]);

        $account = CompanyAccount::query()->findOrFail($validated['company_account_id']);
        $folio = $folios->openFolio($model);

        try {
            $accounts->bill($folio, $account);
        } catch (InvalidArgumentException $e) {
            return $this->back($model->id, $e->getMessage(), refused: true);
        }

        return $this->back($model->id, __('php/http/controllers/reservation_folio_action_controller.billed_to_account', ['account' => (string) $account->name]));
    }

    /** ⚠️ Un-billing is a NEW STATE, not a reversal — nothing was posted, so there is nothing to reverse. */
    public function unbillFromAccount(CompanyAccountService $accounts, FolioService $folios, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);
        $accounts->unbill($folios->openFolio($model));

        return $this->back($model->id, __('php/http/controllers/reservation_folio_action_controller.returned_to_guest'));
    }
}
