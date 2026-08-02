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
                    <a class="vy-btn vy-btn--accent" href="?start={{ $window['start'] }}&new=1">
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

        {{-- INLINE NEW RESERVATION (exact-match) — two-phase: enter guest + stay (GET "Check rack") → the REAL
             available units (UnitAvailabilityService) → Confirm = a POST to the certified ReservationCreateController
             → AssignmentService::createBooking (row-lock non-overlap) + the real confirmation email. #20-safe. --}}
        @if (! $readOnly && $newRes['open'] && $property !== null)
            <section class="vy-card vy-fade vy-newres" style="margin-bottom:16px">
                <div class="vy-detail-head" style="margin-bottom:12px">
                    <h2 class="vy-card-title">New reservation</h2>
                    <span class="vy-line-sub" style="margin-top:0">{{ $property['name'] }} · direct entry</span>
                </div>

                {{-- Phase 1: the stay + guest (GET → re-checks availability) --}}
                <form method="GET" class="vy-newres-form">
                    <input type="hidden" name="start" value="{{ $window['start'] }}">
                    <input type="hidden" name="new" value="1">
                    <div class="vy-newres-grid">
                        <label class="vy-field"><span>Guest first name</span><input type="text" name="gf" value="{{ $newRes['form']['guest_first'] }}" placeholder="e.g. Marco" class="vy-input"></label>
                        <label class="vy-field"><span>Guest last name</span><input type="text" name="gl" value="{{ $newRes['form']['guest_last'] }}" placeholder="e.g. Reyes" class="vy-input"></label>
                        <label class="vy-field"><span>Adults</span><input type="number" name="adults" min="1" max="12" value="{{ $newRes['form']['adults'] }}" class="vy-input"></label>
                        <label class="vy-field"><span>Check-in</span><input type="date" name="ci" value="{{ $newRes['form']['check_in'] }}" class="vy-input"></label>
                        <label class="vy-field"><span>Check-out</span><input type="date" name="co" value="{{ $newRes['form']['check_out'] }}" class="vy-input"></label>
                        <label class="vy-field"><span>Source</span>
                            <select name="source" class="vy-input">
                                @foreach ($newRes['sources'] as $val => $label)<option value="{{ $val }}" @selected($newRes['form']['source'] === $val)>{{ $label }}</option>@endforeach
                            </select>
                        </label>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;margin-top:12px">
                        <button type="submit" class="vy-btn vy-btn--accent">Check rack</button>
                        <span class="vy-line-sub" style="margin-top:0">Matches an existing profile if the guest has stayed before.</span>
                    </div>
                </form>

                {{-- Phase 2: the REAL available units for the entered dates → Confirm (certified POST) --}}
                @if ($newRes['checked'])
                    <div style="margin-top:16px;border-top:1px solid var(--vy-hairline);padding-top:14px">
                        <div class="vy-eyebrow" style="margin-bottom:8px">Unit · available {{ \Illuminate\Support\Carbon::parse($newRes['form']['check_in'])->format('j M') }} – {{ \Illuminate\Support\Carbon::parse($newRes['form']['check_out'])->format('j M') }} · {{ $newRes['nights'] }} {{ \Illuminate\Support\Str::plural('night', $newRes['nights']) }}</div>
                        @forelse ($newRes['options'] as $opt)
                            <div class="vy-newres-rt">
                                <div class="vy-newres-rt-head">
                                    <span class="vy-newres-rt-name">{{ $opt['room_type_name'] }}</span>
                                    <span class="vy-newres-rt-rate">{{ $opt['rate'] }} <span style="color:var(--vy-muted);font-weight:400">· {{ $newRes['nights'] }}-night stay</span></span>
                                </div>
                                <div class="vy-newres-units">
                                    @foreach ($opt['units'] as $u)
                                        <form method="POST" action="{{ route('app.reservations.store') }}" class="vy-newres-unit">
                                            @csrf
                                            <input type="hidden" name="check_in" value="{{ $newRes['form']['check_in'] }}">
                                            <input type="hidden" name="check_out" value="{{ $newRes['form']['check_out'] }}">
                                            <input type="hidden" name="room_type_id" value="{{ $opt['room_type_id'] }}">
                                            <input type="hidden" name="unit_id" value="{{ $u['id'] }}">
                                            <input type="hidden" name="adults" value="{{ $newRes['form']['adults'] }}">
                                            <input type="hidden" name="source" value="{{ $newRes['form']['source'] }}">
                                            <input type="hidden" name="guest_first_name" value="{{ $newRes['form']['guest_first'] }}">
                                            <input type="hidden" name="guest_last_name" value="{{ $newRes['form']['guest_last'] }}">
                                            <button type="submit" class="vy-pill vy-newres-confirm">{{ $u['label'] }} · Confirm</button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="vy-empty" style="padding:18px 12px">No units are free (and priced) for those dates — try other dates.</div>
                        @endforelse
                        <div class="vy-line-sub" style="margin-top:12px;font-size:12px">
                            Confirming creates a real booking on the certified engine · the guest gets a confirmation email · it appears on the rack immediately.
                            <a href="{{ route('app.reservations.create', ['check_in' => $newRes['form']['check_in'], 'check_out' => $newRes['form']['check_out']]) }}" style="color:var(--vy-accent);font-weight:600">Need rate plans, extras &amp; payment? Open the full form →</a>
                        </div>
                    </div>
                @endif
            </section>
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
