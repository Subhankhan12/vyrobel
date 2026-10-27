{{-- ⚠️ ADMIN 01 · PHASE 4 — THE SIGNUP FUNNEL BY SOURCE, from facts the database already records. Stages it cannot
     measure are NAMED below, not dropped. --}}
@php
    $stages = \App\Support\Platform\PlatformFunnel::stages();
    // ADMIN 04 — from the ONE list of sources, so a new one (the console's) can never drop out of the columns.
    $cols = ['total' => __('admin/funnel.col_all')] + array_replace(\App\Support\Platform\TenantLifecycle::sourceLabels(), ['console' => __('admin/funnel.col_console')]) + ['unknown' => __('admin/funnel.col_unknown')];
    $value = fn (string $col, string $stage): int => $col === 'total' ? $f['total'][$stage] : $f['sources'][$col][$stage];
@endphp
<x-admin.shell active="funnel" :title="__('admin/funnel.title')" :subtitle="__('admin/funnel.subtitle')">

    <div class="ad-filters" style="justify-content: space-between;">
        <span class="ad-stat-sub" style="margin: 0;">{{ $f['include_internal'] ? __('admin/funnel.scope_including_internal') : __('admin/funnel.scope_customers_only') }}</span>
        <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.funnel', $f['include_internal'] ? [] : ['internal' => 'include']) }}">{{ $f['include_internal'] ? __('admin/funnel.toggle_customers_only') : __('admin/funnel.toggle_include_internal') }}</a>
    </div>

    @if ($f['total']['signed_up'] === 0)
        <div class="ad-card" style="margin-bottom: 18px;">
            {!! __('admin/funnel.no_customers_yet') !!}
        </div>
    @endif

    <div class="ad-card" style="overflow-x: auto; padding: 8px 10px;">
        <table class="ad-table">
            <thead><tr><th>{{ __('admin/funnel.th_stage') }}</th>@foreach ($cols as $label)<th class="ad-num">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($stages as $key => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        @foreach ($cols as $col => $_)
                            @php $n = $value($col, $key); $top = $value($col, 'signed_up'); @endphp
                            <td class="ad-num">{{ number_format($n) }}@if ($key !== 'signed_up' && $top > 0)<span style="color: var(--ad-faint); font-size: 11.5px;"> · {{ (int) round($n * 100 / $top) }}%</span>@endif</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="ad-grid-2" style="margin-top: 18px;">
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/funnel.unmeasured_heading') }}</h2>
            @foreach (\App\Support\Platform\PlatformFunnel::unmeasured() as $unmeasured)
                <div class="ad-row" style="align-items: flex-start;"><span class="ad-row-k" style="color: var(--ad-ink);"><strong>{{ $unmeasured['stage'] }}</strong><div style="font-size: 12px; color: var(--ad-muted); font-weight: 400;">{{ $unmeasured['why'] }}</div></span><span class="ad-hpill ad-hpill--grey">{{ __('admin/funnel.unmeasured_pill') }}</span></div>
            @endforeach
        </div>
        <div class="ad-card">
            <h2 class="ad-panel-title">{{ __('admin/funnel.not_a_stage_heading') }}</h2>
            <p class="ad-stat-sub" style="margin: 0 0 8px; line-height: 1.6;">{{ __('admin/funnel.not_a_stage_intro') }}</p>
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/funnel.without_property') }}</span><span class="ad-row-v">{{ number_format($f['without_property']) }}</span></div>
            <p class="ad-reason-hint" style="margin-top: 10px;">{{ __('admin/funnel.unknown_hint') }}</p>
        </div>
    </div>
</x-admin.shell>
