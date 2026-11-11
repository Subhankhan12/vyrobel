{{-- Owner Rates — exact-match rebuild (#81) to the decoded "Owner Rates" wireframe, on REAL owner-scoped data.
     Four sections: Proposed changes (REAL rate-proposals #67 — Approve/Decline + the BUILT Counter), Current
     rates (real Base/Weekend/Occ — Peak omitted, unmodelled #39), Pricing guardrails (REAL, enforcing on the
     manager's proposals), Where you sit (the REAL average alone — comp-set/market omitted, external data we
     lack #39). PLUS the app's real DIRECT-EDIT editor (a shipped capability the wireframe's proposals model
     omits — kept, documented). Every write is a controller POST, CAP_ACTIONS, audited (#20).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="rates" :title="__('livewire/owner/rates.title')" :subtitle="__('livewire/owner/rates.subtitle')" :viewer="$viewer">
    <style>
        /* ⚠️ x-show ERASES an inline display (XShowInlineDisplayContractTest): the counter form lays out by class. */
        .vy-rt-counter-form { margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; }
        /* ⚠️ This page was written with the prototype's RAW pre-AA greys (#A69A92, #8A8079) rather than the
           tokens — 21 measured AA failures, the worst of the owner realm so far, on the copy that explains
           what the guardrail actually does. All moved to --vy-muted (4.80-6.76 across this page's
           sampled backgrounds; the old pair measured 1.89-3.74). */
        /* ⚠️⚠️ WAS AN INLINE `grid-template-columns: 1fr 1fr 1fr` (twice) — `minmax(auto, 1fr)`, a
           min-content floor, and 232px of sideways scroll: the worst of the twelve.
           ⚠️ THESE ARE CONTROLS, AND ONE OF THEM SETS A REAL NIGHTLY RATE. So they STACK on a phone
           rather than sit in a scroller: three date/number inputs at ~105px each are not fillable, and a
           control a thumb has to scroll to find is the failure the portfolio fix was withdrawn over.
           ⚠️ NOTHING ABOUT THE INPUTS CHANGED — no name, no id, no posted key. Only the container. */
        .vy-rt-fieldrow { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:10px; }
        @media (max-width:700px) { .vy-rt-fieldrow { grid-template-columns:minmax(0,1fr); } }
        .vy-rt-card { background:rgba(255,252,248,.58); backdrop-filter:blur(22px) saturate(1.15); border:1px solid rgba(255,255,255,.75); border-radius:20px; box-shadow:0 14px 40px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65); padding:22px 24px; }
        /* The wireframe's two columns — a wide "decide" column and a narrower "constrain/context" rail.
           Its own flex values: the main column grows aggressively (999) from a 360px basis, the rail from
           300px with a 280px floor, so they stack on a narrow screen without a media query. */
        .vy-rt-cols { display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start; }
        .vy-rt-col-main { flex:999 1 360px; min-width:0; }
        .vy-rt-col-side { flex:1 1 300px; min-width:280px; display:flex; flex-direction:column; gap:18px; }
        .vy-rt-h2 { margin:0; font-family:var(--vy-display),'Sora',sans-serif; font-weight:500; font-size:16px; letter-spacing:-.01em; }
        .vy-rt-eyebrow { font-size:11px; font-weight:500; letter-spacing:.06em; text-transform:uppercase; color:var(--vy-muted); }
        .vy-rt-prop { border:1px solid rgba(255,255,255,.7); background:rgba(255,255,255,.4); border-radius:15px; padding:16px 18px; }
        .vy-rt-delta { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .vy-rt-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:8px 15px; border-radius:11px; font:inherit; font-size:12.5px; font-weight:500; cursor:pointer; border:1px solid transparent; }
        .vy-rt-approve { background:linear-gradient(140deg,#5FA35A,#3F7A38); color:#fff; border:none; }
        .vy-rt-decline { background:rgba(255,255,255,.7); border:1px solid rgba(120,70,40,.16); color:var(--vy-muted); }
        .vy-rt-counter { background:rgba(255,255,255,.7); border:1px solid rgba(120,70,40,.16); color:#C2410C; }
        .vy-rt-seg { display:flex; gap:6px; padding:4px; border-radius:14px; background:rgba(120,70,40,.06); border:1px solid rgba(120,70,40,.10); }
        .vy-rt-segopt { flex:1; padding:9px 0; border-radius:11px; border:none; background:transparent; color:var(--vy-muted); font:inherit; font-size:12.5px; font-weight:500; cursor:pointer; transition:background .14s; }
        .vy-rt-segopt.on { background:#fff; color:#C2410C; box-shadow:0 2px 8px rgba(150,90,50,.18); }
        .vy-rt-in { display:flex; align-items:center; gap:6px; border:1px solid rgba(120,70,40,.14); background:rgba(255,255,255,.75); border-radius:12px; padding:9px 12px; }
        .vy-rt-in input { flex:1; min-width:0; border:none; outline:none; background:transparent; font:inherit; font-size:13.5px; color:var(--vy-ink); padding:0; }
        /* ⚠️ `outline:none` on the inner input left these controls with NO visible focus indicator at all
           (WCAG 2.4.7) — and they are the guardrail's floor/ceiling and the counter-offer rate. The ring
           goes on the WRAPPER via :focus-within, matching .vy-input:focus elsewhere in the shell. */
        .vy-rt-in:focus-within { border-color: var(--vy-accent-border); box-shadow: 0 0 0 3px rgba(232, 112, 58, .10); }
    </style>

    @php $notice = session('owner_notice') ?? session('rate_notice'); $error = session('owner_error') ?? session('rate_error'); @endphp
    @if ($notice)
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ $notice }}</div></div>
    @endif
    @if ($error)
        <div class="vy-card vy-fade" style="border-left:3px solid #B4400F;margin-bottom:16px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ $error }}</div></div>
    @endif

    {{-- ══ 1 · PROPOSED RATE CHANGES (real #67 + the built counter) ══ --}}
    {{-- ⚠️ The wireframe's TWO COLUMNS: everything the owner READS AND DECIDES on the left (the proposals
         they act on, then the rates those proposals are about), and everything that CONSTRAINS or
         CONTEXTUALISES on the right (the guardrails, then where they sit). The page was a stack of
         full-width bands with one 2-up row in the middle, so the guardrail card — the thing the left
         column's proposals are judged against — sat below the fold of what it governs. --}}
    <div class="vy-rt-cols">
    <div class="vy-rt-col-main">

    <section class="vy-rt-card vy-fade" style="margin-bottom:18px">
        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px">
            <h2 class="vy-rt-h2">{{ __('livewire/owner/rates.proposed_title') }}</h2>
            @if (count($proposals) > 0)<span class="vy-rt-eyebrow" style="color:var(--vy-accent)">{{ trans_choice('livewire/owner/rates.to_review', count($proposals), ['count' => count($proposals)]) }}</span>@endif
        </div>
        <p style="font-size:12.5px;color:var(--vy-muted);margin:6px 0 0">{{ __('livewire/owner/rates.proposed_intro') }}</p>

        @forelse ($proposals as $p)
            <div class="vy-rt-prop" style="margin-top:14px" x-data="{ counter: false }">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap">
                    <div style="min-width:0;flex:1">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span style="font-size:14px;font-weight:600;color:var(--vy-ink)">{{ $p['villa'] }}</span>
                            <span class="vy-rt-eyebrow" style="letter-spacing:.02em;text-transform:none;color:var(--vy-muted)">{{ $p['window'] }}</span>
                        </div>
                        @if ($p['rationale'] !== '')<div style="font-size:12.5px;color:var(--vy-muted);margin-top:4px;line-height:1.5">{{ $p['rationale'] }}</div>@endif
                        <div style="font-size:11.5px;color:var(--vy-muted);margin-top:6px">{{ __('livewire/owner/rates.effective', ['date' => $p['effective']]) }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end">
                            <span class="vy-mono" style="font-size:12.5px;color:var(--vy-muted);text-decoration:line-through">{{ $p['from_str'] }}</span>
                            <span class="vy-mono" style="font-size:16px;font-weight:600;color:var(--vy-ink)">{{ $p['to_str'] }}</span>
                        </div>
                        <div style="font-size:10.5px;color:var(--vy-muted);margin-top:1px">{{ __('livewire/owner/rates.per_night') }}</div>
                        <div style="margin-top:6px"><span class="vy-rt-delta" style="background:{{ $p['up'] ? 'rgba(79,122,70,.14)' : 'rgba(224,146,30,.14)' }};color:{{ $p['up'] ? '#3F6A38' : '#93610C' }}">{{ $p['delta_str'] }}</span></div>
                    </div>
                </div>

                @if ($p['countered'])
                    {{-- The owner has countered — the ball is with the operator. Honest, no action buttons. --}}
                    <div style="margin-top:14px;padding:10px 13px;border-radius:11px;background:rgba(217,102,46,.08);border:1px solid rgba(217,102,46,.2);font-size:12.5px;color:#8A5A2C">
                        {!! __('livewire/owner/rates.countered', ['amount' => e($p['counter_str'])]) !!}
                    </div>
                @else
                    <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
                        <form method="POST" action="{{ route('owner.rate-proposals.approve', $p['id']) }}">@csrf<button type="submit" class="vy-rt-btn vy-rt-approve"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>{{ __('livewire/owner/rates.approve') }}</button></form>
                        <form method="POST" action="{{ route('owner.rate-proposals.decline', $p['id']) }}">@csrf<button type="submit" class="vy-rt-btn vy-rt-decline">{{ __('livewire/owner/rates.decline') }}</button></form>
                        <button type="button" class="vy-rt-btn vy-rt-counter" @click="counter = ! counter">{{ __('livewire/owner/rates.counter_button') }}</button>
                    </div>
                    {{-- Counter form (BUILT #81) — the owner proposes a different nightly rate back to the operator. --}}
                    <form method="POST" action="{{ route('owner.rate-proposals.counter', $p['id']) }}" x-show="counter" x-cloak class="vy-rt-counter-form">
                        @csrf
                        <div style="flex:1;min-width:150px">
                            <div class="vy-rt-eyebrow" style="margin-bottom:6px">{{ __('livewire/owner/rates.your_counter', ['symbol' => $currencySymbol]) }}</div>
                            <div class="vy-rt-in"><span style="color:var(--vy-muted)">{{ $currencySymbol }}</span><input id="vy-lbl-rates-counter-rate" type="text" inputmode="decimal" name="counter_rate" required placeholder="{{ __('livewire/owner/rates.counter_placeholder') }}" maxlength="12"></div>
                        </div>
                        <div style="flex:2;min-width:180px">
                            <div class="vy-rt-eyebrow" style="margin-bottom:6px">{!! __('livewire/owner/rates.note_optional') !!}</div>
                            <div class="vy-rt-in"><label class="vy-sr-only" for="vy-lbl-rates-counter-note">{{ __('livewire/owner/rates.why_this_rate') }}</label>
<input id="vy-lbl-rates-counter-note" type="text" name="counter_note" placeholder="{{ __('livewire/owner/rates.why_this_rate') }}" maxlength="500"></div>
                        </div>
                        <button type="submit" class="vy-rt-btn vy-rt-approve" style="background:linear-gradient(140deg,#E8703A,#C2410C)">{{ __('livewire/owner/rates.send_counter') }}</button>
                    </form>
                @endif
            </div>
        @empty
            <div style="margin-top:14px;padding:18px;border-radius:14px;background:rgba(120,70,40,.04);font-size:13px;color:var(--vy-muted);text-align:center">{{ __('livewire/owner/rates.proposed_empty') }}</div>
        @endforelse
    </section>

        {{-- ══ 2 · CURRENT RATES (real Base/Weekend/Occ — Peak omitted) ══ --}}
        <section class="vy-rt-card vy-fade" style="animation-delay:.04s">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
                <h2 class="vy-rt-h2">{{ __('livewire/owner/rates.current_title') }}</h2>
                <span style="font-size:12px;color:var(--vy-muted)">{{ __('livewire/owner/rates.current_sub') }}</span>
            </div>
            @if ($cards === [])
                <div class="vy-empty" style="margin-top:12px">{{ __('livewire/owner/rates.no_room_types') }}<div class="vy-line-sub" style="margin-top:6px">{{ __('livewire/owner/rates.no_room_types_hint') }}</div></div>
            @else
                <div style="overflow-x:auto;margin-top:12px">
                    <table class="vy-table">
                        <thead><tr><th>{{ __('livewire/owner/rates.col_villa') }}</th><th class="vy-num">{{ __('livewire/owner/rates.col_base') }}</th><th class="vy-num">{{ __('livewire/owner/rates.col_weekend') }}</th><th class="vy-num">{{ __('livewire/owner/rates.col_peak') }}</th><th class="vy-num">{{ __('livewire/owner/rates.col_occ') }}</th></tr></thead>
                        <tbody>
                            @foreach ($cards as $card)
                                <tr>
                                    <td><div class="vy-row-month">{{ $card['name'] }}</div>@unless ($card['has_plan'])<div class="vy-row-sub" style="color:var(--vy-faint)">{{ __('livewire/owner/rates.no_rate_plan') }}</div>@endunless</td>
                                    <td class="vy-num vy-mono">{{ $card['rate_display'] ?? '—' }}</td>
                                    <td class="vy-num vy-mono">{{ $card['weekend_display'] ?? '—' }}</td>
                                    {{-- ⚠️ PEAK reads `rate_seasons` — the covering season or an em-dash. Never a
                                         fabricated figure, and a season that has been DEFINED but not yet applied
                                         says so, because those nights are not selling at it yet. --}}
                                    <td class="vy-num vy-mono">
                                        @if ($card['season'])
                                            {{ $card['season']['display'] }}
                                            <div class="vy-row-sub" style="font-weight:500">@if ($card['season']['applied']){{ $card['season']['name'] }}@else{{ __('livewire/owner/rates.season_not_applied', ['name' => $card['season']['name']]) }}@endif</div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="vy-num vy-mono" style="color:{{ $card['occ_pct'] >= 85 ? '#3F6A38' : ($card['occ_pct'] >= 75 ? '#93610C' : '#B4400F') }}">{{ $card['occ_pct'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    </div>{{-- /vy-rt-col-main --}}

    <div class="vy-rt-col-side">

        {{-- ══ 3 · PRICING GUARDRAILS (BUILT, enforcing) ══ --}}
        <section class="vy-rt-card vy-fade" style="animation-delay:.08s"
                 x-data="{ mode: @js($guardrail['mode']), auto: {{ $guardrail['auto_pct'] }} }">
            <h2 class="vy-rt-h2">{{ __('livewire/owner/rates.guardrails_title') }}</h2>
            <p style="font-size:12px;color:var(--vy-muted);margin:6px 0 14px">{{ __('livewire/owner/rates.guardrails_intro') }}</p>
            <form method="POST" action="{{ route('owner.rates.guardrails') }}">
                @csrf
                <input type="hidden" name="pricing_mode" :value="mode">

                <div class="vy-rt-eyebrow" style="margin-bottom:7px">{{ __('livewire/owner/rates.pricing_mode') }}</div>
                <div class="vy-rt-seg" style="margin-bottom:16px">
                    <button type="button" class="vy-rt-segopt" :class="{ on: mode === 'dynamic' }" @click="mode = 'dynamic'">{{ __('livewire/owner/rates.mode_dynamic') }}</button>
                    <button type="button" class="vy-rt-segopt" :class="{ on: mode === 'fixed' }" @click="mode = 'fixed'">{{ __('livewire/owner/rates.mode_fixed') }}</button>
                </div>

                <div class="vy-rt-eyebrow" style="margin-bottom:7px">{!! __('livewire/owner/rates.auto_approve_within') !!}</div>
                {{-- ⚠️ The visible heading above is a <div>, not a <label for>, so this slider had NO
                     accessible name — the control that sets how much of the owner's pricing authority is
                     delegated. aria-label + aria-valuetext so the value is announced as a tolerance. --}}
                <input type="range" name="auto_approve_pct" min="0" max="25" step="1" x-model="auto"
                       aria-label="{{ __('livewire/owner/rates.auto_approve_label') }}"
                       :aria-valuetext="@js(__('livewire/owner/rates.auto_approve_valuetext', ['pct' => ':pct'])).replace(':pct', auto)"
                       style="width:100%;accent-color:var(--vy-accent);margin-bottom:6px">
                {{-- WAVE 2 · #6 — scoped to what is ACTUALLY enforced. This said "Every change needs your
                     sign-off before it goes live", which is not true of every change: RateProposalService
                     really does enforce the mode, the ±% tolerance AND the floor/ceiling on a PROPOSED rate,
                     but the operator's direct rate write (RateActionController) never consults the guardrail.
                     The copy now promises exactly the proposal path. Binding the operator's direct changes to
                     an owner's floor/ceiling is a real shift of control between operator and owner and is a
                     PRODUCT DECISION, deliberately not made here — see DECISIONS.md. --}}
                <p style="font-size:11px;color:var(--vy-muted);margin:0 0 6px" x-text="Number(auto) === 0 ? @js(__('livewire/owner/rates.auto_approve_none')) : @js(__('livewire/owner/rates.auto_approve_some', ['pct' => ':pct'])).replace(':pct', auto)"></p>
                <p style="font-size:11px;color:var(--vy-muted);margin:0 0 16px">{!! __('livewire/owner/rates.limits_scope') !!}</p>

                {{-- ⚠️ minmax(0,1fr), not 1fr: a grid track's default min-width is `auto`, so these two
                     inputs refused to shrink below their content and pushed the whole page into
                     horizontal overflow the moment the guardrail card moved into the narrower rail. --}}
                <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px;margin-bottom:16px">
                    <div>
                        <div class="vy-rt-eyebrow" style="margin-bottom:7px">{{ __('livewire/owner/rates.floor_per_night') }}</div>
                        <div class="vy-rt-in"><span style="color:var(--vy-muted)">{{ $currencySymbol }}</span><label class="vy-sr-only" for="vy-lbl-rates-floor">{{ __('livewire/owner/rates.floor_min') }}</label>
<input id="vy-lbl-rates-floor" type="text" inputmode="decimal" name="floor" value="{{ $guardrail['floor'] }}" placeholder="{{ __('livewire/owner/rates.floor_min') }}" maxlength="12"></div>
                    </div>
                    <div>
                        <div class="vy-rt-eyebrow" style="margin-bottom:7px">{{ __('livewire/owner/rates.ceiling_per_night') }}</div>
                        <div class="vy-rt-in"><span style="color:var(--vy-muted)">{{ $currencySymbol }}</span><label class="vy-sr-only" for="vy-lbl-rates-ceiling">{{ __('livewire/owner/rates.ceiling_max') }}</label>
<input id="vy-lbl-rates-ceiling" type="text" inputmode="decimal" name="ceiling" value="{{ $guardrail['ceiling'] }}" placeholder="{{ __('livewire/owner/rates.ceiling_max') }}" maxlength="12"></div>
                    </div>
                </div>

                <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">{{ __('livewire/owner/rates.save_guardrails') }}</button>
            </form>
        </section>

        {{-- ══ 4 · WHERE YOU SIT (real average; comp-set/market honestly omitted) ══ --}}
        <section class="vy-rt-card vy-fade" style="animation-delay:.12s">
            <h2 class="vy-rt-h2">{{ __('livewire/owner/rates.where_you_sit') }}</h2>
            <div style="margin-top:14px">
                <div class="vy-rt-eyebrow">{{ __('livewire/owner/rates.average_rate') }}</div>
                <div class="vy-mono" style="font-family:var(--vy-display),'Sora',sans-serif;font-weight:600;font-size:28px;letter-spacing:-.02em;margin-top:4px">{{ $avgDisplay ?? '—' }}</div>
                {{-- ⚠️ The wireframe puts a comp-set average and a "priced 5% below market" bar here. There is
                     no market-rate source, so there is no comparison to draw — and a market bar is exactly the
                     kind of authoritative-looking figure that must not be invented (#39). The sentence stays. --}}
                <p style="font-size:12px;color:var(--vy-muted);line-height:1.55;margin:12px 0 0;padding-top:12px;border-top:1px solid rgba(120,70,40,.12)">
                    {{ __('livewire/owner/rates.market_benchmarking') }}
                </p>
            </div>
        </section>
    </div>{{-- /vy-rt-col-side --}}
    </div>{{-- /vy-rt-cols --}}

    {{-- ══ DIRECT EDIT (the app's real shipped capability — kept alongside the manager-proposes model) ══ --}}
    @if ($cards !== [])
        @php
            $rateMap = [];
            foreach ($cards as $c) { $rateMap[$c['id']] = ['rate' => $c['rate_units'] ?? '', 'weekend' => $c['weekend_pct'] !== null ? (float) $c['weekend_pct'] : 0, 'min' => $c['min_stay'] ?? '']; }
            $sel = $rateMap[$selectedId] ?? ['rate' => '', 'weekend' => 0, 'min' => ''];
        @endphp
        <section class="vy-rt-card vy-fade" style="animation-delay:.16s;margin-top:18px"
                 x-data="{ map: @js($rateMap), rate: '{{ old('rate', $sel['rate']) }}', weekend: {{ old('weekend_uplift', $sel['weekend']) }}, min: '{{ old('min_stay', $sel['min']) }}',
                           prefill(id) { const m = this.map[id]; if (m) { this.rate = m.rate; this.weekend = m.weekend; this.min = m.min; } } }">
            <h2 class="vy-rt-h2">{{ __('livewire/owner/rates.direct_title') }}</h2>
            <p style="font-size:12px;color:var(--vy-muted);margin:6px 0 14px">{{ __('livewire/owner/rates.direct_intro') }}</p>
            <form method="POST" action="{{ route('owner.rates.apply') }}">
                @csrf
                <div class="vy-rt-fieldrow">
                    <div class="vy-field" style="margin:0">
                        <label class="vy-field-label" for="room_type_id">{{ __('livewire/owner/rates.room_type') }}</label>
                        <select id="room_type_id" name="room_type_id" class="vy-input" required @change="prefill($event.target.value)">
                            @foreach ($cards as $card)<option value="{{ $card['id'] }}" @selected($selectedId === $card['id'])>{{ $card['name'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="start_date">{{ __('livewire/owner/rates.from') }}</label><input id="start_date" type="date" name="start_date" value="{{ old('start_date', $from) }}" class="vy-input" required></div>
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="end_date">{{ __('livewire/owner/rates.to') }}</label><input id="end_date" type="date" name="end_date" value="{{ old('end_date', $to) }}" class="vy-input" required></div>
                </div>
                <div class="vy-rt-fieldrow">
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="rate">{{ __('livewire/owner/rates.nightly_rate', ['symbol' => $currencySymbol]) }}</label><input id="rate" type="text" inputmode="decimal" name="rate" x-model="rate" placeholder="{{ __('livewire/owner/rates.rate_placeholder') }}" class="vy-input" required></div>
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="weekend_uplift">{{ __('livewire/owner/rates.weekend_uplift') }}</label><input id="weekend_uplift" type="text" inputmode="numeric" name="weekend_uplift" x-model="weekend" placeholder="0" class="vy-input"></div>
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="min_stay">{{ __('livewire/owner/rates.min_stay') }}</label><input id="min_stay" type="text" inputmode="numeric" name="min_stay" x-model="min" placeholder="{{ __('livewire/owner/rates.min_stay_placeholder') }}" class="vy-input"></div>
                </div>
                <div x-show="weekend > 0 && Number(rate) > 0" x-cloak class="vy-line-sub" style="font-size:12px;margin:0 0 12px;color:var(--vy-ink)">
                    @php
                        // I18N-01 — the two live figures stay Alpine elements; the sentence around them is one key.
                        $vyWeekendHtml = '<strong style="color:var(--vy-accent)" x-text="'.\Illuminate\Support\Js::from($currencySymbol)->toHtml().' + Math.round(Number(rate) * (1 + Number(weekend)/100))"></strong>';
                        $vyWeekdayHtml = '<span x-text="'.\Illuminate\Support\Js::from($currencySymbol)->toHtml().' + rate"></span>';
                    @endphp
                    {!! __('livewire/owner/rates.weekend_preview', ['weekend' => $vyWeekendHtml, 'weekday' => $vyWeekdayHtml]) !!}
                </div>
                <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">{{ __('livewire/owner/rates.update_rate') }}</button>
            </form>
        </section>
    @endif
</x-owner.shell>
