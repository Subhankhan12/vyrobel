{{-- Operator Unit Requests (Phase 2 · Sub-gate 6; #104 GATE 1 — now OPERATIONAL). The operator reviews an owner's
     add-unit request and ACTS on it: Approve creates the REAL unit (assigned to the owner, made bookable) via the
     certified OwnerUnitRequestService; Decline notifies the owner. Both are `can:units.manage` controller POSTs
     (PRG). Tenant-scoped. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="unit-requests" title="Unit requests" :subtitle="$pending.' pending'" :viewer="$viewer">

        <p class="vy-line-sub" style="font-size:13px;margin-bottom:16px;color:var(--vy-muted)">
            Owners request new units here. Review the details, then <strong>Approve</strong> to create the unit (it’s added to your inventory and assigned to the owner), or <strong>Decline</strong> — the owner is notified either way.
        </p>

        @if (session('unit_request_notice'))
            <div class="vy-flash" style="margin-bottom:14px;background:rgba(26,127,75,.10);color:#1a7f4b;border:1px solid rgba(26,127,75,.2);border-radius:12px;padding:11px 15px;font-size:13px;font-weight:500">{{ session('unit_request_notice') }}</div>
        @endif
        @if (session('unit_request_error'))
            <div class="vy-flash is-err" style="margin-bottom:14px;border-radius:12px;padding:11px 15px;font-size:13px;font-weight:500">{{ session('unit_request_error') }}</div>
        @endif

        <div class="vy-card vy-fade" x-data="{ openRow: null, mode: null, propertyId: '', properties: @js($properties) }">
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
                                @if ($canManage)<th style="text-align:right">Review</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $r)
                                <tr>
                                    <td>{{ $r['owner'] }}</td>
                                    <td style="font-weight:600">{{ $r['name'] }}</td>
                                    <td>{{ $r['kind'] }}</td>
                                    <td>
                                        {{-- ⚠️ DO9-3 — `withdrawn` joins `declined` on the muted tone. The final
                                             else-arm here is the AMBER "needs attention" colour, so without
                                             this a request the owner had RETRACTED would keep signalling as
                                             actionable to the operator — the label would say Withdrawn while
                                             the colour said pending. (Text is fine: ucfirst() renders it.) --}}
                                        @php $tone = $r['status'] === 'approved' ? '#1a7f4b' : (in_array($r['status'], ['declined', 'withdrawn'], true) ? 'var(--vy-muted)' : '#b26a08'); @endphp
                                        <span style="display:inline-block;font-size:11px;font-weight:600;padding:2px 9px;border-radius:999px;color:{{ $tone }};background:color-mix(in srgb, {{ $tone }} 12%, transparent)">{{ $r['status'] === 'approved' ? 'Approved' : ucfirst($r['status']) }}</span>
                                    </td>
                                    <td style="white-space:nowrap">{{ $r['when'] }}</td>
                                    <td style="max-width:300px;color:var(--vy-muted);font-size:12.5px">
                                        {{ $r['details'] }}
                                        @if ($r['created_unit'])<br><span style="color:#1a7f4b;font-weight:600">→ created unit: {{ $r['created_unit'] }}</span>@endif
                                        @if ($r['review_note'] !== '')<br><span style="font-style:italic">“{{ $r['review_note'] }}”</span>@endif
                                    </td>
                                    @if ($canManage)
                                        <td style="text-align:right;white-space:nowrap">
                                            @if ($r['status'] === 'pending')
                                                <button type="button" class="vy-btn" @click="openRow = (openRow === '{{ $r['id'] }}' ? null : '{{ $r['id'] }}'); mode = 'approve'; propertyId = ''" style="font-size:12px;padding:5px 12px">Approve</button>
                                                <button type="button" class="vy-btn vy-btn--ghost" @click="openRow = (openRow === '{{ $r['id'] }}' ? null : '{{ $r['id'] }}'); mode = 'decline'" style="font-size:12px;padding:5px 12px">Decline</button>
                                            @else
                                                <span style="color:var(--vy-faint);font-size:12px">Reviewed</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                                @if ($canManage && $r['status'] === 'pending')
                                    <tr x-show="openRow === '{{ $r['id'] }}'" x-cloak>
                                        <td colspan="7" style="background:rgba(255,255,255,.4);border-radius:12px">
                                            {{-- APPROVE — pick a property + one of its room types; the unit is created under them. --}}
                                            <form x-show="mode === 'approve'" method="POST" action="{{ route('app.unit-requests.approve', $r['id']) }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;padding:14px 6px">
                                                @csrf
                                                <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600">Property
                                                    <select name="property_id" required x-model="propertyId" class="vy-input" style="min-width:180px">
                                                        <option value="">Choose…</option>
                                                        <template x-for="p in properties" :key="p.id">
                                                            <option :value="p.id" x-text="p.name"></option>
                                                        </template>
                                                    </select>
                                                </label>
                                                <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600">Room type
                                                    <select name="room_type_id" required class="vy-input" style="min-width:180px">
                                                        <option value="">Choose a property first…</option>
                                                        <template x-for="rt in (properties.find(p => p.id === propertyId)?.room_types ?? [])" :key="rt.id">
                                                            <option :value="rt.id" x-text="rt.name"></option>
                                                        </template>
                                                    </select>
                                                </label>
                                                <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600;flex:1;min-width:180px">Note (optional)
                                                    <input type="text" name="note" maxlength="500" class="vy-input" placeholder="Anything to tell the owner">
                                                </label>
                                                <button type="submit" class="vy-btn vy-btn--accent" style="font-size:12.5px">Create unit</button>
                                            </form>
                                            {{-- DECLINE — a reason, sent to the owner. No inventory is created. --}}
                                            <form x-show="mode === 'decline'" method="POST" action="{{ route('app.unit-requests.decline', $r['id']) }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;padding:14px 6px">
                                                @csrf
                                                <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:600;flex:1;min-width:220px">Reason (optional)
                                                    <input type="text" name="note" maxlength="500" class="vy-input" placeholder="Why this wasn’t approved">
                                                </label>
                                                <button type="submit" class="vy-btn vy-btn--ghost" style="font-size:12.5px">Decline request</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </x-operator.shell>
</div>
