{{--
    Owner Block Calendar (exact-match pass · invariant #60) — the owner holds / releases dates on their OWN units
    (personal use, maintenance, seasonal closures), rebuilt to the decoded "Owner Block Calendar" wireframe on the
    REAL block/release backend. Shell from <x-owner.shell active="calendar">.

    HONESTY MAP (see App\Livewire\Owner\OwnerBlockCalendar + App\Services\OwnerBlockService):
      • REAL grid — availability for the selected owned unit + month: past / booked (guest NAME only, #28) / owner
        block / open, all owner-scoped (OwnerScope + OwnerPortalService::units()).
      • REAL write — click a day, or press-and-drag a range (client-side Alpine), then Confirm → a REAL block POST
        (owner.calendar.block → OwnerBlockService → the certified AssignmentService, non-overlap enforced,
        $capActions #20/#42, audited). Click a blocked day → a REAL release (DELETE). Reason (Personal stay /
        Maintenance / Seasonal hold) is stored in the block's notes; the reason column stays the real enum 'owner'.
      • REAL series — REPEAT expands into N REAL individual blocks sharing a series_id (never a fake "scheduled
        series", #39); an occurrence on a taken night is skipped + reported honestly.
      • REAL list — the Scheduled blocks are the real blocks (a series collapses to one row · "Series · N");
        Remove releases them (with confirm). The toast + Undo release what was just created.

    RAW-BLADE RULE: every `@` follows a space, newline or tag; `@click`/`@mousedown` are Alpine (left uncompiled);
    inline conditionals sit after whitespace so Blade compiles them.
--}}
<x-owner.shell active="calendar" title="Block calendar" :subtitle="'Hold dates for personal stays, maintenance or seasonal closures — blocked nights won’t accept guest bookings.'" :viewer="$viewer">
    <style>
        .vy-cal-cols { display:flex; align-items:flex-start; gap:18px; flex-wrap:wrap; }
        .vy-cal-main { flex:1.7; min-width:480px; }
        .vy-cal-rail { flex:1; min-width:300px; display:flex; flex-direction:column; gap:18px; }
        .vy-cal-toolbar { display:flex; align-items:center; justify-content:space-between; gap:14px 20px; flex-wrap:wrap; padding:14px 16px; margin-bottom:18px; }
        .vy-cal-unit { display:inline-flex; align-items:center; gap:7px; padding:7px 14px; border-radius:var(--vy-r-pill); border:1px solid rgba(120,70,40,.16); background:rgba(255,255,255,.6); color:var(--vy-ink); font-family:var(--vy-sans); font-size:13px; font-weight:500; text-decoration:none; transition:filter .16s; }
        .vy-cal-unit.is-active { background:var(--vy-grad); color:#fff; border-color:transparent; box-shadow:var(--vy-shadow-accent); }
        .vy-cal-unit .dot { width:8px; height:8px; border-radius:50%; background:var(--vy-accent); }
        .vy-cal-unit.is-active .dot { background:rgba(255,255,255,.85); }
        .vy-cal-navbtn { width:34px; height:34px; border-radius:11px; border:1px solid rgba(120,70,40,.16); background:rgba(255,255,255,.6); color:var(--vy-ink); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; text-decoration:none; transition:color .16s,border-color .16s; }
        .vy-cal-navbtn:hover { color:var(--vy-accent); border-color:var(--vy-accent-border); }
        .vy-cal-today { padding:7px 14px; border-radius:var(--vy-r-pill); border:1px solid rgba(120,70,40,.16); background:rgba(255,255,255,.6); color:var(--vy-ink); font-family:var(--vy-sans); font-size:12.5px; font-weight:500; text-decoration:none; }
        .vy-cal-legend { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:16px; }
        .vy-cal-leg { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--vy-muted); }
        .vy-cal-swatch { width:12px; height:12px; border-radius:4px; }
        .vy-cal-grid { user-select:none; display:grid; grid-template-columns:repeat(7,1fr); gap:7px; }
        .vy-cal-dow { text-align:center; font-size:10px; font-weight:600; letter-spacing:.07em; color:#A69A92; padding-bottom:3px; }
        .vy-cal-cell { position:relative; min-height:58px; border-radius:11px; padding:7px 8px; display:flex; flex-direction:column; box-sizing:border-box; font-family:var(--vy-sans); text-align:left; border:1px solid transparent; }
        .vy-cal-num { font-size:12.5px; font-weight:600; color:var(--vy-ink); font-variant-numeric:tabular-nums; }
        .vy-cal-open { background:rgba(255,255,255,.6); border-color:rgba(120,70,40,.14); cursor:pointer; transition:background .12s,transform .08s; }
        .vy-cal-open:hover { background:rgba(255,255,255,.85); }
        .vy-cal-open.is-selecting { background:linear-gradient(140deg,#F3B98E,#E8875A); border-color:transparent; }
        .vy-cal-open.is-selecting .vy-cal-num { color:#fff; }
        .vy-cal-booked { background:rgba(232,112,58,.16); border-color:rgba(232,112,58,.28); overflow:hidden; }
        .vy-cal-guest { margin-top:auto; font-size:10px; font-weight:600; color:#B4400F; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .vy-cal-blocked { background:#3A2E27; cursor:pointer; border:none; }
        .vy-cal-blocked .vy-cal-num { color:#fff; }
        .vy-cal-blocked .dot { position:absolute; bottom:8px; left:8px; width:6px; height:6px; border-radius:50%; background:var(--vy-peach); }
        .vy-cal-past { background:rgba(255,255,255,.28); }
        .vy-cal-past .vy-cal-num { color:#C9BEB6; }
        .vy-cal-selcard { background:rgba(232,112,58,.08); border:1px solid rgba(232,112,58,.2); border-radius:12px; padding:11px 13px; margin-bottom:14px; }
        .vy-cal-eyebrow { font-size:11px; font-weight:500; letter-spacing:.06em; text-transform:uppercase; margin-bottom:6px; }
        .vy-cal-reason { display:flex; flex-direction:column; gap:7px; margin-bottom:12px; }
        .vy-cal-reasonbtn { display:flex; align-items:center; justify-content:space-between; gap:9px; padding:10px 13px; border-radius:11px; border:1px solid rgba(120,70,40,.16); background:rgba(255,255,255,.55); color:var(--vy-ink); font-family:var(--vy-sans); font-size:13px; font-weight:500; cursor:pointer; text-align:left; }
        .vy-cal-reasonbtn.is-active { border-color:var(--vy-accent-border); background:rgba(232,112,58,.1); color:#B4400F; }
        .vy-cal-note { width:100%; box-sizing:border-box; padding:9px 12px; border-radius:11px; border:1px solid rgba(120,70,40,.16); background:#fff; font-family:var(--vy-sans); font-size:12.5px; color:var(--vy-ink); outline:none; margin-bottom:12px; }
        .vy-cal-repeat { border-top:1px solid rgba(120,70,40,.14); padding-top:12px; margin-bottom:14px; }
        .vy-cal-freq { display:flex; gap:6px; margin:10px 0; }
        .vy-cal-freqbtn { flex:1; padding:7px; border-radius:9px; border:1px solid rgba(120,70,40,.16); background:rgba(255,255,255,.55); color:var(--vy-ink); font-family:var(--vy-sans); font-size:12px; font-weight:500; cursor:pointer; }
        .vy-cal-freqbtn.is-active { background:var(--vy-grad); color:#fff; border-color:transparent; }
        .vy-cal-step { display:inline-flex; align-items:center; gap:10px; }
        .vy-cal-stepbtn { width:26px; height:26px; border-radius:8px; border:1px solid rgba(120,70,40,.18); background:rgba(255,255,255,.7); color:var(--vy-ink); font-size:15px; line-height:1; cursor:pointer; }
        .vy-cal-actions { display:flex; gap:9px; }
        .vy-cal-confirm { flex:1; padding:11px; border-radius:12px; border:none; cursor:pointer; background:var(--vy-grad); color:#fff; font-family:var(--vy-sans); font-size:13.5px; font-weight:600; box-shadow:var(--vy-shadow-accent); transition:transform .14s; }
        .vy-cal-confirm:hover { transform:translateY(-1px); }
        .vy-cal-cancel { padding:11px 16px; border-radius:12px; border:1px solid rgba(120,70,40,.16); background:rgba(255,255,255,.6); color:var(--vy-muted); font-family:var(--vy-sans); font-size:13px; font-weight:500; cursor:pointer; }
        .vy-cal-empty { display:flex; flex-direction:column; align-items:center; gap:9px; padding:22px 10px; text-align:center; color:var(--vy-muted); font-size:12.5px; }
        .vy-cal-brow { display:flex; align-items:flex-start; gap:11px; padding:11px 0; border-top:1px solid rgba(120,70,40,.1); }
        .vy-cal-brow:first-of-type { border-top:none; }
        .vy-cal-series { display:inline-flex; align-items:center; gap:4px; padding:1px 7px; border-radius:var(--vy-r-pill); background:rgba(232,112,58,.12); color:#B4400F; font-size:10px; font-weight:600; }
        .vy-cal-remove { border:none; background:none; color:var(--vy-muted); cursor:pointer; font-size:14px; line-height:1; padding:4px 6px; border-radius:8px; }
        .vy-cal-remove:hover { color:var(--vy-accent); background:rgba(232,112,58,.12); }
        .vy-cal-toast { position:fixed; bottom:26px; left:50%; transform:translateX(-50%); z-index:60; display:flex; align-items:center; gap:14px; padding:12px 18px; border-radius:14px; background:var(--vy-grad-dark); color:#fff; box-shadow:0 14px 40px rgba(60,30,15,.34); font-size:13px; animation:vyCalToastIn .3s cubic-bezier(.22,.61,.36,1) both; }
        .vy-cal-toast--error { background:linear-gradient(140deg,#8A3A1E,#6E2A12); }
        .vy-cal-toast form { display:inline; }
        .vy-cal-undo { border:1px solid rgba(255,255,255,.4); background:rgba(255,255,255,.12); color:#fff; padding:5px 12px; border-radius:var(--vy-r-pill); font-family:var(--vy-sans); font-size:12px; font-weight:600; cursor:pointer; }
        @keyframes vyCalToastIn { from { opacity:0; transform:translate(-50%,14px); } to { opacity:1; transform:translate(-50%,0); } }
    </style>

    @if ($unit === null)
        <div class="vy-card"><div class="vy-empty">No units are linked to your account yet.</div></div>
    @else
        <div
            x-data="{
                selecting:false, selStart:null, selEnd:null,
                reason:@js($reasons[0]), note:'', repeat:false, freq:'weekly', count:4, max:{{ $maxOccurrences }},
                get lo(){ return this.selStart===null?null:(this.selStart<=this.selEnd?this.selStart:this.selEnd); },
                get hi(){ return this.selStart===null?null:(this.selStart>=this.selEnd?this.selStart:this.selEnd); },
                get hasSel(){ return this.selStart!==null; },
                get nights(){ if(this.selStart===null) return 0; return Math.round((new Date(this.hi)-new Date(this.lo))/86400000)+1; },
                inSel(d){ return this.hasSel && d>=this.lo && d<=this.hi; },
                startSel(d){ this.selecting=true; this.selStart=d; this.selEnd=d; },
                overSel(d){ if(this.selecting) this.selEnd=d; },
                endSel(){ this.selecting=false; },
                reset(){ this.selecting=false; this.selStart=null; this.selEnd=null; this.repeat=false; },
                fmt(d,y){ return new Date(d+'T00:00:00').toLocaleDateString('en-GB', y?{day:'numeric',month:'short',year:'numeric'}:{day:'numeric',month:'short'}); },
                get selRangeText(){ if(!this.hasSel) return ''; return this.lo===this.hi ? this.fmt(this.hi,true) : this.fmt(this.lo,false)+' – '+this.fmt(this.hi,true); },
                get repeatSummary(){ const l={weekly:'weekly',fortnightly:'every 2 weeks',monthly:'monthly'}[this.freq]; return 'Repeats '+l+' · '+this.count+' blocks in total'; },
                dec(){ if(this.count>2) this.count--; },
                inc(){ if(this.count<this.max) this.count++; },
                doBlock(){ this.$refs.blockForm.submit(); }
            }"
            @mouseup.window="endSel()"
        >
            {{-- Toolbar — unit switcher + month nav (GET so the owner middleware re-runs; #20) --}}
            <div class="vy-card vy-cal-toolbar vy-fade">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span class="vy-cal-eyebrow" style="margin:0 2px 0 0;color:#9C9089">Unit</span>
                    @foreach ($units as $u)
                        <a class="vy-cal-unit {{ $u['id'] === $unit['id'] ? 'is-active' : '' }}" href="{{ route('owner.calendar', ['unit' => $u['id'], 'month' => $month['value']]) }}">
                            <span class="dot"></span>{{ $u['label'] }}
                        </a>
                    @endforeach
                </div>
                <div style="display:flex;align-items:center;gap:10px">
                    <a class="vy-cal-navbtn" href="{{ route('owner.calendar', ['unit' => $unit['id'], 'month' => $month['prev']]) }}" aria-label="Previous month"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></a>
                    <div style="min-width:168px;text-align:center;font-family:var(--vy-display);font-weight:600;font-size:17px;font-variant-numeric:tabular-nums">{{ $month['label'] }}</div>
                    <a class="vy-cal-navbtn" href="{{ route('owner.calendar', ['unit' => $unit['id'], 'month' => $month['next']]) }}" aria-label="Next month"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a>
                    <a class="vy-cal-today" href="{{ route('owner.calendar', ['unit' => $unit['id'], 'month' => $month['today']]) }}">Today</a>
                </div>
            </div>

            <div class="vy-cal-cols">
                {{-- Calendar panel --}}
                <div class="vy-cal-main">
                    <div class="vy-card vy-fade" style="animation-delay:.06s">
                        <div class="vy-cal-legend">
                            <span class="vy-cal-leg"><span class="vy-cal-swatch" style="background:rgba(232,112,58,.16);border:1px solid rgba(232,112,58,.28)"></span>Booked</span>
                            <span class="vy-cal-leg"><span class="vy-cal-swatch" style="background:#3A2E27"></span>Owner block</span>
                            <span class="vy-cal-leg"><span class="vy-cal-swatch" style="background:linear-gradient(140deg,#F3B98E,#E8875A)"></span>Selecting</span>
                            <span class="vy-cal-leg"><span class="vy-cal-swatch" style="background:rgba(255,255,255,.6);border:1px solid rgba(120,70,40,.14)"></span>Open</span>
                            <span style="margin-left:auto;font-size:11.5px;color:#A69A92;font-variant-numeric:tabular-nums">{{ $statLine }}</span>
                        </div>

                        <div class="vy-cal-grid">
                            @foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $dow)
                                <div class="vy-cal-dow">{{ $dow }}</div>
                            @endforeach
                            @foreach ($grid as $c)
                                @if ($c['state'] === 'blank')
                                    <div></div>
                                @elseif ($c['state'] === 'open')
                                    <div class="vy-cal-cell vy-cal-open" data-date="{{ $c['date'] }}"
                                         @mousedown.prevent="startSel('{{ $c['date'] }}')" @mouseenter="overSel('{{ $c['date'] }}')"
                                         :class="{ 'is-selecting': inSel('{{ $c['date'] }}') }">
                                        <span class="vy-cal-num">{{ $c['day'] }}</span>
                                    </div>
                                @elseif ($c['state'] === 'booked')
                                    <div class="vy-cal-cell vy-cal-booked" title="Booked · {{ $c['guest'] }}">
                                        <span class="vy-cal-num">{{ $c['day'] }}</span>
                                        <span class="vy-cal-guest">{{ $c['guest'] }}</span>
                                    </div>
                                @elseif ($c['state'] === 'blocked')
                                    <form method="POST" action="{{ route('owner.calendar.release', $c['block_id']) }}" style="display:contents">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="vy-cal-cell vy-cal-blocked" title="Owner block — click to release">
                                            <span class="vy-cal-num">{{ $c['day'] }}</span>
                                            <span class="dot"></span>
                                        </button>
                                    </form>
                                @else
                                    <div class="vy-cal-cell vy-cal-past"><span class="vy-cal-num">{{ $c['day'] }}</span></div>
                                @endif
                            @endforeach
                        </div>
                        <p style="margin:15px 0 0;font-size:11.5px;color:#A69A92">Click a day, or press and drag across several, to block them. Click a blocked day to release it.</p>
                    </div>
                </div>

                {{-- Right rail --}}
                <div class="vy-cal-rail">
                    {{-- Composer --}}
                    <div class="vy-card vy-fade" style="animation-delay:.12s">
                        <div style="display:flex;align-items:center;gap:9px;margin-bottom:14px">
                            <span style="width:30px;height:30px;border-radius:9px;background:var(--vy-grad);display:inline-flex;align-items:center;justify-content:center;box-shadow:var(--vy-shadow-accent)"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18"/><path d="M9 14l2 2 4-4"/></svg></span>
                            <div style="font-family:var(--vy-display);font-weight:600;font-size:15.5px">Block dates</div>
                        </div>

                        <div x-show="hasSel" x-cloak style="animation:vyCardIn .2s ease both">
                            <div class="vy-cal-selcard">
                                <div class="vy-cal-eyebrow" style="color:#B4400F;margin-bottom:3px">Selected</div>
                                <div style="font-family:var(--vy-display);font-weight:600;font-size:15px" x-text="selRangeText"></div>
                                <div style="font-size:12px;color:var(--vy-muted);margin-top:2px"><span x-text="nights"></span> <span x-text="nights === 1 ? 'night' : 'nights'"></span> · {{ $unit['label'] }}</div>
                            </div>

                            <div class="vy-cal-eyebrow" style="color:#9C9089">Reason</div>
                            <div class="vy-cal-reason">
                                @foreach ($reasons as $r)
                                    <button type="button" class="vy-cal-reasonbtn" :class="{ 'is-active': reason === @js($r) }" @click="reason = @js($r)">
                                        <span>{{ $r }}</span>
                                        <svg x-show="reason === @js($r)" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C2410C" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                    </button>
                                @endforeach
                            </div>

                            <input class="vy-cal-note" x-model="note" maxlength="200" placeholder="Add a note (optional)">

                            <div class="vy-cal-repeat">
                                <button type="button" @click="repeat = !repeat" style="display:flex;align-items:center;justify-content:space-between;width:100%;background:none;border:none;cursor:pointer;padding:0">
                                    <span style="display:inline-flex;align-items:center;gap:9px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 22l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg><span style="font-size:13.5px;font-weight:500;color:var(--vy-ink)">Repeat this block</span></span>
                                    <span style="font-size:12px;color:var(--vy-accent);font-weight:600" x-text="repeat ? 'On' : 'Off'"></span>
                                </button>
                                <div x-show="repeat" x-cloak>
                                    <div class="vy-cal-freq">
                                        <button type="button" class="vy-cal-freqbtn" :class="{ 'is-active': freq === 'weekly' }" @click="freq = 'weekly'">Weekly</button>
                                        <button type="button" class="vy-cal-freqbtn" :class="{ 'is-active': freq === 'fortnightly' }" @click="freq = 'fortnightly'">2 weeks</button>
                                        <button type="button" class="vy-cal-freqbtn" :class="{ 'is-active': freq === 'monthly' }" @click="freq = 'monthly'">Monthly</button>
                                    </div>
                                    <div style="display:flex;align-items:center;justify-content:space-between">
                                        <span style="font-size:12.5px;color:var(--vy-muted)">Occurrences</span>
                                        <span class="vy-cal-step">
                                            <button type="button" class="vy-cal-stepbtn" @click="dec()" aria-label="Fewer occurrences">−</button>
                                            <span style="min-width:18px;text-align:center;font-weight:600" x-text="count"></span>
                                            <button type="button" class="vy-cal-stepbtn" @click="inc()" aria-label="More occurrences">+</button>
                                        </span>
                                    </div>
                                    <div style="margin-top:9px;font-size:11.5px;color:#A69A92" x-text="repeatSummary"></div>
                                </div>
                            </div>

                            <div class="vy-cal-actions">
                                <button type="button" class="vy-cal-confirm" @click="doBlock()">Block these dates</button>
                                <button type="button" class="vy-cal-cancel" @click="reset()">Cancel</button>
                            </div>
                        </div>

                        <div x-show="!hasSel" class="vy-cal-empty">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D9BFAE" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18"/><path d="M8 3v3"/><path d="M16 3v3"/></svg>
                            <span>Select one or more open days on the calendar to create a block.</span>
                        </div>

                        {{-- The REAL block POST (fields bound to the Alpine selection; empty repeat → null server-side) --}}
                        <form method="POST" action="{{ route('owner.calendar.block') }}" x-ref="blockForm" style="display:none">
                            @csrf
                            <input type="hidden" name="unit_id" value="{{ $unit['id'] }}">
                            <input type="hidden" name="first_night" :value="lo">
                            <input type="hidden" name="last_night" :value="hi">
                            <input type="hidden" name="reason" :value="reason">
                            <input type="hidden" name="note" :value="note">
                            <input type="hidden" name="repeat_freq" :value="repeat ? freq : ''">
                            <input type="hidden" name="repeat_count" :value="repeat ? count : ''">
                        </form>
                    </div>

                    {{-- Scheduled blocks --}}
                    <div class="vy-card vy-fade" style="animation-delay:.18s">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px">
                            <div style="font-family:var(--vy-display);font-weight:600;font-size:15.5px">Scheduled blocks</div>
                            <span style="font-size:11.5px;color:#A69A92">{{ count($blocks) }} {{ \Illuminate\Support\Str::plural('block', count($blocks)) }}</span>
                        </div>
                        <div class="vy-line-sub" style="margin-bottom:6px">{{ $unit['label'] }}</div>

                        @forelse ($blocks as $b)
                            <div class="vy-cal-brow" x-data="{ confirming: false }">
                                <span style="flex:none;width:9px;height:9px;border-radius:3px;background:#3A2E27;margin-top:5px"></span>
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:13px;font-weight:600">{{ $b['range'] }}</div>
                                    <div style="font-size:11.5px;color:var(--vy-muted);display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:2px">
                                        <span>{{ $b['reason'] }}</span>
                                        <span>{{ $b['nights'] }} {{ \Illuminate\Support\Str::plural('night', $b['nights']) }}</span>
                                        @if ($b['is_series'])
                                            <span class="vy-cal-series"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>Series · {{ $b['count'] }}</span>
                                        @endif
                                    </div>
                                    @if ($b['note'])
                                        <div style="font-size:11.5px;color:#A69A92;margin-top:3px">{{ $b['note'] }}</div>
                                    @endif
                                </div>
                                <form method="POST" action="{{ $b['is_series'] ? route('owner.calendar.release-series', $b['series_id']) : route('owner.calendar.release', $b['block_id']) }}" style="flex:none">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="vy-cal-remove" x-show="!confirming" @click="confirming = true" aria-label="Remove block">✕</button>
                                    <span x-show="confirming" x-cloak style="display:inline-flex;gap:6px">
                                        <button type="submit" style="border:none;background:var(--vy-grad);color:#fff;padding:5px 11px;border-radius:var(--vy-r-pill);font-family:var(--vy-sans);font-size:11.5px;font-weight:600;cursor:pointer">Remove</button>
                                        <button type="button" style="border:1px solid rgba(120,70,40,.18);background:rgba(255,255,255,.7);color:var(--vy-muted);padding:5px 11px;border-radius:var(--vy-r-pill);font-family:var(--vy-sans);font-size:11.5px;font-weight:500;cursor:pointer" @click="confirming = false">Keep</button>
                                    </span>
                                </form>
                            </div>
                        @empty
                            <div class="vy-cal-empty" style="padding:14px 10px">No upcoming blocks for this unit.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Toast + Undo (real — Undo releases what was just created) --}}
        @if (session('block_notice'))
            <div class="vy-cal-toast" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)">
                <span>{{ session('block_notice') }}</span>
                @if (session('block_undo_block'))
                    <form method="POST" action="{{ route('owner.calendar.release', session('block_undo_block')) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="vy-cal-undo">Undo</button>
                    </form>
                @elseif (session('block_undo_series'))
                    <form method="POST" action="{{ route('owner.calendar.release-series', session('block_undo_series')) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="vy-cal-undo">Undo</button>
                    </form>
                @endif
            </div>
        @endif
        @if (session('block_error'))
            <div class="vy-cal-toast vy-cal-toast--error" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)">
                <span>{{ session('block_error') }}</span>
            </div>
        @endif
    @endif
</x-owner.shell>
