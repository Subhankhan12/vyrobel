{{-- SUPER ADMIN · BUILD 1 · GATE 2 — the customer list: every org, real data, via the audited read. #28 — only
     operational facts (name/status/plan/counts), never end-guest PII. --}}
<x-admin.shell active="customers" title="Customers">
    <div class="ad-eyebrow">Platform console</div>
    <h1 class="ad-h1">Customers</h1>
    <p class="ad-lead">Every organization on the platform.</p>

    <form method="GET" action="{{ route('admin.customers.index') }}" class="ad-filters">
        <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search name or slug…">
        <select name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach (['active' => 'Active', 'trialing' => 'On trial', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled'] as $key => $label)
                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="ad-btn ad-btn--ghost ad-btn--sm">Filter</button>
    </form>

    <div class="ad-card" style="padding: 8px 10px;">
        @if (count($rows) > 0)
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
        @else
            <div class="ad-empty" style="padding: 30px 14px;">No customers match — {{ ($filters['search'] !== '' || $filters['status'] !== '') ? 'try clearing the filters.' : 'no organizations on the platform yet.' }}</div>
        @endif
    </div>
</x-admin.shell>
