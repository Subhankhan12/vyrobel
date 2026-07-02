{{-- UNSTYLED contract stub (Phase 2 Step 8) — front-desk board. Read-only; no CSS/design system.
     Data comes through FrontDeskService (the real staff-action layer). The staff ACTIONS the
     styled surface will call — check-in, check-out, room move, payment collection — have working,
     tested backends in FrontDeskService; interactivity + offline sync are the design pass. --}}
<div>
    <h1>Front desk — contract stub</h1>
    <p>Property: {{ $property['name'] ?? '—' }} · Date: {{ $date }}</p>

    @foreach (['Arrivals' => $arrivals, 'Departures' => $departures, 'In-house' => $inHouse] as $heading => $rows)
        <h2>{{ $heading }}</h2>
        <table border="1">
            <thead>
                <tr><th>Reference</th><th>Guest</th><th>Status</th><th>Check-in</th><th>Check-out</th><th>Balance (minor)</th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['reference'] }}</td>
                        <td>{{ $row['guest'] }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ $row['check_in'] }}</td>
                        <td>{{ $row['check_out'] }}</td>
                        <td>{{ $row['balance_minor'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">none</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    {{-- Action contracts (backends live in FrontDeskService; UI is the design pass):
         check-in:        FrontDeskService::checkIn(reservation)            → status checked_in (no inventory)
         check-out:       FrontDeskService::checkOut(reservation)           → status checked_out (+ departure HK task)
         room move:       FrontDeskService::moveRoom(reservationUnit, unit) → AssignmentService::assignUnit (Level B re-check)
         payment collect: FrontDeskService::collectPayment(reservation, money, token?) → FolioService (ledger recompute) --}}
</div>
