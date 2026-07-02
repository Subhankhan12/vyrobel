{{-- UNSTYLED contract stub — availability grid from AvailabilityService. --}}
<div>
    <h1>Room rack — contract stub</h1>
    <p>Property: {{ $property['name'] ?? '(no property)' }}</p>
    <table border="1">
        <thead>
            <tr>
                <th>Room type</th>
                @foreach ($dates as $date)
                    <th>{{ $date }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['room_type'] }} ({{ $row['kind'] }})</td>
                    @foreach ($dates as $date)
                        <td>{{ $row['availability'][$date] ?? 0 }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
