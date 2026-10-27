{{-- Guest message thread (Phase 4c · SG3; guest Phase-4 pixel-diff G4) — one owned conversation, MIRRORED
     transcript (the guest's own messages right, the property's left) + a real reply via InboxService. Matched to
     the wireframe's chat layout, with motion. ⚠️ HONEST (#39/#28): a name-only chat header ("The property" — NO
     "Marco Silva" persona, NO "Online now"/typing presence, NO "replies in ~10 min" SLA, NO "urgent? Call +351…"
     phone). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="messages" :title="$subject">
    <a class="vy-link" href="{{ route('guest.messages') }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">{{ __('guest/message-thread.back_to_messages') }}</a>

    {{-- Chat header (name-only, no presence/SLA/phone) --}}
    <div style="display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:18px;box-shadow:var(--vy-shadow);padding:16px 20px;margin:12px 0 16px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="width:44px;height:44px;flex-shrink:0;border-radius:13px;background:linear-gradient(140deg,#E8703A,#C2410C);display:grid;place-items:center;box-shadow:var(--vy-shadow-accent)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div style="min-width:0">
            {{-- ⚠️ PROMOTED FROM A <div>, NOT INVENTED. This text was already the page's visible title; only its ELEMENT was wrong, so a screen reader navigating by heading found nothing on this screen. ⚠️ `margin:0` keeps the rendering byte-identical — an <h1> carries default margins a <div> does not. --}}
            <h1 style="font-family:var(--vy-display);font-weight:600;font-size:17px;color:var(--vy-ink);letter-spacing:-.01em;margin:0">{{ $subject }}</h1>
            <div style="font-size:12px;color:var(--vy-muted);margin-top:1px">{{ __('guest/message-thread.with_property_team') }}</div>
        </div>
    </div>

    {{-- Transcript --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .1s both">
        @forelse ($messages as $message)
            <div style="display:flex;margin:8px 0;{{ $message['mine'] ? 'justify-content:flex-end' : 'justify-content:flex-start' }}">
                <div style="max-width:78%;padding:10px 13px;border-radius:14px;{{ $message['mine'] ? 'background:var(--vy-accent);color:#fff;border-bottom-right-radius:4px' : 'background:var(--vy-wash);color:var(--vy-ink);border-bottom-left-radius:4px' }}">
                    <div style="font-size:10.5px;opacity:.75;margin-bottom:3px">{{ $message['author'] }} · {{ $message['at'] }}</div>
                    @if ($message['body'] !== '')
                        <div style="font-size:14px;line-height:1.45;white-space:pre-wrap">{{ $message['body'] }}</div>
                    @endif
                    {{-- pictures, documents and voice notes the property sent (2026-10-06) — opened through this guest's own door --}}
                    @foreach ($message['files'] as $file)
                        @if ($file['kind'] === 'image')
                            <a class="vy-gm-photo" href="{{ $file['url'] }}" target="_blank" rel="noopener"><img src="{{ $file['url'] }}" alt="{{ $file['name'] }}" loading="lazy"></a>
                        @elseif ($file['kind'] === 'voice')
                            <audio controls preload="none" src="{{ $file['url'] }}" class="vy-gm-voice" aria-label="{{ __('guest/message-thread.voice_message') }}"></audio>
                        @else
                            <a class="vy-gm-file" href="{{ $file['url'] }}" download>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg><span class="vy-gm-file-name">{{ $file['name'] }}</span><span class="vy-gm-file-size">{{ $file['size'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">{{ __('guest/message-thread.empty_state') }}</div>
        @endforelse
    </section>

    {{-- Reply --}}
    <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .14s both">
        <form method="POST" action="{{ route('guest.messages.send', $thread) }}" style="display:flex;flex-direction:column;gap:10px">
            @csrf
            <label class="vy-sr-only" for="vy-lbl-message-thread-body">{{ __('guest/message-thread.reply_placeholder') }}</label>
            <textarea id="vy-lbl-message-thread-body" class="vy-input" name="body" rows="3" placeholder="{{ __('guest/message-thread.reply_placeholder') }}" maxlength="5000" required style="resize:vertical"></textarea>
            <div><button type="submit" class="vy-btn">{{ __('guest/message-thread.send') }}</button></div>
        </form>
    </section>
    <style>
        .vy-gm-photo { display: block; margin-top: 8px; border-radius: 12px; overflow: hidden; max-width: 280px; }
        .vy-gm-photo img { display: block; width: 100%; height: auto; aspect-ratio: 4 / 3; object-fit: cover; background: rgba(120, 70, 40, .06); }
        .vy-gm-voice { display: block; margin-top: 8px; width: 260px; max-width: 100%; height: 38px; }
        .vy-gm-file { display: flex; align-items: center; gap: 8px; margin-top: 8px; padding: 9px 12px; border-radius: 12px; background: #fff; color: var(--vy-ink); text-decoration: none; font-size: 13px; border: 1px solid var(--vy-glass-border); }
        .vy-gm-file-name { font-weight: 600; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-gm-file-size { margin-left: auto; color: var(--vy-muted); font-size: 12px; white-space: nowrap; }
    </style>
</x-guest.shell>
