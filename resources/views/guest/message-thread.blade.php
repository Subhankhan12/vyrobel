{{-- Guest message thread (Phase 4c · SG3) — one owned conversation, MIRRORED transcript (the guest's own messages
     right, the property's left) + a real reply via InboxService. RAW-BLADE RULE: every `@` follows a space,
     newline or tag. --}}
<x-guest.shell active="messages" :title="$subject">
    <a class="vy-link" href="{{ route('guest.messages') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">← Messages</a>

    <h1 class="vy-h1" style="margin-top:10px">{{ $subject }}</h1>

    {{-- Transcript --}}
    <section class="vy-card" style="margin-bottom:14px">
        @forelse ($messages as $message)
            <div style="display:flex;margin:8px 0;{{ $message['mine'] ? 'justify-content:flex-end' : 'justify-content:flex-start' }}">
                <div style="max-width:78%;padding:10px 13px;border-radius:14px;{{ $message['mine'] ? 'background:var(--vy-grad);color:#fff;border-bottom-right-radius:4px' : 'background:var(--vy-wash);color:var(--vy-ink);border-bottom-left-radius:4px' }}">
                    <div style="font-size:10.5px;opacity:.75;margin-bottom:3px">{{ $message['author'] }} · {{ $message['at'] }}</div>
                    <div style="font-size:14px;line-height:1.45;white-space:pre-wrap">{{ $message['body'] }}</div>
                </div>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">No messages yet.</div>
        @endforelse
    </section>

    {{-- Reply --}}
    <section class="vy-card">
        <form method="POST" action="{{ route('guest.messages.send', $thread) }}" style="display:flex;flex-direction:column;gap:10px">
            @csrf
            <textarea name="body" rows="3" placeholder="Write a reply…" maxlength="5000" required style="padding:10px 12px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff;resize:vertical"></textarea>
            <div><button type="submit" class="vy-btn">Send</button></div>
        </form>
    </section>
</x-guest.shell>
