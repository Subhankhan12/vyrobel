{{-- SUPER ADMIN · BUILD 1 · GATE 3 — the plan catalog (DB-backed, authoritative). Editing a plan's features
     drives real feature-gating (Organization::planAllows reads plan_features). --}}
@php use App\Support\Money\MoneyFormatter; @endphp
<x-admin.shell active="plans" :title="__('admin/plans/index.title')"
    :subtitle="__('admin/plans/index.subtitle')">

    @if (session('admin_error'))
        <div class="ad-flash ad-flash--error">{{ session('admin_error') }}</div>
    @endif

    @if (auth('admin')->user()?->may('billing.manage'))
        <div style="margin: 0 0 16px;"><a class="ad-btn" href="{{ route('admin.plans.create') }}">{{ __('admin/plans/index.new_plan_button') }}</a></div>
    @endif

    <div class="ad-card" style="padding: 8px 10px;">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>{{ __('admin/plans/index.col_plan') }}</th>
                    <th>{{ __('admin/plans/index.col_price') }}</th>
                    <th>{{ __('admin/plans/index.col_features') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                    @php
                        $intervalLabel = match ($plan->interval) {
                            'monthly' => __('admin/plans/index.interval_monthly'),
                            'yearly' => __('admin/plans/index.interval_yearly'),
                            default => ucfirst((string) $plan->interval),
                        };
                    @endphp
                    <tr>
                        <td>
                            <a class="ad-cell-link" href="{{ route('admin.plans.edit', $plan->id) }}">{{ $plan->name }}</a>
                            <div style="color: var(--ad-faint); font-size: 12px;">{{ $plan->code }} · {{ $intervalLabel }}</div>
                        </td>
                        <td>{{ MoneyFormatter::display($plan->price_minor, $plan->currency) }} <span style="color: var(--ad-faint);">/ {{ $plan->interval === 'yearly' ? __('admin/plans/index.per_year_short') : __('admin/plans/index.per_month_short') }}</span></td>
                        <td>
                            @foreach ($plan->features as $f)
                                <span class="ad-pill {{ $f->enabled ? 'ad-pill--active' : 'ad-pill--cancelled' }}" style="margin: 2px 3px 2px 0;">
                                    {{ $f->key }}@if ($f->enabled && $f->limit !== null) · {{ $f->limit }}@elseif (! $f->enabled) · {{ __('admin/plans/index.feature_off') }} @endif
                                </span>
                            @endforeach
                        </td>
                        <td>
                            @if ($plan->is_active)
                                <span class="ad-pill ad-pill--active">{{ __('admin/plans/index.active_pill') }}</span>
                            @else
                                <span class="ad-pill ad-pill--cancelled">{{ __('admin/plans/index.inactive_pill') }}</span>
                            @endif
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            {{-- ADMIN 01 · Phase 1 — plan controls only for a role that may change billing; each toggle carries a reason. --}}
                            @if (auth('admin')->user()?->may('billing.manage'))
                                <form method="POST" action="{{ route('admin.plans.toggle', $plan->id) }}" class="ad-inline-form" style="display: inline-flex;" data-guard-submit>
                                    @csrf
                                    <input class="ad-input" name="reason" required minlength="5" maxlength="500" placeholder="{{ __('admin/plans/index.reason_placeholder') }}" aria-label="{{ __('admin/plans/index.reason_placeholder') }}" style="width: 150px; flex: 0 0 auto;">
                                    <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">{{ $plan->is_active ? __('admin/plans/index.deactivate_button') : __('admin/plans/index.activate_button') }}</button>
                                </form>
                                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('common.edit') }}</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Audit fix P2-3 — honest about the catalog scope: the tiers are fixed + editable; new tiers are a follow-up. --}}
    <div class="ad-card" style="margin-top: 18px;">
        <h2 class="ad-panel-title">{{ __('admin/plans/index.about_heading') }}</h2>
        <p class="ad-stat-sub" style="line-height: 1.65;">
            {{ __('admin/plans/index.about_body') }}
        </p>
    </div>
</x-admin.shell>
