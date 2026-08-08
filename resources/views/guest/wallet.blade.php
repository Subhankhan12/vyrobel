{{-- Guest Wallet / Credits (Phase 4c · SG2; guest Phase-4 pixel-diff G3) — DERIVED balance over the append-only
     credit ledger, redeem-a-code, apply to a booking, added/used history. Matched to the wireframe: an
     available-credit HERO + "How credits work" + card sections + motion. Integer money, reconciling.
     ⚠️ HONEST (#39): the wireframe's REFERRAL block ("Copy invite link · 1 friend joined · Preview what friends
     see") has NO backend (no invite links / friend tracking — a referral BONUS can post to the ledger, but there
     is no invite PROGRAM) → REFUSED as an honest "coming soon". "Apply credit automatically" has no auto-apply
     backend → omitted (credit is applied at checkout / to a booking below). RAW-BLADE RULE: `@` after space/nl/tag. --}}
<x-guest.shell active="wallet" title="Wallet">
    @if (session('status'))
        <div class="vy-notice" style="margin-bottom:14px">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="vy-notice" style="margin-bottom:14px;background:rgba(180,68,58,.10);border-color:rgba(180,68,58,.3);border-left-color:rgb(150,50,42)">{{ session('error') }}</div>
    @endif

    {{-- ── Hero: available credit ─────────────────────────────────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;gap:22px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:24px 26px;margin-bottom:18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="width:56px;height:56px;flex-shrink:0;border-radius:18px;background:linear-gradient(140deg,#E8703A,#C2410C);display:grid;place-items:center;box-shadow:var(--vy-shadow-accent)">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="13" rx="2.5"/><path d="M2 10h20"/><path d="M16 15h3"/></svg>
        </div>
        <div style="flex:1;min-width:200px">
            <div style="font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-accent)">Available credit</div>
            <div style="font-family:var(--vy-display);font-weight:700;font-size:32px;letter-spacing:-.02em;color:{{ $spendable_minor > 0 ? 'rgb(70,120,60)' : 'var(--vy-ink)' }};margin-top:2px">{{ $spendable }}</div>
            <div style="font-size:12.5px;color:var(--vy-muted);margin-top:2px">Across all your Vyrobel stays · applied at checkout. Expired credit isn’t included.</div>
        </div>
    </div>

    {{-- ── Redeem a code ──────────────────────────────────────────────────────────────────────────────── --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .1s both">
        <div class="vy-eyebrow" style="margin-bottom:10px">Redeem a code</div>
        <form method="POST" action="{{ route('guest.wallet.redeem') }}" style="display:flex;gap:8px;flex-wrap:wrap" data-guard-submit>
            @csrf
            <input class="vy-input" type="text" name="code" placeholder="Enter a code" maxlength="64" style="flex:1;min-width:160px">
            <button type="submit" class="vy-btn">Redeem</button>
        </form>
    </section>

    {{-- ── Apply to a booking ─────────────────────────────────────────────────────────────────────────── --}}
    @if ($spendable_minor > 0 && count($owed) > 0)
        <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .14s both">
            <div class="vy-eyebrow" style="margin-bottom:10px">Apply to a booking</div>
            @foreach ($owed as $booking)
                <form method="POST" action="{{ route('guest.wallet.apply', $booking['id']) }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:9px 0;border-bottom:1px solid var(--vy-hairline)" data-guard-submit>
                    @csrf
                    <div style="flex:1;min-width:150px">
                        <div style="font-size:13.5px;color:var(--vy-ink)">{{ $booking['property'] }}</div>
                        <div style="font-size:11.5px;color:var(--vy-faint)">{{ $booking['reference'] }} · {{ $booking['balance'] }} owed</div>
                    </div>
                    <input class="vy-input" type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" style="width:96px">
                    <button type="submit" class="vy-btn vy-btn--ghost">Apply</button>
                </form>
            @endforeach
        </section>
    @endif

    {{-- ── History ("Every credit added and used") ────────────────────────────────────────────────────── --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .18s both">
        <div class="vy-eyebrow" style="margin-bottom:10px">Every credit added &amp; used</div>
        @forelse ($entries as $entry)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid var(--vy-hairline)">
                <div style="min-width:0">
                    <div style="font-size:13.5px;color:var(--vy-ink)">{{ $entry['label'] }}{{ $entry['expired'] ? ' · expired' : '' }}</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">{{ $entry['description'] }}{{ $entry['expires'] && ! $entry['expired'] ? ' · expires '.$entry['expires'] : '' }}</div>
                </div>
                <div style="text-align:right;white-space:nowrap;flex-shrink:0">
                    <div style="font-size:14px;font-weight:600;color:{{ $entry['is_debit'] ? 'var(--vy-muted)' : 'rgb(70,120,60)' }}">{{ $entry['signed_amount'] }}</div>
                    <div style="font-size:11px;color:var(--vy-faint)">{{ $entry['date'] }}</div>
                </div>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted)">No credit yet — redeem a code or check back after a refund.</div>
        @endforelse
    </section>

    {{-- ── How credits work + honest referral/loyalty deferral ────────────────────────────────────────── --}}
    <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .22s both">
        <div class="vy-eyebrow" style="margin-bottom:8px">How credits work</div>
        <ul style="margin:0;padding-left:18px;font-size:12.5px;color:var(--vy-muted);line-height:1.7">
            <li>Credit comes from refunds, cancelled add-ons and any code you redeem.</li>
            <li>It’s applied to your bookings at checkout, or to an outstanding balance above.</li>
            <li>Most credits are valid for 12 months — the expiry shows on each entry.</li>
        </ul>
        {{-- ⚠️ #39: NO fabricated "invite a friend · copy link · 1 friend joined" program — a referral BONUS can
             land in the ledger, but there is no invite/referral PROGRAM, so it is honestly deferred (not faked). --}}
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--vy-hairline);font-size:12.5px;color:var(--vy-faint)">Loyalty tiers and a refer-a-friend programme are coming soon.</div>
    </section>
</x-guest.shell>
