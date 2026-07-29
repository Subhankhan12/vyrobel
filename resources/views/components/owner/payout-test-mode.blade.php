@props(['show' => true])

{{-- Shared TEST-MODE boundary banner (payout engine · Phase 4). Rendered on the owner Payouts + Balance
     surfaces while the automated payout engine runs in TEST MODE. HONEST: it never implies real money is
     moving — real transfers activate only at the founder's live cutover. --}}
@if ($show)
    <div role="note" style="display:flex;gap:10px;align-items:flex-start;border:1px solid rgba(180,120,20,.35);background:rgba(250,210,120,.14);border-radius:14px;padding:12px 14px;margin-bottom:16px">
        <span aria-hidden="true" style="font-size:16px;line-height:1.2">⚠️</span>
        <div style="font-size:12.5px;line-height:1.5;color:var(--vy-ink)">
            <strong>Test mode.</strong>
            Automated payouts run in test mode — no real funds move. Real transfers activate when your account goes live.
        </div>
    </div>
@endif
