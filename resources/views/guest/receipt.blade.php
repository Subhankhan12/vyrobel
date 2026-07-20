{{-- Guest Folio Receipt (Phase 4c · SG1) — one owned booking's folio + downloadable money documents (real, via
     PdfService, reconciling). Refund Receipt / Credit Note appear only when a real refund exists. RAW-BLADE RULE:
     every `@` follows a space, newline or tag. --}}
<x-guest.shell active="payments" :title="'Receipt · '.$trip['reference']">
    <a class="vy-link" href="{{ route('guest.payments') }}" style="font-size:12.5px;color:var(--vy-accent);text-decoration:none">← Payments</a>

    <h1 class="vy-h1" style="margin-top:10px">Receipt</h1>
    <p class="vy-lead">{{ $trip['property'] }} · {{ $trip['check_in'] }} → {{ $trip['check_out'] }} · {{ $trip['reference'] }}</p>

    {{-- Folio --}}
    <section class="vy-card" style="margin-bottom:14px">
        <div class="vy-eyebrow" style="margin-bottom:12px">Charges &amp; payments</div>
        @forelse ($folio['lines'] as $line)
            <div style="display:flex;justify-content:space-between;gap:10px;padding:7px 0;border-bottom:1px solid var(--vy-hairline);font-size:13.5px">
                <span style="color:var(--vy-ink)">{{ $line['label'] }}</span>
                <span style="color:var(--vy-ink)">{{ $line['amount'] }}</span>
            </div>
        @empty
            <div style="font-size:13px;color:var(--vy-muted);padding-bottom:6px">No charges posted yet.</div>
        @endforelse

        <div style="display:flex;justify-content:space-between;gap:10px;padding-top:11px;font-size:13px;color:var(--vy-muted)">
            <span>Total charges</span><span>{{ $folio['charges'] }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;gap:10px;padding-top:4px;font-size:13px;color:var(--vy-muted)">
            <span>Paid</span><span>{{ $folio['paid'] }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;margin-top:10px;padding-top:10px;border-top:1px solid var(--vy-hairline)">
            <span style="font-family:var(--vy-display);font-size:15px;font-weight:700;color:var(--vy-ink)">Balance</span>
            <span style="font-family:var(--vy-display);font-size:16px;font-weight:700;color:{{ $folio['balance_due'] ? 'var(--vy-amber)' : 'rgb(70,120,60)' }}">{{ $folio['balance_due'] ? $folio['balance'] : 'Paid in full' }}</span>
        </div>

        @if ($folio['balance_due'])
            <div style="margin-top:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                <span class="vy-btn" style="opacity:.55;cursor:not-allowed" title="Coming soon">Settle · Soon</span>
                <span style="font-size:11.5px;color:var(--vy-faint)">Online payment is coming — for now, settle on arrival.</span>
            </div>
        @endif
    </section>

    {{-- Downloadable documents --}}
    <section class="vy-card">
        <div class="vy-eyebrow" style="margin-bottom:12px">Documents</div>
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
