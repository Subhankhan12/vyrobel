{{-- Operator Checkout (Phase 7b · SG2) — the chosen tier + period, company/VAT capture, and the price
     summary (placeholder). The Subscribe action is the ACCOUNT-GATED Stripe seam — it takes no money, stores
     no card, and does not activate. Live billing is honestly "coming soon". --}}
<div>
    <x-operator.shell active="billing" title="Checkout" :subtitle="$tier['name'].' plan'" :viewer="$viewer">

        @if (session('billing_notice'))
            <div class="vy-flash vy-fade" role="status">{{ session('billing_notice') }}</div>
        @endif

        <a class="vy-btn vy-btn--ghost vy-fade" href="{{ route('app.billing') }}" style="margin-bottom:14px">← Back to plans</a>

        <div class="vy-ins-grid vy-fade">
            {{-- LEFT — company / VAT + billing period --}}
            <div class="vy-card">
                <div class="vy-card-head"><h3>Your details</h3><span class="vy-card-note">for your invoices</span></div>

                {{-- Billing period toggle (real — switches the summary price via GET) --}}
                <div class="vy-field">
                    <label class="vy-field-label">Billing period</label>
                    <div class="vy-seg">
                        <a class="vy-seg-btn @if ($interval === 'monthly') is-on @endif" href="{{ route('app.billing.checkout', ['plan' => $tier['code'], 'interval' => 'monthly']) }}">Monthly</a>
                        <a class="vy-seg-btn @if ($interval === 'yearly') is-on @endif" href="{{ route('app.billing.checkout', ['plan' => $tier['code'], 'interval' => 'yearly']) }}">Yearly</a>
                    </div>
                </div>

                <form method="POST" action="{{ route('app.billing.subscribe') }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $tier['code'] }}">
                    <input type="hidden" name="interval" value="{{ $interval }}">

                    <div class="vy-field">
                        <label class="vy-field-label" for="billing_company">Company name</label>
                        <input class="vy-input" id="billing_company" name="billing_company" value="{{ old('billing_company', $company) }}" maxlength="255" placeholder="e.g. Seaside Group Lda">
                    </div>
                    <div class="vy-field">
                        <label class="vy-field-label" for="billing_vat_id">VAT / tax ID <span class="vy-fl-note">· optional</span></label>
                        <input class="vy-input" id="billing_vat_id" name="billing_vat_id" value="{{ old('billing_vat_id', $vat_id) }}" maxlength="64" placeholder="e.g. PT 512 034 776">
                    </div>

                    {{-- ⚠️ THE ACCOUNT-GATED SEAM — no card field, no charge. --}}
                    <div class="vy-bill-gate">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/></svg>
                        <div>Card payment is coming soon. Vyrobel isn't collecting payments yet — subscribing records your choice and continues your trial; no card is asked for or charged.</div>
                    </div>

                    <button type="submit" class="vy-btn vy-btn--accent" style="width:100%">Subscribe to {{ $tier['name'] }} <span class="vy-soon-tag">Soon</span></button>
                </form>
            </div>

            {{-- RIGHT — the summary (placeholder price, reflects the chosen tier + period) --}}
            <div class="vy-card">
                <div class="vy-card-head"><h3>Summary</h3></div>
                <div class="vy-report-line"><span>Plan</span><span>{{ $tier['name'] }}</span></div>
                <div class="vy-report-line"><span>Billing</span><span>{{ ucfirst($interval) }}</span></div>
                <div class="vy-report-line vy-report-total"><span>{{ $price }}</span><span>{{ $period_label }}</span></div>
                <p class="vy-bill-disclaimer" style="margin-top:12px">Placeholder price while we finalise pricing. Billed {{ $interval }} once live billing is available.</p>
            </div>
        </div>
    </x-operator.shell>
</div>
