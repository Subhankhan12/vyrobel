{{-- Guest Folio Receipt (Phase 4c · SG1; guest Phase-4 pixel-diff G3) — one owned booking's folio + downloadable
     money documents, matched to the wireframe: a document-style receipt header + folio + documents, with motion.
     The on-screen figures come from the SHARED GuestReservationView::folio (the SAME folio the reservation screen
     shows — ONE source, so the receipt can't drift from the folio, #17). A Refund Receipt / Credit Note is offered
     ONLY when a real refund exists — never fabricated. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="payments" :title="'Receipt · '.$trip['reference']">
    <a class="vy-link" href="{{ route('guest.payments') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">← Payments</a>

    {{-- ── Receipt header (document style) ────────────────────────────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:24px 26px;margin:12px 0 18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <div style="min-width:0">
                <div style="font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-accent)">Receipt</div>
                <div style="font-family:var(--vy-display);font-weight:700;font-size:24px;letter-spacing:-.02em;color:var(--vy-ink);margin-top:3px">{{ $trip['property'] }}</div>
                <div style="font-size:12.5px;color:var(--vy-muted);margin-top:4px">{{ $trip['check_in'] }} → {{ $trip['check_out'] }}</div>
            </div>
            <div style="text-align:right;flex-shrink:0">
                <div style="font-size:11px;color:var(--vy-faint);letter-spacing:.05em;text-transform:uppercase">Reference</div>
                <div style="font-size:14px;font-weight:600;color:var(--vy-ink);font-variant-numeric:tabular-nums">{{ $trip['reference'] }}</div>
                <span class="vy-badge vy-badge--{{ $folio['balance_due'] ? 'pending' : 'confirmed' }}" style="margin-top:8px">{{ $folio['balance_due'] ? 'Balance due' : 'Balance settled' }}</span>
            </div>
        </div>
    </div>

    {{-- ── Folio (charges & payments) — from the shared source, reconciling ──────────────────────────── --}}
    <section class="vy-card" style="margin-bottom:14px;animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .12s both">
        <div class="vy-eyebrow" style="margin-bottom:12px">Charges &amp; payments</div>
        @forelse ($folio['lines'] as $line)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:7px 0;border-bottom:1px solid var(--vy-hairline);font-size:13.5px">
                <span style="color:var(--vy-ink)">{{ $line['label'] }}</span>
                <span style="color:var(--vy-ink);font-variant-numeric:tabular-nums">{{ $line['amount'] }}</span>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted);padding-bottom:6px">No charges posted yet.</div>
        @endforelse

        <div style="display:flex;justify-content:space-between;gap:10px;padding-top:11px;font-size:13px;color:var(--vy-muted)"><span>Subtotal (total charges)</span><span style="font-variant-numeric:tabular-nums">{{ $folio['charges'] }}</span></div>
        <div style="display:flex;justify-content:space-between;gap:10px;padding-top:4px;font-size:13px;color:var(--vy-muted)"><span>Total paid</span><span style="font-variant-numeric:tabular-nums">{{ $folio['paid'] }}</span></div>
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;margin-top:10px;padding-top:10px;border-top:1px solid var(--vy-hairline)">
            <span style="font-family:var(--vy-display);font-size:15px;font-weight:700;color:var(--vy-ink)">Balance</span>
            <span style="font-family:var(--vy-display);font-size:16px;font-weight:700;color:{{ $folio['balance_due'] ? 'var(--vy-amber)' : 'rgb(70,120,60)' }}">{{ $folio['balance_due'] ? $folio['balance'] : 'Paid in full' }}</span>
        </div>

        @if ($folio['balance_due'])
            <div style="margin-top:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <a class="vy-btn" href="{{ route('guest.payments') }}">Pay your balance →</a>
                <span style="font-size:11.5px;color:var(--vy-faint)">Pay by card from your Payments page — securely on Stripe.</span>
            </div>
        @endif
    </section>

    {{-- ── Downloadable documents ─────────────────────────────────────────────────────────────────────── --}}
    <section class="vy-card" style="animation:vyFadeUp .45s cubic-bezier(.22,.61,.36,1) .16s both">
        <div class="vy-eyebrow" style="margin-bottom:4px">Documents</div>
        <p style="font-size:12px;color:var(--vy-muted);margin:0 0 12px">Need a VAT invoice? Download it below — every document is generated from this folio.</p>
        <div style="display:flex;flex-direction:column;gap:8px">
            <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.receipt', $reservation) }}" style="justify-content:space-between;text-decoration:none">Folio receipt <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
            <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.invoice', $reservation) }}" style="justify-content:space-between;text-decoration:none">VAT invoice <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
            <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.statement', $reservation) }}" style="justify-content:space-between;text-decoration:none">Statement of account <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
            @if ($hasRefund)
                <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.refund', $reservation) }}" style="justify-content:space-between;text-decoration:none">Refund receipt <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
                <a class="vy-btn vy-btn--ghost" href="{{ route('guest.doc.credit-note', $reservation) }}" style="justify-content:space-between;text-decoration:none">Credit note <span style="font-size:11px;color:var(--vy-faint)">PDF</span></a>
            @endif
        </div>
    </section>
</x-guest.shell>
