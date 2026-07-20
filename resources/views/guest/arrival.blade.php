{{-- Guest Arrival Info (Phase 4b · SG4) — the arrival pack for ONE owned booking. The door code + wifi are gated
     by the arrival window (ArrivalPack): shown only close to the stay, withheld otherwise — never a fabricated
     code. Directions/times are the property's real data. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" :title="'Arrival · '.$reference">
    <a class="vy-link" href="{{ route('guest.reservation', request()->route('reservation')) }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">← Back to booking</a>

    <h1 class="vy-h1" style="margin-top:10px">Arrival info</h1>
    <p class="vy-lead" style="margin-bottom:16px">Everything you need to get in at {{ $property }} on {{ $checkIn }}.</p>

    {{-- Getting here — real property data --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">Getting here</div>
        @if ($pack['directions'])
            <div style="font-size:13.5px;color:var(--vy-ink);margin-bottom:10px">{{ $pack['directions'] }}</div>
        @else
            <div style="font-size:13px;color:var(--vy-muted);margin-bottom:10px">We’ll share directions before your stay.</div>
        @endif
        <div style="display:flex;gap:22px;font-size:13px">
            @if ($pack['check_in_time'])
                <div><span style="color:var(--vy-faint);font-size:11px">Check-in from</span><br><span style="color:var(--vy-ink)">{{ $pack['check_in_time'] }}</span></div>
            @endif
            @if ($pack['check_out_time'])
                <div><span style="color:var(--vy-faint);font-size:11px">Check-out by</span><br><span style="color:var(--vy-ink)">{{ $pack['check_out_time'] }}</span></div>
            @endif
        </div>
    </section>

    {{-- Access — door code + wifi, released only within the arrival window --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">Getting in</div>

        @if ($pack['released'])
            {{-- Door code --}}
            @if ($pack['door_code'])
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;background:var(--vy-wash);margin-bottom:10px">
                    <div>
                        <div style="font-size:11px;color:var(--vy-faint)">Door code</div>
                        <div style="font-family:var(--vy-display);font-size:22px;font-weight:700;letter-spacing:3px;color:var(--vy-ink)">{{ $pack['door_code'] }}</div>
                    </div>
                    <button type="button" class="vy-btn vy-btn--ghost" data-copy="{{ $pack['door_code'] }}" style="flex:none">Copy</button>
                </div>
            @else
                <div style="font-size:13px;color:var(--vy-muted);margin-bottom:10px">Collect your key at reception when you arrive.</div>
            @endif

            {{-- WiFi --}}
            @if ($pack['wifi_ssid'])
                <div style="padding:12px 14px;border-radius:12px;border:1px solid var(--vy-hairline)">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
                        <div><div style="font-size:11px;color:var(--vy-faint)">WiFi network</div><div style="font-size:14px;color:var(--vy-ink)">{{ $pack['wifi_ssid'] }}</div></div>
                    </div>
                    @if ($pack['wifi_password'])
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:8px;padding-top:8px;border-top:1px solid var(--vy-hairline)">
                            <div><div style="font-size:11px;color:var(--vy-faint)">Password</div><div style="font-size:14px;color:var(--vy-ink);font-family:var(--vy-display)">{{ $pack['wifi_password'] }}</div></div>
                            <button type="button" class="vy-btn vy-btn--ghost" data-copy="{{ $pack['wifi_password'] }}" style="flex:none">Copy</button>
                        </div>
                    @endif
                </div>
            @endif

            @if ($pack['arrival_notes'])
                <div style="font-size:13px;color:var(--vy-muted);margin-top:12px;line-height:1.5">{{ $pack['arrival_notes'] }}</div>
            @endif
        @else
            <div style="display:flex;align-items:center;gap:12px">
                <span style="flex:none;width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:var(--vy-wash);color:var(--vy-muted)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                </span>
                <div>
                    <div style="font-size:13.5px;color:var(--vy-ink)">Your access details appear here closer to arrival</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">The door code and WiFi unlock as your stay approaches — for your security.</div>
                </div>
            </div>
        @endif
    </section>

    {{-- Local recommendations — honestly not built yet (no fabricated content) --}}
    <section class="vy-card">
        <div class="vy-eyebrow" style="margin-bottom:6px">Local recommendations</div>
        <div style="font-size:13px;color:var(--vy-muted)">Our neighbourhood guide is coming soon.</div>
    </section>

    <script>
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-copy');
                if (navigator.clipboard) { navigator.clipboard.writeText(text); }
                var original = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = original; }, 1400);
            });
        });
    </script>
</x-guest.shell>
