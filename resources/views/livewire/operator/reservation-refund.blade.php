{{-- Operator Refund — the dedicated refund composer (Wave 2 · sub-gate 3). Styled on the shared operator
     design system via <x-operator.shell>. The screen READS; the refund posts to the existing amount-aware
     FolioService action (append-only) and persists.

     THE POINT OF THIS SCREEN IS TO RESPECT THE #27 CUMULATIVE GUARD:
       · one payment is PICKED (refundPayment needs a specific Payment — an aggregate cannot form the call)
       · the REMAINING refundable is shown per payment, from the service's own helper
       · FULL = the remaining, never the original (the original at a part-refunded payment IS the over-refund)
       · PARTIAL is capped at the remaining — a courtesy; the service is still the authority
       · at remaining = 0 NO control is offered at all — its persistence is what made the bug reachable --}}
<div>
    <x-operator.shell active="reservations" title="Refund a guest"
        :subtitle="$reservation['reference'] . ' · ' . $reservation['status_label']" :viewer="$viewer">

        {{-- ⚠️ ONE component decides refusal-vs-success (audit D4). This view used to hardcode a tick, so
             a refused refund or a lost inventory race rendered as a receipt. --}}
        @if (session('folio_notice'))
            <x-operator.flash-notice :message="session('folio_notice')" :refused="(bool) session('folio_refused', false)" />
        @endif

        <div class="vy-detail-cols vy-fade">
            {{-- MAIN: the payment picker — one card per genuinely settled payment --}}
            <div class="vy-stack">
                <div class="vy-card">
                    <div class="vy-detail-head">
                        <span class="vy-detail-ref">{{ $reservation['reference'] }}</span>
                        <a href="{{ route('app.reservations.show', $reservation['id']) }}" class="vy-act vy-act--ghost">Back to reservation</a>
                    </div>
                    <div class="vy-facts" style="margin-top: 14px;">
                        <div><div class="vy-fact-label">Guest</div><div class="vy-fact-val">{{ $reservation['guest'] ?? '—' }}</div></div>
                        <div><div class="vy-fact-label">Stay</div><div class="vy-fact-val">{{ $reservation['check_in'] }} → {{ $reservation['check_out'] }}</div></div>
                    </div>
                </div>

                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 4px;">Refund a payment</h2>
                    {{-- TRUE and enforced three times over: here, in the controller, and in refundPayment()
                         itself (which rejects any type that is not payment/capture). --}}
                    <div class="vy-recon" style="margin-top: 0;">Only settled payments can be refunded.</div>

                    {{-- ⚠️⚠️ F-39 — THE CONSEQUENCE SITS BEFORE THE ACTION, AND THAT IS THE WHOLE FIX.
                         This callout lived in the RIGHT RAIL's "What happens" card. At 1280 the rail is a
                         340px column that starts higher than the second form, so the warning MEASURED above
                         the amount-aware button (661 vs 829) and looked correctly ordered. ⚠️ THAT WAS A
                         COLUMN ARTEFACT, NOT AN ORDER. At 375 the grid collapses to one column and the
                         warning fell to 2152 — BELOW the one-click button at 1113 AND the amount-aware
                         button at 1472. Neither path disclosed the consequence while the operator decided.
                         ⚠️ MOVED, NOT COPIED — a second copy of one sentence is how two surfaces drift
                         (§N.8f), and the wording is untouched: Phase 7 called it the best disclosure in the
                         product and two later passes copied its register. This is a POSITION fix.
                         ⚠️ PLACED ABOVE THE PAYMENT LIST, not inside one payment's block, so it precedes
                         every button on BOTH paths for EVERY listed payment — one placement, no repetition. --}}
                    <div class="vy-callout is-cost" style="margin-top: 12px;">
                        <div class="vy-callout-title">It can’t be reversed</div>
                        A refund reverses a payment, not the charges — so the folio shows the guest owing again. Refunds cannot be reversed once issued.
                    </div>

                    @forelse ($payments as $payment)
                        <div class="vy-unit-row" style="align-items: flex-start;">
                            <div style="flex: 1 1 auto; min-width: 0;">
                                <div class="vy-um-name">
                                    {{ $payment['label'] }} · {{ $payment['original'] }}
                                    @if ($payment['state'])
                                        <span class="vy-ledger-flag">{{ $payment['state'] }}</span>
                                    @endif
                                </div>
                                {{-- The REAL gateway the money moved through. There is no card brand or last4
                                     column in the schema — nothing of the sort is rendered. --}}
                                <div class="vy-um-sub">{{ $payment['gateway'] }} · {{ $payment['date'] }}</div>

                                {{-- Every figure below is the SERVICE's own arithmetic, not this screen's. --}}
                                <div class="vy-facts" style="margin-top: 12px;">
                                    <div><div class="vy-fact-label">Original</div><div class="vy-fact-val">{{ $payment['original'] }}</div></div>
                                    <div><div class="vy-fact-label">Already refunded</div><div class="vy-fact-val">{{ $payment['refunded'] }}</div></div>
                                    {{-- ⚠️ A refund Stripe has ACCEPTED but not yet moved. It holds headroom, so the desk sees WHY the
                                         remaining figure is smaller — a refund in flight that appears nowhere is how a second one gets issued. --}}
                                    @if (! empty($payment['in_flight']))
                                        <div><div class="vy-fact-label">Refund in progress</div><div class="vy-fact-val">{{ $payment['in_flight'] }}</div></div>
                                    @endif
                                    <div><div class="vy-fact-label">Still refundable</div><div class="vy-fact-val">{{ $payment['remaining'] }}</div></div>
                                </div>

                                @if ($payment['refundable'])
                                    {{-- FULL = the REMAINING. Posts no `amount`, so the controller takes its
                                         full-remaining path — the same call the inline detail button makes. --}}
                                    <form method="POST" action="{{ route('app.reservations.refund', $reservation['id']) }}" style="margin-top: 14px;"
                                        data-guard-submit
                                        onsubmit="return confirm('Refund {{ $payment['remaining'] }} — the whole remaining amount of this payment? A refund cannot be reversed.');">
                                        @csrf
                                        <input type="hidden" name="payment" value="{{ $payment['id'] }}">
                                        <x-busy-btn class="vy-act vy-act--accent" busy-label="Refunding…">Refund the remaining {{ $payment['remaining'] }}</x-busy-btn>
                                    </form>

                                    {{-- PARTIAL — capped at the remaining. The cap is a courtesy for the
                                         operator; FolioService is the guard, and it rejects an over-refund
                                         outright rather than quietly clamping it. --}}
                                    <form method="POST" action="{{ route('app.reservations.refund', $reservation['id']) }}" style="margin-top: 14px;"
                                        data-guard-submit
                                        onsubmit="return confirm('Issue this partial refund? A refund cannot be reversed.');">
                                        @csrf
                                        <input type="hidden" name="payment" value="{{ $payment['id'] }}">
                                        <label class="vy-field-label" for="amount-{{ $payment['id'] }}">Or refund part of it ({{ $payment['currency'] }})</label>
                                        <div class="vy-input-group">
                                            <input class="vy-input" type="number" inputmode="decimal" step="0.01"
                                                min="0.01" max="{{ $payment['max_input'] }}"
                                                name="amount" id="amount-{{ $payment['id'] }}" placeholder="0.00">
                                        </div>

                                        {{-- #100 — the refund REASON (recorded on the folio + reports) + REFUND-TO. --}}
                                        <label class="vy-field-label" style="margin-top: 12px;">Reason <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">· recorded on the folio &amp; in reports</span></label>
                                        <select class="vy-input" name="reason">
                                            <option value="">Choose a reason…</option>
                                            <option value="duplicate">Duplicate charge</option>
                                            <option value="overcharge">Overcharge</option>
                                            <option value="service_issue">Service issue</option>
                                            <option value="cancellation">Cancellation</option>
                                            <option value="goodwill">Goodwill</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <label class="vy-field-label" for="vy-lbl-reservation-refund-note">Add a note (optional)</label>
                                        <input id="vy-lbl-reservation-refund-note" class="vy-input" type="text" name="note" maxlength="200" placeholder="Add a note (optional)" style="margin-top: 8px;">

                                        <label class="vy-field-label" style="margin-top: 12px;">Refund to</label>
                                        <div style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: var(--vy-ink);">
                                            <label style="display: flex; align-items: center; gap: 8px;"><input type="radio" name="destination" value="original" checked> Original method · <span style="color: var(--vy-muted);">{{ $payment['gateway'] }}</span></label>
                                            <label style="display: flex; align-items: center; gap: 8px;"><input type="radio" name="destination" value="manual_bank"> Manual bank transfer <span style="color: var(--vy-faint);">· recorded, no gateway</span></label>
                                        </div>

{{-- ⚠️ WHO BEARS IT — rendered ONLY for an owner-attributed reservation (there is no owner to charge on an
     operator-owned unit, so the question is meaningless there). ⚠️ "The owner" is CHECKED by default and
     stated in words: absorbing a refund must be a deliberate act, never something that happens because a
     control was left alone. The choice is captured with this refund and cannot be changed afterwards. --}}
                                        @if ($isOwnerAttributed)
                                            <label class="vy-field-label" style="margin-top: 12px;">Whose cost is this?</label>
                                            <div style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: var(--vy-ink);">
                                                <label style="display: flex; align-items: center; gap: 8px;"><input type="radio" name="borne_by" value="owner" checked> The owner <span style="color: var(--vy-faint);">· deducted from their share of this booking</span></label>
                                                <label style="display: flex; align-items: center; gap: 8px;"><input type="radio" name="borne_by" value="operator"> Ours <span style="color: var(--vy-faint);">· we absorb it; the owner is not charged</span></label>
                                            </div>
                                        @endif

                                        <x-busy-btn class="vy-act vy-act--accent" busy-label="Refunding…" style="margin-top: 12px;">Review &amp; issue refund</x-busy-btn>
                                    </form>
                                    <div class="vy-recon" style="margin-top: 8px;">Up to {{ $payment['remaining'] }} is still refundable against this payment. Anything above that is refused, not trimmed to fit.</div>
                                @else
                                    {{-- NO control at remaining = 0. A refund button that survives a full
                                         refund is exactly what let one €369.00 payment take three of them. --}}
                                    <div class="vy-recon" style="margin-top: 12px;">This payment is fully refunded — there is nothing left to refund against it.</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty">No settled payment on this folio yet. Only settled payments can be refunded.</div>
                    @endforelse
                </div>
            </div>

            {{-- RIGHT RAIL: the folio context that makes the number checkable + the honest gaps --}}
            <div class="vy-stack">
                @if ($folio !== null)
                    <div class="vy-card">
                        <span class="vy-eyebrow">This folio</span>
                        <div class="vy-totals" style="margin-top: 12px; border-top: none; padding-top: 0;">
                            <div class="vy-total-row"><span class="vy-t-label">Charges</span><span class="vy-t-val">{{ $folio['charges'] }}</span></div>
                            <div class="vy-total-row"><span class="vy-t-label">Paid</span><span class="vy-t-val">{{ $folio['paid'] }}</span></div>
                            <div class="vy-total-row is-balance @unless ($folio['has_balance']) is-settled @endunless">
                                <span class="vy-t-label">Balance due</span><span class="vy-t-val">{{ $folio['balance'] }}</span>
                            </div>
                        </div>

                        @if ($folio['reconciles'])
                            <span class="vy-recon">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                Reconciled to the folio ledger
                            </span>
                        @endif
                    </div>
                @endif

                <div class="vy-card">
                    <span class="vy-eyebrow">What happens</span>
                    {{-- #100 — every claim here is one the code genuinely makes now (the owner-statement
                         adjustment is wired via the payment.refunded listener → OwnerAttributionService). --}}
                    <div class="vy-callout is-oos" style="margin-top: 12px;">
                        <div class="vy-callout-title">A reversing entry is appended</div>
                        The refund is posted as its own new entry on the ledger. The original payment is never edited or removed.
                    </div>
                    <div class="vy-callout is-oos" style="margin-top: 10px;">
                        <div class="vy-callout-title">The owner statement adjusts</div>
                        For an owner-attributed stay, the owner's net share of the refund is posted to their ledger and lands on their next statement close.
                    </div>
                    <div class="vy-recon" style="margin-top: 10px;">The guest is emailed a refund confirmation automatically when the refund succeeds. Card refunds run in test mode until go-live — no real money moves yet.</div>
                </div>

                <div class="vy-card">
                    <span class="vy-eyebrow">Not yet available</span>
                    {{-- The only genuine remaining gap: per-CHARGE selection needs a folio_items↔payments link
                         (a credit note, a different concept). Refund reason + the guest email are now REAL. --}}
                    <div class="vy-action-list" style="margin-top: 10px;">
                        <div class="vy-defer-item"><span>Refund by individual charge</span><span class="vy-soon-tag">Soon</span></div>
                    </div>
                    <div class="vy-recon" style="margin-top: 8px;">Refunds attach to a payment, not to individual charges — a per-charge credit note is a separate concept.</div>
                </div>
            </div>
        </div>

    </x-operator.shell>
</div>
