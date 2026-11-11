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
    <x-operator.shell active="reservations" :title="__('livewire/operator/reservation-refund.page_title')"
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
                        <a href="{{ route('app.reservations.show', $reservation['id']) }}" class="vy-act vy-act--ghost">{{ __('livewire/operator/reservation-refund.back_to_reservation') }}</a>
                    </div>
                    <div class="vy-facts" style="margin-top: 14px;">
                        <div><div class="vy-fact-label">{{ __('livewire/operator/reservation-refund.guest') }}</div><div class="vy-fact-val">{{ $reservation['guest'] ?? '—' }}</div></div>
                        <div><div class="vy-fact-label">{{ __('livewire/operator/reservation-refund.stay') }}</div><div class="vy-fact-val">{{ $reservation['check_in'] }} <span class="vy-flip-rtl">→</span> {{ $reservation['check_out'] }}</div></div>
                    </div>
                </div>

                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom: 4px;">{{ __('livewire/operator/reservation-refund.refund_a_payment') }}</h2>
                    {{-- TRUE and enforced three times over: here, in the controller, and in refundPayment()
                         itself (which rejects any type that is not payment/capture). --}}
                    <div class="vy-recon" style="margin-top: 0;">{{ __('livewire/operator/reservation-refund.only_settled') }}</div>

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
                        <div class="vy-callout-title">{{ __('livewire/operator/reservation-refund.cannot_reverse_title') }}</div>
                        {{ __('livewire/operator/reservation-refund.cannot_reverse_body') }}
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
                                    <div><div class="vy-fact-label">{{ __('livewire/operator/reservation-refund.original') }}</div><div class="vy-fact-val">{{ $payment['original'] }}</div></div>
                                    <div><div class="vy-fact-label">{{ __('livewire/operator/reservation-refund.already_refunded') }}</div><div class="vy-fact-val">{{ $payment['refunded'] }}</div></div>
                                    {{-- ⚠️ A refund Stripe has ACCEPTED but not yet moved. It holds headroom, so the desk sees WHY the
                                         remaining figure is smaller — a refund in flight that appears nowhere is how a second one gets issued. --}}
                                    @if (! empty($payment['in_flight']))
                                        <div><div class="vy-fact-label">{{ __('livewire/operator/reservation-refund.refund_in_progress') }}</div><div class="vy-fact-val">{{ $payment['in_flight'] }}</div></div>
                                    @endif
                                    <div><div class="vy-fact-label">{{ __('livewire/operator/reservation-refund.still_refundable') }}</div><div class="vy-fact-val">{{ $payment['remaining'] }}</div></div>
                                </div>

                                @if ($payment['refundable'])
                                    {{-- FULL = the REMAINING. Posts no `amount`, so the controller takes its
                                         full-remaining path — the same call the inline detail button makes. --}}
                                    <form method="POST" action="{{ route('app.reservations.refund', $reservation['id']) }}" style="margin-top: 14px;"
                                        data-guard-submit
                                        onsubmit="return confirm(@js(__('livewire/operator/reservation-refund.refund_remaining_confirm', ['amount' => $payment['remaining']])));">
                                        @csrf
                                        <input type="hidden" name="payment" value="{{ $payment['id'] }}">
                                        <x-busy-btn class="vy-act vy-act--accent" :busy-label="__('livewire/operator/reservation-refund.refunding')">{{ __('livewire/operator/reservation-refund.refund_remaining', ['amount' => $payment['remaining']]) }}</x-busy-btn>
                                    </form>

                                    {{-- PARTIAL — capped at the remaining. The cap is a courtesy for the
                                         operator; FolioService is the guard, and it rejects an over-refund
                                         outright rather than quietly clamping it. --}}
                                    <form method="POST" action="{{ route('app.reservations.refund', $reservation['id']) }}" style="margin-top: 14px;"
                                        data-guard-submit
                                        onsubmit="return confirm(@js(__('livewire/operator/reservation-refund.partial_confirm')));">
                                        @csrf
                                        <input type="hidden" name="payment" value="{{ $payment['id'] }}">
                                        <label class="vy-field-label" for="amount-{{ $payment['id'] }}">{{ __('livewire/operator/reservation-refund.refund_part', ['currency' => $payment['currency']]) }}</label>
                                        <div class="vy-input-group">
                                            {{-- D-MONEY-COMMA: text + a decimal keypad, read by the server in the page's language; the server
                                                 caps the refund at what is refundable (#27), as it always did --}}
                                            <input class="vy-input" type="text" inputmode="decimal" autocomplete="off"
                                                name="amount" id="amount-{{ $payment['id'] }}" placeholder="0.00">
                                        </div>
                                        <x-money-readout :for="'amount-'.$payment['id']" :currency="$payment['currency']" verb="refunds" :max-minor="$payment['max_minor']" />

                                        {{-- #100 — the refund REASON (recorded on the folio + reports) + REFUND-TO. --}}
                                        <label class="vy-field-label" style="margin-top: 12px;">{{ __('livewire/operator/reservation-refund.reason') }} <span style="text-transform: none; font-weight: 500; color: var(--vy-faint);">{{ __('livewire/operator/reservation-refund.reason_note') }}</span></label>
                                        <select class="vy-input" name="reason">
                                            <option value="">{{ __('livewire/operator/reservation-refund.choose_reason') }}</option>
                                            <option value="duplicate">{{ __('livewire/operator/reservation-refund.reason_duplicate') }}</option>
                                            <option value="overcharge">{{ __('livewire/operator/reservation-refund.reason_overcharge') }}</option>
                                            <option value="service_issue">{{ __('livewire/operator/reservation-refund.reason_service_issue') }}</option>
                                            <option value="cancellation">{{ __('livewire/operator/reservation-refund.reason_cancellation') }}</option>
                                            <option value="goodwill">{{ __('livewire/operator/reservation-refund.reason_goodwill') }}</option>
                                            <option value="other">{{ __('livewire/operator/reservation-refund.reason_other') }}</option>
                                        </select>
                                        <label class="vy-field-label" for="vy-lbl-reservation-refund-note">{{ __('livewire/operator/reservation-refund.add_note') }}</label>
                                        <input id="vy-lbl-reservation-refund-note" class="vy-input" type="text" name="note" maxlength="200" placeholder="{{ __('livewire/operator/reservation-refund.add_note') }}" style="margin-top: 8px;">

                                        <label class="vy-field-label" style="margin-top: 12px;">{{ __('livewire/operator/reservation-refund.refund_to') }}</label>
                                        <div style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: var(--vy-ink);">
                                            <label style="display: flex; align-items: center; gap: 8px;"><input type="radio" name="destination" value="original" checked> {!! __('livewire/operator/reservation-refund.original_method', ['gateway' => e($payment['gateway'])]) !!}</label>
                                            <label style="display: flex; align-items: center; gap: 8px;"><input type="radio" name="destination" value="manual_bank"> {{ __('livewire/operator/reservation-refund.manual_bank_transfer') }} <span style="color: var(--vy-faint);">{{ __('livewire/operator/reservation-refund.manual_bank_transfer_note') }}</span></label>
                                        </div>

{{-- ⚠️ WHO BEARS IT — rendered ONLY for an owner-attributed reservation (there is no owner to charge on an
     operator-owned unit, so the question is meaningless there). ⚠️ "The owner" is CHECKED by default and
     stated in words: absorbing a refund must be a deliberate act, never something that happens because a
     control was left alone. The choice is captured with this refund and cannot be changed afterwards. --}}
                                        @if ($isOwnerAttributed)
                                            <label class="vy-field-label" style="margin-top: 12px;">{{ __('livewire/operator/reservation-refund.whose_cost') }}</label>
                                            <div style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: var(--vy-ink);">
                                                <label style="display: flex; align-items: center; gap: 8px;"><input type="radio" name="borne_by" value="owner" checked> {{ __('livewire/operator/reservation-refund.borne_owner') }} <span style="color: var(--vy-faint);">{{ __('livewire/operator/reservation-refund.borne_owner_note') }}</span></label>
                                                <label style="display: flex; align-items: center; gap: 8px;"><input type="radio" name="borne_by" value="operator"> {{ __('livewire/operator/reservation-refund.borne_operator') }} <span style="color: var(--vy-faint);">{{ __('livewire/operator/reservation-refund.borne_operator_note') }}</span></label>
                                            </div>
                                        @endif

                                        <x-busy-btn class="vy-act vy-act--accent" :busy-label="__('livewire/operator/reservation-refund.refunding')" style="margin-top: 12px;">{{ __('livewire/operator/reservation-refund.review_issue') }}</x-busy-btn>
                                    </form>
                                    <div class="vy-recon" style="margin-top: 8px;">{{ __('livewire/operator/reservation-refund.up_to_refundable', ['amount' => $payment['remaining']]) }}</div>
                                @else
                                    {{-- NO control at remaining = 0. A refund button that survives a full
                                         refund is exactly what let one €369.00 payment take three of them. --}}
                                    <div class="vy-recon" style="margin-top: 12px;">{{ __('livewire/operator/reservation-refund.fully_refunded') }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty">{{ __('livewire/operator/reservation-refund.no_settled_payment') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- RIGHT RAIL: the folio context that makes the number checkable + the honest gaps --}}
            <div class="vy-stack">
                @if ($folio !== null)
                    <div class="vy-card">
                        <span class="vy-eyebrow">{{ __('livewire/operator/reservation-refund.this_folio') }}</span>
                        <div class="vy-totals" style="margin-top: 12px; border-top: none; padding-top: 0;">
                            <div class="vy-total-row"><span class="vy-t-label">{{ __('livewire/operator/reservation-refund.charges') }}</span><span class="vy-t-val">{{ $folio['charges'] }}</span></div>
                            <div class="vy-total-row"><span class="vy-t-label">{{ __('livewire/operator/reservation-refund.paid') }}</span><span class="vy-t-val">{{ $folio['paid'] }}</span></div>
                            <div class="vy-total-row is-balance @unless ($folio['has_balance']) is-settled @endunless">
                                <span class="vy-t-label">{{ __('livewire/operator/reservation-refund.balance_due') }}</span><span class="vy-t-val">{{ $folio['balance'] }}</span>
                            </div>
                        </div>

                        @if ($folio['reconciles'])
                            <span class="vy-recon">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                {{ __('livewire/operator/reservation-refund.reconciled') }}
                            </span>
                        @endif
                    </div>
                @endif

                <div class="vy-card">
                    <span class="vy-eyebrow">{{ __('livewire/operator/reservation-refund.what_happens') }}</span>
                    {{-- #100 — every claim here is one the code genuinely makes now (the owner-statement
                         adjustment is wired via the payment.refunded listener → OwnerAttributionService). --}}
                    <div class="vy-callout is-oos" style="margin-top: 12px;">
                        <div class="vy-callout-title">{{ __('livewire/operator/reservation-refund.reversing_entry_title') }}</div>
                        {{ __('livewire/operator/reservation-refund.reversing_entry_body') }}
                    </div>
                    <div class="vy-callout is-oos" style="margin-top: 10px;">
                        <div class="vy-callout-title">{{ __('livewire/operator/reservation-refund.owner_statement_title') }}</div>
                        {{ __('livewire/operator/reservation-refund.owner_statement_body') }}
                    </div>
                    <div class="vy-recon" style="margin-top: 10px;">{{ __(($mailSends ?? false) ? 'livewire/operator/reservation-refund.guest_emailed' : 'livewire/operator/reservation-refund.guest_not_emailed') }}</div>   {{-- D-MAIL-CLAIMS: "emailed automatically" only while email IS sent --}}
                </div>

                <div class="vy-card">
                    <span class="vy-eyebrow">{{ __('livewire/operator/reservation-refund.not_yet_available') }}</span>
                    {{-- The only genuine remaining gap: per-CHARGE selection needs a folio_items↔payments link
                         (a credit note, a different concept). Refund reason + the guest email are now REAL. --}}
                    <div class="vy-action-list" style="margin-top: 10px;">
                        <div class="vy-defer-item"><span>{{ __('livewire/operator/reservation-refund.refund_by_charge') }}</span><span class="vy-soon-tag">{{ __('livewire/operator/reservation-refund.soon') }}</span></div>
                    </div>
                    <div class="vy-recon" style="margin-top: 8px;">{{ __('livewire/operator/reservation-refund.per_charge_note') }}</div>
                </div>
            </div>
        </div>

    </x-operator.shell>
</div>
