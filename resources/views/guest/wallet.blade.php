{{-- Guest Wallet / Credits (Phase 4c · SG2; guest Phase-4 pixel-diff G3) — DERIVED balance over the append-only
     credit ledger, redeem-a-code, apply to a booking, added/used history. Matched to the wireframe: an
     available-credit HERO + "How credits work" + card sections + motion. Integer money, reconciling.
     ⚠️ HONEST (#39): the wireframe's REFERRAL block ("Copy invite link · 1 friend joined · Preview what friends
     see") has NO backend (no invite links / friend tracking — a referral BONUS can post to the ledger, but there
     is no invite PROGRAM) → REFUSED as an honest "coming soon". "Apply credit automatically" has no auto-apply
     backend → omitted (credit is applied at checkout / to a booking below). RAW-BLADE RULE: `@` after space/nl/tag. --}}
<x-guest.shell active="wallet" :title="__('guest/wallet.page_title')">
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
            {{-- ⚠️⚠️ WRITTEN, NOT PROMOTED — AND THEREFORE PROVISIONAL. Every sibling screen (Saved,
                 Messages, Notifications, Payments) carries its own name as the hero title; this one had
                 only an eyebrow labelling the figure, so there was no existing text to promote and a
                 screen reader navigating by heading found nothing at all here.
                 ⚠️ "Wallet" is a plain, accurate noun phrase matching the nav item and the shell's own
                 `title` prop — deliberately not branding and not a slogan. ⚠️ THE WORDING IS THE FOUNDER'S
                 TO CHANGE; only the ELEMENT is not. --}}
            <h1 style="font-family:var(--vy-display);font-weight:700;font-size:24px;letter-spacing:-.02em;color:var(--vy-ink);margin:0 0 6px">{{ __('guest/wallet.heading') }}</h1>
            <div style="font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-accent-text)">{{ __('guest/wallet.available_credit') }}</div>
            <div style="font-family:var(--vy-display);font-weight:700;font-size:32px;letter-spacing:-.02em;color:{{ $spendable_minor > 0 ? 'var(--vy-green-text)' : 'var(--vy-ink)' }};margin-top:2px">{{ $spendable }}</div>
            <div style="font-size:12.5px;color:var(--vy-muted);margin-top:2px">{{ __('guest/wallet.available_note') }}</div>
        </div>
    </div>

    {{-- ── Redeem a code ──────────────────────────────────────────────────────────────────────────────── --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .1s both">
        <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/wallet.redeem_heading') }}</div>
        <form method="POST" action="{{ route('guest.wallet.redeem') }}" style="display:flex;gap:8px;flex-wrap:wrap" data-guard-submit>
            @csrf
            <input class="vy-input" type="text" name="code" placeholder="{{ __('guest/wallet.code_placeholder') }}" maxlength="64" aria-label="{{ __('guest/wallet.code_label') }}" style="flex:1;min-width:160px">
            <button type="submit" class="vy-btn">{{ __('guest/wallet.redeem_button') }}</button>
        </form>
    </section>

    {{-- ── Apply to a booking ─────────────────────────────────────────────────────────────────────────── --}}
    @if ($spendable_minor > 0 && count($owed) > 0)
        <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .14s both">
            <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/wallet.apply_heading') }}</div>
            @foreach ($owed as $booking)
                <form method="POST" action="{{ route('guest.wallet.apply', $booking['id']) }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:9px 0;border-bottom:1px solid var(--vy-hairline)" data-guard-submit>
                    @csrf
                    <div style="flex:1;min-width:150px">
                        <div style="font-size:13.5px;color:var(--vy-ink)">{{ $booking['property'] }}</div>
                        <div style="font-size:11.5px;color:var(--vy-faint)">{{ $booking['reference'] }} · {{ __('guest/wallet.owed', ['amount' => $booking['balance']]) }}</div>
                    </div>
                    <input class="vy-input" type="text" inputmode="decimal" autocomplete="off" name="amount" placeholder="0.00"
                           aria-label="{{ __('guest/wallet.apply_amount_label', ['reference' => $booking['reference']]) }}" style="width:96px">
                    <button type="submit" class="vy-btn vy-btn--ghost">{{ __('guest/wallet.apply_button') }}</button>
                </form>
            @endforeach
        </section>
    @endif

    {{-- ── History ("Every credit added and used") ────────────────────────────────────────────────────── --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .18s both">
        <div class="vy-eyebrow" style="margin-bottom:10px">{{ __('guest/wallet.history_heading') }}</div>
        @forelse ($entries as $entry)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid var(--vy-hairline)">
                <div style="min-width:0">
                    <div style="font-size:13.5px;color:var(--vy-ink)">{{ $entry['label'] }}{{ $entry['expired'] ? ' · '.__('guest/wallet.expired') : '' }}</div>
                    <div style="font-size:11.5px;color:var(--vy-faint)">{{ $entry['description'] }}{{ $entry['expires'] && ! $entry['expired'] ? ' · '.__('guest/wallet.expires_on', ['date' => $entry['expires']]) : '' }}</div>
                </div>
                <div style="text-align:right;white-space:nowrap;flex-shrink:0">
                    {{-- ⚠️ THE VISUAL MUST MATCH THE FACT. An EXPIRED credit does not count toward the spendable
                         balance (GuestWalletService::spendableMinor excludes it), but it used to render in the
                         same confident green "+" as a live one — the word "expired" sat in the label while the
                         figure still read as money you have. Expired credit now renders muted + struck, like a
                         debit: the signal agrees with the balance above it. --}}
                    <div style="font-size:14px;font-weight:600;color:{{ $entry['is_debit'] || $entry['expired'] ? 'var(--vy-muted)' : 'var(--vy-green-text)' }}{{ $entry['expired'] ? ';text-decoration:line-through' : '' }}">{{ $entry['signed_amount'] }}</div>
                    <div style="font-size:11px;color:var(--vy-faint)">{{ $entry['date'] }}</div>
                </div>
            </div>
        @empty
            {{-- The empty state repeated the same refund promise the card below used to make — same fix. --}}
            <div style="font-size:13px;color:var(--vy-muted)">{{ __('guest/wallet.history_empty') }}</div>
        @endforelse
    </section>

    {{-- ── How credits work + honest referral/loyalty deferral ────────────────────────────────────────── --}}
    <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .22s both">
        <div class="vy-eyebrow" style="margin-bottom:8px">{{ __('guest/wallet.how_heading') }}</div>
        {{-- ⚠️ G10 — THESE THREE LINES ARE A POLICY STATEMENT, so each one is checked against the engine.
             TWO were false. (a) "Credit comes from refunds, cancelled add-ons and any code you redeem": only
             the CODE has a production writer — GuestWalletService::redeemCode(). Nothing posts a wallet credit
             on a refund (FolioService::refundPayment returns money to the payment method), and
             TYPE_ADD_ON_CANCELLED exists only as a constant + a display label, with no writer anywhere. The
             demo LOOKS right because DemoSeeder posts a refund credit directly. (b) "Most credits are valid for
             12 months" — no 12-month rule exists in any code path: `credit()` defaults expiresAt to NULL (never
             expires) and a promo credit inherits the PROMO's own expiry. The line came from the prototype mock
             ("valid for 12 months from the day they land"), the same shape as the door code's hardcoded "48h"
             that GuestReservationTest already guards against. Neither of the two real credits obeyed it: one
             never expires, the other expires in 60 days — and the page displayed that 60-day date directly
             above the claim. Rewritten to what the code actually does. --}}
        <ul style="margin:0;padding-left:18px;font-size:12.5px;color:var(--vy-muted);line-height:1.7">
            <li>{{ __('guest/wallet.how_source') }}</li>
            <li>{{ __('guest/wallet.how_applied') }}</li>
            <li>{{ __('guest/wallet.how_expiry') }}</li>
        </ul>
        {{-- ⚠️ #39: NO fabricated "invite a friend · copy link · 1 friend joined" program — a referral BONUS can
             land in the ledger, but there is no invite/referral PROGRAM, so it is honestly deferred (not faked). --}}
        <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--vy-hairline);font-size:12.5px;color:var(--vy-faint)">{{ __('guest/wallet.loyalty_soon') }}</div>
    </section>
</x-guest.shell>
