{{-- UNSTYLED contract stub — operator maintenance list. REAL, tenant-scoped data; the styled screen is a
     later design pass (same pattern as the original Phase-1 stubs). No CSS. --}}
<div>
    <h1>Maintenance</h1>
    <p>{{ count($tickets) }} ticket(s)</p>

    @if (count($tickets) === 0)
        <p>No maintenance tickets.</p>
    @else
        <table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>Unit</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th>
                    <th>Out of service</th><th>Cost</th><th>Opened</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tickets as $t)
                    <tr>
                        <td>{{ $t['unit'] }}</td>
                        <td>{{ $t['title'] }}</td>
                        <td>{{ $t['category'] }}</td>
                        <td>{{ $t['priority'] }}</td>
                        <td>{{ $t['status'] }}</td>
                        <td>{{ $t['out_of_service'] ? 'yes' : 'no' }}</td>
                        <td>{{ $t['cost'] ?? '—' }}</td>
                        <td>{{ $t['opened'] }}</td>
                        <td><a href="{{ route('app.maintenance.show', $t['id']) }}">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
