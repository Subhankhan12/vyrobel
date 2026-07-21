{{-- Owner Block Calendar (Phase 2 · Sub-gate 2) — the owner holds dates on their OWN units. Reads are
     owner-scoped through the unit; writes go POST → OwnerBlockController → OwnerBlockService → the certified
     AssignmentService (single inventory write path). Blocked/booked days come from real inventory; a booked day
     shows "Booked" WITHOUT a guest name. Unit + month ride GET so the owner middleware re-runs.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="calendar" title="Block calendar" :subtitle="$unit['label'] ?? null" :viewer="$viewer">

    @if (session('block_notice'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('block_notice') }}</div>
        </div>
    @endif
    @if (session('block_error'))
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber, #b8860b);margin-bottom:16px">
            <div style="font-size:13.5px;color:var(--vy-ink)">{{ session('block_error') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber, #b8860b);margin-bottom:16px">
            <div style="font-size:13px;color:var(--vy-ink)">{{ $errors->first() }}</div>
        </div>
    @endif

    @if ($unit === null)
        <div class="vy-card vy-fade">
            <div class="vy-empty">No units are linked to your account yet, so there is nothing to block.</div>
        </div>
    @else
        <p class="vy-line-sub" style="font-size:13px;margin-bottom:18px">
            Hold dates for personal stays, maintenance or seasonal closures — blocked nights won’t accept guest bookings.
        </p>

        {{-- Unit picker (GET, preserves the month) --}}
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:14px">
            <span class="vy-kpi-label" style="margin-right:4px">Unit</span>
            @foreach ($units as $u)
                <a href="{{ route('owner.calendar', ['unit' => $u['id'], 'month' => $month['value']]) }}"
                   class="vy-btn {{ $u['id'] === $unit['id'] ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:7px 14px">{{ $u['label'] }}</a>
            @endforeach
        </div>

        <div class="vy-cols" style="display:grid;grid-template-columns:1.7fr 1fr;gap:18px;align-items:start">

            {{-- LEFT — the month grid --}}
            <section class="vy-card vy-fade">
                {{-- Month nav (GET, preserves the unit) --}}
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px">
                    <h2 class="vy-card-title">{{ $month['label'] }}</h2>
                    <div style="display:flex;gap:6px">
                        <a href="{{ route('owner.calendar', ['unit' => $unit['id'], 'month' => $month['prev']]) }}" class="vy-btn vy-btn--ghost" style="padding:6px 12px">‹</a>
                        <a href="{{ route('owner.calendar', ['unit' => $unit['id'], 'month' => $month['today']]) }}" class="vy-btn vy-btn--ghost" style="padding:6px 12px">Today</a>
                        <a href="{{ route('owner.calendar', ['unit' => $unit['id'], 'month' => $month['next']]) }}" class="vy-btn vy-btn--ghost" style="padding:6px 12px">›</a>
                    </div>
                </div>

                {{-- Legend --}}
                <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:11.5px;color:var(--vy-muted);margin-bottom:12px">
                    <span><span class="vyc-swatch vyc-open"></span> Open</span>
                    <span><span class="vyc-swatch vyc-booked"></span> Booked</span>
                    <span><span class="vyc-swatch vyc-blocked"></span> Owner hold</span>
                    <span style="margin-left:auto;color:var(--vy-faint)">{{ $blockedThisMonth }} {{ \Illuminate\Support\Str::plural('night', $blockedThisMonth) }} blocked this month</span>
                </div>

                <div class="vyc-grid">
                    @foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $dow)
                        <div class="vyc-dow">{{ $dow }}</div>
                    @endforeach
                    {{-- Leading blanks so day 1 lands on its weekday (1=Mon) --}}
                    @for ($i = 1; $i < $month['first_weekday']; $i++)
                        <div></div>
                    @endfor
                    @foreach ($grid as $cell)
                        @if ($cell['state'] === 'blocked')
                            {{-- 🔒 SG2 — releasing a hold is an "Approval" (co-owner only). A view-only sub-user sees
                                 the blocked day but cannot click to release it (the route is co-owner-gated too). --}}
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                <form method="POST" action="{{ route('owner.calendar.release', $cell['block_id']) }}" style="margin:0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="vyc-day vyc-blocked" title="Owner hold — click to release">{{ $cell['day'] }}</button>
                                </form>
                            @else
                                <div class="vyc-day vyc-blocked" title="Owner hold">{{ $cell['day'] }}</div>
                            @endownercan
                        @elseif ($cell['state'] === 'booked')
                            <div class="vyc-day vyc-booked" title="Booked — a guest reservation">
                                <span>{{ $cell['day'] }}</span>
                                <span class="vyc-tag">Booked</span>
                            </div>
                        @elseif ($cell['state'] === 'past')
                            <div class="vyc-day vyc-past">{{ $cell['day'] }}</div>
                        @else
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                <button type="button" class="vyc-day vyc-open" data-date="{{ $cell['date'] }}" title="Open — click to fill the block form">{{ $cell['day'] }}</button>
                            @else
                                <div class="vyc-day vyc-open" title="Open">{{ $cell['day'] }}</div>
                            @endownercan
                        @endif
                    @endforeach
                </div>
            </section>

            {{-- RIGHT — the block form + scheduled blocks --}}
            <div class="vy-stack" style="display:flex;flex-direction:column;gap:18px">

                {{-- 🔒 SG2 — blocking dates is an "Approval" (co-owner only); a view-only sub-user gets a read-only
                     calendar with no block form. The POST route is co-owner-gated too, so this is control-parity. --}}
                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                <section class="vy-card vy-fade">
                    <h2 class="vy-card-title" style="margin-bottom:4px">Block dates</h2>
                    <p class="vy-line-sub" style="font-size:12px;margin:0 0 12px">Pick the first and last night to hold on <strong>{{ $unit['label'] }}</strong>.</p>

                    <form method="POST" action="{{ route('owner.calendar.block') }}">
                        @csrf
                        <input type="hidden" name="unit_id" value="{{ $unit['id'] }}">

                        <div class="vy-field" style="margin-bottom:10px">
                            <label class="vy-field-label" for="first_night">First night</label>
                            <input id="first_night" type="date" name="first_night" value="{{ old('first_night') }}" min="{{ $month['today'] }}-01" class="vy-input" required>
                        </div>
                        <div class="vy-field" style="margin-bottom:10px">
                            <label class="vy-field-label" for="last_night">Last night</label>
                            <input id="last_night" type="date" name="last_night" value="{{ old('last_night') }}" class="vy-input" required>
                        </div>
                        <div class="vy-field" style="margin-bottom:14px">
                            <label class="vy-field-label" for="reason">Reason</label>
                            <select id="reason" name="reason" class="vy-input" required>
                                @foreach ($reasons as $reason)
                                    <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ $reason }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="vy-btn vy-btn--accent" style="width:100%;justify-content:center">Block these dates</button>
                    </form>
                    <p class="vy-line-sub" style="font-size:11.5px;margin-top:10px">A date already booked or blocked can’t be held — you’ll be told to pick open dates.</p>
                </section>
                @endownercan

                <section class="vy-card vy-fade" style="animation-delay:.04s">
                    <div style="display:flex;justify-content:space-between;align-items:baseline">
                        <h2 class="vy-card-title">Scheduled blocks</h2>
                        <span class="vy-line-sub" style="font-size:12px">{{ count($blocks) }} {{ \Illuminate\Support\Str::plural('block', count($blocks)) }}</span>
                    </div>

                    @forelse ($blocks as $block)
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--vy-hairline)">
                            <div>
                                <div style="font-size:13px;font-weight:600;color:var(--vy-ink)">{{ $block['range'] }}</div>
                                <div class="vy-line-sub" style="font-size:12px">{{ $block['reason'] }} · {{ $block['nights'] }} {{ \Illuminate\Support\Str::plural('night', $block['nights']) }}</div>
                            </div>
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                <form method="POST" action="{{ route('owner.calendar.release', $block['id']) }}" style="margin:0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12px">Remove</button>
                                </form>
                            @endownercan
                        </div>
                    @empty
                        <div class="vy-empty" style="margin-top:10px">No blocks on this unit yet.</div>
                    @endforelse
                </section>
            </div>
        </div>
    @endif

    <style>
        .vyc-swatch { display:inline-block;width:10px;height:10px;border-radius:3px;vertical-align:middle;margin-right:2px }
        .vyc-swatch.vyc-open { background:rgba(255,255,255,.7);border:1px solid var(--vy-glass-border) }
        .vyc-swatch.vyc-booked { background:var(--vy-grad, #c98a5e) }
        .vyc-swatch.vyc-blocked { background:var(--vy-accent, #b5654a) }
        .vyc-grid { display:grid;grid-template-columns:repeat(7,1fr);gap:5px }
        .vyc-dow { font-size:10.5px;font-weight:600;color:var(--vy-faint);text-align:center;padding-bottom:4px;letter-spacing:.04em }
        .vyc-day { aspect-ratio:1;border-radius:var(--vy-r-sm, 8px);display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:12.5px;border:1px solid var(--vy-glass-border);width:100%;font-family:var(--vy-sans) }
        .vyc-open { background:rgba(255,255,255,.6);color:var(--vy-ink);cursor:pointer }
        .vyc-open:hover { border-color:var(--vy-accent);box-shadow:0 0 0 1px var(--vy-accent) inset }
        .vyc-booked { background:color-mix(in srgb, var(--vy-grad, #c98a5e) 20%, transparent);color:var(--vy-ink);cursor:not-allowed }
        .vyc-blocked { background:color-mix(in srgb, var(--vy-accent, #b5654a) 22%, transparent);color:var(--vy-ink);cursor:pointer }
        .vyc-blocked:hover { box-shadow:0 0 0 1px var(--vy-accent) inset }
        .vyc-past { background:transparent;color:var(--vy-faint);border-color:transparent }
        .vyc-tag { font-size:8.5px;text-transform:uppercase;letter-spacing:.03em;color:var(--vy-muted) }
        @media (max-width:820px) { .vy-cols { grid-template-columns:1fr !important } }
    </style>

    {{-- Progressive enhancement: clicking an OPEN day fills the block form (still fully works without JS). --}}
    <script>
        (function () {
            var first = document.getElementById('first_night'), last = document.getElementById('last_night');
            if (!first || !last) return;
            document.querySelectorAll('.vyc-open[data-date]').forEach(function (el) {
                el.addEventListener('click', function () {
                    var d = el.getAttribute('data-date');
                    if (!first.value || (last.value && first.value)) { first.value = d; last.value = d; }
                    else if (d < first.value) { first.value = d; }
                    else { last.value = d; }
                });
            });
        })();
    </script>
</x-owner.shell>
