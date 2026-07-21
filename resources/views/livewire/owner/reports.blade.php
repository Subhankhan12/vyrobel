{{-- Owner Reports (Phase 6b · SG3) — per-unit performance for the owner's OWN units + real net earnings,
     owner-scoped. Earnings are Σ the owner's ledger (via OwnerLedgerAttribution) — the same figure the
     statements show. Nights/occupancy are the SG1 metric layer over the owner's units. No fabrication. --}}
<x-owner.shell active="reports" title="Reports" :viewer="$viewer">

    {{-- Date-range selector (for the nights/occupancy columns) --}}
    <div class="vy-fade" style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap">
        @foreach ($ranges as $r)
            <a class="vy-btn {{ $r['active'] ? 'vy-btn--accent' : 'vy-btn--ghost' }}" href="?range={{ $r['key'] }}">{{ $r['label'] }}</a>
        @endforeach
    </div>

    {{-- NET EARNINGS — reconciles to your statements (Σ the owner ledger) --}}
    <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
        <div class="vy-kpi">
            <div class="vy-kpi-label">Net earnings to date</div>
            <div class="vy-kpi-val">{{ count($totalNet) > 0 ? implode(' · ', $totalNet) : '—' }}</div>
            <div class="vy-kpi-sub">Reconciles to your statements</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Units</div>
            <div class="vy-kpi-val">{{ $unitsCount }}</div>
            <div class="vy-kpi-sub">In your portfolio</div>
        </div>
    </div>

    {{-- Links to the real Phase-2 documents --}}
    <div class="vy-fade" style="display:flex;gap:8px;margin:16px 0">
        <a class="vy-btn vy-btn--ghost" href="{{ route('owner.archive') }}">View statements</a>
        <a class="vy-btn vy-btn--ghost" href="{{ route('owner.tax') }}">Tax center</a>
    </div>

    {{-- PER-UNIT PERFORMANCE — real nights/occupancy (range) + real net earnings (to date) --}}
    <div class="vy-card vy-fade">
        <div class="vy-card-title">Per unit · nights &amp; occupancy in {{ $activeRange['label'] }}</div>
        @if (count($rows) === 0)
            <div class="vy-empty">No units linked to your account yet.</div>
        @else
            <table class="vy-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th class="vy-num">Nights</th>
                        <th class="vy-num">Occupancy</th>
                        <th class="vy-num">Net to date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td><div class="vy-row-month">{{ $row['code'] }}</div>@if ($row['name'] !== $row['code'])<div class="vy-row-sub">{{ $row['name'] }}</div>@endif</td>
                            <td class="vy-num">{{ $row['nights'] }}</td>
                            <td class="vy-num">{{ $row['occupancy_pct'] }}%</td>
                            <td class="vy-num">{{ $row['earnings'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-owner.shell>
