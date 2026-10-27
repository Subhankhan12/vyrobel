{{-- Guest Arrival Info (Phase 4b · SG4) — the arrival pack for ONE owned booking. The door code + wifi are gated
     by the arrival window (ArrivalPack): shown only close to the stay, withheld otherwise — never a fabricated
     code. Directions/times are the property's real data. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" :title="__('guest/arrival.page_title', ['reference' => $reference])">
    <a class="vy-link" href="{{ route('guest.reservation', $reservationId) }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">{{ __('guest/arrival.back_to_booking') }}</a>

    <div class="vy-eyebrow" style="margin-top:12px;color:var(--vy-accent-text)">{{ __('guest/arrival.eyebrow') }}</div>
    <h1 class="vy-h1" style="margin-top:4px">{{ __('guest/arrival.heading', ['property' => $property, 'first_name' => $firstName]) }}</h1>
    <p class="vy-lead" style="margin-bottom:12px">{{ __('guest/arrival.lead', ['date' => $checkIn]) }}</p>
    <a class="vy-btn vy-btn--ghost" href="{{ route('guest.arrival.pdf', $reservationId) }}" style="display:inline-flex;align-items:center;gap:7px;margin-bottom:16px">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 21h16"/></svg>
        {{ __('guest/arrival.save_printable_guide') }}
    </a>

    {{-- Getting here — real property data --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">{{ __('guest/arrival.getting_here') }}</div>
        @if ($pack['directions'])
            <div style="font-size:13.5px;color:var(--vy-ink);margin-bottom:10px">{{ $pack['directions'] }}</div>
        @else
            <div style="font-size:13px;color:var(--vy-muted);margin-bottom:10px">{{ __('guest/arrival.directions_pending') }}</div>
        @endif
        <div style="display:flex;gap:22px;font-size:13px">
            @if ($pack['check_in_time'])
                <div><span style="color:var(--vy-faint);font-size:11px">{{ __('guest/arrival.check_in_from') }}</span><br><span style="color:var(--vy-ink)">{{ $pack['check_in_time'] }}</span></div>
            @endif
            @if ($pack['check_out_time'])
                <div><span style="color:var(--vy-faint);font-size:11px">{{ __('guest/arrival.check_out_by') }}</span><br><span style="color:var(--vy-ink)">{{ $pack['check_out_time'] }}</span></div>
            @endif
        </div>
    </section>

    {{-- Access — door code + wifi, released only within the arrival window --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">{{ __('guest/arrival.getting_in') }}</div>

        @if ($pack['released'])
            {{-- Door code --}}
            @if ($pack['door_code'])
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;background:var(--vy-wash);margin-bottom:10px">
                    <div>
                        <div style="font-size:11px;color:var(--vy-faint)">{{ __('guest/arrival.door_code') }}</div>
                        <div style="font-family:var(--vy-display);font-size:22px;font-weight:700;letter-spacing:3px;color:var(--vy-ink)">{{ $pack['door_code'] }}</div>
                    </div>
                    <button type="button" class="vy-btn vy-btn--ghost" data-copy="{{ $pack['door_code'] }}" style="flex:none">{{ __('guest/arrival.copy') }}</button>
                </div>
            @else
                <div style="font-size:13px;color:var(--vy-muted);margin-bottom:10px">{{ __('guest/arrival.collect_key') }}</div>
            @endif

            {{-- WiFi --}}
            @if ($pack['wifi_ssid'])
                <div style="padding:12px 14px;border-radius:12px;border:1px solid var(--vy-hairline)">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
                        <div><div style="font-size:11px;color:var(--vy-faint)">{{ __('guest/arrival.wifi_network') }}</div><div style="font-size:14px;color:var(--vy-ink)">{{ $pack['wifi_ssid'] }}</div></div>
                    </div>
                    @if ($pack['wifi_password'])
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:8px;padding-top:8px;border-top:1px solid var(--vy-hairline)">
                            <div><div style="font-size:11px;color:var(--vy-faint)">{{ __('guest/arrival.wifi_password') }}</div><div style="font-size:14px;color:var(--vy-ink);font-family:var(--vy-display)">{{ $pack['wifi_password'] }}</div></div>
                            <button type="button" class="vy-btn vy-btn--ghost" data-copy="{{ $pack['wifi_password'] }}" style="flex:none">{{ __('guest/arrival.copy') }}</button>
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
                    <div style="font-size:13.5px;color:var(--vy-ink)">{{ __('guest/arrival.access_pending') }}</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">{{ $pack['has_code'] ? __('guest/arrival.access_unlocks_on', ['date' => $pack['released_from']]) : __('guest/arrival.access_not_set') }}</div>
                </div>
            </div>
        @endif
    </section>

    {{-- Local recommendations — honestly not built yet (no fabricated content) --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:6px">{{ __('guest/arrival.local_recommendations') }}</div>
        <div style="font-size:13px;color:var(--vy-muted)">{{ __('guest/arrival.guide_coming_soon') }}</div>
    </section>

    {{-- Message host — the REAL guest↔operator channel (name-only; no fabricated host/SLA/phone) --}}
    <section class="vy-card" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
        <div>
            <div style="font-size:14px;color:var(--vy-ink);font-weight:500">{{ __('guest/arrival.need_anything') }}</div>
            <div style="font-size:12px;color:var(--vy-muted);margin-top:2px">{{ __('guest/arrival.message_the_team') }}</div>
        </div>
        <a class="vy-btn vy-btn--accent" href="{{ route('guest.messages') }}" style="flex:none">{{ __('guest/arrival.message_button') }}</a>
    </section>

    <script>
        document.querySelectorAll('[data-copy]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-copy');
                if (navigator.clipboard) { navigator.clipboard.writeText(text); }
                var original = btn.textContent;
                btn.textContent = @js(__('guest/arrival.copied'));
                setTimeout(function () { btn.textContent = original; }, 1400);
            });
        });
    </script>
</x-guest.shell>
