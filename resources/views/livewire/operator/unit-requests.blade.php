{{-- Operator Unit Requests (Phase 2 · Sub-gate 6) — the operator-visibility side of an owner's add-unit request.
     Read-only: the operator reviews the request and creates the real unit as their own action (not built here).
     Tenant-scoped. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="unit-requests" title="Unit requests" :subtitle="$pending.' pending'" :viewer="$viewer">

        <p class="vy-line-sub" style="font-size:13px;margin-bottom:16px;color:var(--vy-muted)">
            Owners request new units here. Review the details, then create the unit in the system yourself — a request never adds inventory on its own.
        </p>

        <div class="vy-card vy-fade">
            @if ($requests === [])
                <div class="vy-empty">No unit requests yet.</div>
            @else
                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>Owner</th>
                                <th>Requested unit</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Sent</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $r)
                                <tr>
                                    <td>{{ $r['owner'] }}</td>
                                    <td style="font-weight:600">{{ $r['name'] }}</td>
                                    <td>{{ $r['kind'] }}</td>
                                    <td>
                                        @php $tone = $r['status'] === 'approved' ? '#1a7f4b' : ($r['status'] === 'declined' ? 'var(--vy-muted)' : '#b26a08'); @endphp
                                        <span style="display:inline-block;font-size:11px;font-weight:600;padding:2px 9px;border-radius:999px;color:{{ $tone }};background:color-mix(in srgb, {{ $tone }} 12%, transparent)">{{ ucfirst($r['status']) }}</span>
                                    </td>
                                    <td style="white-space:nowrap">{{ $r['when'] }}</td>
                                    <td style="max-width:340px;color:var(--vy-muted);font-size:12.5px">{{ $r['details'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </x-operator.shell>
</div>
