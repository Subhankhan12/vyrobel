{{-- Guest Messages (Phase 4c · SG3) — the guest's own conversations with the property (the guest side of the
     Unified Inbox). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="messages" title="Messages">
    <h1 class="vy-h1">Messages</h1>
    <p class="vy-lead">Questions about your stay — parking, breakfast, wifi, late checkout. We’ll reply here.</p>

    {{-- Threads --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:10px">Your conversations</div>
        @forelse ($threads as $thread)
            <a href="{{ route('guest.messages.show', $thread['id']) }}" class="vy-row--tappable" style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 10px;margin:0 -10px;border-bottom:1px solid var(--vy-hairline);text-decoration:none">
                <div style="display:flex;align-items:center;gap:9px">
                    @if ($thread['unread'])<span style="flex:none;width:8px;height:8px;border-radius:50%;background:var(--vy-accent)"></span>@endif
                    <span style="font-size:14px;color:var(--vy-ink)">{{ $thread['subject'] }}</span>
                </div>
                <span style="font-size:11.5px;color:var(--vy-faint)">{{ $thread['at'] }}</span>
            </a>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">No messages yet — start a conversation below.</div>
        @endforelse
    </section>

    {{-- Start a new message --}}
    <section class="vy-card">
        <div class="vy-eyebrow" style="margin-bottom:10px">New message</div>
        <form method="POST" action="{{ route('guest.messages.start') }}" style="display:flex;flex-direction:column;gap:10px">
            @csrf
            <input type="text" name="subject" placeholder="Subject (e.g. Parking)" maxlength="120" required style="padding:10px 12px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff">
            <textarea name="body" rows="3" placeholder="Your message" maxlength="5000" required style="padding:10px 12px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff;resize:vertical"></textarea>
            <div><button type="submit" class="vy-btn">Send</button></div>
        </form>
    </section>
</x-guest.shell>
