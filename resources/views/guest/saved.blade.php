{{-- Guest Saved (Phase 4c · SG4) — the wishlist (saved rooms) + waitlist (join for sold-out dates). Real,
     self-only stores; the notify trigger + auto-hold are deferred honestly. RAW-BLADE RULE: every `@` follows a
     space, newline or tag. --}}
<x-guest.shell active="saved" :title="__('guest/saved.page_title')">
    {{-- Hero --}}
    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:22px 26px;margin-bottom:18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="width:52px;height:52px;flex-shrink:0;border-radius:16px;background:linear-gradient(140deg,#E8703A,#C2410C);display:grid;place-items:center;box-shadow:var(--vy-shadow-accent)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>
        </div>
        <div style="min-width:0">
            {{-- ⚠️ PROMOTED FROM A <div>, NOT INVENTED. This text was already the page's visible title; only its ELEMENT was wrong, so a screen reader navigating by heading found nothing on this screen. ⚠️ `margin:0` keeps the rendering byte-identical — an <h1> carries default margins a <div> does not. --}}
            <h1 style="font-family:var(--vy-display);font-weight:700;font-size:24px;letter-spacing:-.02em;color:var(--vy-ink);margin:0">{{ __('guest/saved.heading') }}</h1>
            <div style="font-size:13px;color:var(--vy-muted);margin-top:3px">{{ __('guest/saved.subheading') }}</div>
        </div>
    </div>

    @if (session('status'))
        <div class="vy-notice" style="margin-bottom:14px">{{ session('status') }}</div>
    @endif

    {{-- Wishlist --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .1s both">
        <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/saved.wishlist_heading') }}</div>
        @forelse ($wishlist as $item)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--vy-hairline)">
                <div>
                    <div style="font-size:14px;color:var(--vy-ink)">{{ $item['room'] }}</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">{{ $item['property'] }}</div>
                </div>
                <form method="POST" action="{{ route('guest.saved.wishlist.remove', $item['id']) }}" style="margin:0" data-guard-submit>
                    @csrf
                    <button type="submit" class="vy-btn vy-btn--ghost">{{ __('common.remove') }}</button>
                </form>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">{{ __('guest/saved.wishlist_empty') }}</div>
        @endforelse
    </section>

    {{-- Waitlist --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/saved.waitlist_heading') }}</div>
        @forelse ($waitlist as $entry)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--vy-hairline)">
                <div>
                    <div style="font-size:14px;color:var(--vy-ink)">{{ $entry['room'] }}@if ($entry['expired']) <span style="font-size:10.5px;color:var(--vy-amber-text)">· {{ __('guest/saved.expired') }}</span>@endif</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">
                        {{ $entry['dates'] }}
                        @if ($entry['notify_similar']) · {{ __('guest/saved.notify_similar_tag') }} @endif
                        @if ($entry['expires'] && ! $entry['expired']) · {{ __('guest/saved.keep_until', ['date' => $entry['expires']]) }} @endif
                    </div>
                </div>
                <div style="display:flex;gap:6px">
                    @if ($entry['expired'])
                        <form method="POST" action="{{ route('guest.saved.waitlist.reconfirm', $entry['id']) }}" style="margin:0" data-guard-submit>@csrf<button type="submit" class="vy-btn">{{ __('guest/saved.reconfirm_button') }}</button></form>
                    @endif
                    <form method="POST" action="{{ route('guest.saved.waitlist.leave', $entry['id']) }}" style="margin:0" data-guard-submit>@csrf<button type="submit" class="vy-btn vy-btn--ghost">{{ __('guest/saved.leave_button') }}</button></form>
                </div>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">{{ __('guest/saved.waitlist_empty') }}</div>
        @endforelse
    </section>

    {{-- Join the waitlist --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/saved.join_heading') }}</div>
        <form method="POST" action="{{ route('guest.saved.waitlist.join') }}" style="display:flex;flex-direction:column;gap:10px" data-guard-submit>
            @csrf
            <select name="room_type_id" required aria-label="{{ __('guest/saved.room_label') }}" style="padding:10px 12px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff">
                <option value="">{{ __('guest/saved.choose_room') }}</option>
                @foreach ($roomTypes as $rt)
                    <option value="{{ $rt['id'] }}">{{ $rt['label'] }}</option>
                @endforeach
            </select>
            <select name="mode" required aria-label="{{ __('guest/saved.flexibility_label') }}" style="padding:10px 12px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff">
                <option value="dates">{{ __('guest/saved.mode_dates') }}</option>
                <option value="month">{{ __('guest/saved.mode_month') }}</option>
            </select>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input type="date" name="check_in" aria-label="{{ __('guest/saved.check_in_label') }}" style="flex:1;min-width:130px;padding:9px 11px;border-radius:10px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:13.5px;background:#fff">
                <input type="date" name="check_out" aria-label="{{ __('guest/saved.check_out_label') }}" style="flex:1;min-width:130px;padding:9px 11px;border-radius:10px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:13.5px;background:#fff">
                <input type="month" name="flexible_month" aria-label="{{ __('guest/saved.flexible_month_label') }}" style="flex:1;min-width:130px;padding:9px 11px;border-radius:10px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:13.5px;background:#fff">
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--vy-muted)"><input type="checkbox" name="notify_similar" value="1"> {{ __('guest/saved.notify_similar_label') }}</label>
            <div><button type="submit" class="vy-btn">{{ __('guest/saved.join_button') }}</button></div>
        </form>
        <div style="font-size:11.5px;color:var(--vy-faint);margin-top:10px">{{ __('guest/saved.join_note') }}</div>
    </section>
</x-guest.shell>
