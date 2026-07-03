{{-- UNSTYLED contract stub (Phase 3 · Module 1 Step 6) — a single owner statement + line breakdown.
     Read-only; no CSS. Owner+tenant scoped — another owner's/org's statement 404s. --}}
<div>
    <h1>Statement #{{ $statement['sequence_no'] ?? '—' }} — contract stub</h1>
    <p>Period: {{ $statement['period_start'] }} … {{ $statement['period_end'] }} · Status: {{ $statement['status'] }}</p>
    <p>Net payable: {{ $statement['net_payable_minor'] }} {{ $statement['currency'] }}
        (source net {{ $statement['net_source_minor'] }} {{ $statement['source_currency'] }})</p>
    @if ($statement['fx_rate'] !== null)
        <p>FX: {{ $statement['source_currency'] }}→{{ $statement['currency'] }} @ {{ $statement['fx_rate'] }} ({{ $statement['fx_source'] }})</p>
    @endif

    <h2>Lines</h2>
    <table border="1">
        <thead><tr><th>Type</th><th>Description</th><th>Amount (minor)</th><th>Currency</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line['line_type'] }}</td>
                    <td>{{ $line['description'] }}</td>
                    <td>{{ $line['amount_minor'] }}</td>
                    <td>{{ $line['currency'] }}</td>
                    <td>{{ $line['unverified'] ? 'provisional (pending reconciliation)' : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
