{{-- ADMIN 01 · PHASE 2 — the TRIAGE QUEUE: organisations worth a look, COUNTED per bucket, each with the exact
     definition it counts by and a link to the same list in the directory (one definition, two screens). ⚠️ It
     surfaces; it never acts. Customers only unless internal organisations are asked for; archived never. --}}
<x-admin.shell active="triage" :title="__('admin/customers/triage.title')" :subtitle="__('admin/customers/triage.subtitle')">

    <div class="ad-filters" style="justify-content: space-between;">
        <span class="ad-stat-sub" style="margin: 0;">
            {{ $includeInternal ? __('admin/customers/triage.scope_including_internal') : __('admin/customers/triage.scope_customers_only') }}
        </span>
        <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.customers.triage', $includeInternal ? [] : ['internal' => 'include']) }}">
            {{ $includeInternal ? __('admin/customers/triage.toggle_customers_only') : __('admin/customers/triage.toggle_include_internal') }}
        </a>
    </div>

    <div style="display: grid; gap: 14px;">
        @foreach ($buckets as $key => $bucket)
            <div class="ad-card ad-bucket">
                <div>
                    <h2 class="ad-panel-title" style="margin: 0 0 6px;">{{ $bucket['label'] }}</h2>
                    <p class="ad-stat-sub" style="margin: 0; line-height: 1.6;">{{ $bucket['definition'] }}</p>
                </div>
                <div style="text-align: right;">
                    <div class="ad-bucket-count">{{ number_format($bucket['count']) }}</div>
                    @if ($bucket['count'] > 0)
                        <a class="ad-cell-link" style="font-size: 12.5px;"
                           href="{{ route('admin.customers.index', array_filter(['bucket' => $key, 'kind' => $includeInternal ? null : 'customers'])) }}">{{ __('admin/customers/triage.view_in_directory') }}</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($customers === 0 && ! $includeInternal)
        <p class="ad-stat-sub" style="margin-top: 14px;">{!! __('admin/customers/triage.no_customers_yet') !!}</p>
    @endif
</x-admin.shell>
