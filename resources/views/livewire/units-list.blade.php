{{-- Operator Units — the org's physical units (design pass · final operator stub). Styled on the
     shared operator design system via <x-operator.shell>. All data is REAL and tenant-scoped; the
     status tags are keyed to the real unit enums. Read-only. --}}
<div>
    <x-operator.shell active="units" title="Units" subtitle="Inventory" :viewer="$viewer">

        {{-- DERIVED summary + honest NO-BACKEND affordances --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left vy-summary" style="margin:0;">
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $summary['total'] }}</span><span class="vy-sc-label">Units</span></div>
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $summary['active'] }}</span><span class="vy-sc-label">Active</span></div>
                <div class="vy-summary-chip @if ($summary['to_clean'] > 0) is-due @endif"><span class="vy-sc-num">{{ $summary['to_clean'] }}</span><span class="vy-sc-label">To clean</span></div>
                <div class="vy-summary-chip @if ($summary['out_of_service'] > 0) is-due @endif"><span class="vy-sc-num">{{ $summary['out_of_service'] }}</span><span class="vy-sc-label">Out of service</span></div>
            </div>
            <div class="vy-right">
                <span class="vy-pill is-soon" aria-disabled="true">Filters <span class="vy-soon-tag">Soon</span></span>
                <span class="vy-pill is-soon" aria-disabled="true">Export <span class="vy-soon-tag">Soon</span></span>
                <span class="vy-btn is-soon" aria-disabled="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add unit <span class="vy-soon-tag">Soon</span>
                </span>
            </div>
        </div>

        @if (count($units) === 0)
            <div class="vy-card vy-fade"><div class="vy-empty">No units yet — add a property and room types to see its units.</div></div>
        @else
            <div class="vy-card vy-card--flush vy-fade">
                <div class="vy-utable-wrap">
                    <table class="vy-utable">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Room type</th>
                                <th>Kind</th>
                                <th>Sleeps</th>
                                <th>Housekeeping</th>
                                <th>Condition</th>
                                <th>Owner</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($units as $unit)
                                <tr>
                                    <td>
                                        <div class="vy-u-code">{{ $unit['code'] }}
                                            @unless ($unit['active'])<span class="vy-u-inactive">Inactive</span>@endunless
                                        </div>
                                        @if ($unit['name'])<div class="vy-u-sub">{{ $unit['name'] }}</div>@endif
                                    </td>
                                    <td>{{ $unit['room_type'] }}</td>
                                    <td>{{ $unit['kind'] }}</td>
                                    <td>{{ $unit['occupancy'] }}</td>
                                    <td><span class="vy-tag tone-{{ $unit['hk_tone'] }}"><span class="vy-dot"></span>{{ $unit['hk_label'] }}</span></td>
                                    <td><span class="vy-tag tone-{{ $unit['condition_tone'] }}"><span class="vy-dot"></span>{{ $unit['condition_label'] }}</span></td>
                                    <td>
                                        @if ($unit['owner'])
                                            <span class="vy-u-owner"><span class="vy-mini-avatar">{{ strtoupper(mb_substr($unit['owner'], 0, 1)) }}</span>{{ $unit['owner'] }}</span>
                                        @else
                                            {{-- No owner_id set — operator-owned inventory (e.g. a hotel room). Real, not blank-faked. --}}
                                            <span class="vy-u-operator">Operator-owned</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="vy-deferred-note" style="margin-top:16px;">
                <span class="vy-soon-tag">Not yet available</span>
                Per-unit photos, editing &amp; bulk actions are planned — cleanliness and condition above are
                the real unit status; housekeeping moves happen on the board.
            </div>
        @endif

    </x-operator.shell>
</div>
