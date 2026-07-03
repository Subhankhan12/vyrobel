{{-- UNSTYLED contract stub (Phase 3 · Module 1 Step 6) — owner portal dashboard. Read-only; no
     CSS/design system. Every read is owner-scoped (OwnerScope): this owner sees ONLY their own
     units / statements / payouts, and a non-linked user fails closed (empty). The styled owner PWA
     is the design pass. --}}
<div>
    <h1>Owner portal — contract stub</h1>

    <h2>My units</h2>
    <table border="1">
        <thead><tr><th>Code</th><th>Room type</th><th>Occupancy (available/night)</th></tr></thead>
        <tbody>
            @forelse ($units as $unit)
                <tr>
                    <td>{{ $unit['code'] }}</td>
                    <td>{{ $unit['room_type'] ?? '—' }}</td>
                    <td>
                        @foreach ($unit['occupancy'] as $date => $available){{ $date }}={{ $available }} @endforeach
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">No units.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>My statements</h2>
    <table border="1">
        <thead><tr><th>#</th><th>Period</th><th>Status</th><th>Net payable (minor)</th><th>Currency</th><th>Pending OTA?</th><th>Detail</th></tr></thead>
        <tbody>
            @forelse ($statements as $s)
                <tr>
                    <td>{{ $s['sequence_no'] ?? '—' }}</td>
                    <td>{{ $s['period_start'] }} … {{ $s['period_end'] }}</td>
                    <td>{{ $s['status'] }}</td>
                    <td>{{ $s['net_payable_minor'] }}</td>
                    <td>{{ $s['currency'] }}</td>
                    <td>{{ $s['has_unverified'] ? 'yes (provisional)' : 'no' }}</td>
                    <td><a href="{{ route('owner.statements.show', $s['id']) }}">view</a></td>
                </tr>
            @empty
                <tr><td colspan="7">No statements.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>My payouts</h2>
    <table border="1">
        <thead><tr><th>Amount (minor)</th><th>Currency</th><th>Rail</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($payouts as $p)
                <tr>
                    <td>{{ $p['amount_minor'] }}</td>
                    <td>{{ $p['currency'] }}</td>
                    <td>{{ $p['rail'] }}</td>
                    <td>{{ $p['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No payouts.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Totals</h2>
    <p>Statements net (payable): {{ $totals['statements_net_minor'] }} {{ $totals['currency'] ?? '' }}</p>
    <p>Paid to date: {{ $totals['paid_minor'] }} {{ $totals['currency'] ?? '' }}</p>
</div>
