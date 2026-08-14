{{-- Guest Reservation detail (Phase 4b · SG2; guest Phase-4 pixel-diff) — one owned booking, matched to the
     wireframe: a status hero + 4 quick-stat tiles, then a 2-col bento (balance · arrival · enhance · cancellation
     · message · timeline). REAL over the shared backend; guest-isolated in the controller.
     ⚠️ HONEST (#39): NO Apple Pay / saved-card vault / Face-ID (SAQ A — pay by hosted Stripe on Payments); NO
     "~10 min" reply SLA; the door code/wifi are ArrivalPack-gated to the release window (#33 — withheld early).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="trips" :title="'Booking '.$trip['reference']">
    <a class="vy-link" href="{{ route('guest.trips') }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">← All trips</a>

    {{-- ── Hero: status + welcome + CTA + 4 quick-stat tiles ─────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;gap:22px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:24px 26px;margin:12px 0 18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="display:flex;align-items:flex-start;gap:18px;flex:1;min-width:280px">
            <div style="width:56px;height:56px;flex-shrink:0;border-radius:18px;background:linear-gradient(140deg,#E8703A,#C2410C);display:grid;place-items:center;box-shadow:var(--vy-shadow-accent)">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/></svg>
            </div>
            <div style="min-width:0">
                <span class="vy-badge vy-badge--{{ $trip['stage_tone'] }}" style="margin-bottom:9px">{{ $trip['stage_label'] }}</span>
                <div style="font-family:var(--vy-display);font-weight:700;font-size:25px;letter-spacing:-.02em;line-height:1.12;color:var(--vy-ink)">{{ $trip['property'] }}</div>
                @if ($trip['location'] !== '')
                    <div style="font-size:13px;color:var(--vy-muted);margin-top:5px">{{ $trip['location'] }} · hosted with Vyrobel</div>
                @endif
                @if ($active)
                    <a href="{{ route('guest.checkin', $trip['id']) }}" style="display:inline-flex;align-items:center;gap:7px;margin-top:14px;padding:9px 18px;border-radius:30px;background:var(--vy-accent);color:#fff;font-size:12.5px;font-weight:500;text-decoration:none;box-shadow:var(--vy-shadow-accent)">Check in online · 2 min<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a>
                @endif
            </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;flex:1.5;min-width:320px">
            @php
                $tile = 'display:flex;flex-direction:column;align-items:flex-start;text-align:left;padding:13px 14px;border-radius:15px;background:rgba(255,255,255,.55);border:1px solid var(--vy-hairline);text-decoration:none;box-sizing:border-box';
                $tileNum = 'font-family:var(--vy-display);font-weight:600;font-size:19px;letter-spacing:-.02em;color:var(--vy-ink);line-height:1;white-space:nowrap';
                $tileSub = 'font-size:11px;color:var(--vy-muted);margin-top:4px';
                $tileIcon = 'width:28px;height:28px;border-radius:9px;background:rgba(217,102,46,.12);color:#B4400F;display:grid;place-items:center;margin-bottom:9px';
            @endphp
            <a href="#sec-arrival" style="{{ $tile }}">
                <span style="{{ $tileIcon }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg></span>
                <span style="{{ $tileNum }}">{{ $checkInShort }}</span>
                <span style="{{ $tileSub }}">Check-in{{ $arrival['check_in_time'] ? ' · '.$arrival['check_in_time'] : '' }}</span>
            </a>
            <a href="#sec-arrival" style="{{ $tile }}">
                <span style="{{ $tileIcon }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4"/><path d="M14 17l5-5-5-5"/><path d="M19 12H7"/></svg></span>
                <span style="{{ $tileNum }}">{{ $checkOutShort }}</span>
                <span style="{{ $tileSub }}">Check-out{{ $arrival['check_out_time'] ? ' · '.$arrival['check_out_time'] : '' }}</span>
            </a>
            <div style="{{ $tile }}">
                <span style="{{ $tileIcon }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg></span>
                <span style="{{ $tileNum }}">{{ $trip['nights'] }}</span>
                <span style="{{ $tileSub }}">{{ \Illuminate\Support\Str::plural('Night', $trip['nights']) }} · {{ $guests }} {{ \Illuminate\Support\Str::plural('guest', $guests) }}</span>
            </div>
            <a href="#sec-balance" style="{{ $tile }}">
                <span style="{{ $tileIcon }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></span>
                <span style="{{ $tileNum }}">{{ $folio['balance_due'] ? $folio['balance'] : $folio['paid'] }}</span>
                <span style="{{ $tileSub }}">{{ $folio['balance_due'] ? 'Balance due' : 'Balance · settled' }}</span>
            </a>
        </div>
    </div>

    {{-- ── Bento grid (2-col responsive) ─────────────────────────────────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;align-items:start">

        {{-- Balance --}}
        <section id="sec-balance" class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .12s both">
            <div class="vy-eyebrow" style="margin-bottom:12px">Balance</div>
            <div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;font-size:13px;color:var(--vy-muted)"><span>Total charges</span><span>{{ $folio['charges'] }}</span></div>
            <div style="display:flex;justify-content:space-between;gap:10px;padding:4px 0;font-size:13px;color:var(--vy-muted)"><span>Paid</span><span>{{ $folio['paid'] }}</span></div>
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;margin-top:10px;padding-top:12px;border-top:1px solid var(--vy-hairline)">
                <span style="font-family:var(--vy-display);font-size:15px;font-weight:700;color:var(--vy-ink)">{{ $folio['balance_due'] ? 'Remaining balance' : 'Fully settled' }}</span>
                <span style="font-family:var(--vy-display);font-size:18px;font-weight:700;color:{{ $folio['balance_due'] ? 'var(--vy-amber-text)' : 'var(--vy-green-text)' }}">{{ $folio['balance_due'] ? $folio['balance'] : 'Paid in full' }}</span>
            </div>
            @if ($folio['balance_due'])
                <a class="vy-btn" href="{{ route('guest.payments') }}" style="margin-top:14px;width:100%;box-sizing:border-box">Pay your balance →</a>
                <p style="font-size:11.5px;color:var(--vy-faint);margin:10px 0 0">Pay by card securely on Stripe — your card details never touch Vyrobel.</p>
            @else
                <p style="font-size:12.5px;color:var(--vy-muted);margin:12px 0 0">Your stay is fully settled — nothing more to pay.</p>
            @endif
            <div style="margin-top:12px"><a class="vy-link" href="{{ route('guest.receipt', $trip['id']) }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">View receipt &amp; documents →</a></div>
        </section>

        {{-- Arrival info (ArrivalPack self-gates the code/wifi to the window — #33) --}}
        <section id="sec-arrival" class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .16s both">
            <div class="vy-eyebrow" style="margin-bottom:12px">Arrival info</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
                {{-- WAVE 3 — no invented hour (#39). These fell back to "From 15:00" / "By 11:00" when the property
                     had published no time, so a guest was told an arrival hour the operator never set — and the
                     Arrival page beside it correctly showed nothing. ArrivalPack::time() already returns null;
                     the tile is simply omitted rather than filled with a plausible guess. --}}
                @if ($arrival['check_in_time'] !== null)
                    <div style="padding:10px 12px;border-radius:12px;background:var(--vy-wash);border:1px solid var(--vy-hairline)"><div style="font-size:11px;color:var(--vy-muted)">Check-in</div><div style="font-size:14px;font-weight:600;color:var(--vy-ink);margin-top:2px">{{ $arrival['check_in_time'] }}</div></div>
                @endif
                @if ($arrival['check_out_time'] !== null)
                    <div style="padding:10px 12px;border-radius:12px;background:var(--vy-wash);border:1px solid var(--vy-hairline)"><div style="font-size:11px;color:var(--vy-muted)">Check-out</div><div style="font-size:14px;font-weight:600;color:var(--vy-ink);margin-top:2px">{{ $arrival['check_out_time'] }}</div></div>
                @endif
            </div>
            @if ($arrival['released'] && $arrival['door_code'] !== null)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-top:1px solid var(--vy-hairline);font-size:13px"><span style="color:var(--vy-muted)">Door code</span><span style="font-weight:700;font-variant-numeric:tabular-nums;color:var(--vy-ink)">{{ $arrival['door_code'] }}</span></div>
                @if ($arrival['wifi_ssid'] !== null)
                    <div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-top:1px solid var(--vy-hairline);font-size:13px"><span style="color:var(--vy-muted)">Wi-Fi</span><span style="font-weight:600;color:var(--vy-ink)">{{ $arrival['wifi_ssid'] }}</span></div>
                @endif
            @else
                {{-- ⚠️ THE UNLOCK DATE FOLLOWS THE ENGINE, and the promise follows the DATA.
                     This line used to read a hardcoded "48h before check-in" — the PROTOTYPE's mock value
                     ("Door code arrives 48h before check-in"), which happens to equal ArrivalPack::LEAD_DAYS
                     (2 days) today and would silently lie to the guest the moment that constant moved. It now
                     prints the gate's OWN `released_from`, so the screen can never disagree with the window
                     that actually withholds the code. (Same shape as the owner realm's mock-rate docblock: a
                     design literal quoted on the surface whose engine defines the real one.)
                     `has_code` exists precisely so we do not promise a code that no unit carries — outside the
                     window we may say WHEN, but only when there is genuinely something to release. --}}
                <div style="display:flex;align-items:center;gap:9px;padding:11px 13px;border-radius:12px;background:var(--vy-wash);border:1px solid var(--vy-hairline);font-size:12.5px;color:var(--vy-muted)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    @if ($arrival['has_code'])
                        Your door code &amp; Wi-Fi unlock on {{ $arrival['released_from'] }}.
                    @else
                        Your arrival details appear here before check-in.
                    @endif
                </div>
            @endif
            <div style="margin-top:12px"><a class="vy-link" href="{{ route('guest.arrival', $trip['id']) }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">Full arrival info →</a></div>
        </section>

        {{-- Enhance / manage --}}
        @if ($active)
            <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .2s both">
                <div class="vy-eyebrow" style="margin-bottom:6px">Enhance your stay</div>
                <p style="font-size:12.5px;color:var(--vy-muted);margin:0 0 14px">Add extras, request an early check-in, or update your details.</p>
                <div style="display:flex;gap:9px;flex-wrap:wrap">
                    <a class="vy-btn vy-btn--ghost" href="{{ route('guest.extras', $trip['id']) }}" style="text-decoration:none">Add-ons &amp; extras</a>
                    <a class="vy-btn vy-btn--ghost" href="{{ route('guest.manage', $trip['id']) }}" style="text-decoration:none">Manage booking</a>
                </div>
            </section>
        @endif

        {{-- Cancellation --}}
        <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .24s both">
            <div class="vy-eyebrow" style="margin-bottom:8px">Cancellation policy</div>
            @if ($freeUntil !== null)
                <div style="display:flex;align-items:center;gap:9px;font-size:13px;color:var(--vy-ink)"><span style="width:8px;height:8px;border-radius:50%;background:var(--vy-green-text)"></span>Free cancellation until <strong>{{ $freeUntil }}</strong></div>
            @else
                <div style="font-size:13px;color:var(--vy-muted)">Cancellation terms apply to this booking.</div>
            @endif
            @if ($active)
                <div style="margin-top:12px"><a class="vy-link" href="{{ route('guest.manage', $trip['id']) }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">Cancel or change this booking →</a></div>
            @endif
        </section>

        {{-- Message the property (NO fabricated SLA) --}}
        <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .28s both">
            <div class="vy-eyebrow" style="margin-bottom:8px">Questions?</div>
            <p style="font-size:12.5px;color:var(--vy-muted);margin:0 0 12px">Message {{ $trip['property'] }} directly about your stay.</p>
            <a class="vy-btn vy-btn--ghost" href="{{ route('guest.messages') }}" style="text-decoration:none">Message the property</a>
        </section>

        {{-- Timeline --}}
        <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .32s both">
            <div class="vy-eyebrow" style="margin-bottom:12px">Timeline</div>
            @forelse ($timeline as $event)
                <div style="display:flex;gap:11px;padding:6px 0">
                    <span style="flex:none;width:7px;height:7px;border-radius:50%;background:var(--vy-accent);margin-top:6px"></span>
                    <div><div style="font-size:13.5px;color:var(--vy-ink)">{{ $event['label'] }}</div><div style="font-size:11.5px;color:var(--vy-faint)">{{ $event['at'] }}</div></div>
                </div>
            @empty
                <div style="font-size:13px;color:var(--vy-muted)">Nothing to show yet.</div>
            @endforelse
        </section>
    </div>
</x-guest.shell>
