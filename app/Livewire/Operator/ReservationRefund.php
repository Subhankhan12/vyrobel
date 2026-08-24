<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Folio;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\FolioService;
use App\Services\OwnerAttributionService;
use App\Support\Folio\FolioTotals;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Refund — the dedicated refund composer (design pass · Wave 2 · sub-gate 3). Styled on the SAME
 * operator design system as every other operator screen (operator layout, <x-operator.shell>,
 * FormatsOperatorData — reused, never redefined). This component ONLY READS; the write is the existing
 * amount-aware POST → ReservationFolioActionController::refund → FolioService (PRG).
 *
 * RMB rule: `{reservation}` is a plain string prop resolved in render() via a TENANT-SCOPED findOrFail
 * (after SetTenantForWebRequest) — a cross-org id 404s. Never a route-model-bound prop, and never a prop
 * literally named `$reservation` (it would shadow the `'reservation'` view key and fatal).
 *
 * ═══ WHY THIS SCREEN EXISTS: TO RESPECT THE GUARD, NOT TO RE-IMPLEMENT IT ═══
 *
 * `FolioService::refundPayment()` bounds the CUMULATIVE total refunded against a payment (invariant #27,
 * docs/DECISIONS.md 2026-07-16) — that service IS the authority and this screen re-derives none of it.
 * What the screen adds is a UI that cannot invite a rejection, because the shipped UI inviting one is
 * precisely how a €369.00 payment once took three full refunds:
 *   1. A REAL PAYMENT PICKER. `refundPayment()` needs ONE specific Payment; the design shows only an
 *      aggregate "Paid €322.00" and could never form the call on a folio with two settled payments.
 *   2. Every per-payment figure — original, Σ already refunded, remaining refundable — comes from the
 *      service's OWN helpers (`refundedTotalMinor()` / `remainingRefundableMinor()`). Never re-summed
 *      here, so the screen cannot drift from the guard it is showing.
 *   3. FULL means the REMAINING, never the original — sending the original at a part-refunded payment IS
 *      the over-refund. PARTIAL is capped at the remaining (`max` = remaining in major units).
 *   4. At remaining = 0 the screen offers NO control at all. HTML caps are a courtesy for the operator;
 *      the service is the guard. Both, not either.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — the settled payments (real `payments` rows), each one's original amount, its gateway, its
 *                processed date; the guest NAME; the reservation reference/stay. The refund itself is REAL
 *                and persists append-only (a NEW offsetting row; the original is never touched).
 *   • DERIVED  — Σ refunded + remaining refundable, from the service helpers; charges/paid/balance from the
 *                SHARED FolioTotals formula — the same source the reservation detail and the folio PDF use,
 *                so all three reconcile to the cent.
 *   • ⚠️ STALE-CORRECTED 2026-08-08 — this block used to read "the refund REASON is DEFERRED:
 *                `refundPayment()` has NO `$reason` param". That has been untrue since #100: the signature is
 *                `refundPayment(Payment $original, Money $amount, ?string $reason = null, ?string $idempotencyKey = null)`,
 *                {@see ReservationFolioActionController} passes a composed reason, and it is stored on the
 *                refund row's `metadata` JSON (no schema column — hence "payments has no reason column" was
 *                the only accurate half). The reason input is LIVE, not a "Soon" row. Corrected during the
 *                audit H1 fix pass, which touched this signature again — a docblock asserting a parameter
 *                does not exist, next to the parameter, is exactly the trap that hid the refund race.
 *   • DEFERRED — emailing the guest a refund confirmation: no operator→guest send exists (app/Mail holds
 *                only OwnerInviteMail + PasswordResetMail). A disabled "Soon" row, never a silent no-op.
 *   • OMITTED  — "By charge" mode: it needs a `folio_items` ↔ `payments` link that does not exist. That is
 *                a credit note, a different concept, not a refund.
 *   • OMITTED  — "Original card · Visa ····4242": `payments` has `card_token` ONLY (token, and $hidden).
 *                There is no brand and no last4 column anywhere in the schema. Fabricated → never rendered.
 *                The real `payments.gateway` is shown instead.
 *   • OMITTED  — "Arrives · 2–5 business days": no settlement-ETA field exists (a GatewayResult carries
 *                {id, status}). Pure invention.
 *   • DERIVED  — `payment_status = 'refunded'` is now a REAL reservation-level state (Phase 0.2 — a fully-
 *                refunded stay). This screen still computes its finer per-PAYMENT refund state (Σ refunded /
 *                remaining refundable) from the real refund rows via the service helpers — the granularity a
 *                single reservation-level flag cannot carry.
 *   • Guest PII — name only; the encrypted email/phone are never rendered (the ReservationDetail posture).
 *
 * KNOWN GAP, recorded not papered over: the `payments.refund` permission exists (config/authorization.php)
 * and is deliberately narrow — Property Owner / Group Admin / Accountant hold it; Hotel Manager and Front
 * Desk hold `payments.record` but NOT `payments.refund`. Neither this screen's route nor the pre-existing
 * refund POST carries `can:payments.refund`, so a Front Desk user can still reach both. Closing that is a
 * routes change (out of scope for this gate); the gate here is EnsureBackOfficeAccess, which is real and
 * proven — an owner-role user is refused.
 */
