{{-- SUPER ADMIN · BUILD 2 · GATE 1 — Security & Activity. Real auth signals (login/failed/lockout) + a
     cross-tenant account-events slice (via the audited read). Privacy: security-monitoring purpose, 90-day
     retention (surfaced honestly). --}}
@php
    $eventPill = ['login' => 'ad-pill--active', 'failed' => 'ad-pill--suspended', 'lockout' => 'ad-pill--suspended'];
    $realm = ['web' => __('admin/activity/index.realm_web'), 'guest' => __('admin/activity/index.realm_guest'), 'admin' => __('admin/activity/index.realm_admin')];
    // I18N-01 — the stored codes in words; an unknown code reads as before (capitalised).
    $eventLabel = fn (string $e): string => match ($e) {
        'login' => __('admin/activity/index.event_login'),
        'failed' => __('admin/activity/index.event_failed'),
        'lockout' => __('admin/activity/index.event_lockout'),
        default => ucfirst($e),
    };
    $subjectLabel = fn (string $t): string => match ($t) {
        'user' => __('admin/activity/index.subject_user'),
        'guest' => __('admin/activity/index.subject_guest'),
        'platform_admin' => __('admin/activity/index.subject_platform_admin'),
        'unknown' => __('admin/activity/index.subject_unknown'),
        default => ucfirst(str_replace('_', ' ', $t)),
    };
    $actorLabel = fn (string $a): string => match ($a) {
        'user' => __('admin/activity/index.actor_user'),
        'agent' => __('admin/activity/index.actor_agent'),
        'system' => __('admin/activity/index.actor_system'),
        default => ucfirst($a),
    };
@endphp
<x-admin.shell active="activity" :title="__('admin/activity/index.page_title')"
    :subtitle="__('admin/activity/index.page_subtitle', ['days' => $retentionDays])">

    <div class="ad-kpis" style="margin-bottom: 20px;">
        <div class="ad-kpi">
            <div class="ad-kpi-label">{{ __('admin/activity/index.kpi_failures') }}</div>
            <div class="ad-kpi-val" style="color: {{ $failures24h > 0 ? 'var(--ad-danger)' : 'var(--ad-ink)' }};">{{ number_format($failures24h) }}</div>
            <div class="ad-kpi-sub">{{ __('admin/activity/index.kpi_failures_help') }}</div>
        </div>
        <div class="ad-kpi" style="grid-column: span 2;">
            <div class="ad-kpi-label">{{ __('admin/activity/index.privacy') }}</div>
            <div class="ad-kpi-sub" style="margin-top: 10px; line-height: 1.6;">
                {!! __('admin/activity/index.privacy_body', ['days' => e($retentionDays)]) !!}
            </div>
        </div>
    </div>

    <div class="ad-card" style="padding: 8px 10px; margin-bottom: 20px;">
        <h2 class="ad-panel-title" style="padding: 14px 14px 4px;">{{ __('admin/activity/index.recent_sign_ins') }}</h2>
        @if ($logins->count() > 0)
            <table class="ad-table">
                <thead>
                    <tr><th>{{ __('admin/activity/index.col_event') }}</th><th>{{ __('admin/activity/index.col_realm') }}</th><th>{{ __('admin/activity/index.col_account') }}</th><th>{{ __('admin/activity/index.col_ip') }}</th><th>{{ __('admin/activity/index.col_when') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($logins as $row)
                        <tr>
                            <td><span class="ad-pill {{ $eventPill[$row->event] ?? 'ad-pill--cancelled' }}">{{ $eventLabel((string) $row->event) }}</span></td>
                            <td style="color: var(--ad-muted);">{{ $realm[$row->guard] ?? ($row->guard ?? '—') }}</td>
                            <td>{{ $row->identifier ?? ($row->subject_type ? $subjectLabel((string) $row->subject_type) : '—') }}</td>
                            <td style="color: var(--ad-faint); font-variant-numeric: tabular-nums;">{{ $row->ip ?? '—' }}</td>
                            <td style="color: var(--ad-muted);"><x-admin.time :at="$row->occurred_at" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="ad-empty" style="padding: 24px 14px;">{{ __('admin/activity/index.no_sign_ins') }}</div>
        @endif
    </div>

    <div class="ad-card" style="padding: 8px 10px;">
        <h2 class="ad-panel-title" style="padding: 14px 14px 4px;">{!! __('admin/activity/index.recent_account_events') !!}</h2>
        @if (count($accountEvents) > 0)
            <table class="ad-table">
                <thead>
                    <tr><th>{{ __('admin/activity/index.col_event') }}</th><th>{{ __('admin/activity/index.col_customer') }}</th><th>{{ __('admin/activity/index.col_actor') }}</th><th>{{ __('admin/activity/index.col_when') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($accountEvents as $event)
                        <tr>
                            <td style="font-weight: 600;" title="{{ $event['action'] }}">{{ \App\Support\Platform\ActionLabel::event($event['action']) }}</td>
                            <td style="color: var(--ad-muted);">{{ $event['organization'] }}</td>
                            <td style="color: var(--ad-faint);">{{ $actorLabel((string) $event['actor_type']) }}</td>
                            <td style="color: var(--ad-muted);"><x-admin.time :at="$event['created_at']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="ad-empty" style="padding: 24px 14px;">{{ __('admin/activity/index.no_account_events') }}</div>
        @endif
    </div>
</x-admin.shell>
