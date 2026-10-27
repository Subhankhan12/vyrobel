{{-- ADMIN 01 · PHASE 2 — the TENANT DIRECTORY: every organisation, real data, via the audited read. ⚠️ Account
     metadata only (name, plan, status, signup source, verified, created, last sign-in, counts) — never a guest.
     Archived organisations are hidden unless the status filter asks for them. --}}
@php
    $sortLink = function (string $key, string $label) use ($filters): string {
        $active = ($filters['sort'] ?? '') === $key || (($filters['sort'] ?? '') === '' && $key === 'created');
        $defaultDir = $key === 'name' ? 'asc' : 'desc';
        $current = ($filters['dir'] ?? '') !== '' ? $filters['dir'] : $defaultDir;
        $next = $active ? ($current === 'asc' ? 'desc' : 'asc') : $defaultDir;
        $url = route('admin.customers.index', array_filter(array_merge($filters, ['sort' => $key, 'dir' => $next, 'page' => null])));
        $arrow = $active ? ($current === 'asc' ? ' ↑' : ' ↓') : '';

        return '<a class="ad-sort" href="'.e($url).'">'.e($label).$arrow.'</a>';
    };
    $bucketCode = (string) ($filters['bucket'] ?? '');
    $bucket = isset(\App\Support\Platform\PlatformCustomers::BUCKETS[$bucketCode])
        ? [\App\Support\Platform\PlatformCustomers::bucketLabel($bucketCode), \App\Support\Platform\PlatformCustomers::bucketDefinition($bucketCode)]
        : null;
    $anyFilter = collect($filters)->except(['sort', 'dir'])->filter(fn ($v) => $v !== '')->isNotEmpty();
