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

        @if (session('folio_notice'))
            <div class="vy-toast vy-fade" role="status">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('folio_notice') }}
            </div>
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
                                            <x-busy-btn class="vy-act vy-act--ghost" busy-label="Refunding…">Refund</x-busy-btn>
                                        </div>
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
                    <span class="vy-eyebrow">What a refund does</span>
                    {{-- Every claim here is one the code genuinely makes. The design's "the owner statement
                         adjusts on the next close" is NOT among them: OwnerAttributionService has no
                         production caller and reads folio_items + reservation_units, neither of which a
                         refund touches — the statement would not move by a cent. --}}
                    <div class="vy-callout is-oos" style="margin-top: 12px;">
                        <div class="vy-callout-title">A reversing entry is appended</div>
                        The refund is posted as its own new entry on the ledger. The original payment is never edited or removed.
                    </div>
                    <div class="vy-callout is-cost" style="margin-top: 10px;">
                        <div class="vy-callout-title">The balance goes back up</div>
                        A refund reverses a payment, not the charges — so the folio shows the guest owing again. Refunds cannot be reversed once issued.
                    </div>
                </div>

                <div class="vy-card">
                    <span class="vy-eyebrow">Not yet available</span>
                    {{-- DEFERRED, stated plainly rather than drawn and left inert. The reason chips need a
                         `$reason` on FolioService::refundPayment (a service change, not approved here) AND a
                         vocabulary decision — the design's 5 chips map to no enum in the schema. The guest
                         email needs a Mailable that does not exist. Neither is faked. --}}
                    <div class="vy-action-list" style="margin-top: 10px;">
                        <div class="vy-defer-item"><span>Refund reason</span><span class="vy-soon-tag">Soon</span></div>
                        <div class="vy-defer-item"><span>Email the guest a confirmation</span><span class="vy-soon-tag">Soon</span></div>
                        <div class="vy-defer-item"><span>Refund by charge</span><span class="vy-soon-tag">Soon</span></div>
                    </div>
                    <div class="vy-recon" style="margin-top: 8px;">A reason is not stored anywhere yet, so the refund records none. Refunds attach to a payment, not to individual charges.</div>
                </div>
            </div>
        </div>

    </x-operator.shell>
</div>
