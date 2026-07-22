{{-- Email Previews index (Phase 3 · Sub-gate 4) — every transactional email, rendered with the tenant's real
     sample data. Honest about each trigger: which send on a real event today vs template-ready-trigger-deferred.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-layouts.operator title="Email previews">
<x-operator.shell active="email-previews" title="Email previews" subtitle="Every transactional email, with real sample data">

    <p class="vy-line-sub" style="font-size:13px;margin-bottom:16px;color:var(--vy-muted)">
        Rendered with this organization’s latest booking — nothing is sent from here. Delivery is real via Mailpit in
        development; production delivery needs a mail provider (account-gated).
    </p>

    <section class="vy-card vy-fade">
        <div style="display:grid;grid-template-columns:1fr;gap:2px">
            @foreach ($catalog as $key => $email)
                <a href="{{ route('app.email-previews.show', $key) }}" target="_blank" rel="noopener"
                   style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 6px;border-bottom:1px solid var(--vy-hairline);text-decoration:none;color:inherit">
                    <span>
                        <span style="display:block;font-size:14px;font-weight:600;color:var(--vy-ink)">{{ $email['title'] }}</span>
                        <span style="display:block;font-size:12px;color:var(--vy-muted);margin-top:1px">{{ $email['status'] }}</span>
                    </span>
                    <span style="flex:none;font-size:12px;font-weight:600;color:var(--vy-accent)">Preview →</span>
                </a>
            @endforeach
        </div>
    </section>

</x-operator.shell>
</x-layouts.operator>