@endphp
<x-admin.shell active="customers" :title="__('admin/customers/index.title')" :subtitle="__('admin/customers/index.subtitle')">

    {{-- ADMIN 04 — create an organisation (a tenant manager only; the founder gets a set-password link). --}}
    @if (auth('admin')->user()?->may('tenants.manage'))
        <div style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
            <a href="{{ route('admin.customers.create') }}" class="ad-btn ad-btn--sm">{{ __('admin/customers/index.create_organisation') }}</a>
        </div>
    @endif

    @if ($bucket)
        <div class="ad-flash" style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <span>{!! __('admin/customers/index.triage_banner', ['name' => e($bucket[0]), 'description' => e($bucket[1])]) !!}</span>
            <a href="{{ route('admin.customers.index') }}" class="ad-cell-link">{{ __('common.clear') }}</a>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.customers.index') }}" class="ad-filters">
        <label class="vy-sr-only" for="vy-lbl-index-search">{{ __('admin/customers/index.search_placeholder') }}</label>
        <input id="vy-lbl-index-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('admin/customers/index.search_placeholder') }}">
        <select aria-label="{{ __('admin/customers/index.status_label') }}" name="status" onchange="this.form.submit()">
            <option value="">{{ __('admin/customers/index.all_statuses') }}</option>
            @foreach (\App\Support\Platform\PlatformCustomers::statusLabels() as $key => $label)
                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select aria-label="{{ __('admin/customers/index.kind_label') }}" name="kind" onchange="this.form.submit()">
            <option value="">{{ __('admin/customers/index.kind_all') }}</option>
            <option value="customers" @selected($filters['kind'] === 'customers')>{{ __('admin/customers/index.kind_customers') }}</option>
            <option value="internal" @selected($filters['kind'] === 'internal')>{{ __('admin/customers/index.kind_internal') }}</option>
        </select>
        <select aria-label="{{ __('admin/customers/index.source_label') }}" name="source" onchange="this.form.submit()">
            <option value="">{{ __('admin/customers/index.source_any') }}</option>
            @foreach (\App\Support\Platform\TenantLifecycle::sourceLabels() as $key => $label)
                <option value="{{ $key }}" @selected($filters['source'] === $key)>{{ $label }}</option>
            @endforeach
            <option value="unknown" @selected($filters['source'] === 'unknown')>{{ __('admin/customers/index.source_unknown') }}</option>
        </select>
        <select aria-label="{{ __('admin/customers/index.verified_label') }}" name="verified" onchange="this.form.submit()">
            <option value="">{{ __('admin/customers/index.verified_any') }}</option>
            <option value="yes" @selected($filters['verified'] === 'yes')>{{ __('admin/customers/index.verified_yes') }}</option>
            <option value="no" @selected($filters['verified'] === 'no')>{{ __('admin/customers/index.verified_no') }}</option>
        </select>
        @foreach (['bucket', 'sort', 'dir'] as $keep)
            @if ($filters[$keep] !== '')
                <input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}">
            @endif
        @endforeach
        <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('common.filter') }}</button>
        <a href="{{ route('admin.customers.triage') }}" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('admin/customers/index.triage_queue') }}</a>
    </form>

    <div class="ad-card" style="padding: 8px 10px; overflow-x: auto;">
        @if ($rows->total() > 0)
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>{!! $sortLink('name', __('admin/customers/index.col_organisation')) !!}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('admin/customers/index.col_plan') }}</th>
                        <th>{{ __('admin/customers/index.col_signed_up_with') }}</th>
                        <th>{{ __('admin/customers/index.col_verified') }}</th>
                        <th>{!! $sortLink('created', __('admin/customers/index.col_created')) !!}</th>
                        <th>{!! $sortLink('last_login', __('admin/customers/index.col_last_sign_in')) !!}</th>
                        <th class="ad-num">{!! $sortLink('properties', __('admin/customers/index.col_properties')) !!}</th>
                        <th class="ad-num">{!! $sortLink('users', __('admin/customers/index.col_users')) !!}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <a class="ad-cell-link" href="{{ route('admin.customers.show', $row['id']) }}">{{ $row['name'] }}</a>
                                @if ($row['internal'])
                                    <span class="ad-pill ad-pill--cancelled" style="margin-left: 6px; font-size: 10.5px;">{{ __('admin/customers/index.internal_pill') }}</span>
                                @endif
                                <div style="color: var(--ad-faint); font-size: 12px;">{{ $row['slug'] }}</div>
                            </td>
                            <td>
                                @if ($row['archived'])
                                    <span class="ad-pill ad-pill--cancelled">{{ __('admin/customers/index.archived_pill') }}</span>
                                @else
                                    <span class="ad-pill ad-pill--{{ $row['status'] }}">{{ \App\Support\Platform\PlatformCustomers::statusLabel($row['status']) }}</span>
                                @endif
                            </td>
                            <td>{{ $row['plan'] ?? '—' }}</td>
                            <td style="{{ ! $row['source_known'] ? 'color: var(--ad-faint);' : '' }}">{{ $row['source'] }}</td>
                            <td>{{ $row['verified'] ? __('common.yes') : __('common.no') }}</td>
                            <td style="color: var(--ad-muted);"><x-admin.time :at="$row['created_at']" date /></td>
                            <td style="color: var(--ad-muted);"><x-admin.time :at="$row['last_login_at']" :empty="__('admin/customers/index.never_signed_in')" /></td>
                            <td class="ad-num">{{ number_format($row['properties']) }}</td>
                            <td class="ad-num">{{ number_format($row['members']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @include('admin.partials.pager', ['rows' => $rows])
        @else
            <div class="ad-empty" style="padding: 30px 14px;">{{ $anyFilter ? __('admin/customers/index.empty_filtered') : __('admin/customers/index.empty_none') }}</div>
        @endif
    </div>

    <p class="ad-stat-sub" style="margin-top: 12px; line-height: 1.6;">
        {!! __('admin/customers/index.help_verified') !!}
        {!! __('admin/customers/index.help_last_sign_in') !!}
        {!! __('admin/customers/index.help_signed_up_with') !!}
    </p>
</x-admin.shell>
