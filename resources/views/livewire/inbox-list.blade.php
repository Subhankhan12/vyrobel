{{-- UNSTYLED contract stub — operator inbox thread list. REAL, tenant-scoped data; the styled screen is a
     later design pass (same pattern as the original Phase-1 stubs). No CSS. Shows guest NAME + a last-message
     preview + channel/status/unread; the encrypted participant_identity/contact is NEVER rendered. --}}
<div>
    <h1>Inbox</h1>
    <p>{{ count($threads) }} thread(s)</p>

    @if (count($threads) === 0)
        <p>No conversations yet.</p>
    @else
        <table border="1" cellpadding="4">
            <thead>
                <tr>
                    <th>Guest</th><th>Channel</th><th>Status</th><th>Unread</th>
                    <th>Last message</th><th>When</th><th>Booking</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($threads as $t)
                    <tr>
                        <td>{{ $t['guest'] }}</td>
                        <td>{{ $t['channel'] }}</td>
                        <td>{{ $t['status'] }}</td>
                        <td>{{ $t['unread'] }}</td>
                        <td>{{ $t['preview'] }}</td>
                        <td>{{ $t['last_at'] ?? '—' }}</td>
                        <td>{{ $t['reservation'] ?? '—' }}</td>
                        <td><a href="{{ route('app.inbox.show', $t['id']) }}">Open</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
