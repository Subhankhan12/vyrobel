{{-- SUPER ADMIN · BUILD 1 · GATE 2 — the customer list: every org, real data, via the audited read. #28 — only
     operational facts (name/status/plan/counts), never end-guest PII. --}}
<x-admin.shell active="customers" title="Customers" subtitle="Every organization on the platform.">

    <form method="GET" action="{{ route('admin.customers.index') }}" class="ad-filters">
        <label class="vy-sr-only" for="vy-lbl-index-search">Search name or slug…</label>
        <input id="vy-lbl-index-search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search name or slug…">
        <select name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach (['active' => 'Active', 'trialing' => 'On trial', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled'] as $key => $label)
                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
            @endforeach
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
                                <div style="color: var(--ad-faint); font-size: 12px;">{{ $row['slug'] }}</div>
                            </td>
                            <td><span class="ad-pill ad-pill--{{ $row['status'] }}">{{ ucfirst($row['status']) }}</span></td>
                            <td>{{ $row['plan'] ?? '—' }}</td>
                            <td class="ad-num">{{ number_format($row['properties']) }}</td>
                            <td class="ad-num">{{ number_format($row['members']) }}</td>
                            <td style="color: var(--ad-muted);">{{ $row['created_at']?->format('j M Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pager (audit fix P2-1) — control-room styled; the search/status filters are carried in the links. --}}
            @if ($rows->hasPages())
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-top: 1px solid var(--ad-border);">
                    <span style="color: var(--ad-faint); font-size: 12.5px;">
                        Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ number_format($rows->total()) }}
                    </span>
                    <span style="display: flex; gap: 8px;">
                        @if ($rows->onFirstPage())
                            <span class="ad-btn ad-btn--ghost ad-btn--sm" style="opacity: .4; cursor: default;">Previous</span>
                        @else
                            <a href="{{ $rows->previousPageUrl() }}" class="ad-btn ad-btn--ghost ad-btn--sm">Previous</a>
                        @endif
                        @if ($rows->hasMorePages())
                            <a href="{{ $rows->nextPageUrl() }}" class="ad-btn ad-btn--ghost ad-btn--sm">Next</a>
                        @else
                            <span class="ad-btn ad-btn--ghost ad-btn--sm" style="opacity: .4; cursor: default;">Next</span>
                        @endif
                    </span>
                </div>
            @endif
        @else
            <div class="ad-empty" style="padding: 30px 14px;">No customers match — {{ ($filters['search'] !== '' || $filters['status'] !== '') ? 'try clearing the filters.' : 'no organizations on the platform yet.' }}</div>
        @endif
    </div>
</x-admin.shell>
