{{-- UNSTYLED contract stub — operator inbox thread view + reply box. REAL, tenant-scoped; styled later.
     No CSS. The reply box is a REAL outbound (POST → InboxActionController → InboxService::sendOutbound → PRG);
     on a deferred channel it surfaces an honest failed state, not a fake send. Guest NAME shown; the encrypted
     participant_identity/contact is NEVER rendered. --}}
<div>
    <p><a href="{{ route('app.inbox') }}">&larr; Inbox</a></p>

    <h1>Conversation with {{ $thread['guest'] }}</h1>
    <p>
        Channel: {{ $thread['channel'] }} · Status: {{ $thread['status'] }}
        @if ($thread['reservation']) · Booking: {{ $thread['reservation'] }} @endif
        @if ($thread['subject']) · Subject: {{ $thread['subject'] }} @endif
    </p>

    @if (session('inbox_notice'))
        <p role="status"><strong>{{ session('inbox_notice') }}</strong></p>
    @endif
    @if (session('inbox_error'))
        <p role="alert"><strong>{{ session('inbox_error') }}</strong></p>
    @endif

    <h2>Messages</h2>
    @forelse ($messages as $m)
        <div style="margin-bottom: 8px;">
            <strong>{{ $m['direction'] === 'inbound' ? $m['author'] : 'You ('.$m['author'].')' }}</strong>
            <em>{{ $m['at'] }}</em>
            @if ($m['direction'] === 'outbound')
                — delivery: {{ $m['delivery_status'] }}
            @endif
            <div>{{ $m['body'] }}</div>
        </div>
    @empty
        <p>No messages in this thread.</p>
    @endforelse

    <h2>Reply</h2>
    <form method="POST" action="{{ route('app.inbox.reply', $thread['id']) }}">
        @csrf
        <textarea name="body" rows="3" cols="60" required placeholder="Type a reply…"></textarea>
        <br>
        <button type="submit">Send reply</button>
    </form>
</div>
