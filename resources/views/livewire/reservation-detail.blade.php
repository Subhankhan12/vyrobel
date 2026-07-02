{{-- UNSTYLED contract stub — reservation + units + folio reads. --}}
<div>
    <h1>Reservation {{ $reservation['reference'] }} — contract stub</h1>
    <ul>
        <li>Status: {{ $reservation['status'] }}</li>
        <li>Source: {{ $reservation['source'] }}</li>
        <li>Dates: {{ $reservation['check_in'] }} &rarr; {{ $reservation['check_out'] }}</li>
        <li>Occupancy: {{ $reservation['adults'] }} adults, {{ $reservation['children'] }} children</li>
        <li>Lead guest: {{ $reservation['guest'] ?? '—' }}</li>
        <li>Payment status: {{ $reservation['payment_status'] }}</li>
    </ul>

    <h2>Units</h2>
    <table border="1">
        <thead>
            <tr><th>Room type</th><th>Unit</th><th>Dates</th><th>Status</th><th>Price (minor)</th></tr>
        </thead>
        <tbody>
            @foreach ($units as $unit)
                <tr>
                    <td>{{ $unit['room_type'] }}</td>
                    <td>{{ $unit['unit'] ?? '(unassigned)' }}</td>
                    <td>{{ $unit['check_in'] }} &rarr; {{ $unit['check_out'] }}</td>
                    <td>{{ $unit['status'] }}</td>
                    <td>{{ $unit['price_minor'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Folio</h2>
    @if ($folio)
        <p>
            Total {{ $folio['total_minor'] }} / Paid {{ $folio['paid_minor'] }} /
            Balance {{ $folio['balance_minor'] }} ({{ $reservation['currency'] }} minor units)
        </p>
        <h3>Charges</h3>
        <ul>
            @foreach ($folio['items'] as $item)
                <li>{{ $item['type'] }} — {{ $item['description'] }} — {{ $item['amount_minor'] }}</li>
            @endforeach
        </ul>
        <h3>Payments</h3>
        <ul>
            @foreach ($folio['payments'] as $payment)
                <li>{{ $payment['type'] }} ({{ $payment['status'] }}) — {{ $payment['amount_minor'] }}</li>
            @endforeach
        </ul>
    @else
        <p>No folio.</p>
    @endif
</div>
