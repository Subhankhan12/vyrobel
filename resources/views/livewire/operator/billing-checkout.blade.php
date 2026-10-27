{{-- Operator Checkout (Phase 7b · SG2) — the chosen tier + period, company/VAT capture, and the price
     summary (placeholder). The Subscribe action is the ACCOUNT-GATED Stripe seam — it takes no money, stores
     no card, and does not activate. Live billing is honestly "coming soon". --}}
<div>
    <x-operator.shell active="billing" :title="__('livewire/operator/billing-checkout.page_title')" :subtitle="__('livewire/operator/billing-checkout.plan_subtitle', ['plan' => $tier['name']])" :viewer="$viewer">

        @if (session('billing_notice'))
            <div class="vy-flash vy-fade" role="status">{{ session('billing_notice') }}</div>
        @endif

        <a class="vy-btn vy-btn--ghost vy-fade" href="{{ route('app.billing') }}" style="margin-bottom:14px">{{ __('livewire/operator/billing-checkout.back_to_plans') }}</a>

        <div class="vy-ins-grid vy-fade">
            {{-- LEFT — company / VAT + billing period --}}
            <div class="vy-card">
                <div class="vy-card-head"><h3>{{ __('livewire/operator/billing-checkout.your_details') }}</h3><span class="vy-card-note">{{ __('livewire/operator/billing-checkout.for_your_invoices') }}</span></div>

                {{-- Billing period toggle (real — switches the summary price via GET) --}}
                <div class="vy-field">
                    <label class="vy-field-label">{{ __('livewire/operator/billing-checkout.billing_period') }}</label>
                    <div class="vy-seg">
                        <a class="vy-seg-btn @if ($interval === 'monthly') is-on @endif" href="{{ route('app.billing.checkout', ['plan' => $tier['code'], 'interval' => 'monthly']) }}">{{ __('livewire/operator/billing-checkout.interval_monthly') }}</a>
                        <a class="vy-seg-btn @if ($interval === 'yearly') is-on @endif" href="{{ route('app.billing.checkout', ['plan' => $tier['code'], 'interval' => 'yearly']) }}">{{ __('livewire/operator/billing-checkout.interval_yearly') }}</a>
                    </div>
                </div>

                <form method="POST" action="{{ route('app.billing.subscribe') }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $tier['code'] }}">
                    <input type="hidden" name="interval" value="{{ $interval }}">

                    <div class="vy-field">
                        <label class="vy-field-label" for="billing_company">{{ __('livewire/operator/billing-checkout.company_name') }}</label>
                        <input class="vy-input" id="billing_company" name="billing_company" value="{{ old('billing_company', $company) }}" maxlength="255" placeholder="{{ __('livewire/operator/billing-checkout.company_placeholder') }}">
                    </div>
                    <div class="vy-field">
                        <label class="vy-field-label" for="billing_vat_id">{!! __('livewire/operator/billing-checkout.vat_label') !!}</label>
                        <input class="vy-input" id="billing_vat_id" name="billing_vat_id" value="{{ old('billing_vat_id', $vat_id) }}" maxlength="64" placeholder="{{ __('livewire/operator/billing-checkout.vat_placeholder') }}">
                    </div>

                    {{-- 🔒 SAQ A — the card is entered on Stripe's secure hosted page, NEVER here. No card field. --}}
                    <div class="vy-bill-gate">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z"/></svg>
                        <div>{{ __('livewire/operator/billing-checkout.card_on_stripe') }}</div>
                    </div>

                    <button type="submit" class="vy-btn vy-btn--accent" style="width:100%">{{ __('livewire/operator/billing-checkout.continue_to_checkout') }}</button>
                </form>
            </div>

            {{-- RIGHT — the summary (placeholder price, reflects the chosen tier + period) --}}
            <div class="vy-card">
                <div class="vy-card-head"><h3>{{ __('livewire/operator/billing-checkout.summary') }}</h3></div>
                <div class="vy-report-line"><span>{{ __('livewire/operator/billing-checkout.plan') }}</span><span>{{ $tier['name'] }}</span></div>
                <div class="vy-report-line"><span>{{ __('livewire/operator/billing-checkout.billing') }}</span><span>{{ $interval === 'yearly' ? __('livewire/operator/billing-checkout.interval_yearly') : __('livewire/operator/billing-checkout.interval_monthly') }}</span></div>
                <div class="vy-report-line vy-report-total"><span>{{ $price }}</span><span>{{ $period_label }}</span></div>
                <p class="vy-bill-disclaimer" style="margin-top:12px">{{ $interval === 'yearly' ? __('livewire/operator/billing-checkout.disclaimer_yearly') : __('livewire/operator/billing-checkout.disclaimer_monthly') }}</p>
            </div>
        </div>
    </x-operator.shell>
</div>
