{{-- Guest Messages (Phase 4c · SG3; guest Phase-4 pixel-diff G4) — the guest's own conversations with the property
     (the guest side of the Unified Inbox). Matched to the wireframe: a hero + conversation list + new-message, with
     motion. ⚠️ HONEST (#39/#28): NO fabricated "Online now" / typing indicator (no websockets/presence), NO "reply
     within ~10 min" SLA, NO named-host persona ("Marco Silva"), NO "urgent? Call +351…" phone — the property side
     is name-only (real sender label or "Property"). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="messages" title="Messages">
    {{-- Hero --}}
    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:22px 26px;margin-bottom:18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="width:52px;height:52px;flex-shrink:0;border-radius:16px;background:linear-gradient(140deg,#E8703A,#C2410C);display:grid;place-items:center;box-shadow:var(--vy-shadow-accent)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div style="min-width:0">
            <div style="font-family:var(--vy-display);font-weight:700;font-size:24px;letter-spacing:-.02em;color:var(--vy-ink)">Messages</div>
            <div style="font-size:13px;color:var(--vy-muted);margin-top:3px">Questions about your stay — parking, breakfast, wifi, late checkout. The property replies right here.</div>
        </div>
    </div>

    {{-- Conversations --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .1s both">
        <div class="vy-eyebrow" style="margin-bottom:10px">Your conversations</div>
        @forelse ($threads as $thread)
            <a href="{{ route('guest.messages.show', $thread['id']) }}" class="vy-row--tappable" style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 10px;margin:0 -10px;border-bottom:1px solid var(--vy-hairline);text-decoration:none">
                <div style="display:flex;align-items:center;gap:9px;min-width:0">
                    @if ($thread['unread'])<span style="flex:none;width:8px;height:8px;border-radius:50%;background:var(--vy-accent)"></span>@endif
                    <span style="font-size:14px;color:var(--vy-ink)">{{ $thread['subject'] }}</span>
                </div>
                <span style="font-size:11.5px;color:var(--vy-faint);flex-shrink:0">{{ $thread['at'] }}</span>
            </a>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">No messages yet — start a conversation below.</div>
        @endforelse
    </section>

    {{-- Start a new message --}}
    <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .14s both">
        <div class="vy-eyebrow" style="margin-bottom:10px">New message</div>
        <form method="POST" action="{{ route('guest.messages.start') }}" style="display:flex;flex-direction:column;gap:10px">
            @csrf
            <input class="vy-input" type="text" name="subject" placeholder="Subject (e.g. Parking)" maxlength="120" required>
            <textarea class="vy-input" name="body" rows="3" placeholder="Your message" maxlength="5000" required style="resize:vertical"></textarea>
            <div><button type="submit" class="vy-btn">Send</button></div>
        </form>
    </section>
</x-guest.shell>
