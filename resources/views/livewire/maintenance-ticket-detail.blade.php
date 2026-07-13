{{-- UNSTYLED contract stub — operator maintenance ticket detail. REAL, tenant-scoped; styled later. No CSS. --}}
<div>
    <p><a href="{{ route('app.maintenance') }}">&larr; Maintenance</a></p>
    <h1>{{ $ticket['title'] }}</h1>

    <dl>
        <dt>Status</dt><dd>{{ $ticket['status'] }}</dd>
        <dt>Category</dt><dd>{{ $ticket['category'] }}</dd>
        <dt>Priority</dt><dd>{{ $ticket['priority'] }}</dd>
        <dt>Unit</dt><dd>{{ $ticket['unit'] }} (condition: {{ $ticket['unit_condition'] }})</dd>
        <dt>Takes out of service</dt><dd>{{ $ticket['out_of_service'] ? 'yes' : 'no' }}</dd>
        <dt>Cost</dt><dd>{{ $ticket['cost'] ?? '—' }}</dd>
        <dt>Opened</dt><dd>{{ $ticket['opened'] }}</dd>
        <dt>Resolved</dt><dd>{{ $ticket['resolved'] ?? '—' }}</dd>
        @if ($ticket['description'])<dt>Description</dt><dd>{{ $ticket['description'] }}</dd>@endif
    </dl>

    <h2>Owner expense</h2>
    @if ($expense)
        <p>Amount: {{ $expense['amount'] }} · deductible: {{ $expense['deductible'] ? 'yes' : 'no' }} ·
           ledger posted: {{ $expense['ledger_posted'] ? 'yes' : 'no' }}</p>
    @else
        <p>None — operator-owned unit, or no cost recorded.</p>
    @endif
</div>
