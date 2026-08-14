{{-- Guest Payments hub (Phase 4c · SG1; guest Phase-4 pixel-diff G2) — balance across the guest's own bookings +
     payment history, matched to the wireframe: a status hero + 3 REAL stat tiles, then a 2-col bento (bookings +
     payment activity · payment methods + receipts). All figures real via FolioTotals (#17, reconciling).
     ⚠️ HONEST (#39): paying is REAL hosted Stripe Checkout (card never touches Vyrobel, SAQ A, webhook-settled);
     the wireframe's "Card on file · Expires 04/27" saved-card + Apple Pay are REFUSED — an honest no-stored-card
     state (Vyrobel stores no card). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="payments" title="Payments">
    @php $due = $overview['total_balance_minor'] > 0; @endphp

    @if (session('pay_notice'))
        <div class="vy-card" style="margin-bottom:12px;border-left:3px solid var(--vy-accent)"><div style="font-size:13px;color:var(--vy-ink)">{{ session('pay_notice') }}</div></div>
    @endif
    @if (session('pay_error'))
        <div class="vy-card" style="margin-bottom:12px;border-left:3px solid var(--vy-accent)"><div style="font-size:13px;color:var(--vy-accent-text)">{{ session('pay_error') }}</div></div>
    @endif

    {{-- ⚠️ THE RETURN FROM HOSTED CHECKOUT — the §N.8d UNDERSTATING direction, on the money surface.
         Stripe sends the guest back to `?paid=1` on success, but the folio only settles on the
         `payment_intent.succeeded` WEBHOOK — so at this instant the balance is still legitimately due. The page
         used to read nothing at all from that return: the guest came back from a completed checkout, saw their
         balance unchanged and no acknowledgement whatsoever, and could reasonably conclude it had failed and pay
         again. (The deterministic idempotency key protects the money; the impression was still false.)

         ⚠️ It must NOT say "paid". `?paid=1` is just a URL — anyone can type it, and even a genuine return
         proves only that Stripe's checkout completed, never that the webhook has landed. So this asserts
         nothing about THIS guest or THIS payment: it states the one thing that is unconditionally true, which
         is also exactly what the returning guest needs to know. Overstating here would be the fabricated-settled
         trap on the surface where it matters most. --}}
    @if (request()->query('paid') !== null)
        <div class="vy-card" style="margin-bottom:12px;border-left:3px solid var(--vy-amber-text)">
            <div style="font-size:13px;color:var(--vy-ink)">A card payment can take a few moments to confirm with your bank. Your balance below updates as soon as it does — there's no need to pay again.</div>
        </div>
    @endif

    {{-- ── Hero: status + 3 real stat tiles ─────────────────────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;gap:22px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:24px 26px;margin-bottom:18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="flex:1;min-width:240px">
            <span class="vy-badge vy-badge--{{ $due ? 'pending' : 'confirmed' }}">{{ $due ? 'Balance due' : 'Paid in full · nothing due' }}</span>
            <div style="font-family:var(--vy-display);font-weight:700;font-size:26px;letter-spacing:-.02em;color:var(--vy-ink);margin-top:9px">Payments</div>
            <div style="font-size:13px;color:var(--vy-muted);margin-top:3px">Your balance and payment history across your stays.</div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;flex:1.4;min-width:300px">
            @php $ptile = 'padding:13px 14px;border-radius:15px;background:rgba(255,255,255,.55);border:1px solid var(--vy-hairline)'; $pnum = 'font-family:var(--vy-display);font-weight:600;font-size:20px;letter-spacing:-.02em;color:var(--vy-ink);font-variant-numeric:tabular-nums;line-height:1'; $psub = 'font-size:11px;color:var(--vy-muted);margin-top:5px;display:block'; @endphp
            <div style="{{ $ptile }}"><span style="{{ $pnum }}">{{ $overview['total_charged'] }}</span><span style="{{ $psub }}">Total charged</span></div>
            <div style="{{ $ptile }}"><span style="{{ $pnum }}">{{ $overview['total_paid'] }}</span><span style="{{ $psub }}">Paid to date</span></div>
            <div style="{{ $ptile }}"><span style="{{ $pnum }};color:{{ $due ? 'var(--vy-amber-text)' : 'var(--vy-green-text)' }}">{{ $due ? $overview['total_balance'] : '€0.00' }}</span><span style="{{ $psub }}">Balance due</span></div>
            {{-- WAVE 3 — the REAL refunded total, shown only when there IS one. "Paid to date" is net of
                 refunds, so without this a refunded guest saw a smaller Paid figure and nothing explaining it.
                 No refund ⇒ no tile, rather than a noisy €0.00. --}}
            @if ($overview['total_refunded_minor'] > 0)
                <div style="{{ $ptile }}"><span style="{{ $pnum }}">{{ $overview['total_refunded'] }}</span><span style="{{ $psub }}">Refunded to you</span></div>
            @endif
        </div>
    </div>

    {{-- ── Bento (2-col) ────────────────────────────────────────────────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;align-items:start">

        {{-- LEFT: bookings + payment activity --}}
        <div style="display:flex;flex-direction:column;gap:16px">
            <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .12s both">
                <div class="vy-eyebrow" style="margin-bottom:10px">Your bookings</div>
                @forelse ($overview['bookings'] as $booking)
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid var(--vy-hairline)">
                        <div style="min-width:0">
                            <div style="font-size:14px;color:var(--vy-ink)">{{ $booking['property'] }}</div>
                            <div style="font-size:11.5px;color:var(--vy-faint)">{{ $booking['stay'] }} · {{ $booking['reference'] }}</div>
                        </div>
                        <div style="text-align:right;flex-shrink:0">
                            <div style="font-size:14px;font-weight:600;color:{{ $booking['balance_due'] ? 'var(--vy-amber-text)' : 'var(--vy-green-text)' }}">{{ $booking['balance_due'] ? $booking['balance'] : 'Paid' }}</div>
                            @if ($booking['balance_due'])
                                {{-- 🔒 Pay by card — hosted Stripe Checkout (card never touches Vyrobel); settles on the webhook. --}}
                                <form method="POST" action="{{ route('guest.payments.pay', $booking['id']) }}" style="margin:5px 0 0" data-guard-submit>
                                    @csrf
                                    <x-busy-btn class="vy-btn vy-btn--accent" style="padding:5px 12px;font-size:12px" busy-label="Redirecting…">Pay by card</x-busy-btn>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="vy-empty">No bookings yet.</div>
                @endforelse
            </section>

            <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .16s both">
                <div class="vy-eyebrow" style="margin-bottom:10px">Payment activity</div>
                @forelse ($overview['history'] as $entry)
                    <div style="display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px solid var(--vy-hairline);font-size:13px">
                        <span style="color:var(--vy-ink);min-width:0">{{ $entry['label'] }}<span style="color:var(--vy-faint)"> · {{ $entry['reference'] }}</span></span>
                        <span style="color:var(--vy-ink);white-space:nowrap;flex-shrink:0">{{ $entry['amount'] }} <span style="color:var(--vy-faint);font-size:11.5px">{{ $entry['date'] }}</span></span>
                    </div>
                @empty
                    <div style="font-size:13px;color:var(--vy-muted)">No payments recorded yet.</div>
                @endforelse
                <div style="display:flex;justify-content:space-between;gap:10px;margin-top:12px;padding-top:12px;border-top:1px solid var(--vy-hairline);font-size:13px;color:var(--vy-muted)"><span>Total charged</span><span>{{ $overview['total_charged'] }}</span></div>
                <div style="display:flex;justify-content:space-between;gap:10px;padding-top:4px;font-size:13px;color:var(--vy-muted)"><span>Net paid</span><span>{{ $overview['total_paid'] }}</span></div>
            </section>
        </div>

        {{-- RIGHT sidebar: payment methods (honest empty) + receipts --}}
        <div style="display:flex;flex-direction:column;gap:16px">
            <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .2s both">
                <div class="vy-eyebrow" style="margin-bottom:10px">Payment methods</div>
                {{-- ⚠️ #39/#41/#28: Vyrobel stores NO card — no "Card on file · Expires 04/27", no Apple Pay. An
                     honest no-stored-card state; you enter your card on Stripe's hosted page each time you pay. --}}
                <div style="display:flex;align-items:flex-start;gap:11px;padding:12px 13px;border-radius:12px;background:var(--vy-wash);border:1px solid var(--vy-hairline)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    <div style="font-size:12.5px;color:var(--vy-muted)">No card is stored on your account. You enter your card securely on Stripe each time you pay — <strong style="color:var(--vy-ink)">your card details never touch Vyrobel</strong>.</div>
                </div>
            </section>

            <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .24s both">
                <div class="vy-eyebrow" style="margin-bottom:10px">Receipts &amp; invoices</div>
                @forelse ($overview['bookings'] as $booking)
                    <a href="{{ route('guest.receipt', $booking['id']) }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid var(--vy-hairline);text-decoration:none">
                        <span style="min-width:0"><span style="display:block;font-size:13px;font-weight:500;color:var(--vy-ink)">Receipt</span><span style="display:block;font-size:11px;color:var(--vy-faint);font-variant-numeric:tabular-nums">{{ $booking['reference'] }} · {{ $booking['balance_due'] ? 'balance due' : 'paid in full' }}</span></span>
                        <span style="font-size:12px;color:var(--vy-accent-text);flex-shrink:0">View →</span>
                    </a>
                @empty
                    <div style="font-size:13px;color:var(--vy-muted)">No receipts yet.</div>
                @endforelse
            </section>
        </div>
    </div>
</x-guest.shell>
