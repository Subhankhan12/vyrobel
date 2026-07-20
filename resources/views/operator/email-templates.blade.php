{{-- Operator Email Templates (Phase 3 · Sub-gate 5) — the management view: subject, trigger status, and merge
     variables for every transactional email, each linking to its live preview. Editing is honestly deferred.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-operator.shell active="email-templates" title="Email templates" subtitle="The transactional emails Vyrobel sends">

    <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
        <div style="font-size:13px;color:var(--vy-ink)">Templates are authored in code today — this is the reference view. <strong>Editing template content is coming</strong>; delivery, variables and triggers below are all live. Email sends via a provider in production (account-gated); in-app and email delivery honour each user’s notification preferences.</div>
    </div>

    <section class="vy-card vy-fade">
        @foreach ($templates as $key => $tpl)
            <div style="padding:14px 4px;border-bottom:1px solid var(--vy-hairline)">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap">
                    <span style="font-size:14.5px;font-weight:700;color:var(--vy-ink)">{{ $tpl['title'] }}</span>
                    <a href="{{ route('app.email-previews.show', $key) }}" target="_blank" rel="noopener" style="flex:none;font-size:12px;font-weight:600;color:var(--vy-accent);text-decoration:none">Preview →</a>
                </div>
                <div style="font-size:12.5px;color:var(--vy-muted);margin-top:3px">Subject: <span style="color:var(--vy-ink)">{{ $tpl['subject'] }}</span></div>
                <div style="font-size:11.5px;color:var(--vy-muted);margin-top:2px">{{ $tpl['trigger'] }}</div>
                <div style="margin-top:7px;display:flex;gap:5px;flex-wrap:wrap">
                    @foreach ($tpl['variables'] as $var)
                        <span style="font-size:11px;color:var(--vy-ink);background:var(--vy-hairline);border-radius:5px;padding:2px 7px">{{ '{'.$var.'}' }}</span>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;flex-wrap:wrap;gap:10px">
            <span class="vy-line-sub" style="font-size:11.5px;color:var(--vy-muted)">Owner &amp; operator alerts also email through the same preference gate — see Notification preferences.</span>
            <span class="vy-btn vy-btn--ghost" style="padding:8px 16px;opacity:.6;cursor:not-allowed" title="Coming soon">Edit templates · Soon</span>
        </div>
    </section>

</x-operator.shell>
