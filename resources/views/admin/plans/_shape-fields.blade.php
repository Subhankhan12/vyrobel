{{-- SUPER ADMIN · the plan's SHAPE and its money basis — shared by create and edit so the two forms cannot
     drift apart (§N.8f). ⚠️ Each shape has its OWN basis: a flat plan is priced, a per-unit plan is rated.
     Showing both at once would invite saving a plan that carries a stale figure from the other shape.

     ⚠️ NO-JS SAFE. The toggle below is progressive enhancement: without it BOTH groups render, which is
     harmless — the server rejects a malformed save either way (PlanIntegrity). The script only reduces noise;
     it is never what makes the plan correct. --}}
@props(['plan' => null])
@php
    use App\Models\Plan;
    use App\Support\Billing\PlanIntegrity;

    $shape = old('pricing_type', $plan?->pricing_type ?? Plan::PRICING_FLAT);
    $priceValue = old('price', $plan !== null ? number_format((int) $plan->price_minor / 100, 2, '.', '') : '');
    $rateValue = old('unit_rate', $plan?->unit_rate_minor !== null ? number_format((int) $plan->unit_rate_minor / 100, 2, '.', '') : '');
@endphp

<div class="ad-card" style="margin-top: 18px;">
    <h2 class="ad-panel-title">{{ __('admin/plans/_shape-fields.pricing_shape_heading') }}</h2>
    <p style="color: var(--ad-faint); font-size: 12.5px; margin: 0 0 14px;">
        {!! __('admin/plans/_shape-fields.pricing_shape_intro') !!}
    </p>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div>
            <label class="ad-label" for="pricing_type">{{ __('admin/plans/_shape-fields.shape_label') }}</label>
            <select class="ad-input" id="pricing_type" name="pricing_type" data-shape-select>
                <option value="{{ Plan::PRICING_FLAT }}" @selected($shape === Plan::PRICING_FLAT)>{{ __('admin/plans/_shape-fields.shape_flat_option') }}</option>
                <option value="{{ Plan::PRICING_PER_UNIT }}" @selected($shape === Plan::PRICING_PER_UNIT)>{{ __('admin/plans/_shape-fields.shape_per_unit_option') }}</option>
            </select>
        </div>
        <div>
            <label class="ad-label" for="interval">{{ __('admin/plans/_shape-fields.billing_interval_label') }}</label>
            <select class="ad-input" id="interval" name="interval">
                <option value="monthly" @selected(old('interval', $plan?->interval) === 'monthly')>{{ __('admin/plans/_shape-fields.interval_monthly_option') }}</option>
                <option value="yearly" @selected(old('interval', $plan?->interval) === 'yearly')>{{ __('admin/plans/_shape-fields.interval_yearly_option') }}</option>
            </select>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
        <div data-shape-group="{{ Plan::PRICING_FLAT }}">
            <label class="ad-label" for="price">{{ __('admin/plans/_shape-fields.price_label') }}</label>
            <input class="ad-input" type="number" step="0.01" min="0" id="price" name="price" value="{{ $priceValue }}">
            <div style="color: var(--ad-faint); font-size: 11.5px; margin-top: 5px;">{{ __('admin/plans/_shape-fields.price_hint') }}</div>
        </div>
        <div data-shape-group="{{ Plan::PRICING_PER_UNIT }}">
            <label class="ad-label" for="unit_rate">{{ __('admin/plans/_shape-fields.unit_rate_label') }}</label>
            <input class="ad-input" type="number" step="0.01" min="0" id="unit_rate" name="unit_rate" value="{{ $rateValue }}">
            {{-- ⚠️ Pre-tax, and the reason is worth stating on the form: under Stripe Tax the customer's own
                 rate applies, and an EU business under reverse charge pays €0 (billing pass 2b). --}}
            <div style="color: var(--ad-faint); font-size: 11.5px; margin-top: 5px;">{{ __('admin/plans/_shape-fields.unit_rate_hint') }}</div>
        </div>
        <div>
            <label class="ad-label" for="currency">{{ __('admin/plans/_shape-fields.currency_label') }}</label>
            <input class="ad-input" type="text" maxlength="3" id="currency" name="currency"
                   value="{{ old('currency', $plan?->currency ?? config('billing.default_currency', 'EUR')) }}" required>
        </div>
        <div>
            <label class="ad-label" for="vat_pct">{!! __('admin/plans/_shape-fields.vat_pct_label') !!}</label>
            <input class="ad-input" type="number" min="0" max="100" id="vat_pct" name="vat_pct" value="{{ old('vat_pct', $plan?->vat_pct) }}">
        </div>
    </div>
</div>

<div class="ad-card" style="margin-top: 18px;">
    <h2 class="ad-panel-title">{{ __('admin/plans/_shape-fields.stripe_price_heading') }}</h2>
    {{-- ⚠️ THE HONEST BOUNDARY, stated on the surface that would otherwise imply otherwise. This panel cannot
         create a Stripe Price: StripeSafetyGuard refuses a live key outside production, by design. So the id is
         pasted in, and until a real one is present the plan stays a draft. --}}
    <p style="color: var(--ad-faint); font-size: 12.5px; margin: 0 0 14px;">
        {!! __('admin/plans/_shape-fields.stripe_price_intro') !!}
    </p>
    <label class="ad-label" for="stripe_price_id">{{ __('admin/plans/_shape-fields.stripe_price_id_label') }}</label>
    <input class="ad-input" type="text" id="stripe_price_id" name="stripe_price_id"
           value="{{ old('stripe_price_id', $plan?->stripe_price_id) }}" placeholder="price_...">

    @if ($plan !== null && ($blocked = PlanIntegrity::blockedFromActivation($plan)) !== null)
        <div class="ad-flash ad-flash--error" style="margin-top: 14px;">
            <strong>{{ __('admin/plans/_shape-fields.draft_not_offered') }}</strong> {{ $blocked }}
        </div>
    @endif
</div>

<script>
    // Progressive enhancement only — see the note at the top of this partial.
    (function () {
        var select = document.querySelector('[data-shape-select]');
        if (!select) return;
        var groups = document.querySelectorAll('[data-shape-group]');
        function sync() {
            groups.forEach(function (group) {
                group.hidden = group.getAttribute('data-shape-group') !== select.value;
            });
        }
        select.addEventListener('change', sync);
        sync();
    })();
</script>
