{{-- Operator Calendar — reservation rack / timeline grid (design pass · Operator Screen 1).
     Styled on the shared Vyrobel design system via <x-operator.shell>. All data is REAL and
     tenant-scoped (availability from AvailabilityService; bars from active reservation_unit lines).
     Presentation only — this view READS, it never mutates inventory. --}}
<div>
    <x-operator.shell active="calendar" title="Calendar"
        :subtitle="$property['name'] ?? 'No property yet'" :viewer="$viewer">

        {{-- Toolbar: REAL window navigation (GET links, RMB-safe) + honest NO-BACKEND affordances --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <a class="vy-icobtn" href="?start={{ $window['prev'] }}&days={{ $window['selected_days'] }}" aria-label="Previous {{ $window['days'] }} nights" title="Previous {{ $window['days'] }} nights">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
                <span class="vy-range">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                    {{ $window['label'] }}
                </span>
                <a class="vy-icobtn" href="?start={{ $window['next'] }}&days={{ $window['selected_days'] }}" aria-label="Next {{ $window['days'] }} nights" title="Next {{ $window['days'] }} nights">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </a>
                <a class="vy-pill" href="?start={{ $window['today'] }}&days={{ $window['selected_days'] }}">Today</a>
                {{-- #104 GATE 3d — view-range switcher (7 / 14 / 30 nights), preserving the current window start. --}}
                <span style="display:inline-flex; gap:4px; margin-left:4px;">
                    @foreach ([7, 14, 30] as $d)
                        <a class="vy-pill @if ($window['selected_days'] === $d) is-on @endif" href="?start={{ $window['start'] }}&days={{ $d }}">{{ $d }}n</a>
                    @endforeach
                </span>
            </div>
            <div class="vy-right">
                {{-- REAL room-type filter (GET; RMB-safe) --}}
                @if (! empty($roomTypeOptions))
                    <form method="GET" class="vy-rack-filter">
                        <input type="hidden" name="start" value="{{ $window['start'] }}">
                        <select name="rt" class="vy-propsw-select" onchange="this.form.submit()" aria-label="Filter by room type">
                            <option value="">All room types</option>
                            @foreach ($roomTypeOptions as $opt)
                                <option value="{{ $opt['id'] }}" @selected($filterRt === $opt['id'])>{{ $opt['name'] }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                {{-- NEW RESERVATION — hidden when the workspace is read-only (writes are off when paused) --}}
                @if (! $readOnly)
                    <a class="vy-btn vy-btn--accent" href="?start={{ $window['start'] }}&days={{ $window['selected_days'] }}&new=1">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        New reservation
                    </a>
                @endif
            </div>
        </div>

        @php $notice = session('new_reservation_notice') ?? session('folio_notice') ?? session('group_notice'); @endphp
        @if ($notice)
            <div class="vy-card vy-fade" style="border-left:3px solid var(--vy-accent);margin-bottom:14px"><div style="font-size:13.5px;color:var(--vy-ink)">{{ $notice }}</div></div>
        @endif

        {{-- NEW RESERVATION MODAL (exact-match) — a frosted overlay, two-phase + #20-safe: enter guest + stay
             (GET "Check rack") → the REAL available units (UnitAvailabilityService) → pick one → Confirm = a POST
             to the certified ReservationCreateController → AssignmentService::createBooking (row-lock non-overlap)
             + the real confirmation email. The client NEVER sends a price (the server re-derives the total). --}}
        @if (! $readOnly && $newRes['open'] && $property !== null)
            <div x-data="{ open: true, dirty: false, gf: @js($newRes['form']['guest_first']), gl: @js($newRes['form']['guest_last']), adults: {{ (int) $newRes['form']['adults'] }}, source: '{{ $newRes['form']['source'] }}', sel: '', selTotal: '', selLabel: '', close() { this.open = false; history.replaceState({}, '', location.pathname + '?start={{ $window['start'] }}&days={{ $window['selected_days'] }}'); } }" x-cloak>
                <div class="vy-modal-backdrop" x-show="open" @click="close()" style="z-index:60"
                     x-transition.opacity>
                    <div class="vy-modal vy-newres-modal" @click.stop>
                        {{-- header --}}
                        <div class="vy-modal-head">
                            <div style="flex:1">
                                <h2 class="vy-modal-title">New reservation</h2>
                                <div class="vy-modal-sub">{{ $property['name'] }} · direct entry</div>
                            </div>
                            <button type="button" class="vy-modal-x" @click="close()" aria-label="Close">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                            </button>
                        </div>

                        <div class="vy-modal-body">
                            {{-- Phase 1: guest + adults stepper + stay (GET → re-checks availability) --}}
                            <form method="GET" class="vy-newres-p1">
                                <input type="hidden" name="start" value="{{ $window['start'] }}">
                                <input type="hidden" name="days" value="{{ $window['selected_days'] }}">
                                <input type="hidden" name="new" value="1">
                                <input type="hidden" name="adults" :value="adults">
                                {{-- source round-trips through the re-check so a picked channel survives a 'Check rack' --}}
                                <input type="hidden" name="source" :value="source">

                                <div class="vy-field-label">Guest</div>
                                <div class="vy-newres-guest">
                                    <input type="text" name="gf" x-model="gf" placeholder="First name" class="vy-input">
                                    <input type="text" name="gl" x-model="gl" placeholder="Last name" class="vy-input">
                                    <div class="vy-stepper" role="group" aria-label="Adults">
                                        <button type="button" @click="adults = Math.max(1, adults - 1)" aria-label="Fewer adults">&minus;</button>
                                        <span x-text="adults + (adults === 1 ? ' adult' : ' adults')"></span>
                                        <button type="button" @click="adults = Math.min(12, adults + 1)" aria-label="More adults">+</button>
                                    </div>
                                </div>
                                <div class="vy-help">Matches an existing profile if the guest has stayed before.</div>

                                <div class="vy-field-label" style="margin-top:16px">Stay</div>
                                <div class="vy-newres-dates">
                                    <input type="date" name="ci" value="{{ $newRes['form']['check_in'] }}" class="vy-input" @change="dirty = true">
                                    <input type="date" name="co" value="{{ $newRes['form']['check_out'] }}" class="vy-input" @change="dirty = true">
                                    @if ($newRes['checked'])<span class="vy-newres-nights">{{ $newRes['nights'] }} {{ \Illuminate\Support\Str::plural('night', $newRes['nights']) }}</span>@endif
                                </div>
                                {{-- edited dates invalidate the checked availability below → must re-check before Confirm --}}
                                <div class="vy-help vy-newres-dirty" x-show="dirty" x-cloak style="color:var(--vy-accent)">Dates changed — click “Check rack” to refresh availability.</div>

                                <button type="submit" class="vy-btn vy-btn--accent vy-newres-check">Check rack</button>
                            </form>

                            {{-- Phase 2: the REAL available units → pick → Source → running total → Confirm (certified POST) --}}
                            @if ($newRes['checked'])
                                <div class="vy-field-label" style="margin-top:20px">Unit · available {{ \Illuminate\Support\Carbon::parse($newRes['form']['check_in'])->format('j M') }} – {{ \Illuminate\Support\Carbon::parse($newRes['form']['check_out'])->format('j M') }}</div>
                                <div class="vy-newres-rooms">
                                    @forelse ($newRes['units'] as $u)
                                        {{-- data-* carries the (possibly apostrophe'd) room name safely into Alpine, never into a JS literal --}}
                                        <button type="button" class="vy-newres-room"
                                                data-unit="{{ $u['unit_id'] }}"
                                                data-total="{{ $u['total_display'] }}"
                                                data-label="{{ $u['room_type_name'] }} · {{ $u['unit_label'] }}"
                                                :class="sel === '{{ $u['unit_id'] }}' ? 'is-sel' : ''"
                                                @click="sel = $event.currentTarget.dataset.unit; selTotal = $event.currentTarget.dataset.total; selLabel = $event.currentTarget.dataset.label">
                                            <span class="vy-newres-room-info">
                                                <span class="vy-newres-room-name">{{ $u['room_type_name'] }} · {{ $u['unit_label'] }}</span>
                                                <span class="vy-newres-room-sub">{{ $newRes['nights'] }}-night stay</span>
                                            </span>
                                            <span class="vy-newres-room-rate">{{ $u['total_display'] }}</span>
                                            <span class="vy-newres-radio" :class="sel === '{{ $u['unit_id'] }}' ? 'on' : ''"></span>
                                        </button>
                                        {{-- the certified per-unit POST form (hidden); the total is NOT sent — the server re-derives it --}}
                                        <form method="POST" action="{{ route('app.reservations.store') }}" id="resf-{{ $u['unit_id'] }}" class="vy-newres-hidden">
                                            @csrf
                                            <input type="hidden" name="check_in" value="{{ $newRes['form']['check_in'] }}">
                                            <input type="hidden" name="check_out" value="{{ $newRes['form']['check_out'] }}">
                                            <input type="hidden" name="room_type_id" value="{{ $u['room_type_id'] }}">
                                            <input type="hidden" name="unit_id" value="{{ $u['unit_id'] }}">
                                            <input type="hidden" name="adults" :value="adults">
                                            <input type="hidden" name="source" :value="source">
                                            <input type="hidden" name="guest_first_name" :value="gf">
                                            <input type="hidden" name="guest_last_name" :value="gl">
                                        </form>
                                    @empty
                                        <div class="vy-empty" style="padding:16px 12px">No units are free (and priced) for those dates — try other dates.</div>
                                    @endforelse
                                </div>

                                @if (count($newRes['units']) > 0)
                                    <div class="vy-field-label" style="margin-top:16px">Source</div>
                                    <div class="vy-newres-sources">
                                        @foreach ($newRes['sources'] as $val => $label)
                                            <button type="button" class="vy-source-pill" :class="source === '{{ $val }}' ? 'on' : ''" @click="source = '{{ $val }}'">{{ $label }}</button>
                                        @endforeach
                                    </div>

                                    <div class="vy-newres-total">
                                        <span class="vy-newres-total-lbl" x-text="sel ? selLabel : 'Pick a unit above'"></span>
                                        <span class="vy-newres-total-amt" x-text="sel ? selTotal : '—'"></span>
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- footer actions --}}
                        <div class="vy-modal-foot vy-newres-foot">
                            @if ($newRes['checked'] && count($newRes['units']) > 0)
                                {{-- one Confirm: submit the SELECTED unit's certified form. The :value bindings above
                                     are flushed by the time this later click fires (no same-tick set+submit race). --}}
                                <button type="button" class="vy-btn vy-btn--accent" :disabled="! sel || dirty"
                                        @click="sel && ! dirty && document.getElementById('resf-' + sel).requestSubmit()">Confirm reservation</button>
                            @endif
                            <button type="button" class="vy-btn vy-newres-cancel" @click="close()">Cancel</button>
                        </div>
                        <div class="vy-newres-note">
                            Creates a real booking on the certified engine · the guest gets a confirmation email · you land on the confirmation (with a self-check-in QR).
                            <a href="{{ route('app.reservations.create', ['check_in' => $newRes['form']['check_in'], 'check_out' => $newRes['form']['check_out']]) }}">Need rate plans, extras &amp; payment? Open the full form →</a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($property === null || count($rows) === 0)
            <div class="vy-card vy-fade">
                <div class="vy-empty">
                    @if ($property === null)
                        No property yet — add a property to see its reservation rack.
                    @else
                        No room types on <strong>{{ $property['name'] }}</strong> yet.
                    @endif
                </div>
            </div>
        @else
            <div class="vy-card vy-card--flush vy-fade">
                <div class="vy-rack-scroll">
                    {{-- --night-cols inherits to every descendant grid so all night columns stay aligned. --}}
                    <div class="vy-rack" style="--night-cols: repeat({{ $window['days'] }}, minmax(38px, 1fr));">

                        {{-- Date header (REAL window) --}}
                        <div class="vy-rack-flexrow vy-rack-head">
                            <div class="vy-rack-corner vy-rack-labelcol">Room type</div>
                            <div class="vy-nights">
                                @foreach ($dates as $d)
                                    <div class="vy-day @if ($d['weekend']) is-weekend @endif">
                                        <div class="vy-day-dow">{{ $d['dow'] }}</div>
                                        <div class="vy-day-num">{{ $d['day'] }}</div>
                                        <div class="vy-day-mon">{{ $d['mon'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- One group per room type: a header row (sticky label + REAL availability strip),
                             then ONE ROW PER PHYSICAL UNIT (the wireframe's axis) carrying that unit's own
                             bookings + its out-of-order / block periods (hatched). --}}
                        @foreach ($rows as $row)
                            {{-- Room-type header: label + REAL availability per night (AvailabilityService). --}}
                            <div class="vy-rack-flexrow vy-rt-availrow">
                                <div class="vy-rt-label vy-rack-labelcol">
                                    <span class="vy-rt-name">{{ $row['room_type'] }}</span>
                                    <span class="vy-rt-kind">{{ $row['kind'] }}</span>
                                    <span class="vy-rt-meta">{{ $row['units'] }} {{ \Illuminate\Support\Str::plural('unit', $row['units']) }}</span>
                                </div>
                                <div class="vy-nights">
                                    @foreach ($dates as $d)
                                        @php $avail = $row['availability'][$d['date']] ?? 0; @endphp
                                        <div class="vy-avail @if ($d['weekend']) is-weekend @endif @if ($avail === 0) is-zero @elseif ($avail === 1) is-tight @endif"
                                             title="{{ $avail }} available on {{ $d['date'] }}">{{ $avail }}</div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- One row per physical unit — its bookings + its OOO/block bars (real inventory_blocks). --}}
                            @foreach ($row['unit_rows'] as $unit)
                                <div class="vy-rack-flexrow vy-unit-row @if ($unit['state'] !== 'active') is-{{ $unit['state'] }} @endif">
                                    <div class="vy-unit-label vy-rack-labelcol">
                                        <span class="vy-unit-code">{{ $unit['code'] }}</span>
                                        @if ($unit['state_label'] !== '')<span class="vy-unit-state state-{{ $unit['state'] }}">{{ $unit['state_label'] }}</span>@endif
                                        @if ($unit['name'] !== $unit['code'])<span class="vy-unit-name">{{ $unit['name'] }}</span>@endif
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div class="vy-lane">
                                            <div class="vy-lane-bg">
                                                @foreach ($dates as $d)
                                                    <div class="@if ($d['weekend']) is-weekend @endif"></div>
                                                @endforeach
                                            </div>

                                            {{-- A non-bookable STATE (paused / out-of-service) is DATELESS — a full-window
                                                 band (never a fabricated date-ranged bar). It reconciles the row with the
                                                 header count (which already subtracts these units) and shows WHY it's unbookable. --}}
                                            @if ($unit['state'] !== 'active')
                                                <div class="vy-lane-ooo">
                                                    <span class="vy-ooo vy-ooo--full state-{{ $unit['state'] }}"
                                                          style="grid-column: 1 / span {{ count($dates) }};"
                                                          title="{{ $unit['state_label'] }} — not accepting new bookings">
                                                        <span class="vy-ooo-label">{{ $unit['state_label'] }}</span>
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- OOO / block periods — hatched, below the bookings, REAL inventory_blocks. --}}
                                            @if (! empty($unit['ooo']))
                                                <div class="vy-lane-ooo">
                                                    @foreach ($unit['ooo'] as $blk)
                                                        <span class="vy-ooo ooo-{{ $blk['reason'] }} @if ($blk['clip_start']) is-clip-start @endif @if ($blk['clip_end']) is-clip-end @endif"
                                                              style="grid-column: {{ $blk['offset'] + 1 }} / span {{ $blk['span'] }};"
                                                              title="{{ $blk['label'] }}">
                                                            <span class="vy-ooo-label">{{ $blk['label'] }}</span>
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- REAL booking bars for THIS unit (a unit never double-books → no lanes). --}}
                                            <div class="vy-lane-bars">
                                                @foreach ($unit['bars'] as $bar)
                                                    {{-- #104 GATE 3d — the booking bar links to the reservation detail (the wireframe's clickable bar). --}}
                                                    <a class="vy-bar tone-{{ $bar['tone'] }} @if ($bar['clip_start']) is-clip-start @endif @if ($bar['clip_end']) is-clip-end @endif"
                                                       href="{{ route('app.reservations.show', $bar['id']) }}"
                                                       style="grid-column: {{ $bar['offset'] + 1 }} / span {{ $bar['span'] }}; text-decoration: none;"
                                                       title="{{ $bar['guest'] }} — {{ $bar['status_label'] }} · {{ $bar['source'] }} · {{ $bar['nights'] }} {{ \Illuminate\Support\Str::plural('night', $bar['nights']) }} · {{ $bar['ref'] }}">
                                                        <span class="vy-bar-guest">{{ $bar['guest'] }}</span>
                                                        <span class="vy-bar-flag">{{ $bar['status_label'] }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            {{-- Reservations with no unit yet — a real "Unassigned" track (lane-packed; they can overlap). --}}
                            @foreach ($row['unassigned'] as $lane)
                                <div class="vy-rack-flexrow vy-unit-row vy-unit-row--unassigned">
                                    <div class="vy-unit-label vy-rack-labelcol">
                                        <span class="vy-unit-code">Unassigned</span>
                                        @if (! $loop->first)<span class="vy-unit-name">overflow</span>@endif
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div class="vy-lane">
                                            <div class="vy-lane-bg">
                                                @foreach ($dates as $d)
                                                    <div class="@if ($d['weekend']) is-weekend @endif"></div>
                                                @endforeach
                                            </div>
                                            <div class="vy-lane-bars">
                                                @foreach ($lane as $bar)
                                                    <a class="vy-bar tone-{{ $bar['tone'] }} @if ($bar['clip_start']) is-clip-start @endif @if ($bar['clip_end']) is-clip-end @endif"
                                                       href="{{ route('app.reservations.show', $bar['id']) }}"
                                                       style="grid-column: {{ $bar['offset'] + 1 }} / span {{ $bar['span'] }}; text-decoration: none;"
                                                       title="{{ $bar['guest'] }} — {{ $bar['status_label'] }} · {{ $bar['source'] }} · {{ $bar['nights'] }} {{ \Illuminate\Support\Str::plural('night', $bar['nights']) }} · {{ $bar['ref'] }} · not yet assigned to a unit">
                                                        <span class="vy-bar-guest">{{ $bar['guest'] }}</span>
                                                        <span class="vy-bar-flag">{{ $bar['status_label'] }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>

                {{-- Legend (warm status tones — the REAL reservation statuses, no green/red) --}}
                <div style="padding: 16px 22px 20px;">
                    <div class="vy-legend">
                        <span class="vy-legend-item"><span class="vy-legend-swatch tone-confirmed"></span> Confirmed</span>
                        <span class="vy-legend-item"><span class="vy-legend-swatch tone-in-house"></span> Checked in</span>
                        <span class="vy-legend-item"><span class="vy-legend-swatch tone-departed"></span> Checked out</span>
                        <span class="vy-legend-item"><span class="vy-legend-swatch tone-pending"></span> Tentative / enquiry</span>
                        <span class="vy-legend-item"><span class="vy-legend-swatch tone-no-show"></span> No-show</span>
                        <span class="vy-legend-item"><span class="vy-legend-swatch swatch-ooo"></span> Out of order / blocked</span>
                    </div>
                    {{-- The rack now creates real bookings (the New reservation flow above → the certified engine).
                         Drag-to-reschedule stays deferred honestly (it mutates inventory) — never a broken drag. --}}
                    <div class="vy-deferred-note" style="margin-top: 14px;">
                        <span class="vy-soon-tag">Not yet available</span>
                        Drag-to-reschedule is planned. Each row is a physical unit; hatched bars are real
                        out-of-order / owner blocks; the header strip shows units available per night. Use
                        “New reservation” to book.
                    </div>
                </div>
            </div>
        @endif

    </x-operator.shell>
</div>
