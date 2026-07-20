{{-- Guest Saved (Phase 4c · SG4) — the wishlist (saved rooms) + waitlist (join for sold-out dates). Real,
     self-only stores; the notify trigger + auto-hold are deferred honestly. RAW-BLADE RULE: every `@` follows a
     space, newline or tag. --}}
<x-guest.shell active="saved" title="Saved">
    <h1 class="vy-h1">Saved</h1>
    <p class="vy-lead">Rooms you love, and waitlists for the dates you want.</p>

    @if (session('status'))
        <div class="vy-notice" style="margin-bottom:14px">{{ session('status') }}</div>
    @endif

    {{-- Wishlist --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:10px">Wishlist</div>
        @forelse ($wishlist as $item)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--vy-hairline)">
                <div>
                    <div style="font-size:14px;color:var(--vy-ink)">{{ $item['room'] }}</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">{{ $item['property'] }}</div>
                </div>
                <form method="POST" action="{{ route('guest.saved.wishlist.remove', $item['id']) }}" style="margin:0">
                    @csrf
                    <button type="submit" class="vy-btn vy-btn--ghost">Remove</button>
                </form>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">Nothing saved yet — tap “Save” on a room while browsing to keep it here.</div>
        @endforelse
    </section>

    {{-- Waitlist --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:10px">Waitlist</div>
        @forelse ($waitlist as $entry)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--vy-hairline)">
                <div>
                    <div style="font-size:14px;color:var(--vy-ink)">{{ $entry['room'] }}@if ($entry['expired']) <span style="font-size:10.5px;color:var(--vy-amber)">· expired</span>@endif</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">
                        {{ $entry['dates'] }}
                        @if ($entry['notify_similar']) · notify me of similar @endif
                        @if ($entry['expires'] && ! $entry['expired']) · keep until {{ $entry['expires'] }} @endif
                    </div>
                </div>
                <div style="display:flex;gap:6px">
                    @if ($entry['expired'])
                        <form method="POST" action="{{ route('guest.saved.waitlist.reconfirm', $entry['id']) }}" style="margin:0">@csrf<button type="submit" class="vy-btn">Re-confirm</button></form>
                    @endif
                    <form method="POST" action="{{ route('guest.saved.waitlist.leave', $entry['id']) }}" style="margin:0">@csrf<button type="submit" class="vy-btn vy-btn--ghost">Leave</button></form>
                </div>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">You’re not on any waitlist. Join one below for sold-out dates.</div>
        @endforelse
    </section>

    {{-- Join the waitlist --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:10px">Join the waitlist</div>
        <form method="POST" action="{{ route('guest.saved.waitlist.join') }}" style="display:flex;flex-direction:column;gap:10px">
            @csrf
            <select name="room_type_id" required style="padding:10px 12px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff">
                <option value="">Choose a room…</option>
                @foreach ($roomTypes as $rt)
                    <option value="{{ $rt['id'] }}">{{ $rt['label'] }}</option>
                @endforeach
            </select>
            <select name="mode" required style="padding:10px 12px;border-radius:12px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:14px;background:#fff">
                <option value="dates">Exact dates</option>
                <option value="month">Anytime in a month</option>
            </select>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input type="date" name="check_in" style="flex:1;min-width:130px;padding:9px 11px;border-radius:10px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:13.5px;background:#fff">
                <input type="date" name="check_out" style="flex:1;min-width:130px;padding:9px 11px;border-radius:10px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:13.5px;background:#fff">
                <input type="month" name="flexible_month" style="flex:1;min-width:130px;padding:9px 11px;border-radius:10px;border:1px solid var(--vy-glass-border);font-family:var(--vy-sans);font-size:13.5px;background:#fff">
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--vy-muted)"><input type="checkbox" name="notify_similar" value="1"> Notify me about similar rooms too</label>
            <div><button type="submit" class="vy-btn">Join waitlist</button></div>
        </form>
        <div style="font-size:11.5px;color:var(--vy-faint);margin-top:10px">If a spot opens we’ll email you first. Text alerts and auto-holding a room are coming soon.</div>
    </section>
</x-guest.shell>
