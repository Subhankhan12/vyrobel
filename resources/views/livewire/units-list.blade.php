{{-- UNSTYLED contract stub — raw structure only, no design. --}}
<div>
    <h1>Units — contract stub</h1>
    <table border="1">
        <thead>
            <tr>
                <th>Code</th><th>Name</th><th>Kind</th><th>Room type</th>
                <th>Housekeeping</th><th>Condition</th><th>Active</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($units as $unit)
                <tr>
                    <td>{{ $unit['code'] }}</td>
                    <td>{{ $unit['name'] }}</td>
                    <td>{{ $unit['unit_kind'] }}</td>
                    <td>{{ $unit['room_type'] }}</td>
                    <td>{{ $unit['housekeeping_status'] }}</td>
                    <td>{{ $unit['condition_status'] }}</td>
                    <td>{{ $unit['is_active'] ? 'yes' : 'no' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
