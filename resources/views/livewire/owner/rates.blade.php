{{-- Owner Rates — exact-match rebuild (#81) to the decoded "Owner Rates" wireframe, on REAL owner-scoped data.
     Four sections: Proposed changes (REAL rate-proposals #67 — Approve/Decline + the BUILT Counter), Current
     rates (real Base/Weekend/Occ — Peak omitted, unmodelled #39), Pricing guardrails (REAL, enforcing on the
     manager's proposals), Where you sit (the REAL average alone — comp-set/market omitted, external data we
     lack #39). PLUS the app's real DIRECT-EDIT editor (a shipped capability the wireframe's proposals model
     omits — kept, documented). Every write is a controller POST, CAP_ACTIONS, audited (#20).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="rates" :title="'Rates & pricing'" :subtitle="'Review the pricing your manager proposes and set the limits they work within.'" :viewer="$viewer">
    <style>
        .vy-rt-card { background:rgba(255,252,248,.58); backdrop-filter:blur(22px) saturate(1.15); border:1px solid rgba(255,255,255,.75); border-radius:20px; box-shadow:0 14px 40px rgba(150,90,50,.16), inset 0 1px 0 rgba(255,255,255,.65); padding:22px 24px; }
        .vy-rt-h2 { margin:0; font-family:var(--vy-display),'Sora',sans-serif; font-weight:500; font-size:16px; letter-spacing:-.01em; }
        .vy-rt-eyebrow { font-size:11px; font-weight:500; letter-spacing:.06em; text-transform:uppercase; color:#9C9089; }
        .vy-rt-prop { border:1px solid rgba(255,255,255,.7); background:rgba(255,255,255,.4); border-radius:15px; padding:16px 18px; }
        .vy-rt-delta { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .vy-rt-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:8px 15px; border-radius:11px; font:inherit; font-size:12.5px; font-weight:500; cursor:pointer; border:1px solid transparent; }
        .vy-rt-approve { background:linear-gradient(140deg,#5FA35A,#3F7A38); color:#fff; border:none; }
        .vy-rt-decline { background:rgba(255,255,255,.7); border:1px solid rgba(120,70,40,.16); color:#8A8079; }
        .vy-rt-counter { background:rgba(255,255,255,.7); border:1px solid rgba(120,70,40,.16); color:#C2410C; }
        .vy-rt-seg { display:flex; gap:6px; padding:4px; border-radius:14px; background:rgba(120,70,40,.06); border:1px solid rgba(120,70,40,.10); }
        .vy-rt-segopt { flex:1; padding:9px 0; border-radius:11px; border:none; background:transparent; color:#8A8079; font:inherit; font-size:12.5px; font-weight:500; cursor:pointer; transition:background .14s; }
        .vy-rt-segopt.on { background:#fff; color:#C2410C; box-shadow:0 2px 8px rgba(150,90,50,.18); }
        .vy-rt-in { display:flex; align-items:center; gap:6px; border:1px solid rgba(120,70,40,.14); background:rgba(255,255,255,.75); border-radius:12px; padding:9px 12px; }
        .vy-rt-in input { flex:1; min-width:0; border:none; outline:none; background:transparent; font:inherit; font-size:13.5px; color:var(--vy-ink); padding:0; }
    </style>

    @php $notice = session('owner_notice') ?? session('rate_notice'); $error = session('owner_error') ?? session('rate_error'); @endphp
    @if ($notice)
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ $notice }}</div></div>
    @endif
    @if ($error)
        <div class="vy-card vy-fade" style="border-left:3px solid #B4400F;margin-bottom:16px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ $error }}</div></div>
    @endif

    {{-- ══ 1 · PROPOSED RATE CHANGES (real #67 + the built counter) ══ --}}
    <section class="vy-rt-card vy-fade" style="margin-bottom:18px">
        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px">
            <h2 class="vy-rt-h2">Proposed rate changes</h2>
            @if (count($proposals) > 0)<span class="vy-rt-eyebrow" style="color:var(--vy-accent)">{{ count($proposals) }} to review</span>@endif
        </div>
        <p style="font-size:12.5px;color:#8A8079;margin:6px 0 0">Your manager suggests these based on demand. Approve, decline, or send a counter.</p>

        @forelse ($proposals as $p)
            <div class="vy-rt-prop" style="margin-top:14px" x-data="{ counter: false }">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap">
                    <div style="min-width:0;flex:1">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span style="font-size:14px;font-weight:600;color:var(--vy-ink)">{{ $p['villa'] }}</span>
                            <span class="vy-rt-eyebrow" style="letter-spacing:.02em;text-transform:none;color:#A69A92">{{ $p['window'] }}</span>
                        </div>
                        @if ($p['rationale'] !== '')<div style="font-size:12.5px;color:#8A8079;margin-top:4px;line-height:1.5">{{ $p['rationale'] }}</div>@endif
                        <div style="font-size:11.5px;color:#A69A92;margin-top:6px">Effective {{ $p['effective'] }}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end">
                            <span class="vy-mono" style="font-size:12.5px;color:#A69A92;text-decoration:line-through">{{ $p['from_str'] }}</span>
                            <span class="vy-mono" style="font-size:16px;font-weight:600;color:var(--vy-ink)">{{ $p['to_str'] }}</span>
                        </div>
                        <div style="font-size:10.5px;color:#A69A92;margin-top:1px">per night</div>
                        <div style="margin-top:6px"><span class="vy-rt-delta" style="background:{{ $p['up'] ? 'rgba(79,122,70,.14)' : 'rgba(224,146,30,.14)' }};color:{{ $p['up'] ? '#3F6A38' : '#93610C' }}">{{ $p['delta_str'] }}</span></div>
                    </div>
                </div>

                @if ($p['countered'])
                    {{-- The owner has countered — the ball is with the operator. Honest, no action buttons. --}}
                    <div style="margin-top:14px;padding:10px 13px;border-radius:11px;background:rgba(217,102,46,.08);border:1px solid rgba(217,102,46,.2);font-size:12.5px;color:#8A5A2C">
                        You countered with <strong class="vy-mono" style="color:var(--vy-accent)">{{ $p['counter_str'] }}</strong> — awaiting your manager’s response.
                    </div>
                @else
                    <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
                        <form method="POST" action="{{ route('owner.rate-proposals.approve', $p['id']) }}">@csrf<button type="submit" class="vy-rt-btn vy-rt-approve"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Approve</button></form>
                        <form method="POST" action="{{ route('owner.rate-proposals.decline', $p['id']) }}">@csrf<button type="submit" class="vy-rt-btn vy-rt-decline">Decline</button></form>
                        <button type="button" class="vy-rt-btn vy-rt-counter" @click="counter = ! counter">Counter…</button>
                    </div>
                    {{-- Counter form (BUILT #81) — the owner proposes a different nightly rate back to the operator. --}}
                    <form method="POST" action="{{ route('owner.rate-proposals.counter', $p['id']) }}" x-show="counter" x-cloak style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
                        @csrf
                        <div style="flex:1;min-width:150px">
                            <div class="vy-rt-eyebrow" style="margin-bottom:6px">Your counter · € / night</div>
                            <div class="vy-rt-in"><span style="color:#A69A92">€</span><input type="text" inputmode="decimal" name="counter_rate" required placeholder="e.g. 450" maxlength="12"></div>
                        </div>
                        <div style="flex:2;min-width:180px">
                            <div class="vy-rt-eyebrow" style="margin-bottom:6px">Note <span style="text-transform:none;letter-spacing:0;color:#B7ADA4">· optional</span></div>
                            <div class="vy-rt-in"><input type="text" name="counter_note" placeholder="Why this rate" maxlength="500"></div>
                        </div>
                        <button type="submit" class="vy-rt-btn vy-rt-approve" style="background:linear-gradient(140deg,#E8703A,#C2410C)">Send counter</button>
                    </form>
                @endif
            </div>
        @empty
            <div style="margin-top:14px;padding:18px;border-radius:14px;background:rgba(120,70,40,.04);font-size:13px;color:#8A8079;text-align:center">No proposed changes right now — you’re all caught up.</div>
        @endforelse
    </section>

    <div class="vy-cols" style="display:grid;grid-template-columns:1.4fr 1fr;gap:18px;align-items:start">

        {{-- ══ 2 · CURRENT RATES (real Base/Weekend/Occ — Peak omitted) ══ --}}
        <section class="vy-rt-card vy-fade" style="animation-delay:.04s">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px">
                <h2 class="vy-rt-h2">Current rates</h2>
                <span style="font-size:12px;color:#A69A92">Live nightly rates across your units</span>
            </div>
            @if ($cards === [])
                <div class="vy-empty" style="margin-top:12px">You don’t have any room types to price here.<div class="vy-line-sub" style="margin-top:6px">Rates are set per room type; where your unit shares a room type with another party, your management team sets the rate.</div></div>
            @else
                <div style="overflow-x:auto;margin-top:12px">
                    <table class="vy-table">
                        <thead><tr><th>Villa</th><th class="vy-num">Base</th><th class="vy-num">Weekend</th><th class="vy-num">Occ.</th></tr></thead>
                        <tbody>
                            @foreach ($cards as $card)
                                <tr>
                                    <td><div class="vy-row-month">{{ $card['name'] }}</div>@unless ($card['has_plan'])<div class="vy-row-sub" style="color:var(--vy-faint)">No rate plan</div>@endunless</td>
                                    <td class="vy-num vy-mono">{{ $card['rate_display'] ?? '—' }}</td>
                                    <td class="vy-num vy-mono">{{ $card['weekend_display'] ?? '—' }}</td>
                                    <td class="vy-num vy-mono" style="color:{{ $card['occ_pct'] >= 85 ? '#3F6A38' : ($card['occ_pct'] >= 75 ? '#93610C' : '#B4400F') }}">{{ $card['occ_pct'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- ══ 3 · PRICING GUARDRAILS (BUILT, enforcing) ══ --}}
        <section class="vy-rt-card vy-fade" style="animation-delay:.08s"
                 x-data="{ mode: @js($guardrail['mode']), auto: {{ $guardrail['auto_pct'] }} }">
            <h2 class="vy-rt-h2">Pricing guardrails</h2>
            <p style="font-size:12px;color:#8A8079;margin:6px 0 14px">The limits your manager prices within.</p>
            <form method="POST" action="{{ route('owner.rates.guardrails') }}">
                @csrf
                <input type="hidden" name="pricing_mode" :value="mode">

                <div class="vy-rt-eyebrow" style="margin-bottom:7px">Pricing mode</div>
                <div class="vy-rt-seg" style="margin-bottom:16px">
                    <button type="button" class="vy-rt-segopt" :class="{ on: mode === 'dynamic' }" @click="mode = 'dynamic'">Dynamic</button>
                    <button type="button" class="vy-rt-segopt" :class="{ on: mode === 'fixed' }" @click="mode = 'fixed'">Fixed</button>
                </div>

                <div class="vy-rt-eyebrow" style="margin-bottom:7px">Auto-approve changes within <span class="vy-mono" style="color:var(--vy-accent)">±<span x-text="auto"></span>%</span></div>
                <input type="range" name="auto_approve_pct" min="0" max="25" step="1" x-model="auto" style="width:100%;accent-color:var(--vy-accent);margin-bottom:6px">
                <p style="font-size:11px;color:#A69A92;margin:0 0 16px" x-text="Number(auto) === 0 ? 'Every change needs your sign-off before it goes live.' : ('Changes within ±' + auto + '% go live automatically; anything larger waits for your approval.')"></p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px">
                    <div>
                        <div class="vy-rt-eyebrow" style="margin-bottom:7px">Floor / night</div>
                        <div class="vy-rt-in"><span style="color:#A69A92">€</span><input type="text" inputmode="decimal" name="floor" value="{{ $guardrail['floor'] }}" placeholder="min" maxlength="12"></div>
                    </div>
                    <div>
                        <div class="vy-rt-eyebrow" style="margin-bottom:7px">Ceiling / night</div>
                        <div class="vy-rt-in"><span style="color:#A69A92">€</span><input type="text" inputmode="decimal" name="ceiling" value="{{ $guardrail['ceiling'] }}" placeholder="max" maxlength="12"></div>
                    </div>
                </div>

                <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">Save guardrails</button>
            </form>
        </section>
    </div>

    {{-- ══ 4 · WHERE YOU SIT (real average; comp-set/market honestly omitted) ══ --}}
    <section class="vy-rt-card vy-fade" style="animation-delay:.12s;margin-top:18px">
        <h2 class="vy-rt-h2">Where you sit</h2>
        <div style="display:flex;align-items:center;gap:32px;flex-wrap:wrap;margin-top:14px">
            <div>
                <div class="vy-rt-eyebrow">Your average rate</div>
                <div class="vy-mono" style="font-family:var(--vy-display),'Sora',sans-serif;font-weight:600;font-size:28px;letter-spacing:-.02em;margin-top:4px">{{ $avgDisplay ?? '—' }}</div>
            </div>
            <div style="flex:1;min-width:200px;font-size:12px;color:#8A8079;line-height:1.55;padding-left:20px;border-left:1px solid rgba(120,70,40,.12)">
                Market benchmarking — how your rates compare to similar local properties — is coming once we connect a market-rate source. We won’t show a comparison until it’s real.
            </div>
        </div>
    </section>

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
            <h2 class="vy-rt-h2">Adjust a rate directly</h2>
            <p style="font-size:12px;color:#8A8079;margin:6px 0 14px">You can also set your own rate directly — it goes live immediately (no sign-off), for every unit in the room type.</p>
            <form method="POST" action="{{ route('owner.rates.apply') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px">
                    <div class="vy-field" style="margin:0">
                        <label class="vy-field-label" for="room_type_id">Room type</label>
                        <select id="room_type_id" name="room_type_id" class="vy-input" required @change="prefill($event.target.value)">
                            @foreach ($cards as $card)<option value="{{ $card['id'] }}" @selected($selectedId === $card['id'])>{{ $card['name'] }}</option>@endforeach
                        </select>
                    </div>
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="start_date">From</label><input id="start_date" type="date" name="start_date" value="{{ old('start_date', $from) }}" class="vy-input" required></div>
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="end_date">To</label><input id="end_date" type="date" name="end_date" value="{{ old('end_date', $to) }}" class="vy-input" required></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:10px">
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="rate">Nightly rate (€)</label><input id="rate" type="text" inputmode="decimal" name="rate" x-model="rate" placeholder="e.g. 175" class="vy-input" required></div>
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="weekend_uplift">Weekend uplift (%)</label><input id="weekend_uplift" type="text" inputmode="numeric" name="weekend_uplift" x-model="weekend" placeholder="0" class="vy-input"></div>
                    <div class="vy-field" style="margin:0"><label class="vy-field-label" for="min_stay">Min stay (nights)</label><input id="min_stay" type="text" inputmode="numeric" name="min_stay" x-model="min" placeholder="blank" class="vy-input"></div>
                </div>
                <div x-show="weekend > 0 && Number(rate) > 0" x-cloak class="vy-line-sub" style="font-size:12px;margin:0 0 12px;color:var(--vy-ink)">
                    Fri &amp; Sat nights become <strong style="color:var(--vy-accent)" x-text="'€' + Math.round(Number(rate) * (1 + Number(weekend)/100))"></strong> / night; weekdays stay <span x-text="'€' + rate"></span>.
                </div>
                <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">Update rate</button>
            </form>
        </section>
    @endif
</x-owner.shell>
