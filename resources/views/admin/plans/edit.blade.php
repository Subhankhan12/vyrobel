{{-- SUPER ADMIN · BUILD 1 · GATE 3 — edit a plan: name, price (integer money #17), active, and feature
     entitlements. The feature toggles are the REAL gate (planAllows reads plan_features). --}}
@php
    use App\Support\Money\MoneyFormatter;
    // labels by feature CODE (the keys stay codes; they are the form's field names)
    $featureLabels = [
        'analytics' => __('admin/plans/edit.feature_analytics'),
        'channels' => __('admin/plans/edit.feature_channels'),
        'team' => __('admin/plans/edit.feature_team'),
    ];
    $intervalLabel = match ($plan->interval) {
        'monthly' => __('admin/plans/edit.interval_monthly'),
        'yearly' => __('admin/plans/edit.interval_yearly'),
        default => ucfirst((string) $plan->interval),
    };
    $current = $plan->features->keyBy('key');
@endphp
<x-admin.shell active="plans" :title="$plan->name"
    :subtitle="$plan->code.' · '.$intervalLabel"
    :back="route('admin.plans.index')" :back-label="__('admin/plans/edit.back_label')">

    @if (session('admin_error'))
        <div class="ad-flash ad-flash--error">{{ session('admin_error') }}</div>
    @endif


    <form method="POST" action="{{ route('admin.plans.update', $plan->id) }}" style="max-width: 640px;" data-guard-submit>
        @csrf
        @method('PUT')

        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/plans/edit.details_heading') }}</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label class="ad-label" for="name">{{ __('admin/plans/edit.plan_name_label') }}</label>
                    <input class="ad-input" type="text" id="name" name="name" value="{{ old('name', $plan->name) }}" required>
                </div>
                <div>
                    <label class="ad-label" for="code">{{ __('admin/plans/edit.code_label') }}</label>
                    <input class="ad-input" type="text" id="code" name="code" value="{{ old('code', $plan->code) }}" required>
                </div>
            </div>
            <label style="display: flex; align-items: center; gap: 9px; margin-top: 16px; font-size: 13.5px; color: var(--ad-muted); cursor: pointer;">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>
                {{ __('admin/plans/edit.is_active_label') }}
            </label>
            {{-- ⚠️ Editing a live plan is SAFE, and saying so on the form is the point: existing subscribers
                 bill from the terms CAPTURED on their subscription, so nothing changed here reaches them. --}}
            <p style="color: var(--ad-faint); font-size: 12px; margin: 12px 0 0;">
                {!! __('admin/plans/edit.changes_apply_note') !!}
            </p>
        </div>

        @include('admin.plans._shape-fields', ['plan' => $plan])

        <div class="ad-card" style="margin-top: 18px;">
            <h2 class="ad-panel-title">{{ __('admin/plans/edit.features_heading') }}</h2>
            <p class="ad-stat-sub" style="margin: -8px 0 16px;">{{ __('admin/plans/edit.features_intro') }}</p>
            @foreach ($featureLabels as $key => $label)
                @php $feat = $current->get($key); @endphp
                <div class="ad-row" style="align-items: center;">
                    <span class="ad-row-k" style="flex: 1;">
                        <strong style="color: var(--ad-ink); font-weight: 600;">{{ $label }}</strong>
                        <span style="color: var(--ad-faint); font-size: 12px;"> · {{ $key }}</span>
                    </span>
                    @if ($key === 'team')
                        <span style="display: inline-flex; align-items: center; gap: 8px; margin-right: 16px;">
                            <span style="font-size: 12px; color: var(--ad-faint);">{{ __('admin/plans/edit.team_limit_label') }}</span>
                            <input class="ad-input" style="width: 90px; padding: 7px 10px;" type="number" min="0" name="features[{{ $key }}][limit]"
                                   value="{{ old('features.'.$key.'.limit', $feat?->limit) }}" placeholder="∞">
                        </span>
                    @endif
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="features[{{ $key }}][enabled]" value="1" @checked(old('features.'.$key.'.enabled', $feat?->enabled ?? false))>
                        <span style="font-size: 13px;">{{ __('admin/plans/edit.enabled_label') }}</span>
                    </label>
                </div>
            @endforeach
        </div>

        @include('admin.partials.reason', ['placeholder' => __('admin/plans/edit.reason_placeholder')])

        <div style="display: flex; gap: 10px; margin-top: 18px;">
            <button type="submit" class="ad-btn">{{ __('admin/plans/edit.save_plan_button') }}</button>
            <a href="{{ route('admin.plans.index') }}" class="ad-btn ad-btn--ghost">{{ __('common.cancel') }}</a>
        </div>
    </form>
</x-admin.shell>
