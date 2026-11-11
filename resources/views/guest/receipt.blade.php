{{-- Guest Folio Receipt (Phase 4c · SG1; guest Phase-4 pixel-diff G3) — one owned booking's folio + downloadable
     money documents, matched to the wireframe: a document-style receipt header + folio + documents, with motion.
     The on-screen figures come from the SHARED GuestReservationView::folio (the SAME folio the reservation screen
     shows — ONE source, so the receipt can't drift from the folio, #17). A Refund Receipt / Credit Note is offered
     ONLY when a real refund exists — never fabricated. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="payments" :title="__('guest/receipt.page_title', ['reference' => $trip['reference']])">
    <a class="vy-link" href="{{ route('guest.payments') }}" style="font-size:12.5px;color:var(--vy-accent-text);text-decoration:none">{{ __('guest/receipt.back_to_payments') }}</a>

    {{-- ── Receipt header (document style) ────────────────────────────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:24px 26px;margin:12px 0 18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <div style="min-width:0">
                <div style="font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-accent-text)">{{ __('guest/receipt.receipt') }}</div>
                {{-- ⚠️ PROMOTED FROM A <div>, NOT INVENTED. This text was already the page's visible title; only its ELEMENT was wrong, so a screen reader navigating by heading found nothing on this screen. ⚠️ `margin:0` keeps the rendering byte-identical — an <h1> carries default margins a <div> does not. --}}
                <h1 style="font-family:var(--vy-display);font-weight:700;font-size:24px;letter-spacing:-.02em;color:var(--vy-ink);margin-top:3px;margin:0">{{ $trip['property'] }}</h1>
                <div style="font-size:12.5px;color:var(--vy-muted);margin-top:4px">{{ $trip['check_in'] }} <span class="vy-flip-rtl">→</span> {{ $trip['check_out'] }}</div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <div style="font-size:11px;color:var(--vy-faint);letter-spacing:.05em;text-transform:uppercase">{{ __('guest/receipt.reference') }}</div>
                <div style="font-size:14px;font-weight:600;color:var(--vy-ink);font-variant-numeric:tabular-nums">{{ $trip['reference'] }}</div>
                <span class="vy-badge vy-badge--{{ $folio['balance_due'] ? 'pending' : 'confirmed' }}" style="margin-top:8px">{{ $folio['balance_due'] ? __('guest/receipt.badge_balance_due') : __('guest/receipt.badge_balance_settled') }}</span>
            </div>
        </div>
    </div>

    {{-- ── Folio (charges & payments) — from the shared source, reconciling ──────────────────────────── --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .12s both">
        <div class="vy-eyebrow" style="margin-bottom:12px">{{ __('guest/receipt.charges_payments') }}</div>
        @forelse ($folio['lines'] as $line)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:7px 0;border-bottom:1px solid var(--vy-hairline);font-size:13.5px">
                <span style="color:var(--vy-ink)">{{ $line['label'] }}</span>
                <span style="color:var(--vy-ink);font-variant-numeric:tabular-nums">{{ $line['amount'] }}</span>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted);padding-bottom:6px">{{ __('guest/receipt.no_charges') }}</div>
        @endforelse

        <div style="display:flex;justify-content:space-between;gap:10px;padding-top:11px;font-size:13px;color:var(--vy-muted)"><span>{{ __('guest/receipt.subtotal') }}</span><span style="font-variant-numeric:tabular-nums">{{ $folio['charges'] }}</span></div>
        <div style="display:flex;justify-content:space-between;gap:10px;padding-top:4px;font-size:13px;color:var(--vy-muted)"><span>{{ __('guest/receipt.total_paid') }}</span><span style="font-variant-numeric:tabular-nums">{{ $folio['paid'] }}</span></div>
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;margin-top:10px;padding-top:10px;border-top:1px solid var(--vy-hairline)">
            <span style="font-family:var(--vy-display);font-size:15px;font-weight:700;color:var(--vy-ink)">{{ __('guest/receipt.balance') }}</span>
            <span style="font-family:var(--vy-display);font-size:16px;font-weight:700;color:{{ $folio['balance_due'] ? 'var(--vy-amber-text)' : 'var(--vy-green-text)' }}">{{ $folio['balance_due'] ? $folio['balance'] : __('guest/receipt.paid_in_full') }}</span>
        </div>

        @if ($folio['balance_due'])
            <div style="margin-top:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <a class="vy-btn" href="{{ route('guest.payments') }}">{{ __('guest/receipt.pay_balance') }}</a>
                <span style="font-size:11.5px;color:var(--vy-faint)">{{ __('guest/receipt.pay_from_payments') }}</span>
            </div>
        @endif
    </section>

    {{-- ── Downloadable documents ─────────────────────────────────────────────────────────────────────── --}}
    <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .16s both">
        <div class="vy-eyebrow" style="margin-bottom:4px">{{ __('guest/receipt.documents') }}</div>
        <p style="font-size:12px;color:var(--vy-muted);margin:0 0 12px">{{ __('guest/receipt.documents_intro') }}</p>
        <div style="display:flex;flex-direction:column;gap:8px">
            <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.receipt', $reservation) }}" style="justify-content:space-between;text-decoration:none">{{ __('guest/receipt.folio_receipt') }} <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
            <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.invoice', $reservation) }}" style="justify-content:space-between;text-decoration:none">{{ __('guest/receipt.vat_invoice') }} <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
            <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.statement', $reservation) }}" style="justify-content:space-between;text-decoration:none">{{ __('guest/receipt.statement_of_account') }} <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
            @if ($hasRefund)
                <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.refund', $reservation) }}" style="justify-content:space-between;text-decoration:none">{{ __('guest/receipt.refund_receipt') }} <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
                <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.credit-note', $reservation) }}" style="justify-content:space-between;text-decoration:none">{{ __('guest/receipt.credit_note') }} <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
            @endif
        </div>
    </section>
</x-guest.shell>
