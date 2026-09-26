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
    $bucket = \App\Support\Platform\PlatformCustomers::BUCKETS[$filters['bucket'] ?? ''] ?? null;
    $anyFilter = collect($filters)->except(['sort', 'dir'])->filter(fn ($v) => $v !== '')->isNotEmpty();
@endphp
<x-admin.shell active="customers" title="Organisations" subtitle="Every organisation on the platform — account metadata only, never a guest.">

    {{-- ADMIN 04 — create an organisation (a tenant manager only; the founder gets a set-password link). --}}
    @if (auth('admin')->user()?->may('tenants.manage'))
        <div style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
            <a href="{{ route('admin.customers.create') }}" class="ad-btn ad-btn--sm">Create an organisation</a>
        </div>
    @endif

    @if ($bucket)
        <div class="ad-flash" style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <span><strong>Triage · {{ $bucket[0] }}</strong> — {{ $bucket[1] }}</span>
            <a href="{{ route('admin.customers.index') }}" class="ad-cell-link">Clear</a>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.customers.index') }}" class="ad-filters">
        <label class="vy-sr-only" for="vy-lbl-index-search">Search name or slug…</label>
        <input id="vy-lbl-index-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search name or slug…">
        <select aria-label="Status" name="status" onchange="this.form.submit()">
            <option value="">All statuses (not archived)</option>
            @foreach (\App\Support\Platform\PlatformCustomers::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select aria-label="Kind" name="kind" onchange="this.form.submit()">
            <option value="">Customers &amp; internal</option>
            <option value="customers" @selected($filters['kind'] === 'customers')>Customers only</option>
            <option value="internal" @selected($filters['kind'] === 'internal')>Internal only</option>
        </select>
        <select aria-label="Signup source" name="source" onchange="this.form.submit()">
            <option value="">Any signup source</option>
            @foreach (\App\Support\Platform\TenantLifecycle::SIGNUP_SOURCES as $key => $label)
                <option value="{{ $key }}" @selected($filters['source'] === $key)>{{ $label }}</option>
            @endforeach
            <option value="unknown" @selected($filters['source'] === 'unknown')>Unknown (before recording began)</option>
        </select>
        <select aria-label="Verified" name="verified" onchange="this.form.submit()">
            <option value="">Verified or not</option>
            <option value="yes" @selected($filters['verified'] === 'yes')>Verified</option>
            <option value="no" @selected($filters['verified'] === 'no')>Not verified</option>
        </select>
        @foreach (['bucket', 'sort', 'dir'] as $keep)
            @if ($filters[$keep] !== '')
                <input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}">
            @endif
        @endforeach
        <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">Filter</button>
        <a href="{{ route('admin.customers.triage') }}" class="ad-btn ad-btn--ghost ad-btn--sm">Triage queue</a>
    </form>

    <div class="ad-card" style="padding: 8px 10px; overflow-x: auto;">
        @if ($rows->total() > 0)
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>{!! $sortLink('name', 'Organisation') !!}</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th>Signed up with</th>
                        <th>Verified</th>
                        <th>{!! $sortLink('created', 'Created') !!}</th>
                        <th>{!! $sortLink('last_login', 'Last sign-in') !!}</th>
                        <th class="ad-num">{!! $sortLink('properties', 'Properties') !!}</th>
                        <th class="ad-num">{!! $sortLink('users', 'Users') !!}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>
                                <a class="ad-cell-link" href="{{ route('admin.customers.show', $row['id']) }}">{{ $row['name'] }}</a>
                                @if ($row['internal'])
                                    <span class="ad-pill ad-pill--cancelled" style="margin-left: 6px; font-size: 10.5px;">Internal</span>
                                @endif
                                <div style="color: var(--ad-faint); font-size: 12px;">{{ $row['slug'] }}</div>
                            </td>
                            <td>
                                @if ($row['archived'])
                                    <span class="ad-pill ad-pill--cancelled">Archived</span>
                                @else
                                    <span class="ad-pill ad-pill--{{ $row['status'] }}">{{ \App\Support\Platform\PlatformCustomers::STATUSES[$row['status']] ?? ucfirst($row['status']) }}</span>
                                @endif
                            </td>
                            <td>{{ $row['plan'] ?? '—' }}</td>
                            <td style="{{ $row['source'] === 'Unknown' ? 'color: var(--ad-faint);' : '' }}">{{ $row['source'] }}</td>
                            <td>{{ $row['verified'] ? 'Yes' : 'No' }}</td>
                            <td style="color: var(--ad-muted);"><x-admin.time :at="$row['created_at']" date /></td>
                            <td style="color: var(--ad-muted);"><x-admin.time :at="$row['last_login_at']" empty="Never" /></td>
                            <td class="ad-num">{{ number_format($row['properties']) }}</td>
                            <td class="ad-num">{{ number_format($row['members']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @include('admin.partials.pager', ['rows' => $rows])
        @else
            <div class="ad-empty" style="padding: 30px 14px;">No organisations match — {{ $anyFilter ? 'try clearing the filters.' : 'no organisations on the platform yet.' }}</div>
        @endif
    </div>

    <p class="ad-stat-sub" style="margin-top: 12px; line-height: 1.6;">
        <strong>Verified</strong>: at least one of its users has confirmed an email address.
        <strong>Last sign-in</strong>: the latest completed sign-in by any of its users — the sign-up session itself does not count.
        <strong>Signed up with</strong>: recorded only since this console began recording it; older organisations read “Unknown”, because nothing recorded it — not because they came through email.
    </p>
</x-admin.shell>
