{{-- ⚠️ ADMIN 01 · PHASE 4 — THE SIGNUP FUNNEL BY SOURCE, from facts the database already records. Stages it cannot
     measure are NAMED below, not dropped. --}}
@php
    $stages = \App\Support\Platform\PlatformFunnel::STAGES;
    $cols = ['total' => 'All', 'email' => 'Email', 'google' => 'Google', 'twitter' => 'X', 'unknown' => 'Unknown'];
    $value = fn (string $col, string $stage): int => $col === 'total' ? $f['total'][$stage] : $f['sources'][$col][$stage];
@endphp
<x-admin.shell active="funnel" title="Signup funnel" subtitle="Where organisations get to, split by how they signed up — from what is recorded, nothing invented.">

    <div class="ad-filters" style="justify-content: space-between;">
        <span class="ad-stat-sub" style="margin: 0;">{{ $f['include_internal'] ? 'Including Vyrobel’s own internal organisations.' : 'Customers only.' }}</span>
        <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.funnel', $f['include_internal'] ? [] : ['internal' => 'include']) }}">{{ $f['include_internal'] ? 'Customers only' : 'Include internal' }}</a>
    </div>

    @if ($f['total']['signed_up'] === 0)
        <div class="ad-card" style="margin-bottom: 18px;">
            <strong>No customers yet</strong> — every organisation on the platform is one of Vyrobel’s own, so the funnel is empty by definition. Include internal organisations to see the machinery work.
        </div>
    @endif

    <div class="ad-card" style="overflow-x: auto; padding: 8px 10px;">
        <table class="ad-table">
            <thead><tr><th>Stage</th>@foreach ($cols as $label)<th class="ad-num">{{ $label }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($stages as $key => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        @foreach ($cols as $col => $_)
                            @php $n = $value($col, $key); $top = $value($col, 'signed_up'); @endphp
                            <td class="ad-num">{{ number_format($n) }}@if ($key !== 'signed_up' && $top > 0)<span style="color: var(--ad-faint); font-size: 11.5px;"> · {{ (int) round($n * 100 / $top) }}%</span>@endif</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="ad-grid-2" style="margin-top: 18px;">
        <div class="ad-card">
            <h2 class="ad-panel-title">Not measured — and why</h2>
            @foreach (\App\Support\Platform\PlatformFunnel::UNMEASURED as $stage => $why)
                <div class="ad-row" style="align-items: flex-start;"><span class="ad-row-k" style="color: var(--ad-ink);"><strong>{{ $stage }}</strong><div style="font-size: 12px; color: var(--ad-muted); font-weight: 400;">{{ $why }}</div></span><span class="ad-hpill ad-hpill--grey">Unmeasured</span></div>
            @endforeach
        </div>
        <div class="ad-card">
            <h2 class="ad-panel-title">Not a stage</h2>
            <p class="ad-stat-sub" style="margin: 0 0 8px; line-height: 1.6;">“Added a property” — sign-up creates the first property in the same step, so every organisation starts with one. Measured, not assumed:</p>
            <div class="ad-row"><span class="ad-row-k">Organisations with no property</span><span class="ad-row-v">{{ number_format($f['without_property']) }}</span></div>
            <p class="ad-reason-hint" style="margin-top: 10px;">“Unknown” is an organisation created before the sign-up source was recorded — not a guess that it came by email.</p>
        </div>
    </div>
</x-admin.shell>
