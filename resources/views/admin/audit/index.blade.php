{{-- ADMIN 01 · PHASE 1 — the platform-admin AUDIT LOG. Read-only by construction: no edit/delete route exists,
     and the ledger refuses UPDATE/DELETE at the model and at the database connection. --}}
<x-admin.shell active="audit" title="Audit log"
    subtitle="Every console action — who, when, what, on what, and why. Rows are never edited or deleted.">

    <form method="GET" action="{{ route('admin.audit.index') }}" class="ad-filters">
        <select name="kind" aria-label="Kind">
            <option value="changes" @selected($filters['kind'] === 'changes')>Changes &amp; sign-ins</option>
            <option value="reads" @selected($filters['kind'] === 'reads')>Page views &amp; reads</option>
            <option value="all" @selected($filters['kind'] === 'all')>Everything</option>
        </select>
        <select name="admin" aria-label="Admin">
            <option value="">Any admin</option>
            @foreach ($admins as $a)
                <option value="{{ $a->id }}" @selected($filters['admin'] === $a->id)>{{ $a->name }}</option>
            @endforeach
        </select>
        <select name="action" aria-label="Action">
            <option value="">Any action</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
            @endforeach
        </select>
        <select name="tenant" aria-label="Organisation">
            <option value="">Any organisation</option>
            @foreach ($organizations as $org)
                <option value="{{ $org->id }}" @selected($filters['tenant'] === $org->id)>{{ $org->name }}{{ $org->is_internal ? ' (internal)' : '' }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] }}" aria-label="From">
        <input type="date" name="to" value="{{ $filters['to'] }}" aria-label="To">
        <button type="submit" class="ad-btn ad-btn--sm">Filter</button>
    </form>

    <div class="ad-card" style="padding: 8px 10px; overflow-x: auto;">
        @if ($rows->isEmpty())
            <div class="ad-empty">Nothing matches these filters.</div>
        @else
            <table class="ad-table">
                <thead>
                    <tr><th>When (UTC)</th><th>Admin</th><th>Action</th><th>Target</th><th>Reason</th><th>IP</th></tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td style="white-space: nowrap;">{{ $row->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $row->platform_admin_id ? ($adminNames[$row->platform_admin_id] ?? 'Removed admin') : 'System / CLI' }}</td>
                            <td><code style="font-size: 12px;">{{ $row->action }}</code></td>
                            <td>
                                @if ($row->target_type === 'organization')
                                    {{ $orgNames[$row->target_id] ?? $row->target_id }}
                                @elseif ($row->target_type === 'platform_admin')
                                    Admin: {{ $adminNames[$row->target_id] ?? $row->target_id }}
                                @elseif ($row->target_type)
                                    {{ $row->target_type }}: {{ $row->target_id }}
                                @else
                                    <span style="color: var(--ad-faint);">—</span>
                                @endif
                            </td>
                            <td style="max-width: 320px;">{{ $row->reason ?? '—' }}</td>
                            <td class="ad-num">{{ $row->ip ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @include('admin.partials.pager', ['rows' => $rows])
        @endif
    </div>
</x-admin.shell>
