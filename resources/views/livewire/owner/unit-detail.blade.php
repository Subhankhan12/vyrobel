{{-- Owner Unit Detail (exact-match pass · invariant #65) — rebuilt to the decoded "Owner Unit Detail" wireframe on
     REAL owner-scoped data. This page AGGREGATES already-built features (anti-drift, REUSED not rebuilt): the
     Batch-B analytics (KPIs/occupancy/revenue/channel-mix/best-badge), OwnerLedgerAttribution (deductions, reconciles
     #17), name-only stays (#28), OwnerBlockService (tap-to-block · owner.calendar.block), OwnerRateService (nightly +
     min-stay + WEEKEND UPLIFT · owner.rates.apply), the reviews feature (ReviewRating/ReviewList, first-name+initial
     byline #28), and the owner↔operator relay (owner.messages.start — name-free, never owner→guest). Wave-B added the
     REAL richer day-detail (guest name-only #28) + the READ-ONLY cleaning schedule (real HousekeepingTask). HONEST
     where not modelled (#39): OTA sync, the snooze toggle, a per-unit payout timeline, "Direct saved you €X".
     See App\Livewire\Owner\OwnerUnitDetail + docs/OWNER-TEAM-UNITDETAIL-AUDIT.md.
     RAW-BLADE RULE: every `@` follows a space, newline or tag (unregistered @click passes through to Alpine). --}}
<x-owner.shell active="units" :title="$unit['name']" :viewer="$viewer" :breadcrumb="[['label' => 'My units', 'url' => route('owner.units')], ['label' => $unit['name']]]">
    <style>
        .vy-ud-head { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
        .vy-ud-status { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:30px; font-size:11.5px; font-weight:500; }
        .vy-ud-status--live { background:rgba(217,102,46,.13); color:#B4400F; }
        .vy-ud-status--off { background:rgba(120,70,40,.1); color:var(--vy-muted); }
        .vy-ud-status .dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
        .vy-ud-hbtn { display:inline-flex; align-items:center; gap:7px; padding:9px 16px; border-radius:12px; cursor:pointer; font-family:var(--vy-sans); font-size:13px; font-weight:500; text-decoration:none; }
        .vy-ud-sync { margin-left:auto; font-size:11.5px; color:var(--vy-faint); }

        .vy-ud-kpis { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:18px; }
        .vy-ud-kpi-k { font-size:11px; font-weight:500; letter-spacing:.08em; text-transform:uppercase; color:var(--vy-faint); margin-bottom:5px; }
        .vy-ud-kpi-v { font-family:var(--vy-display); font-weight:600; font-size:26px; letter-spacing:-.02em; }
        .vy-ud-kpi-s { font-size:11.5px; color:var(--vy-faint); margin-top:3px; }

        .vy-ud-top { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:18px; align-items:start; margin-bottom:18px; }
        @media (max-width:960px){ .vy-ud-top { grid-template-columns:1fr; } }
        .vy-ud-cols { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:18px; align-items:start; }
        @media (max-width:960px){ .vy-ud-cols { grid-template-columns:1fr; } }
        .vy-ud-stack { display:flex; flex-direction:column; gap:18px; min-width:0; }

        .vy-ud-cal { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }
        .vy-ud-dow { text-align:center; font-size:10px; font-weight:600; letter-spacing:.06em; color:var(--vy-faint); }
        .vy-ud-day { aspect-ratio:1; display:flex; align-items:center; justify-content:center; border-radius:9px; font-size:12.5px; font-variant-numeric:tabular-nums; }
        .vy-ud-day--open { background:rgba(255,255,255,.5); border:1px solid rgba(120,70,40,.1); color:var(--vy-ink); cursor:pointer; }
        .vy-ud-day--open:hover { background:rgba(217,102,46,.1); }
        .vy-ud-day--open.is-sel { background:var(--vy-grad); color:#fff; }
        .vy-ud-day--booked { background:rgba(232,112,58,.16); border:1px solid rgba(232,112,58,.28); color:#8A3410; }
        .vy-ud-day--blocked { background:#3A2E27; color:#F3E7DD; }
        .vy-ud-day--past { background:transparent; color:var(--vy-faint); opacity:.5; }
        .vy-ud-legend { display:flex; align-items:center; gap:16px; margin-top:16px; flex-wrap:wrap; font-size:11.5px; color:var(--vy-muted); }
        .vy-ud-legend .sw { width:11px; height:11px; border-radius:4px; }

        .vy-ud-prow { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:9px 0; border-top:1px solid rgba(120,70,40,.08); font-size:12.5px; }
        .vy-ud-prow .k { color:var(--vy-muted); }
        .vy-ud-prow .v { font-weight:500; font-variant-numeric:tabular-nums; }

        .vy-ud-rev-chart { display:flex; align-items:flex-end; gap:10px; height:120px; }
        .vy-ud-bar-col { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; justify-content:flex-end; height:100%; }
        .vy-ud-bar { width:100%; max-width:30px; border-radius:6px 6px 0 0; background:var(--vy-grad); }
        .vy-ud-bar--occ { background:var(--vy-grad-peach); }

        .vy-ud-reviews-score { font-family:var(--vy-display); font-weight:700; font-size:32px; letter-spacing:-.02em; }
        .vy-ud-review { padding:12px 0; border-top:1px solid var(--vy-hairline); }
        .vy-ud-rail-card { padding:20px; }
        .vy-ud-honest { background:rgba(255,252,248,.5); border:1px dashed rgba(120,70,40,.22); }
    </style>

    @if (session('rate_notice') || session('block_notice'))
        <div class="vy-card vy-fade" style="margin-bottom:14px;border-left:3px solid var(--vy-accent);padding:12px 16px;font-size:13.5px;color:var(--vy-ink)">{{ session('rate_notice') ?? session('block_notice') }}</div>
    @endif
    @if (session('rate_error') || session('block_error'))
        <div class="vy-card vy-fade" style="margin-bottom:14px;border-left:3px solid #C2453A;padding:12px 16px;font-size:13px;color:#8A2F27">{{ session('rate_error') ?? session('block_error') }}</div>
    @endif

    {{-- ── Header: status + edit-pricing + honest sync/share ── --}}
    <div class="vy-ud-head">
        @if ($unit['active'])
            <span class="vy-ud-status vy-ud-status--live"><span class="dot"></span>Active</span>
        @else
            <span class="vy-ud-status vy-ud-status--off"><span class="dot"></span>Not listed</span>
        @endif
        @if ($unit['condition'] !== null)
            <span class="vy-ud-status vy-ud-status--off" style="background:rgba(190,32,28,.1);color:#B01E1E">{{ $unit['condition']['label'] }}</span>
        @endif
        @if ($bestBadge !== null)
            <span class="vy-ud-status" style="background:var(--vy-grad-dark);color:#F7D9C4">★ Best performer of your {{ $bestBadge['count'] }} units · {{ $bestBadge['month'] }}</span>
        @endif
        <a href="#vy-ud-pricing" class="vy-ud-hbtn vy-btn--accent" style="text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
            Edit pricing
        </a>
        {{-- Sync/share — HONEST: OTA channel sync is account-gated; no fake "synced" state (#37/#39). --}}
        <span class="vy-ud-sync">Channel sync is managed by your operator</span>
    </div>

    {{-- ── KPIs — REAL per-unit ── --}}
    <div class="vy-ud-kpis vy-fade">
        <div class="vy-card" style="padding:16px 18px"><div class="vy-ud-kpi-k">Occupancy</div><div class="vy-ud-kpi-v">{{ $occupancy['pct'] }}%</div><div class="vy-ud-kpi-s">{{ $month['label'] }}</div></div>
        <div class="vy-card" style="padding:16px 18px"><div class="vy-ud-kpi-k">Nights sold</div><div class="vy-ud-kpi-v">{{ $occupancy['nights'] }}</div><div class="vy-ud-kpi-s">{{ $month['label'] }}</div></div>
        <div class="vy-card" style="padding:16px 18px"><div class="vy-ud-kpi-k">Net · {{ $month['label'] }}</div><div class="vy-ud-kpi-v">{{ $attribution[0]['net_display'] ?? '—' }}</div><div class="vy-ud-kpi-s">Attributed to you</div></div>
    </div>

    {{-- ── Top: availability calendar (tap-to-block) + pricing ── --}}
    <div class="vy-ud-top">
        {{-- Availability calendar — REAL booked/blocked/open; tap an OPEN day → block; tap a BOOKED/BLOCKED day → detail --}}
        <section class="vy-card vy-fade" x-data="{ sel: null, detail: null, details: @js($dayDetails) }">
            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:4px">
                <h2 class="vy-card-title" style="font-size:16px">Availability · {{ $month['label'] }}</h2>
                <div style="display:flex;align-items:center;gap:10px">
                    <a href="{{ route('owner.units.show', ['unit' => $unit['id'], 'month' => $month['prev']]) }}" class="vy-line-sub" style="text-decoration:none;font-size:15px">‹</a>
                    <a href="{{ route('owner.units.show', ['unit' => $unit['id'], 'month' => $month['next']]) }}" class="vy-line-sub" style="text-decoration:none;font-size:15px">›</a>
                    <span style="font-size:11.5px;color:var(--vy-faint)">Tap an open day to block</span>
                </div>
            </div>
            <div style="font-size:12px;color:var(--vy-muted);margin-bottom:16px;font-variant-numeric:tabular-nums">{{ $calendar['booked'] }} booked · {{ $calendar['blocked'] }} blocked · {{ $calendar['open'] }} open</div>
            <div class="vy-ud-cal" style="margin-bottom:6px">
                @foreach (['MON','TUE','WED','THU','FRI','SAT','SUN'] as $d)<div class="vy-ud-dow">{{ $d }}</div>@endforeach
            </div>
            <div class="vy-ud-cal">
                @for ($i = 1; $i < $calendar['first_weekday']; $i++)<div></div>@endfor
                @foreach ($calendar['days'] as $c)
                    @if ($c['state'] === 'open')
                        <div class="vy-ud-day vy-ud-day--open" :class="sel === @js($c['date']) ? 'is-sel' : ''" @click="sel = (sel === @js($c['date']) ? null : @js($c['date'])); detail = null" title="Open — tap to block">{{ $c['day'] }}</div>
                    @elseif ($c['state'] === 'booked' || $c['state'] === 'blocked')
                        <div class="vy-ud-day vy-ud-day--{{ $c['state'] }}" style="cursor:pointer" @click="detail = (detail && detail.date === @js($c['date'])) ? null : Object.assign({date: @js($c['date'])}, details[@js($c['date'])] || {}); sel = null" title="Tap for detail">{{ $c['day'] }}</div>
                    @else
                        <div class="vy-ud-day vy-ud-day--{{ $c['state'] }}" title="{{ ucfirst($c['state']) }}">{{ $c['day'] }}</div>
                    @endif
                @endforeach
            </div>
            <div class="vy-ud-legend">
                <span style="display:inline-flex;align-items:center;gap:6px"><span class="sw" style="background:rgba(232,112,58,.16);border:1px solid rgba(232,112,58,.28)"></span>Booked</span>
                <span style="display:inline-flex;align-items:center;gap:6px"><span class="sw" style="background:rgba(255,255,255,.5);border:1px solid rgba(120,70,40,.1)"></span>Open</span>
                <span style="display:inline-flex;align-items:center;gap:6px"><span class="sw" style="background:#3A2E27"></span>Blocked</span>
            </div>
            {{-- Wave-B RICHER DAY-DETAIL — a booked day shows the stay (guest NAME-only #28, dates, your net); a blocked
                 day shows its reason. Real data from dayDetails; no guest contact ever. --}}
            <div x-show="detail" x-cloak style="margin-top:16px;padding:14px 16px;border-top:1px solid var(--vy-hairline);background:rgba(255,255,255,.45);border-radius:12px">
                <template x-if="detail && detail.type === 'booked'">
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                            <span style="font-weight:600;font-size:13.5px" x-text="detail.guest"></span>
                            <span style="font-size:11.5px;color:var(--vy-faint)" x-text="detail.reference ? ('Ref ' + detail.reference) : ''"></span>
                        </div>
                        <div style="font-size:12px;color:var(--vy-muted);margin-top:4px"><span x-text="detail.stay"></span> · <span x-text="detail.nights + (detail.nights === 1 ? ' night' : ' nights')"></span></div>
                        <template x-if="detail.net && detail.net.length">
                            <div style="font-size:12px;color:var(--vy-ink);margin-top:6px">Your net: <strong x-text="detail.net.join(' · ')"></strong></div>
                        </template>
                    </div>
                </template>
                <template x-if="detail && detail.type === 'blocked'">
                    <div style="font-size:13px;color:var(--vy-ink)">Blocked · <span x-text="detail.reason"></span></div>
                </template>
            </div>

            {{-- Block composer — appears when an open day is selected; a REAL block (OwnerBlockService, $capActions #20) --}}
            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                <form method="POST" action="{{ route('owner.calendar.block') }}" x-show="sel" x-cloak style="margin-top:16px;padding-top:16px;border-top:1px solid var(--vy-hairline);display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    @csrf
                    <input type="hidden" name="unit_id" value="{{ $unit['id'] }}">
                    <input type="hidden" name="first_night" :value="sel">
                    <input type="hidden" name="last_night" :value="sel">
                    <span style="font-size:12.5px;color:var(--vy-muted)">Block <strong x-text="sel"></strong> as</span>
                    <select name="reason" class="vy-input" style="padding:6px 10px;font-size:12.5px;flex:0 1 auto">
                        @foreach ($blockReasons as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                    </select>
                    <button type="submit" class="vy-btn vy-btn--accent" style="padding:7px 15px;font-size:12.5px">Block day</button>
                    <button type="button" class="vy-btn vy-btn--ghost" style="padding:7px 13px;font-size:12.5px" @click="sel = null">Cancel</button>
                </form>
            @endownercan
            <p class="vy-line-sub" style="font-size:11px;margin-top:12px">Blocked days hold the unit off-sale. Manage all blocks on your <a href="{{ route('owner.calendar', ['unit' => $unit['id']]) }}" style="color:var(--vy-accent)">Calendar</a>.</p>
        </section>

        {{-- Pricing & availability — REAL nightly + min-stay + weekend uplift (OwnerRateService, Wave-A/B) --}}
        <section class="vy-card vy-fade" id="vy-ud-pricing" style="padding:20px" x-data="{ editing: false, rate: {{ $pricing['rate_units'] ?? 0 }}, weekend: {{ $pricing['weekend_pct'] !== null ? (float) $pricing['weekend_pct'] : 0 }} }">
            <div class="vy-eyebrow" style="margin-bottom:12px">Pricing &amp; availability</div>

            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-radius:14px;background:rgba(255,255,255,.42);border:1px solid rgba(120,70,40,.08);margin-bottom:14px">
                <div style="min-width:0">
                    <div style="font-size:13.5px;font-weight:500">{{ $unit['active'] ? 'Listing active' : 'Not listed' }}</div>
                    <div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px">Your operator manages listing status.</div>
                </div>
                <span class="vy-ud-status {{ $unit['active'] ? 'vy-ud-status--live' : 'vy-ud-status--off' }}"><span class="dot"></span>{{ $unit['active'] ? 'Active' : 'Off' }}</span>
            </div>

            <div x-show="! editing">
                <div class="vy-ud-prow"><span class="k">Nightly rate</span><span class="v">{{ $pricing['rate_display'] ?? 'Not set' }}</span></div>
                <div class="vy-ud-prow"><span class="k">Weekend uplift</span><span class="v">{{ $pricing['weekend_pct'] !== null && (float) $pricing['weekend_pct'] > 0 ? rtrim(rtrim(number_format((float) $pricing['weekend_pct'], 2), '0'), '.').'%' : 'None' }}</span></div>
                <div class="vy-ud-prow"><span class="k">Minimum stay</span><span class="v">{{ $pricing['min_stay'] !== null ? $pricing['min_stay'].' '.\Illuminate\Support\Str::plural('night', $pricing['min_stay']) : 'None' }}</span></div>
                @if (! $pricing['editable'])
                    <p class="vy-line-sub" style="font-size:11.5px;margin-top:12px">This room type is shared with other inventory, so its rate is managed by your operator.</p>
                @endif
            </div>

            {{-- Edit — REAL: nightly + min-stay → owner.rates.apply (returns here); weekend uplift NOT offered
                 (unmodelled). The whole edit affordance is CAP_ACTIONS-gated (co-owner only) + editable-only, so a
                 view-only sub-user or a shared room type never gets a write control (#20/#30 — gate the control). --}}
            @if ($pricing['editable'])
                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                    <button type="button" x-show="! editing" class="vy-btn vy-btn--ghost" style="margin-top:14px;padding:8px 14px;font-size:12.5px" @click="editing = true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                        Edit pricing
                    </button>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('owner.rates.apply') }}">
                        @csrf
                        <input type="hidden" name="room_type_id" value="{{ $pricing['room_type_id'] }}">
                        <input type="hidden" name="redirect" value="unit">
                        <input type="hidden" name="unit" value="{{ $unit['id'] }}">
                        <input type="hidden" name="start_date" value="{{ now()->toDateString() }}">
                        <input type="hidden" name="end_date" value="{{ now()->addYear()->subDay()->toDateString() }}">
                        <label class="vy-field-label" for="ud_rate">Nightly rate (€)</label>
                        <input id="ud_rate" type="number" name="rate" step="0.01" min="0" x-model="rate" class="vy-input" style="width:100%;margin-bottom:11px" required>
                        <label class="vy-field-label" for="ud_weekend">Weekend uplift (%)</label>
                        <input id="ud_weekend" type="number" name="weekend_uplift" step="1" min="0" max="200" x-model="weekend" class="vy-input" style="width:100%;margin-bottom:11px" placeholder="Optional — e.g. 20">
                        <label class="vy-field-label" for="ud_min">Minimum stay (nights)</label>
                        <input id="ud_min" type="number" name="min_stay" min="1" max="365" value="{{ $pricing['min_stay'] }}" class="vy-input" style="width:100%;margin-bottom:8px" placeholder="Optional">
                        {{-- Live preview — the derived Fri/Sat price (base × (1 + uplift%)) before saving --}}
                        <p x-show="weekend > 0" x-cloak class="vy-line-sub" style="font-size:11.5px;margin:0 0 8px;color:var(--vy-ink)">Fri &amp; Sat nights become <strong style="color:var(--vy-accent)" x-text="'€' + Math.round(rate * (1 + weekend/100))"></strong> / night; weekdays stay <span x-text="'€' + rate"></span>.</p>
                        <p class="vy-line-sub" style="font-size:11px;margin:0 0 12px">Applies from today across the next year. It’s live immediately.</p>
                        <div style="display:flex;gap:9px">
                            <button type="button" class="vy-btn vy-btn--ghost" style="flex:1;justify-content:center" @click="editing = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent" style="flex:1;justify-content:center">Save changes</button>
                        </div>
                    </form>
                @endownercan
            @endif
        </section>
    </div>

    {{-- ── Main + rail ── --}}
    <div class="vy-ud-cols">
        <div class="vy-ud-stack">
            {{-- Revenue · 6 months (real net) --}}
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:4px">Revenue · 6 months</h2>
                <div class="vy-line-sub" style="margin-bottom:16px">Net to you, per month</div>
                @if ($revenueTrend['has_data'])
                    <div class="vy-ud-rev-chart">
                        @foreach ($revenueTrend['bars'] as $b)
                            <div class="vy-ud-bar-col" title="{{ $b['label'] }} · {{ $b['display'] }}">
                                <span style="font-size:9.5px;color:var(--vy-faint)">{{ $b['display'] }}</span>
                                <div class="vy-ud-bar" style="height:{{ max(2, $b['pct']) }}%"></div>
                                <span style="font-size:10px;color:var(--vy-faint)">{{ $b['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-empty">No revenue in the last 6 months yet.</div>
                @endif
            </section>

            {{-- Occupancy · 12 months (real) --}}
            <section class="vy-card vy-fade">
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:16px">
                    <h2 class="vy-card-title" style="font-size:16px">Occupancy · 12 months</h2>
                    <span class="vy-line-sub">avg {{ $occupancyTrend['avg'] }}% · peak {{ $occupancyTrend['peak_pct'] }}% in {{ $occupancyTrend['peak_label'] }}</span>
                </div>
                <div class="vy-ud-rev-chart">
                    @foreach ($occupancyTrend['bars'] as $b)
                        <div class="vy-ud-bar-col" title="{{ $b['label'] }} · {{ $b['display'] }}">
                            <div class="vy-ud-bar vy-ud-bar--occ" style="height:{{ max(2, $b['pct']) }}%"></div>
                            <span style="font-size:10px;color:var(--vy-faint)">{{ $b['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Attribution / deductions · month (real, reconciles #17) --}}
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:4px">Attribution · {{ $month['label'] }}</h2>
                <div class="vy-line-sub" style="margin-bottom:8px">What this unit earned you, and what was deducted.</div>
                @forelse ($attribution as $group)
                    <table class="vy-table" style="width:100%">
                        <tbody>
                            @foreach ($group['credits'] as $line)<tr><td>{{ $line['label'] }}</td><td class="vy-num vy-mono">{{ $line['display'] }}</td></tr>@endforeach
                            @foreach ($group['deductions'] as $line)<tr><td>{{ $line['label'] }}</td><td class="vy-num vy-mono">{{ $line['display'] }}</td></tr>@endforeach
                            <tr><td><strong>Net</strong></td><td class="vy-num vy-mono" style="color:var(--vy-accent)">{{ $group['net_display'] }}</td></tr>
                        </tbody>
                    </table>
                @empty
                    <div class="vy-empty">No activity attributed this month yet.</div>
                @endforelse
            </section>

            {{-- Stays · month (real, name-only #28) --}}
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:4px">Stays · {{ $month['label'] }}</h2>
                <div class="vy-line-sub" style="margin-bottom:8px">Bookings on this unit that touch this month</div>
                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead><tr><th>Reference</th><th>Stay</th><th class="vy-num">Nights</th><th>Source</th><th>Status</th><th class="vy-num">Your net</th></tr></thead>
                        <tbody>
                            @forelse ($bookings as $b)
                                <tr>
                                    <td class="vy-mono">{{ $b['reference'] ?? '—' }}</td>
                                    <td>{{ $b['stay'] }}</td>
                                    <td class="vy-num">{{ $b['nights'] }}</td>
                                    <td>{{ $b['source_label'] ?? '—' }}</td>
                                    <td>@if ($b['status_meta'])<span class="vy-status vy-status--{{ $b['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $b['status_meta']['label'] }}</span>@else — @endif</td>
                                    <td class="vy-num vy-mono">{{ $b['net_displays'] === [] ? 'Not yet attributed' : implode(' · ', $b['net_displays']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="vy-empty">No stays touch this month.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Reviews & rating — REAL (rating + list; byline first-name+initial #28; no category bars — unmodelled) --}}
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:8px">Reviews &amp; rating</h2>
                @if ($reviews['has_reviews'])
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:6px">
                        <span class="vy-ud-reviews-score">{{ number_format((float) $reviews['average'], 1) }}</span>
                        <div>
                            <div style="color:#E8A24A;font-size:15px">{{ str_repeat('★', (int) round((float) $reviews['average'])) }}{{ str_repeat('☆', 5 - (int) round((float) $reviews['average'])) }}</div>
                            <div class="vy-line-sub">{{ $reviews['count'] }} {{ \Illuminate\Support\Str::plural('review', $reviews['count']) }} · published</div>
                        </div>
                    </div>
                    @foreach ($reviews['list'] as $r)
                        <div class="vy-ud-review">
                            <div style="display:flex;justify-content:space-between;gap:10px"><span style="font-size:13px;font-weight:600">{{ $r['byline'] }}</span><span style="color:#E8A24A;font-size:12px">{{ $r['stars'] }}</span></div>
                            @if ($r['body'])<div class="vy-line-sub" style="font-size:12.5px;margin-top:4px;line-height:1.5">{{ $r['body'] }}</div>@endif
                            <div style="font-size:11px;color:var(--vy-faint);margin-top:3px">{{ $r['at'] }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="vy-empty">No published reviews for this unit yet.</div>
                @endif
            </section>
        </div>

        {{-- RIGHT RAIL --}}
        <div class="vy-ud-stack">
            {{-- Unit details (real) --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">Unit details</span>
                <div class="vy-ud-prow"><span class="k">Code</span><span class="v">{{ $unit['code'] }}</span></div>
                <div class="vy-ud-prow"><span class="k">Room type</span><span class="v">{{ $unit['type'] }}</span></div>
                <div class="vy-ud-prow"><span class="k">Type</span><span class="v">{{ $unit['kind_label'] }}</span></div>
                <div class="vy-ud-prow"><span class="k">Sleeps</span><span class="v">{{ $unit['max_occupancy'] }}</span></div>
                <div class="vy-ud-prow"><span class="k">Listing</span><span class="v">{{ $unit['active'] ? 'Active' : 'Off' }}</span></div>
            </section>

            {{-- Commission (real) --}}
            @if ($commission !== null)
                <section class="vy-card vy-fade vy-ud-rail-card">
                    <span class="vy-eyebrow">Commission</span>
                    <div style="display:flex;align-items:baseline;justify-content:space-between;margin-top:8px">
                        <span style="font-family:var(--vy-display);font-weight:600;font-size:20px">{{ $commission['display'] ?? 'Not set' }}</span>
                        <a href="{{ route('owner.agreement') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">Agreement →</a>
                    </div>
                    <div class="vy-line-sub" style="font-size:11.5px;margin-top:4px">{{ $commission['scope_label'] }}, in effect today.</div>
                </section>
            @endif

            {{-- Channel mix (real; "Direct saved you €X" refused #39) --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">Channel mix · {{ $month['label'] }}</span>
                @if ($channelMix['has_data'])
                    @foreach ($channelMix['rows'] as $c)
                        <div style="margin-top:12px">
                            <div style="display:flex;justify-content:space-between;font-size:12.5px"><span style="font-weight:500">{{ $c['label'] }}</span><span class="vy-muted">{{ $c['nights'] }} {{ \Illuminate\Support\Str::plural('night', $c['nights']) }} · {{ $c['pct'] }}%</span></div>
                            <div class="vy-track" style="margin-top:5px"><span style="width:{{ $c['pct'] }}%"></span></div>
                        </div>
                    @endforeach
                    <p class="vy-line-sub" style="font-size:11px;margin-top:12px">Share of this unit's room-nights by booking source.</p>
                @else
                    <div class="vy-empty" style="padding:16px 0">No bookings this month.</div>
                @endif
            </section>

            {{-- Cleaning — Wave-B: the REAL HousekeepingTask rows (read-only), owner-scoped. The owner SEES the
                 schedule but managing it is the operator's job (honest); never a fabricated cleaner (#39). --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">Cleaning schedule</span>
                @if ($cleanings['has_data'])
                    <div style="margin-top:10px">
                        @foreach ($cleanings['rows'] as $cl)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 0;border-top:1px solid var(--vy-hairline)">
                                <div style="min-width:0">
                                    <div style="font-size:12.5px;font-weight:500;color:var(--vy-ink)">{{ $cl['type'] }}</div>
                                    <div style="font-size:11px;color:var(--vy-faint)">{{ $cl['date'] }}{{ $cl['upcoming'] ? ' · upcoming' : '' }}</div>
                                </div>
                                <span class="vy-status vy-status--{{ $cl['tone'] }}" style="flex-shrink:0"><span class="vy-dot"></span>{{ $cl['status'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="vy-line-sub" style="font-size:11px;margin-top:10px">Coordinated by your management team — message them below to arrange anything.</p>
                @else
                    <p class="vy-line-sub" style="font-size:12.5px;margin-top:8px;line-height:1.55">No cleanings on record for this unit yet. Turnovers are coordinated by your management team — message them below to arrange anything.</p>
                @endif
            </section>

            {{-- Guest messages → the owner↔operator RELAY (name-free #28; never owner→guest) --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">Message your management team</span>
                <p class="vy-line-sub" style="font-size:11.5px;margin:6px 0 10px">About this unit — bookings and guest contact stay with your operator.</p>
                <form method="POST" action="{{ route('owner.messages.start') }}">
                    @csrf
                    <input type="hidden" name="subject" value="About {{ $unit['name'] }}">
                    <textarea name="body" rows="3" class="vy-input" style="width:100%;resize:vertical" placeholder="Ask your management team about this unit…" required></textarea>
                    <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:10px">Send message</button>
                </form>
                <a href="{{ route('owner.messages') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600;display:inline-block;margin-top:10px">Open inbox →</a>
            </section>

            {{-- Your money — payouts are per-statement, not per-unit (honest; no fabricated per-unit timeline #39) --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">Your money</span>
                <a href="{{ route('owner.balance') }}" style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid var(--vy-hairline);text-decoration:none;color:var(--vy-ink);font-size:13px;margin-top:6px"><span>Balance</span><span style="color:var(--vy-accent)">Open →</span></a>
                <a href="{{ route('owner.payouts') }}" style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid var(--vy-hairline);text-decoration:none;color:var(--vy-ink);font-size:13px"><span>Payouts</span><span style="color:var(--vy-accent)">Open →</span></a>
                <p class="vy-line-sub" style="font-size:11px;margin-top:8px">Payouts are made per statement across your whole portfolio, not per unit.</p>
            </section>
        </div>
    </div>
</x-owner.shell>
