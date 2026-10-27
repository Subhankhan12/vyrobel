{{-- ADMIN 01 · PHASE 1 — the platform-admin AUDIT LOG. Read-only by construction: no edit/delete route exists,
     and the ledger refuses UPDATE/DELETE at the model and at the database connection. --}}
<x-admin.shell active="audit" :title="__('admin/audit/index.title')"
    :subtitle="__('admin/audit/index.subtitle')">

    <form method="GET" action="{{ route('admin.audit.index') }}" class="ad-filters">
        <select name="kind" aria-label="{{ __('admin/audit/index.kind_aria') }}">
            <option value="changes" @selected($filters['kind'] === 'changes')>{{ __('admin/audit/index.kind_changes') }}</option>
            <option value="reads" @selected($filters['kind'] === 'reads')>{{ __('admin/audit/index.kind_reads') }}</option>
            <option value="all" @selected($filters['kind'] === 'all')>{{ __('admin/audit/index.kind_all') }}</option>
        </select>
        <select name="admin" aria-label="{{ __('admin/audit/index.admin_aria') }}">
            <option value="">{{ __('admin/audit/index.any_admin') }}</option>
            @foreach ($admins as $a)
                <option value="{{ $a->id }}" @selected($filters['admin'] === $a->id)>{{ $a->name }}</option>
            @endforeach
        </select>
        <select name="action" aria-label="{{ __('admin/audit/index.action_aria') }}">
            <option value="">{{ __('admin/audit/index.any_action') }}</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ \App\Support\Platform\ActionLabel::platform($action) }}</option>
            @endforeach
        </select>
        <select name="tenant" aria-label="{{ __('admin/audit/index.organisation_aria') }}" data-searchable>
            <option value="">{{ __('admin/audit/index.any_organisation') }}</option>
            @foreach ($organizations as $org)
                <option value="{{ $org->id }}" @selected($filters['tenant'] === $org->id)>{{ $org->name }}{{ $org->is_internal ? __('admin/audit/index.internal_suffix') : '' }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] }}" aria-label="{{ __('admin/audit/index.from_aria') }}">
        <input type="date" name="to" value="{{ $filters['to'] }}" aria-label="{{ __('admin/audit/index.to_aria') }}">
        <button type="submit" class="ad-btn ad-btn--sm">{{ __('common.filter') }}</button>
    </form>

    <div class="ad-card" style="padding: 8px 10px; overflow-x: auto;">
        @if ($rows->isEmpty())
            <div class="ad-empty">{{ __('admin/audit/index.empty_state') }}</div>
        @else
            <table class="ad-table">
                <thead>
                    <tr><th>{{ __('admin/audit/index.col_when') }}</th><th>{{ __('admin/audit/index.col_admin') }}</th><th>{{ __('admin/audit/index.col_action') }}</th><th>{{ __('admin/audit/index.col_target') }}</th><th>{{ __('admin/audit/index.col_reason') }}</th><th>{{ __('admin/audit/index.col_ip') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td style="white-space: nowrap;">{{ $row->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $row->platform_admin_id ? ($adminNames[$row->platform_admin_id] ?? __('admin/audit/index.removed_admin')) : __('admin/audit/index.system_cli') }}</td>
                            <td>{{ \App\Support\Platform\ActionLabel::platform($row->action) }}<div><code style="font-size: 11.5px; color: var(--ad-faint);">{{ $row->action }}</code></div></td>
                            <td>
                                @if ($row->target_type === 'organization')
                                    {{ $orgNames[$row->target_id] ?? $row->target_id }}
                                @elseif ($row->target_type === 'platform_admin')
                                    {{ __('admin/audit/index.target_admin', ['name' => $adminNames[$row->target_id] ?? $row->target_id]) }}
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
