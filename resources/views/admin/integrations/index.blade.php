{{-- SUPER ADMIN · BUILD 2 · GATE 2 — the channel-integration catalog. Master platform credentials, encrypted at
     rest; the real sync is account-gated (labelled honestly). --}}
@php use App\Models\PlatformIntegration; @endphp
<x-admin.shell active="integrations" title="Integrations"
    subtitle="Your platform-level master credentials for the booking channels — stored encrypted.">

    @if (session('admin_notice'))
        <div class="ad-flash">{{ session('admin_notice') }}</div>
    @endif

    <div class="ad-card" style="padding: 8px 10px;">
        <table class="ad-table">
            <thead>
                <tr><th>Channel</th><th>Credential</th><th>Status</th><th>Last tested</th><th></th></tr>
            </thead>
            <tbody>
                @foreach (PlatformIntegration::CHANNELS as $key => $label)
                    @php $i = $integrations->get($key); @endphp
                    <tr>
                        <td><a class="ad-cell-link" href="{{ route('admin.integrations.edit', $key) }}">{{ $label }}</a></td>
                        <td>
                            @if ($i && $i->isConfigured())
                                <span style="font-family: ui-monospace, monospace; letter-spacing: .1em; color: var(--ad-muted);">••••••••</span>
                            @else
                                <span style="color: var(--ad-faint);">Not set</span>
                            @endif
                        </td>
                        <td>
                            @if ($i && $i->status === 'tested')
                                <span class="ad-pill ad-pill--active">Tested</span>
                            @elseif ($i && $i->isConfigured())
                                <span class="ad-pill ad-pill--trialing">Configured</span>
                            @else
                                <span class="ad-pill ad-pill--cancelled">Not configured</span>
                            @endif
                            @if ($i && $i->is_enabled)
                                <span class="ad-pill ad-pill--active" style="margin-left: 4px;">Enabled</span>
                            @endif
                        </td>
                        <td style="color: var(--ad-muted);">{{ $i?->last_tested_at?->diffForHumans() ?? '—' }}</td>
                        <td style="text-align: right;"><a href="{{ route('admin.integrations.edit', $key) }}" class="ad-btn ad-btn--ghost ad-btn--sm">Manage</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="ad-card" style="margin-top: 18px;">
        <h2 class="ad-panel-title">How customers connect</h2>
        <p class="ad-stat-sub" style="line-height: 1.65;">
            These are the <strong style="color: var(--ad-ink);">platform's</strong> master credentials. Later, each
            customer connects <strong style="color: var(--ad-ink);">their own</strong> channel account on top (the
            tenant-facing "connect your account" flow) — that per-customer connection, and the live two-way sync, are
            account-gated and arrive with the real channel accounts. This page manages the platform side.
        </p>
    </div>
</x-admin.shell>
