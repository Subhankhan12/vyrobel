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
<x-owner.shell active="units" :title="$unit['name']" :viewer="$viewer" :breadcrumb="[['label' => __('livewire/owner/unit-detail.breadcrumb_units'), 'url' => route('owner.units')], ['label' => $unit['name']]]">
    <style>
        .vy-ud-head { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
        .vy-ud-status { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:30px; font-size:11.5px; font-weight:500; }
        {{-- The design's tint assumes the pill sits on a light card; in the header it sits on the PAGE GRADIENT,
             where #B4400F measured 3.20. Compositing the same tint over a near-white plate gives the surface the
             design assumed (4.63) without changing either the tint or the text colour. --}}
        .vy-ud-status--live { background:linear-gradient(rgba(217,102,46,.13),rgba(217,102,46,.13)),rgba(255,252,248,.92); color:#B4400F; }
        .vy-ud-status--off { background:linear-gradient(rgba(120,70,40,.1),rgba(120,70,40,.1)),rgba(255,252,248,.92); color:var(--vy-muted); }
        .vy-ud-status .dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
        .vy-ud-switch { width:46px; height:27px; border-radius:30px; border:none; padding:3px; box-sizing:border-box; cursor:pointer; display:inline-flex; align-items:center; justify-content:flex-start; background:rgba(120,70,40,.18); box-shadow:inset 0 1px 3px rgba(120,40,10,.18); transition:background .18s, justify-content .18s; }
        .vy-ud-switch.is-on { background:var(--vy-grad); justify-content:flex-end; box-shadow:inset 0 1px 3px rgba(120,40,10,.3); }
        .vy-ud-switch .knob { width:21px; height:21px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(60,30,10,.28); display:block; }
        .vy-ud-switch:focus-visible { outline:2px solid var(--vy-accent-text); outline-offset:2px; }
        .vy-ud-hbtn { display:inline-flex; align-items:center; gap:7px; padding:9px 16px; border-radius:12px; cursor:pointer; font-family:var(--vy-sans); font-size:13px; font-weight:500; text-decoration:none; }
        .vy-ud-sync { margin-left:auto; font-size:11.5px; color:var(--vy-on-grad-soft); }

        .vy-ud-kpis { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:18px; }
        .vy-ud-kpi-k { font-size:11px; font-weight:500; letter-spacing:.08em; text-transform:uppercase; color:var(--vy-on-grad-soft); margin-bottom:5px; }
        .vy-ud-kpi-v { font-family:var(--vy-display); font-weight:600; font-size:26px; letter-spacing:-.02em; }
        .vy-ud-kpi-s { font-size:11.5px; color:var(--vy-on-grad-soft); margin-top:3px; }

        .vy-ud-top { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:18px; align-items:start; margin-bottom:18px; }
        @media (max-width:960px){ .vy-ud-top { grid-template-columns:1fr; } }
        .vy-ud-cols { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:18px; align-items:start; }
        @media (max-width:960px){ .vy-ud-cols { grid-template-columns:1fr; } }
        .vy-ud-stack { display:flex; flex-direction:column; gap:18px; min-width:0; }

        .vy-ud-cal { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }
        .vy-ud-dow { text-align:center; font-size:10px; font-weight:600; letter-spacing:.06em; color:var(--vy-on-grad-soft); }
        {{-- The interactive days are real <button>s (blocking a day is a real write, and a clickable <div> is
             unreachable by keyboard). The reset keeps them pixel-identical to the non-interactive cells. --}}
        .vy-ud-day { aspect-ratio:1; display:flex; align-items:center; justify-content:center; border-radius:9px; font-size:12.5px; font-variant-numeric:tabular-nums; font-family:var(--vy-sans); font-weight:400; padding:0; margin:0; width:100%; box-sizing:border-box; background:none; border:none; color:inherit; text-align:center; }
        button.vy-ud-day:focus-visible { outline:2px solid var(--vy-accent-text); outline-offset:2px; }
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
            <span class="vy-ud-status vy-ud-status--live"><span class="dot"></span>{{ __('livewire/owner/unit-detail.status_active') }}</span>
        @else
            <span class="vy-ud-status vy-ud-status--off"><span class="dot"></span>{{ __('livewire/owner/unit-detail.status_not_listed') }}</span>
        @endif
        @if ($unit['condition'] !== null)
            <span class="vy-ud-status vy-ud-status--off" style="background:rgba(190,32,28,.1);color:#B01E1E">{{ $unit['condition']['label'] }}</span>
        @endif
        @if ($bestBadge !== null)
            <span class="vy-ud-status" style="background:var(--vy-grad-dark);color:#F7D9C4">{{ trans_choice('livewire/owner/unit-detail.best_badge', $bestBadge['count'], ['count' => $bestBadge['count'], 'month' => $bestBadge['month']]) }}</span>
        @endif
        <a href="#vy-ud-pricing" class="vy-ud-hbtn vy-btn--accent" style="text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
            {{ __('livewire/owner/unit-detail.edit_pricing') }}
        </a>
        {{-- Sync/share — HONEST: OTA channel sync is account-gated; no fake "synced" state (#37/#39). --}}
        <span class="vy-ud-sync">{{ __('livewire/owner/unit-detail.sync_managed') }}</span>
    </div>

    {{-- ── KPIs — REAL per-unit ── --}}
    <div class="vy-ud-kpis vy-fade">
        <div class="vy-card" style="padding:16px 18px"><div class="vy-ud-kpi-k">{{ __('livewire/owner/unit-detail.kpi_occupancy') }}</div><div class="vy-ud-kpi-v">{{ $occupancy['pct'] }}%</div><div class="vy-ud-kpi-s">{{ $month['label'] }}</div></div>
        <div class="vy-card" style="padding:16px 18px"><div class="vy-ud-kpi-k">{{ __('livewire/owner/unit-detail.kpi_nights_sold') }}</div><div class="vy-ud-kpi-v">{{ $occupancy['nights'] }}</div><div class="vy-ud-kpi-s">{{ $month['label'] }}</div></div>
        <div class="vy-card" style="padding:16px 18px"><div class="vy-ud-kpi-k">{{ __('livewire/owner/unit-detail.kpi_net', ['month' => $month['label']]) }}</div><div class="vy-ud-kpi-v">{{ $attribution[0]['net_display'] ?? '—' }}</div><div class="vy-ud-kpi-s">{{ __('livewire/owner/unit-detail.kpi_attributed') }}</div></div>
    </div>

    {{-- ── Top: availability calendar (tap-to-block) + pricing ── --}}
    {{-- I18N: the Alpine-filled parts of four translated sentences (the popover's net and reason, the composer's day,
         the weekend preview's two prices) — static markup, so each sentence stays ONE key with its value in place. --}}
    @php
        $detailNetHtml = '<strong x-text="detail.net.join(\' · \')"></strong>';
        $detailReasonHtml = '<span x-text="detail.reason"></span>';
        $selDateHtml = '<strong x-text="sel"></strong>';
        $calendarUrl = route('owner.calendar', ['unit' => $unit['id']]);
        $weekendPriceHtml = '<strong style="color:var(--vy-accent-text)" x-text="'.\Illuminate\Support\Js::from($currencySymbol)->toHtml().' + Math.round(num(rate) * (1 + weekend/100))"></strong>';
        $weekdayPriceHtml = '<span x-text="'.\Illuminate\Support\Js::from($currencySymbol)->toHtml().' + rate"></span>';
    @endphp
    <div class="vy-ud-top">
        {{-- Availability calendar — REAL booked/blocked/open; tap an OPEN day → block; tap a BOOKED/BLOCKED day → detail --}}
        <section class="vy-card vy-fade" x-data="{ sel: null, detail: null, details: @js($dayDetails) }">
            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:4px">
                <h2 class="vy-card-title" style="font-size:16px">{{ __('livewire/owner/unit-detail.availability_heading', ['month' => $month['label']]) }}</h2>
                <div style="display:flex;align-items:center;gap:10px">
                    <a href="{{ route('owner.units.show', ['unit' => $unit['id'], 'month' => $month['prev']]) }}" class="vy-line-sub" style="text-decoration:none;font-size:15px">‹</a>
                    <a href="{{ route('owner.units.show', ['unit' => $unit['id'], 'month' => $month['next']]) }}" class="vy-line-sub" style="text-decoration:none;font-size:15px">›</a>
                    <span style="font-size:11.5px;color:var(--vy-faint)">{{ __('livewire/owner/unit-detail.tap_to_block') }}</span>
                </div>
            </div>
            <div style="font-size:12px;color:var(--vy-muted);margin-bottom:16px;font-variant-numeric:tabular-nums">{{ __('livewire/owner/unit-detail.calendar_counts', ['booked' => $calendar['booked'], 'blocked' => $calendar['blocked'], 'open' => $calendar['open']]) }}</div>
            <div class="vy-ud-cal" style="margin-bottom:6px">
                @foreach ($weekdays as $d)<div class="vy-ud-dow">{{ $d }}</div>@endforeach
            </div>
            <div class="vy-ud-cal">
                @for ($i = 1; $i < $calendar['first_weekday']; $i++)<div></div>@endfor
                {{-- A day's STATE reaches assistive tech as a word, never as a fill colour alone; the two
                     interactive states are <button>s so the block/detail actions are keyboard-operable. --}}
                @foreach ($calendar['days'] as $c)
                    @php $dayLabel = \Illuminate\Support\Carbon::parse($c['date'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('j F Y')); @endphp
                    @if ($c['state'] === 'open')
                        <button type="button" class="vy-ud-day vy-ud-day--open" :class="sel === @js($c['date']) ? 'is-sel' : ''" @click="sel = (sel === @js($c['date']) ? null : @js($c['date'])); detail = null" aria-label="{{ __('livewire/owner/unit-detail.day_open_aria', ['date' => $dayLabel]) }}">{{ $c['day'] }}</button>
                    @elseif ($c['state'] === 'booked' || $c['state'] === 'blocked')
                        <button type="button" class="vy-ud-day vy-ud-day--{{ $c['state'] }}" style="cursor:pointer" @click="detail = (detail && detail.date === @js($c['date'])) ? null : Object.assign({date: @js($c['date'])}, details[@js($c['date'])] || {}); sel = null" aria-label="{{ $c['state'] === 'booked' ? __('livewire/owner/unit-detail.day_booked_aria', ['date' => $dayLabel]) : __('livewire/owner/unit-detail.day_blocked_aria', ['date' => $dayLabel]) }}">{{ $c['day'] }}</button>
                    @else
                        <div class="vy-ud-day vy-ud-day--{{ $c['state'] }}">{{ $c['day'] }}<span class="vy-sr-only"> {{ __('livewire/owner/unit-detail.day_past_sr') }}</span></div>
                    @endif
                @endforeach
            </div>
            <div class="vy-ud-legend">
                <span style="display:inline-flex;align-items:center;gap:6px"><span class="sw" style="background:rgba(232,112,58,.16);border:1px solid rgba(232,112,58,.28)"></span>{{ __('livewire/owner/unit-detail.legend_booked') }}</span>
                <span style="display:inline-flex;align-items:center;gap:6px"><span class="sw" style="background:rgba(255,255,255,.5);border:1px solid rgba(120,70,40,.1)"></span>{{ __('livewire/owner/unit-detail.legend_open') }}</span>
                <span style="display:inline-flex;align-items:center;gap:6px"><span class="sw" style="background:#3A2E27"></span>{{ __('livewire/owner/unit-detail.legend_blocked') }}</span>
            </div>
            {{-- Wave-B RICHER DAY-DETAIL — a booked day shows the stay (guest NAME-only #28, dates, your net); a blocked
                 day shows its reason. Real data from dayDetails; no guest contact ever. --}}
            <div x-show="detail" x-cloak style="margin-top:16px;padding:14px 16px;border-top:1px solid var(--vy-hairline);background:rgba(255,255,255,.45);border-radius:12px">
                <template x-if="detail && detail.type === 'booked'">
                    <div>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                            <span style="font-weight:600;font-size:13.5px" x-text="detail.guest"></span>
                            <span style="font-size:11.5px;color:var(--vy-faint)" x-text="detail.reference ? @js(__('livewire/owner/unit-detail.detail_reference', ['reference' => ':reference'])).replace(':reference', detail.reference) : ''"></span>
                        </div>
                        <div style="font-size:12px;color:var(--vy-muted);margin-top:4px"><span x-text="detail.stay"></span> · <span x-text="detail.nights_label"></span></div>
                        <template x-if="detail.net && detail.net.length">
                            <div style="font-size:12px;color:var(--vy-ink);margin-top:6px">{!! __('livewire/owner/unit-detail.detail_your_net', ['net' => $detailNetHtml]) !!}</div>
                        </template>
                    </div>
                </template>
                <template x-if="detail && detail.type === 'blocked'">
                    <div style="font-size:13px;color:var(--vy-ink)">{!! __('livewire/owner/unit-detail.detail_blocked', ['reason' => $detailReasonHtml]) !!}</div>
                </template>
            </div>

            {{-- Block composer — appears when an open day is selected; a REAL block (OwnerBlockService, $capActions #20) --}}
            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                <form method="POST" action="{{ route('owner.calendar.block') }}" x-show="sel" x-cloak style="margin-top:16px;padding-top:16px;border-top:1px solid var(--vy-hairline);display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                    @csrf
                    <input type="hidden" name="unit_id" value="{{ $unit['id'] }}">
                    <input type="hidden" name="first_night" :value="sel">
                    <input type="hidden" name="last_night" :value="sel">
                    <span style="font-size:12.5px;color:var(--vy-muted)">{!! __('livewire/owner/unit-detail.block_day_as', ['date' => $selDateHtml]) !!}</span>
                    <select name="reason" class="vy-input" style="padding:6px 10px;font-size:12.5px;flex:0 1 auto">
                        @foreach ($blockReasons as $r)<option value="{{ $r }}">{{ $blockReasonLabels[$r] ?? $r }}</option>@endforeach
                    </select>
                    <button type="submit" class="vy-btn vy-btn--accent" style="padding:7px 15px;font-size:12.5px">{{ __('livewire/owner/unit-detail.block_day_button') }}</button>
                    <button type="button" class="vy-btn vy-btn--ghost" style="padding:7px 13px;font-size:12.5px" @click="sel = null">{{ __('common.cancel') }}</button>
                </form>
            @endownercan
            <p class="vy-line-sub" style="font-size:11px;margin-top:12px">{!! __('livewire/owner/unit-detail.blocked_days_note', ['url' => e($calendarUrl)]) !!}</p>
        </section>

        {{-- Pricing & availability — REAL nightly + min-stay + weekend uplift (OwnerRateService, Wave-A/B) --}}
        @once @include('partials.local-number-script') @endonce
        <section class="vy-card vy-fade" id="vy-ud-pricing" style="padding:20px" x-data="{ editing: false, rate: {{ $pricing['rate_units'] ?? 0 }}, weekend: {{ $pricing['weekend_pct'] !== null ? (float) $pricing['weekend_pct'] : 0 }}, num(v) { const n = window.vyLocalNumber ? window.vyLocalNumber.normalize(String(v), 2) : String(v); return n === null || isNaN(Number(n)) ? 0 : Number(n); } }">
            <div class="vy-eyebrow" style="margin-bottom:12px">{{ __('livewire/owner/unit-detail.pricing_heading') }}</div>

            {{-- Wave C · GATE 3 — a REAL owner pause/resume. Pausing removes the unit's capacity (certified availability
                 recompute) so it takes no new bookings; existing stays are kept. CAP_ACTIONS-gated. --}}
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border-radius:14px;background:rgba(255,255,255,.42);border:1px solid rgba(120,70,40,.08);margin-bottom:14px">
                <div style="min-width:0">
                    <div style="font-size:13.5px;font-weight:500">{{ $unit['active'] ? __('livewire/owner/unit-detail.open_for_bookings') : __('livewire/owner/unit-detail.paused') }}</div>
                    <div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px">{{ $unit['active'] ? __('livewire/owner/unit-detail.pause_hint') : __('livewire/owner/unit-detail.paused_hint') }}</div>
                </div>
                {{-- The design's compact toggle. A pill + a labelled button here duplicated the header's status and
                     squeezed the copy into five lines; the switch restores the wireframe and gives the sentence its
                     width back. The STATE stays in words to its left ("Open for bookings" / "Paused"), so it is never
                     carried by the switch's colour alone — and it is still one real gated POST. --}}
                <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
                    @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                        <form method="POST" action="{{ route('owner.units.toggle-status', $unit['id']) }}" style="margin:0">
                            @csrf
                            <button type="submit" class="vy-ud-switch {{ $unit['active'] ? 'is-on' : '' }}" role="switch" aria-checked="{{ $unit['active'] ? 'true' : 'false' }}" aria-label="{{ __('livewire/owner/unit-detail.open_for_bookings') }}"><span class="knob"></span></button>
                        </form>
                    @else
                        <span class="vy-ud-status {{ $unit['active'] ? 'vy-ud-status--live' : 'vy-ud-status--off' }}"><span class="dot"></span>{{ $unit['active'] ? __('livewire/owner/unit-detail.status_active') : __('livewire/owner/unit-detail.paused') }}</span>
                    @endownercan
                </div>
            </div>

            <div x-show="! editing">
                <div class="vy-ud-prow"><span class="k">{{ __('livewire/owner/unit-detail.nightly_rate') }}</span><span class="v">{{ $pricing['rate_display'] ?? __('livewire/owner/unit-detail.not_set') }}</span></div>
                <div class="vy-ud-prow"><span class="k">{{ __('livewire/owner/unit-detail.weekend_uplift') }}</span><span class="v">{{ $pricing['weekend_pct'] !== null && (float) $pricing['weekend_pct'] > 0 ? rtrim(rtrim(number_format((float) $pricing['weekend_pct'], 2), '0'), '.').'%' : __('common.none') }}</span></div>
                <div class="vy-ud-prow"><span class="k">{{ __('livewire/owner/unit-detail.minimum_stay') }}</span><span class="v">{{ $pricing['min_stay'] !== null ? trans_choice('livewire/owner/unit-detail.nights', $pricing['min_stay'], ['count' => $pricing['min_stay']]) : __('common.none') }}</span></div>
                @if (! $pricing['editable'])
                    <p class="vy-line-sub" style="font-size:11.5px;margin-top:12px">{{ __('livewire/owner/unit-detail.rate_shared_note') }}</p>
                @endif
            </div>

            {{-- Edit — REAL: nightly + weekend uplift + min-stay → owner.rates.apply (returns here). The uplift IS
                 offered and IS real (Wave A/B — RateAndRestrictionService writes the uplifted price onto Fri/Sat
                 nights); an earlier note here still called it unmodelled. The whole edit affordance is
                 CAP_ACTIONS-gated (co-owner only) + editable-only, so a view-only sub-user or a shared room type
                 never gets a write control (#20/#30 — gate the control). --}}
            @if ($pricing['editable'])
                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                    <button type="button" x-show="! editing" class="vy-btn vy-btn--ghost" style="margin-top:14px;padding:8px 14px;font-size:12.5px" @click="editing = true">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                        {{ __('livewire/owner/unit-detail.edit_pricing') }}
                    </button>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('owner.rates.apply') }}">
                        @csrf
                        <input type="hidden" name="room_type_id" value="{{ $pricing['room_type_id'] }}">
                        <input type="hidden" name="redirect" value="unit">
                        <input type="hidden" name="unit" value="{{ $unit['id'] }}">
                        <input type="hidden" name="start_date" value="{{ now()->toDateString() }}">
                        <input type="hidden" name="end_date" value="{{ now()->addYear()->subDay()->toDateString() }}">
                        <label class="vy-field-label" for="ud_rate">{{ __('livewire/owner/unit-detail.nightly_rate_label', ['symbol' => $currencySymbol]) }}</label>
                        <input id="ud_rate" type="text" inputmode="decimal" autocomplete="off" name="rate" x-model="rate" class="vy-input" style="width:100%;margin-bottom:11px" required>
                        <label class="vy-field-label" for="ud_weekend">{{ __('livewire/owner/unit-detail.weekend_uplift_label') }}</label>
                        <input id="ud_weekend" type="number" name="weekend_uplift" step="1" min="0" max="200" x-model="weekend" class="vy-input" style="width:100%;margin-bottom:11px" placeholder="{{ __('livewire/owner/unit-detail.weekend_uplift_placeholder') }}">
                        <label class="vy-field-label" for="ud_min">{{ __('livewire/owner/unit-detail.minimum_stay_label') }}</label>
                        <input id="ud_min" type="number" name="min_stay" min="1" max="365" value="{{ $pricing['min_stay'] }}" class="vy-input" style="width:100%;margin-bottom:8px" placeholder="{{ __('common.optional') }}">
                        {{-- Live preview — the derived Fri/Sat price (base × (1 + uplift%)) before saving --}}
                        <p x-show="weekend > 0" x-cloak class="vy-line-sub" style="font-size:11.5px;margin:0 0 8px;color:var(--vy-ink)">{!! __('livewire/owner/unit-detail.weekend_preview', ['weekend_price' => $weekendPriceHtml, 'weekday_price' => $weekdayPriceHtml]) !!}</p>
                        <p class="vy-line-sub" style="font-size:11px;margin:0 0 12px">{{ __('livewire/owner/unit-detail.applies_note') }}</p>
                        <div style="display:flex;gap:9px">
                            <button type="button" class="vy-btn vy-btn--ghost" style="flex:1;justify-content:center" @click="editing = false">{{ __('common.cancel') }}</button>
                            <button type="submit" class="vy-btn vy-btn--accent" style="flex:1;justify-content:center">{{ __('common.save_changes') }}</button>
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
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:4px">{{ __('livewire/owner/unit-detail.revenue_heading') }}</h2>
                <div class="vy-line-sub" style="margin-bottom:16px">{{ __('livewire/owner/unit-detail.revenue_sub') }}</div>
                @if ($revenueTrend['has_data'])
                    <div class="vy-ud-rev-chart">
                        @foreach ($revenueTrend['bars'] as $b)
                            <div class="vy-ud-bar-col" title="{{ $b['label'] }} · {{ $b['display'] }}">
                                <span style="font-size:9.5px;color:var(--vy-faint)">{{ $b['display'] }}</span>
                                <div class="vy-ud-bar" style="height:{{ $b['pct'] }}%{{ $b['pct'] > 0 ? ';min-height:2px' : '' }}"></div>
                                <span style="font-size:10px;color:var(--vy-faint)">{{ $b['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-empty">{{ __('livewire/owner/unit-detail.revenue_empty') }}</div>
                @endif
            </section>

            {{-- Occupancy · 12 months (real) --}}
            <section class="vy-card vy-fade">
                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin-bottom:16px">
                    <h2 class="vy-card-title" style="font-size:16px">{{ __('livewire/owner/unit-detail.occupancy_heading') }}</h2>
                    <span class="vy-line-sub">{{ __('livewire/owner/unit-detail.occupancy_summary', ['avg' => $occupancyTrend['avg'], 'peak' => $occupancyTrend['peak_pct'], 'month' => $occupancyTrend['peak_label']]) }}</span>
                </div>
                <div class="vy-ud-rev-chart">
                    @foreach ($occupancyTrend['bars'] as $b)
                        <div class="vy-ud-bar-col" title="{{ $b['label'] }} · {{ $b['display'] }}">
                            <div class="vy-ud-bar vy-ud-bar--occ" style="height:{{ $b['pct'] }}%{{ $b['pct'] > 0 ? ';min-height:2px' : '' }}"></div>
                            <span style="font-size:10px;color:var(--vy-faint)">{{ $b['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Attribution / deductions · month (real, reconciles #17) --}}
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:4px">{{ __('livewire/owner/unit-detail.attribution_heading', ['month' => $month['label']]) }}</h2>
                <div class="vy-line-sub" style="margin-bottom:8px">{{ __('livewire/owner/unit-detail.attribution_sub') }}</div>
                @forelse ($attribution as $group)
                    <table class="vy-table" style="width:100%">
                        <tbody>
                            @foreach ($group['credits'] as $line)<tr><td>{{ $line['label'] }}</td><td class="vy-num vy-mono">{{ $line['display'] }}</td></tr>@endforeach
                            @foreach ($group['deductions'] as $line)<tr><td>{{ $line['label'] }}</td><td class="vy-num vy-mono">{{ $line['display'] }}</td></tr>@endforeach
                            <tr><td><strong>{{ __('livewire/owner/unit-detail.net') }}</strong></td><td class="vy-num vy-mono" style="color:var(--vy-accent-text)">{{ $group['net_display'] }}</td></tr>
                        </tbody>
                    </table>
                @empty
                    <div class="vy-empty">{{ __('livewire/owner/unit-detail.attribution_empty') }}</div>
                @endforelse
            </section>

            {{-- Stays · month (real, name-only #28) --}}
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:4px">{{ __('livewire/owner/unit-detail.stays_heading', ['month' => $month['label']]) }}</h2>
                <div class="vy-line-sub" style="margin-bottom:8px">{{ __('livewire/owner/unit-detail.stays_sub') }}</div>
                <div style="overflow-x:auto">
                    <table class="vy-table">
                        <thead><tr><th>{{ __('livewire/owner/unit-detail.col_reference') }}</th><th>{{ __('livewire/owner/unit-detail.col_stay') }}</th><th class="vy-num">{{ __('livewire/owner/unit-detail.col_nights') }}</th><th>{{ __('livewire/owner/unit-detail.col_source') }}</th><th>{{ __('common.status') }}</th><th class="vy-num">{{ __('livewire/owner/unit-detail.col_your_net') }}</th></tr></thead>
                        <tbody>
                            @forelse ($bookings as $b)
                                <tr>
                                    <td class="vy-mono">{{ $b['reference'] ?? '—' }}</td>
                                    <td>{{ $b['stay'] }}</td>
                                    <td class="vy-num">{{ $b['nights'] }}</td>
                                    <td>{{ $b['source_label'] ?? '—' }}</td>
                                    <td>@if ($b['status_meta'])<span class="vy-status vy-status--{{ $b['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $b['status_meta']['label'] }}</span>@else — @endif</td>
                                    <td class="vy-num vy-mono">{{ $b['net_displays'] === [] ? __('livewire/owner/unit-detail.not_yet_attributed') : implode(' · ', $b['net_displays']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="vy-empty">{{ __('livewire/owner/unit-detail.stays_empty') }}</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Reviews & rating — REAL (rating + list; byline first-name+initial #28; no category bars — unmodelled) --}}
            <section class="vy-card vy-fade">
                <h2 class="vy-card-title" style="font-size:16px;margin-bottom:8px">{{ __('livewire/owner/unit-detail.reviews_heading') }}</h2>
                @if ($reviews['has_reviews'])
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:6px">
                        <span class="vy-ud-reviews-score">{{ number_format((float) $reviews['average'], 1) }}</span>
                        <div>
                            {{-- DO10-4 capstone: this row used `round()`, so a 4.6 average printed FIVE filled
                                 stars — the owner's own unit read as perfect when it is not. The shared
                                 component floors (4.6 → 4 filled + 1 empty), differs filled/empty by GLYPH,
                                 and carries ONE accessible name instead of ten mute glyphs. --}}
                            <x-star-rating :value="$reviews['average']" size="15px" />
                            <div class="vy-line-sub">{{ trans_choice('livewire/owner/unit-detail.reviews_published', $reviews['count'], ['count' => $reviews['count']]) }}</div>
                        </div>
                    </div>
                    @foreach ($reviews['list'] as $r)
                        <div class="vy-ud-review">
                            <div style="display:flex;justify-content:space-between;gap:10px"><span style="font-size:13px;font-weight:600">{{ $r['byline'] }}</span><span role="img" aria-label="{{ __('livewire/owner/unit-detail.review_stars_aria', ['rating' => $r['rating']]) }}" style="color:var(--vy-amber-text);font-size:12px"><span aria-hidden="true">{{ $r['stars'] }}</span></span></div>
                            @if ($r['body'])<div class="vy-line-sub" style="font-size:12.5px;margin-top:4px;line-height:1.5">{{ $r['body'] }}</div>@endif
                            <div style="font-size:11px;color:var(--vy-faint);margin-top:3px">{{ $r['at'] }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="vy-empty">{{ __('livewire/owner/unit-detail.reviews_empty') }}</div>
                @endif
            </section>
        </div>

        {{-- RIGHT RAIL --}}
        <div class="vy-ud-stack">
            {{-- Unit details (real) --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">{{ __('livewire/owner/unit-detail.unit_details') }}</span>
                <div class="vy-ud-prow"><span class="k">{{ __('livewire/owner/unit-detail.detail_code') }}</span><span class="v">{{ $unit['code'] }}</span></div>
                <div class="vy-ud-prow"><span class="k">{{ __('livewire/owner/unit-detail.detail_room_type') }}</span><span class="v">{{ $unit['type'] }}</span></div>
                <div class="vy-ud-prow"><span class="k">{{ __('livewire/owner/unit-detail.detail_type') }}</span><span class="v">{{ $unit['kind_label'] }}</span></div>
                <div class="vy-ud-prow"><span class="k">{{ __('livewire/owner/unit-detail.detail_sleeps') }}</span><span class="v">{{ $unit['max_occupancy'] }}</span></div>
                <div class="vy-ud-prow"><span class="k">{{ __('livewire/owner/unit-detail.detail_listing') }}</span><span class="v">{{ $unit['active'] ? __('livewire/owner/unit-detail.status_active') : __('livewire/owner/unit-detail.listing_off') }}</span></div>
            </section>

            {{-- Commission (real) --}}
            @if ($commission !== null)
                <section class="vy-card vy-fade vy-ud-rail-card">
                    <span class="vy-eyebrow">{{ __('livewire/owner/unit-detail.commission') }}</span>
                    <div style="display:flex;align-items:baseline;justify-content:space-between;margin-top:8px">
                        <span style="font-family:var(--vy-display);font-weight:600;font-size:20px">{{ $commission['display'] ?? __('livewire/owner/unit-detail.not_set') }}</span>
                        <a href="{{ route('owner.agreement') }}" style="color:var(--vy-accent-text);font-size:12.5px;font-weight:600">{{ __('livewire/owner/unit-detail.agreement_link') }}</a>
                    </div>
                    <div class="vy-line-sub" style="font-size:11.5px;margin-top:4px">{{ __('livewire/owner/unit-detail.terms_in_effect', ['terms' => $commission['scope_label']]) }}</div>
                </section>
            @endif

            {{-- Channel mix (real; "Direct saved you €X" refused #39) --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">{{ __('livewire/owner/unit-detail.channel_mix_heading', ['month' => $month['label']]) }}</span>
                @if ($channelMix['has_data'])
                    @foreach ($channelMix['rows'] as $c)
                        <div style="margin-top:12px">
                            <div style="display:flex;justify-content:space-between;font-size:12.5px"><span style="font-weight:500">{{ $c['label'] }}</span><span class="vy-muted">{{ trans_choice('livewire/owner/unit-detail.channel_nights', $c['nights'], ['count' => $c['nights'], 'pct' => $c['pct']]) }}</span></div>
                            <div class="vy-track" style="margin-top:5px"><span style="width:{{ $c['pct'] }}%"></span></div>
                        </div>
                    @endforeach
                    <p class="vy-line-sub" style="font-size:11px;margin-top:12px">{{ __('livewire/owner/unit-detail.channel_mix_note') }}</p>
                @else
                    <div class="vy-empty" style="padding:16px 0">{{ __('livewire/owner/unit-detail.channel_mix_empty') }}</div>
                @endif
            </section>

            {{-- Cleaning — Wave-B: the REAL HousekeepingTask rows (read-only), owner-scoped. The owner SEES the
                 schedule but managing it is the operator's job (honest); never a fabricated cleaner (#39). --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">{{ __('livewire/owner/unit-detail.cleaning_heading') }}</span>
                @if ($cleanings['has_data'])
                    <div style="margin-top:10px">
                        @foreach ($cleanings['rows'] as $cl)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:9px 0;border-top:1px solid var(--vy-hairline)">
                                <div style="min-width:0">
                                    <div style="font-size:12.5px;font-weight:500;color:var(--vy-ink)">{{ $cl['type'] }}</div>
                                    <div style="font-size:11px;color:var(--vy-faint)">{{ $cl['upcoming'] ? __('livewire/owner/unit-detail.cleaning_date_upcoming', ['date' => $cl['date']]) : $cl['date'] }}</div>
                                </div>
                                <span class="vy-status vy-status--{{ $cl['tone'] }}" style="flex-shrink:0"><span class="vy-dot"></span>{{ $cl['status'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="vy-line-sub" style="font-size:11px;margin-top:10px">{{ __('livewire/owner/unit-detail.cleaning_note') }}</p>
                @else
                    <p class="vy-line-sub" style="font-size:12.5px;margin-top:8px;line-height:1.55">{{ __('livewire/owner/unit-detail.cleaning_empty') }}</p>
                @endif
            </section>

            {{-- Guest messages → the owner↔operator RELAY (name-free #28; never owner→guest) --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">{{ __('livewire/owner/unit-detail.message_heading') }}</span>
                <p class="vy-line-sub" style="font-size:11.5px;margin:6px 0 10px">{{ __('livewire/owner/unit-detail.message_sub') }}</p>
                <form method="POST" action="{{ route('owner.messages.start') }}">
                    @csrf
                    <input type="hidden" name="subject" value="{{ __('livewire/owner/unit-detail.message_subject', ['unit' => $unit['name']]) }}">
                    <label class="vy-sr-only" for="vy-lbl-unit-detail-body">{{ __('livewire/owner/unit-detail.message_placeholder') }}</label>
                    <textarea id="vy-lbl-unit-detail-body" name="body" rows="3" class="vy-input" style="width:100%;resize:vertical" placeholder="{{ __('livewire/owner/unit-detail.message_placeholder') }}" required></textarea>
                    <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center;margin-top:10px">{{ __('livewire/owner/unit-detail.send_message') }}</button>
                </form>
                <a href="{{ route('owner.messages') }}" style="color:var(--vy-accent-text);font-size:12.5px;font-weight:600;display:inline-block;margin-top:10px">{{ __('livewire/owner/unit-detail.open_inbox') }}</a>
            </section>

            {{-- Your money — payouts are per-statement, not per-unit (honest; no fabricated per-unit timeline #39) --}}
            <section class="vy-card vy-fade vy-ud-rail-card">
                <span class="vy-eyebrow">{{ __('livewire/owner/unit-detail.your_money') }}</span>
                <a href="{{ route('owner.balance') }}" style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid var(--vy-hairline);text-decoration:none;color:var(--vy-ink);font-size:13px;margin-top:6px"><span>{{ __('livewire/owner/unit-detail.balance') }}</span><span style="color:var(--vy-accent-text)">{{ __('livewire/owner/unit-detail.open_link') }}</span></a>
                <a href="{{ route('owner.payouts') }}" style="display:flex;justify-content:space-between;padding:10px 0;border-top:1px solid var(--vy-hairline);text-decoration:none;color:var(--vy-ink);font-size:13px"><span>{{ __('livewire/owner/unit-detail.payouts') }}</span><span style="color:var(--vy-accent-text)">{{ __('livewire/owner/unit-detail.open_link') }}</span></a>
                <p class="vy-line-sub" style="font-size:11px;margin-top:8px">{{ __('livewire/owner/unit-detail.payouts_note') }}</p>
            </section>
        </div>
    </div>
</x-owner.shell>
