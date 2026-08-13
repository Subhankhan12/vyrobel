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
    <h2 class="ad-panel-title">Pricing shape</h2>
    <p style="color: var(--ad-faint); font-size: 12.5px; margin: 0 0 14px;">
        A <strong>flat</strong> plan charges one price however many units the account runs. A
        <strong>per-unit</strong> plan charges a rate for every active unit, re-counted each period.
    </p>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div>
            <label class="ad-label" for="pricing_type">Shape</label>
            <select class="ad-input" id="pricing_type" name="pricing_type" data-shape-select>
                <option value="{{ Plan::PRICING_FLAT }}" @selected($shape === Plan::PRICING_FLAT)>Flat — one price per period</option>
                <option value="{{ Plan::PRICING_PER_UNIT }}" @selected($shape === Plan::PRICING_PER_UNIT)>Per unit — rate × active units</option>
            </select>
        </div>
        <div>
            <label class="ad-label" for="interval">Billing interval</label>
            <select class="ad-input" id="interval" name="interval">
                <option value="monthly" @selected(old('interval', $plan?->interval) === 'monthly')>Monthly</option>
                <option value="yearly" @selected(old('interval', $plan?->interval) === 'yearly')>Yearly</option>
            </select>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
        <div data-shape-group="{{ Plan::PRICING_FLAT }}">
            <label class="ad-label" for="price">Price</label>
            <input class="ad-input" type="number" step="0.01" min="0" id="price" name="price" value="{{ $priceValue }}">
            <div style="color: var(--ad-faint); font-size: 11.5px; margin-top: 5px;">The final amount — VAT-inclusive.</div>
        </div>
        <div data-shape-group="{{ Plan::PRICING_PER_UNIT }}">
            <label class="ad-label" for="unit_rate">Rate per unit</label>
            <input class="ad-input" type="number" step="0.01" min="0" id="unit_rate" name="unit_rate" value="{{ $rateValue }}">
            {{-- ⚠️ Pre-tax, and the reason is worth stating on the form: under Stripe Tax the customer's own
                 rate applies, and an EU business under reverse charge pays €0 (billing pass 2b). --}}
            <div style="color: var(--ad-faint); font-size: 11.5px; margin-top: 5px;">Pre-tax — Stripe adds the customer's own tax.</div>
        </div>
        <div>
            <label class="ad-label" for="currency">Currency</label>
            <input class="ad-input" type="text" maxlength="3" id="currency" name="currency"
                   value="{{ old('currency', $plan?->currency ?? config('billing.default_currency', 'EUR')) }}" required>
        </div>
        <div>
            <label class="ad-label" for="vat_pct">VAT % <span style="color: var(--ad-faint); font-weight: 400;">· optional</span></label>
            <input class="ad-input" type="number" min="0" max="100" id="vat_pct" name="vat_pct" value="{{ old('vat_pct', $plan?->vat_pct) }}">
        </div>
    </div>
</div>

<div class="ad-card" style="margin-top: 18px;">
    <h2 class="ad-panel-title">Stripe Price</h2>
    {{-- ⚠️ THE HONEST BOUNDARY, stated on the surface that would otherwise imply otherwise. This panel cannot
         create a Stripe Price: StripeSafetyGuard refuses a live key outside production, by design. So the id is
         pasted in, and until a real one is present the plan stays a draft. --}}
    <p style="color: var(--ad-faint); font-size: 12.5px; margin: 0 0 14px;">
        Create the Price in the Stripe dashboard, then paste its id here — this panel cannot create it.
        A per-unit Price must be <strong>per-unit billing, tax behaviour exclusive</strong>; a flat one
        <strong>licensed, inclusive</strong>. See <code>docs/BILLING-STRIPE-PRICES.md</code>.
    </p>
    <label class="ad-label" for="stripe_price_id">Stripe Price id</label>
    <input class="ad-input" type="text" id="stripe_price_id" name="stripe_price_id"
           value="{{ old('stripe_price_id', $plan?->stripe_price_id) }}" placeholder="price_...">

    @if ($plan !== null && ($blocked = PlanIntegrity::blockedFromActivation($plan)) !== null)
        <div class="ad-flash" style="margin-top: 14px; background: rgba(245,158,158,.1); border-color: rgba(245,158,158,.34); color: #FBD5D5;">
            <strong>Draft — not offered to customers.</strong> {{ $blocked }}
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
