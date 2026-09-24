{{-- SUPER ADMIN · BUILD 1 · GATE 2 — the customer list: every org, real data, via the audited read. #28 — only
     operational facts (name/status/plan/counts), never end-guest PII. --}}
<x-admin.shell active="customers" title="Customers" subtitle="Every organization on the platform.">

    <form method="GET" action="{{ route('admin.customers.index') }}" class="ad-filters">
        <label class="vy-sr-only" for="vy-lbl-index-search">Search name or slug…</label>
        <input id="vy-lbl-index-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search name or slug…">
        <select aria-label="Status" name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach (['active' => 'Active', 'trialing' => 'On trial', 'suspended' => 'Storefront closed', 'cancelled' => 'Cancelled'] as $key => $label)
                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
        {{-- ADMIN 01 · Phase 1 — customers vs Vyrobel's own internal organisations. --}}
        <select name="kind" onchange="this.form.submit()" aria-label="Kind">
            <option value="">Customers &amp; internal</option>
            <option value="customers" @selected(($filters['kind'] ?? '') === 'customers')>Customers only</option>
            <option value="internal" @selected(($filters['kind'] ?? '') === 'internal')>Internal only</option>
        </select>
        <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">Filter</button>
    </form>

    <div class="ad-card" style="padding: 8px 10px;">
        @if ($rows->total() > 0)
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th class="ad-num">Properties</th>
                        <th class="ad-num">Staff</th>
                        <th>Joined</th>
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
                            <td><span class="ad-pill ad-pill--{{ $row['status'] }}">{{ $row['status'] === 'suspended' ? 'Storefront closed' : ucfirst($row['status']) }}</span></td>
                            <td>{{ $row['plan'] ?? '—' }}</td>
                            <td class="ad-num">{{ number_format($row['properties']) }}</td>
                            <td class="ad-num">{{ number_format($row['members']) }}</td>
                            <td style="color: var(--ad-muted);">{{ $row['created_at']?->format('j M Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @include('admin.partials.pager', ['rows' => $rows])
        @else
            <div class="ad-empty" style="padding: 30px 14px;">No customers match — {{ ($filters['search'] !== '' || $filters['status'] !== '' || ($filters['kind'] ?? '') !== '') ? 'try clearing the filters.' : 'no organizations on the platform yet.' }}</div>
        @endif
    </div>
</x-admin.shell>