#[Layout('components.layouts.operator')]
class ReservationRefund extends Component
{
    use FormatsOperatorData;

    public string $reservationId;

    public function mount(string $reservation): void
    {
        $this->reservationId = $reservation;
    }

    public function render(): View
    {
        // Resolved through the container rather than injected into render(): the same idiom
        // ReservationDetail::paymentEntry() already uses to reach the service's remaining-refundable helper.
        $folios = app(FolioService::class);

        $reservation = Reservation::query()
            ->with(['leadGuest'])
            ->findOrFail($this->reservationId);   // tenant-scoped → cross-org 404

        $guest = $reservation->leadGuest;
        $name = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';
        $currency = (string) ($reservation->currency ?? 'EUR');

        // ⚠️ THE WHO-BEARS-IT CONTROL RENDERS ONLY WHERE AN OWNER EXISTS. On an operator-owned unit there is
        //    no owner to charge, so the question is meaningless and the control must not appear at all.
        //    ⚠️ Read through the SAME test the refund listener uses (§N.8f) — a second definition here would
        //    eventually disagree with the one that actually decides whether money moves.
        $isOwnerAttributed = app(OwnerAttributionService::class)
            ->isOwnerAttributed((string) $reservation->getKey(), $currency);

        $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
        $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();

        // Scoped to THIS folio (folio_id), not the reservation and not the guest — the money being refunded
        // belongs to one ledger. Folio is 1:1 with Reservation via firstOrCreate today, so they coincide;
        // query the folio anyway, because that is what the refund actually moves.
        $folioModel = Folio::query()
            ->where('reservation_id', $reservation->id)
            ->with(['items', 'payments'])
            ->first();

        return view('livewire.operator.reservation-refund', [
            'isOwnerAttributed' => $isOwnerAttributed,
            'viewer' => $this->viewer(),
            'reservation' => [
                'id' => (string) $reservation->id,
                'reference' => (string) $reservation->reference,
                'status_label' => $this->reservationStatusMeta((string) $reservation->status)['label'],
                'check_in' => $checkIn->format('j M Y'),
                'check_out' => $checkOut->format('j M Y'),
                'guest' => $name !== '' ? $name : null,
            ],
            'folio' => $folioModel !== null ? $this->folioContext($folioModel, $currency) : null,
            'payments' => $folioModel !== null ? $this->settledPayments($folioModel, $folios) : [],
        ]);
    }

    /**
     * The folio context that makes the refund figure CHECKABLE — from the shared FolioTotals formula, the
     * identical source the reservation detail and the folio PDF read, so the three can never disagree.
     *
     * ⚠️ A refund INCREASES the balance: recompute() does `paid = received − refunded; balance = charges −
     * paid`, and a refund reverses a PAYMENT, never the charges. The caption says so, because the design's
     * "reverses every settled charge on the folio" copy implies the exact opposite of what happens.
     *
     * @return array<string, mixed>
     */
    private function folioContext(Folio $folioModel, string $currency): array
    {
        $totals = FolioTotals::for($folioModel);

        return [
            'charges' => $this->money($totals['charges_minor'], $currency),
            'paid' => $this->money($totals['paid_minor'], $currency),
            'balance' => $this->money($totals['balance_minor'], $currency),
            'has_balance' => $totals['balance_minor'] > 0,
            // Cross-check against the folio's own FolioService-maintained cache — same rows, same formula.
            'reconciles' => $totals['balance_minor'] === (int) $folioModel->balance_minor,
        ];
    }

