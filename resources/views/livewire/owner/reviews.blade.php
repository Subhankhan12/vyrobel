{{-- Owner Reviews & reputation (Phase 5 · SG4 → exact-match rebuild) — matched to the decoded "Owner Reviews"
     wireframe on REAL owner-scoped data. Shell + --vy-* tokens (warm glass, Sora/Inter, vyFadeUp).
     🔒 #28 owner-scoped (the owner's OWN units) + guest INITIAL-only byline; #39 every rating/aggregate DERIVED
     from real published reviews (never stored/fabricated); the operator RESPONSE is read-only (the operator owns
     it, reviews.respond #20). REAL: overall/response-rate/last-30/by-villa/breakdown/feed/responses. HONEST: the
     pending state = "Awaiting a reply" (real, not a fabricated "being drafted"); the OTA review INGEST + the
     Airbnb/Booking.com brand split are account-gated; NO fabricated SLA. By-category = GATE 2 (category ratings).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="reviews" :title="'Reviews & reputation'" :subtitle="'What guests are saying across your villas — '.$agency.' replies for you.'" :viewer="$viewer">
    <style>
        .vy-rv-grid { display:flex; flex-wrap:wrap; gap:20px; align-items:flex-start; }
        .vy-rv-left { flex:999 1 360px; min-width:0; display:flex; flex-direction:column; gap:20px; }
        .vy-rv-right { flex:1 1 290px; min-width:270px; display:flex; flex-direction:column; gap:20px; }
        .vy-rv-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:16px; }
        .vy-rv-villa { display:grid; grid-template-columns:1.3fr 2fr 110px 90px; gap:14px; align-items:center; padding:12px 4px; border-bottom:1px solid rgba(200,180,165,.22); }
        .vy-rv-villa:last-child { border-bottom:none; }
        .vy-rv-bar { height:8px; border-radius:30px; background:rgba(200,180,165,.24); overflow:hidden; }
        .vy-rv-bar > span { display:block; height:100%; border-radius:30px; background:linear-gradient(90deg,#E8A24A,#C2410C); }
        .vy-rv-chip { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:30px; font-size:11px; font-weight:600; font-variant-numeric:tabular-nums; }
        .vy-rv-filt { padding:6px 13px; border-radius:30px; border:1px solid var(--vy-hairline); background:rgba(255,255,255,.5); color:var(--vy-muted); font:inherit; font-size:12px; font-weight:500; cursor:pointer; transition:all .14s; }
        .vy-rv-filt.is-on { border-color:var(--vy-accent-border,rgba(194,65,12,.28)); background:rgba(194,65,12,.12); color:var(--vy-accent); }
        .vy-rv-review { border:1px solid rgba(255,255,255,.7); background:rgba(255,255,255,.4); border-radius:16px; padding:16px 18px; }
        .vy-rv-av { width:40px; height:40px; border-radius:50%; color:#fff; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-family:var(--vy-display); font-weight:600; font-size:14px; }
        .vy-rv-src { display:inline-flex; padding:2px 9px; border-radius:30px; font-size:10.5px; font-weight:600; }
        .vy-rv-reply { margin-top:11px; padding:11px 13px; border-radius:12px; background:rgba(217,102,46,.07); border-left:2.5px solid rgba(194,65,12,.4); }
        .vy-rv-pending { display:inline-flex; align-items:center; gap:7px; margin-top:11px; padding:6px 12px; border-radius:30px; background:rgba(224,146,30,.13); color:#93610C; font-size:11.5px; font-weight:600; }
        .vy-rv-dist { display:grid; grid-template-columns:30px 1fr 34px; gap:10px; align-items:center; }
    </style>

    @php
        // Real reservation-source → badge colours (the Airbnb/Booking.com brand split is unmodelled — honest).
        $srcColor = fn (string $k) => match ($k) {
            'ota' => ['bg' => 'rgba(30,80,180,.1)', 'co' => '#1E50B4'],
            'walk_in', 'agent', 'corporate', 'group' => ['bg' => 'rgba(120,70,40,.1)', 'co' => '#8A6A55'],
            default => ['bg' => 'rgba(217,102,46,.13)', 'co' => '#B4400F'],   // direct / manual
        };
        $avGrad = ['linear-gradient(140deg,#E8703A,#C2410C)', 'linear-gradient(140deg,#6FA764,#4F7A46)', 'linear-gradient(140deg,#E8A63A,#C08A1E)', 'linear-gradient(140deg,#8A6A55,#5E4636)'];
        $reviewsJson = collect($reviews)->map(fn ($r, $i) => $r + ['idx' => $i])->values();
    @endphp

    @if (! $summary['has_reviews'])
        <section class="vy-card vy-fade" style="padding:40px;text-align:center;color:var(--vy-muted);font-size:14px">
            No reviews yet for your units. As guests review their completed stays, your rating and your team’s replies appear here.
        </section>
    @else
    <div class="vy-rv-grid" x-data="{ filter: 'all', rows: {{ \Illuminate\Support\Js::from(collect($reviewsJson)->pluck('unit_key')) }}, get shown() { return this.filter === 'all' ? this.rows.length : this.rows.filter(k => k === this.filter).length; } }">

        {{-- ══ LEFT ══ --}}
        <div class="vy-rv-left">

            {{-- Hero KPIs --}}
            <div class="vy-rv-kpis">
                <div class="vy-card vy-fade" style="padding:18px 20px;animation-delay:.12s">
                    <div class="vy-eyebrow">Overall rating</div>
                    <div style="display:flex;align-items:baseline;gap:8px;margin-top:8px">
                        <span style="font-family:var(--vy-display);font-weight:600;font-size:30px;letter-spacing:-.02em;font-variant-numeric:tabular-nums">{{ number_format((float) $summary['average'], 1) }}</span>
                        <x-star-rating :value="$summary['average']" size="16px" gap="2px" />
                    </div>
                    <div style="font-size:12px;color:var(--vy-faint);margin-top:4px">{{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('review', $summary['count']) }} · all villas</div>
                </div>
                <div class="vy-card vy-fade" style="padding:18px 20px;animation-delay:.18s">
                    <div class="vy-eyebrow">Response rate</div>
                    <div style="font-family:var(--vy-display);font-weight:600;font-size:30px;letter-spacing:-.02em;font-variant-numeric:tabular-nums;margin-top:8px">{{ $summary['response_rate'] }}%</div>
                    <div style="font-size:12px;color:var(--vy-faint);margin-top:4px">{{ $summary['avg_response_hours'] !== null ? 'replies within ~'.$summary['avg_response_hours'].' '.\Illuminate\Support\Str::plural('hour', $summary['avg_response_hours']) : $summary['responded'].' of '.$summary['count'].' replied' }}</div>
                </div>
                <div class="vy-card vy-fade" style="padding:18px 20px;animation-delay:.24s">
                    <div class="vy-eyebrow">Last 30 days</div>
                    <div style="display:flex;align-items:baseline;gap:8px;margin-top:8px">
                        <span style="font-family:var(--vy-display);font-weight:600;font-size:30px;letter-spacing:-.02em;font-variant-numeric:tabular-nums">{{ $summary['last30_count'] }}</span>
                        @if ($summary['last30_average'] !== null)
                            <span class="vy-rv-chip" style="background:rgba(79,122,70,.13);color:#3F6A38">avg {{ number_format((float) $summary['last30_average'], 1) }}</span>
                        @endif
                    </div>
                    <div style="font-size:12px;color:var(--vy-faint);margin-top:4px">new {{ \Illuminate\Support\Str::plural('review', $summary['last30_count']) }}</div>
                </div>
            </div>

            {{-- By villa --}}
            <section class="vy-card vy-fade" style="padding:22px;animation-delay:.28s">
                <h2 class="vy-card-title" style="margin-bottom:14px">By villa</h2>
                @foreach ($summary['by_villa'] as $v)
                    <div class="vy-rv-villa">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $v['name'] }}</div>
                            <div style="font-size:11.5px;color:var(--vy-faint);margin-top:2px">{{ $v['count'] }} {{ \Illuminate\Support\Str::plural('review', $v['count']) }}</div>
                        </div>
                        <div class="vy-rv-bar"><span style="width:{{ $v['pct'] }}%"></span></div>
                        <div style="display:flex;align-items:baseline;gap:6px;justify-content:flex-end">
                            <span style="font-family:var(--vy-display);font-weight:600;font-size:17px;font-variant-numeric:tabular-nums">{{ number_format((float) $v['rating'], 1) }}</span>
                            <span style="color:#E8A63A;font-size:13px">★</span>
                        </div>
                        <div style="text-align:right">
                            <span class="vy-rv-chip" style="{{ $v['direction'] === 'up' ? 'background:rgba(79,122,70,.13);color:#3F6A38' : ($v['direction'] === 'down' ? 'background:rgba(224,146,30,.14);color:#93610C' : 'background:rgba(120,70,40,.08);color:var(--vy-muted)') }}">{{ $v['trend'] }}</span>
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- Recent reviews --}}
            <section class="vy-card vy-fade" style="padding:22px;animation-delay:.34s">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap">
                    <div>
                        <h2 class="vy-card-title" style="margin:0">Recent reviews</h2>
                        <div style="font-size:12.5px;color:var(--vy-faint);margin-top:3px"><span x-text="shown"></span> shown · newest first</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <button type="button" class="vy-rv-filt" :class="filter === 'all' ? 'is-on' : ''" @click="filter = 'all'">All villas</button>
                        @foreach ($summary['by_villa'] as $v)
                            <button type="button" class="vy-rv-filt" :class="filter === @js($v['unit_id']) ? 'is-on' : ''" @click="filter = @js($v['unit_id'])">{{ \Illuminate\Support\Str::of($v['name'])->afterLast(' ')->value() ?: $v['name'] }}</button>
                        @endforeach
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px">
                    @foreach ($reviewsJson as $r)
                        <div class="vy-rv-review" data-rv-row x-show="filter === 'all' || filter === @js($r['unit_key'])" x-transition>
                            <div style="display:flex;align-items:flex-start;gap:13px">
                                <span class="vy-rv-av" style="background:{{ $avGrad[$r['idx'] % count($avGrad)] }}">{{ $r['initials'] }}</span>
                                <div style="flex:1;min-width:0">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
                                        <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap">
                                            <span style="font-size:13.5px;font-weight:600;color:var(--vy-ink)">{{ $r['byline'] }}</span>
                                            <span style="color:#E8A63A;font-size:13px;letter-spacing:1.5px">{{ $r['stars_on'] }}</span><span style="color:#D9C8BC;font-size:13px;letter-spacing:1.5px;margin-left:-6px">{{ $r['stars_off'] }}</span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:8px">
                                            @php $sc = $srcColor($r['source_key']); @endphp
                                            <span class="vy-rv-src" style="background:{{ $sc['bg'] }};color:{{ $sc['co'] }}">{{ $r['source'] }}</span>
                                            <span style="font-size:11.5px;color:var(--vy-faint)">{{ $r['when'] }}</span>
                                        </div>
                                    </div>
                                    <div style="font-size:12px;color:var(--vy-faint);margin-top:3px">{{ $r['villa'] }}</div>
                                    @if ($r['body'] !== '')
                                        <div style="font-size:13px;color:#4A3E36;line-height:1.55;margin-top:9px">{{ $r['body'] }}</div>
                                    @endif
                                    @if (count($r['tags']) > 0)
                                        <div style="margin-top:8px">@foreach ($r['tags'] as $tag)<span style="display:inline-block;padding:3px 9px;border-radius:14px;background:rgba(255,255,255,.55);border:1px solid var(--vy-hairline);font-size:11px;color:var(--vy-muted);margin:0 5px 5px 0">{{ $tag }}</span>@endforeach</div>
                                    @endif
                                    @if ($r['response'] !== '')
                                        <div class="vy-rv-reply">
                                            <div style="font-size:11px;font-weight:600;color:#B4400F;margin-bottom:4px">{{ $agency }} replied</div>
                                            <div style="font-size:12.5px;color:#6E5C50;line-height:1.5">{{ $r['response'] }}</div>
                                        </div>
                                    @elseif ($r['pending'])
                                        <div class="vy-rv-pending"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5l3 2"/></svg>Awaiting a reply from your team</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ══ RIGHT ══ --}}
        <div class="vy-rv-right">

            {{-- Rating breakdown --}}
            <section class="vy-card vy-fade" style="padding:22px;animation-delay:.18s">
                <h2 class="vy-card-title" style="margin-bottom:15px">Rating breakdown</h2>
                <div style="display:flex;flex-direction:column;gap:10px">
                    @foreach ($summary['distribution'] as $d)
                        <div class="vy-rv-dist">
                            <span style="font-size:12px;font-weight:600;color:#6E5C50;font-variant-numeric:tabular-nums">{{ $d['star'] }}<span style="color:#E8A63A">★</span></span>
                            <div class="vy-rv-bar" style="height:7px"><span style="width:{{ $d['pct'] }}%;background:{{ $d['star'] >= 4 ? 'linear-gradient(90deg,#E8A24A,#C2410C)' : ($d['star'] === 3 ? '#E8A63A' : '#C9BBB0') }}"></span></div>
                            <span style="font-size:12px;color:var(--vy-faint);text-align:right;font-variant-numeric:tabular-nums">{{ $d['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- By category — REAL per-category averages (guest sub-ratings); rendered only when guests rated them
                 (never a fabricated score). The softest category links the REAL Wave-C rate-proposals (owner.rates). --}}
            @if (count($summary['categories']) > 0)
                <section class="vy-card vy-fade" style="padding:22px;animation-delay:.24s">
                    <h2 class="vy-card-title" style="margin-bottom:15px">By category</h2>
                    <div style="display:flex;flex-direction:column;gap:13px">
                        @foreach ($summary['categories'] as $c)
                            <div>
                                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:5px">
                                    <span style="font-size:12.5px;font-weight:500;color:var(--vy-ink)">{{ $c['label'] }}</span>
                                    <span style="font-size:12.5px;font-weight:600;font-variant-numeric:tabular-nums">{{ number_format((float) $c['score'], 1) }}</span>
                                </div>
                                <div class="vy-rv-bar" style="height:7px"><span style="width:{{ $c['pct'] }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                    @if ($summary['softest_label'] !== null)
                        <div style="font-size:11.5px;color:var(--vy-faint);line-height:1.5;margin-top:14px">{{ $summary['softest_label'] }} is your softest score — the proposed <a href="{{ route('owner.rates') }}" style="color:var(--vy-accent);text-decoration:none;font-weight:500">rate changes</a> factor this in.</div>
                    @endif
                </section>
            @endif

            {{-- Handled for you — HONEST: real responses; OTA review INGEST + brand split account-gated; no fake SLA --}}
            <section class="vy-fade" style="background:linear-gradient(150deg,rgba(43,35,32,.92),rgba(60,40,30,.9));border-radius:20px;box-shadow:0 14px 40px rgba(60,30,10,.28),inset 0 1px 0 rgba(255,255,255,.12);padding:22px;color:#F3E7DD;animation-delay:.3s">
                <div style="width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center;margin-bottom:13px"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F7D9C4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.7 5.6 6.1.8-4.5 4.2 1.1 6-5.4-2.9-5.4 2.9 1.1-6L3.2 9.4l6.1-.8z"/></svg></div>
                <div style="font-family:var(--vy-display);font-weight:600;font-size:15px;margin-bottom:8px">Handled for you</div>
                <div style="font-size:12.5px;line-height:1.6;color:#D6C4B8">{{ $agency }} replies to your published reviews — {{ $summary['response_rate'] }}% responded{{ $summary['avg_response_hours'] !== null ? ', on average within ~'.$summary['avg_response_hours'].' '.\Illuminate\Support\Str::plural('hour', $summary['avg_response_hours']) : '' }}. Direct-stay reviews appear here automatically; Airbnb &amp; Booking.com review sync activates once those channels are connected.</div>
            </section>
        </div>
    </div>
    @endif
</x-owner.shell>
