{{-- SUPER ADMIN · BUILD 1 · GATE 3 — edit a plan: name, price (integer money #17), active, and feature
     entitlements. The feature toggles are the REAL gate (planAllows reads plan_features). --}}
@php
    use App\Support\Money\MoneyFormatter;
    $featureLabels = ['analytics' => 'Insights & reports', 'channels' => 'Channel manager', 'team' => 'Team members'];
    $current = $plan->features->keyBy('key');
@endphp
<x-admin.shell active="plans" :title="'Edit · '.$plan->name">
    <a href="{{ route('admin.plans.index') }}" class="ad-back">← All plans</a>
    <div class="ad-eyebrow">Plan</div>
    <h1 class="ad-h1">{{ $plan->name }}</h1>
    <p class="ad-lead">{{ $plan->code }} · {{ ucfirst($plan->interval) }}</p>

    @if ($errors->any())
        <div class="ad-flash" style="background: rgba(245,158,158,.1); border-color: rgba(245,158,158,.34); color: #FBD5D5;">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.plans.update', $plan->id) }}" style="max-width: 640px;" data-guard-submit>
        @csrf
        @method('PUT')

        <div class="ad-card">
            <h2 class="ad-panel-title">Details</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label class="ad-label" for="name">Plan name</label>
                    <input class="ad-input" type="text" id="name" name="name" value="{{ old('name', $plan->name) }}" required>
                </div>
                <div>
                    <label class="ad-label" for="price">Price ({{ $plan->currency }}) · per {{ $plan->interval === 'yearly' ? 'year' : 'month' }}</label>
                    <input class="ad-input" type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price', number_format($plan->price_minor / 100, 2, '.', '')) }}" required>
                </div>
            </div>
            <label style="display: flex; align-items: center; gap: 9px; margin-top: 16px; font-size: 13.5px; color: var(--ad-muted); cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>
                Plan is active (offered to customers)
            </label>
        </div>

        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">Features &amp; gating</h2>
            <p class="ad-stat-sub" style="margin: -8px 0 16px;">Toggling a feature changes what customers on this plan can access — immediately (via planAllows).</p>
            @foreach ($featureLabels as $key => $label)
                @php $feat = $current->get($key); @endphp
                <div class="ad-row" style="align-items: center;">
                    <span class="ad-row-k" style="flex: 1;">
                        <strong style="color: var(--ad-ink); font-weight: 600;">{{ $label }}</strong>
                        <span style="color: var(--ad-faint); font-size: 12px;"> · {{ $key }}</span>
                    </span>
                    @if ($key === 'team')
                        <span style="display: inline-flex; align-items: center; gap: 8px; margin-right: 16px;">
                            <span style="font-size: 12px; color: var(--ad-faint);">limit</span>
                            <input class="ad-input" style="width: 90px; padding: 7px 10px;" type="number" min="0" name="features[{{ $key }}][limit]"
                                   value="{{ old('features.'.$key.'.limit', $feat?->limit) }}" placeholder="∞">
                        </span>
                    @endif
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="features[{{ $key }}][enabled]" value="1" @checked(old('features.'.$key.'.enabled', $feat?->enabled ?? false))>
                        <span style="font-size: 13px;">Enabled</span>
                    </label>
                </div>
            @endforeach
        </div>

        <div style="display: flex; gap: 10px; margin-top: 18px;">
            <button type="submit" class="ad-btn">Save plan</button>
            <a href="{{ route('admin.plans.index') }}" class="ad-btn ad-btn--ghost">Cancel</a>
        </div>
    </form>
</x-admin.shell>