    /**
     * The reservation's genuinely settled payments — the picker's rows.
     *
     * `type ∈ payment|capture` AND `status ∈ succeeded|captured` is the real settled test, enforced twice
     * more downstream (the controller re-checks it; `refundPayment()` rejects any other type outright). A
     * pending 3DS payment, a failed one, and a deposit hold are all genuinely un-refundable and are simply
     * not offered — no row is invented and none is hidden that could be refunded.
     *
     * @return list<array<string, mixed>>
     */
    private function settledPayments(Folio $folioModel, FolioService $folios): array
    {
        return $folioModel->payments
            ->filter(fn (Payment $payment): bool => in_array((string) $payment->type, ['payment', 'capture'], true)
                && in_array((string) $payment->status, ['succeeded', 'captured'], true))
            ->sortBy(fn (Payment $payment): int => CarbonImmutable::parse($payment->processed_at ?? $payment->created_at)->getTimestamp())
            ->map(fn (Payment $payment): array => $this->paymentCard($payment, $folios))
            ->values()
            ->all();
    }

    /**
     * One settled payment → its picker card. EVERY refund figure comes from the service's own helpers:
     * `refundedTotalMinor()` (Σ prior SUCCEEDED refunds — a failed one moved no money and must not consume
     * headroom) and `remainingRefundableMinor()` (original − that Σ, never negative). This screen must not
     * re-derive them, or it could show a cap the guard does not enforce.
     *
     * @return array<string, mixed>
     */
    private function paymentCard(Payment $payment, FolioService $folios): array
    {
        $currency = (string) $payment->currency;
        $originalMinor = (int) $payment->amount_minor;
        $refundedMinor = $folios->refundedTotalMinor($payment);
        $remainingMinor = $folios->remainingRefundableMinor($payment);

        return [
            'id' => (string) $payment->id,
            'label' => (string) $payment->type === 'capture' ? 'Capture' : 'Payment',
            // The REAL gateway the money moved through — `payments.gateway` is a real enum column. This is
            // what the design's fabricated "Visa ····4242" is replaced by: no card brand or last4 exists.
            'gateway' => $this->gatewayLabel((string) $payment->gateway),
            'date' => CarbonImmutable::parse($payment->processed_at ?? $payment->created_at)->format('j M Y'),
            'original' => $this->money($originalMinor, $currency),
            'refunded' => $this->money($refundedMinor, $currency),
            'remaining' => $this->money($remainingMinor, $currency),
            'currency' => $currency,
            // THE CAP the input carries, in major units. Plain `.`-decimal, no thousands separator — a
            // grouped "1,200.00" is not a valid HTML number `max` and the browser would drop the cap.
            'max_input' => number_format($remainingMinor / 100, 2, '.', ''),
            'refundable' => $remainingMinor > 0,
            // Total over the four real combinations; nothing is left to an unlabelled fallthrough.
            'state' => match (true) {
                $remainingMinor > 0 && $refundedMinor > 0 => 'Partly refunded',
                $remainingMinor > 0 => null,
                $refundedMinor > 0 => 'Fully refunded',
                default => 'Nothing refundable',
            },
        ];
    }

    /** Real `payments.gateway` enum → a human label. Unknown values are title-cased, never dropped. */
    private function gatewayLabel(string $gateway): string
    {
        return match ($gateway) {
            'stripe' => 'Stripe',
            'manual' => 'Manual / offline',
            'bank_transfer' => 'Bank transfer',
            'ota_collect' => 'Collected by the OTA',
            'paypal' => 'PayPal',
            'square' => 'Square',
            default => ucwords(str_replace('_', ' ', $gateway)),
        };
    }
}
