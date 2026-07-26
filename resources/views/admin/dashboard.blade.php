{{-- SUPER ADMIN · GATE 0 — the authenticated landing. NOT a real dashboard yet (features arrive in later gates).
     It proves the hardened realm renders behind mandatory 2FA and shows the ONE audited, admin-guard-gated
     cross-tenant read (the org count via the existing runAsSuperAdmin closure). Honest empty state otherwise. --}}
<x-admin.shell active="overview" title="Overview">
    <div class="ad-eyebrow">Platform console</div>
    <h1 class="ad-h1">Welcome to the Vyrobel platform admin</h1>
    <p class="ad-lead">The foundation is in place. Customers, plans, security &amp; activity, and integrations arrive in the next build gates.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; max-width: 760px;">
        <div class="ad-card">
            <div class="ad-eyebrow">Organizations</div>
            <div class="ad-stat">{{ number_format($organizationCount) }}</div>
            <div class="ad-stat-sub">Total tenants on the platform.</div>
        </div>
        <div class="ad-card">
            <div class="ad-eyebrow">Foundation</div>
            <div class="ad-stat" style="font-size: 22px; margin-top: 14px;">Isolated &amp; hardened</div>
            <div class="ad-stat-sub">Separate realm · mandatory 2FA · audited cross-tenant reads.</div>
        </div>
    </div>

    <div class="ad-card" style="margin-top: 20px; max-width: 760px;">
        <div class="ad-eyebrow">What's next</div>
        <p class="ad-stat-sub" style="margin-top: 8px; line-height: 1.6;">
            This Overview is intentionally minimal — Gate 0 built only the secure realm and proved it isolated.
            The feature areas (Dashboard metrics, Customers, Plans, Security &amp; activity log, Integrations) build
            on this proven foundation in the gates that follow.
        </p>
    </div>
</x-admin.shell>
