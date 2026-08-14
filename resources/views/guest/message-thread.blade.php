{{-- Guest message thread (Phase 4c · SG3; guest Phase-4 pixel-diff G4) — one owned conversation, MIRRORED
     transcript (the guest's own messages right, the property's left) + a real reply via InboxService. Matched to
     the wireframe's chat layout, with motion. ⚠️ HONEST (#39/#28): a name-only chat header ("The property" — NO
     "Marco Silva" persona, NO "Online now"/typing presence, NO "replies in ~10 min" SLA, NO "urgent? Call +351…"
     phone). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="messages" :title="$subject">
    <a class="vy-link" href="{{ route('guest.messages') }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">← Messages</a>

    {{-- Chat header (name-only, no presence/SLA/phone) --}}
    <div style="display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:18px;box-shadow:var(--vy-shadow);padding:16px 20px;margin:12px 0 16px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="width:44px;height:44px;flex-shrink:0;border-radius:13px;background:linear-gradient(140deg,#E8703A,#C2410C);display:grid;place-items:center;box-shadow:var(--vy-shadow-accent)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div style="min-width:0">
            <div style="font-family:var(--vy-display);font-weight:600;font-size:17px;color:var(--vy-ink);letter-spacing:-.01em">{{ $subject }}</div>
            <div style="font-size:12px;color:var(--vy-muted);margin-top:1px">With the property team</div>
        </div>
    </div>

    {{-- Transcript --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .1s both">
        @forelse ($messages as $message)
            <div style="display:flex;margin:8px 0;{{ $message['mine'] ? 'justify-content:flex-end' : 'justify-content:flex-start' }}">
                <div style="max-width:78%;padding:10px 13px;border-radius:14px;{{ $message['mine'] ? 'background:var(--vy-accent);color:#fff;border-bottom-right-radius:4px' : 'background:var(--vy-wash);color:var(--vy-ink);border-bottom-left-radius:4px' }}">
                    <div style="font-size:10.5px;opacity:.75;margin-bottom:3px">{{ $message['author'] }} · {{ $message['at'] }}</div>
                    <div style="font-size:14px;line-height:1.45;white-space:pre-wrap">{{ $message['body'] }}</div>
                </div>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">No messages yet — say hello below.</div>
        @endforelse
    </section>

    {{-- Reply --}}
    <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .14s both">
        <form method="POST" action="{{ route('guest.messages.send', $thread) }}" style="display:flex;flex-direction:column;gap:10px">
            @csrf
            <textarea class="vy-input" name="body" rows="3" placeholder="Write a reply…" maxlength="5000" required style="resize:vertical"></textarea>
            <div><button type="submit" class="vy-btn">Send</button></div>
        </form>
    </section>
</x-guest.shell>
