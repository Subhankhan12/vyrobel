{{-- SUPER ADMIN · BUILD 2 · GATE 1 — Security & Activity. Real auth signals (login/failed/lockout) + a
     cross-tenant account-events slice (via the audited read). Privacy: security-monitoring purpose, 90-day
     retention (surfaced honestly). --}}
@php
    $eventPill = ['login' => 'ad-pill--active', 'failed' => 'ad-pill--suspended', 'lockout' => 'ad-pill--suspended'];
    $realm = ['web' => 'Operator/Owner', 'guest' => 'Guest', 'admin' => 'Platform admin'];
@endphp
<x-admin.shell active="activity" title="Security & activity"
    :subtitle="'Sign-in activity and account events across the platform — kept for '.$retentionDays.' days, then purged.'">

    <div class="ad-kpis" style="margin-bottom: 20px;">
        <div class="ad-kpi">
            <div class="ad-kpi-label">Failed / lockout · 24h</div>
            <div class="ad-kpi-val" style="color: {{ $failures24h > 0 ? 'var(--ad-danger)' : 'var(--ad-ink)' }};">{{ number_format($failures24h) }}</div>
            <div class="ad-kpi-sub">Repeated failures can signal probing.</div>
        </div>
        <div class="ad-kpi" style="grid-column: span 2;">
            <div class="ad-kpi-label">Privacy</div>
            <div class="ad-kpi-sub" style="margin-top: 10px; line-height: 1.6;">
                Sign-in activity (incl. IP) is captured only as a <strong style="color: var(--ad-ink);">security signal</strong>
                — spotting suspicious logins and support — never ambient tracking. Retained {{ $retentionDays }} days, then
                purged automatically; access is limited to this console.
            </div>
        </div>
    </div>

    <div class="ad-card" style="padding: 8px 10px; margin-bottom: 20px;">
        <h2 class="ad-panel-title" style="padding: 14px 14px 4px;">Recent sign-in activity</h2>
        @if ($logins->count() > 0)
            <table class="ad-table">
                <thead>
                    <tr><th>Event</th><th>Realm</th><th>Account</th><th>IP</th><th>When</th></tr>
                </thead>
                <tbody>
                    @foreach ($logins as $row)
                        <tr>
                            <td><span class="ad-pill {{ $eventPill[$row->event] ?? 'ad-pill--cancelled' }}">{{ ucfirst($row->event) }}</span></td>
                            <td style="color: var(--ad-muted);">{{ $realm[$row->guard] ?? ($row->guard ?? '—') }}</td>
                            <td>{{ $row->identifier ?? ($row->subject_type ? ucfirst(str_replace('_', ' ', $row->subject_type)) : '—') }}</td>
                            <td style="color: var(--ad-faint); font-variant-numeric: tabular-nums;">{{ $row->ip ?? '—' }}</td>
                            <td style="color: var(--ad-muted);">{{ $row->occurred_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="ad-empty" style="padding: 24px 14px;">No sign-in activity captured yet.</div>
        @endif
    </div>

    <div class="ad-card" style="padding: 8px 10px;">
        <h2 class="ad-panel-title" style="padding: 14px 14px 4px;">Recent account events <span style="color: var(--ad-faint); font-weight: 400; font-size: 12px;">· across all customers</span></h2>
        @if (count($accountEvents) > 0)
            <table class="ad-table">
                <thead>
                    <tr><th>Event</th><th>Customer</th><th>Actor</th><th>When</th></tr>
                </thead>
                <tbody>
                    @foreach ($accountEvents as $event)
                        <tr>
                            <td style="font-weight: 600;">{{ $event['action'] }}</td>
                            <td style="color: var(--ad-muted);">{{ $event['organization'] }}</td>
                            <td style="color: var(--ad-faint);">{{ ucfirst($event['actor_type']) }}</td>
                            <td style="color: var(--ad-muted);">{{ $event['created_at']?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="ad-empty" style="padding: 24px 14px;">No account events yet.</div>
        @endif
    </div>
</x-admin.shell>
