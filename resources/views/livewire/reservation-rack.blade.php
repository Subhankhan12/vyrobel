{{-- Operator Calendar — reservation rack / timeline grid (design pass · Operator Screen 1).
     Styled on the shared Vyrobel design system via <x-operator.shell>. All data is REAL and
     tenant-scoped (availability from AvailabilityService; bars from active reservation_unit lines).
     Presentation only — this view READS, it never mutates inventory. --}}
<div class="vy-cal">
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
            </div>
            <div class="vy-right">
                {{-- #104 GATE 3d — view-range switcher (7 / 14 / 30 nights), preserving the current window
                     start. The design places it in the right-hand control group as a segmented pill. --}}
                <span class="vy-seg-range" role="group" aria-label="Nights in view">
                    @foreach ([7, 14, 30] as $d)
                        <a class="vy-seg-btn @if ($window['selected_days'] === $d) is-on @endif"
                           @if ($window['selected_days'] === $d) aria-current="true" @endif
                           href="?start={{ $window['start'] }}&days={{ $d }}">{{ $d }}n</a>
                    @endforeach
                </span>
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
                {{-- NEW RESERVATION — hidden when the workspace is read-only (writes are off when paused).
                     ⚠️ OPERATOR FIX 1 (audit Finding 2) — ALSO gated on `reservations.create`, the permission
                     its target route requires. It was `! $readOnly` only, so a role without the permission was
                     offered a button that 403s. The same control on `/app/reservations` is already
                     permission-wrapped already — this reuses that sibling's gate (#30, §N.8f). --}}
                @if (! $readOnly)
                    @can('reservations.create')
                    <a class="vy-btn vy-btn--accent" href="?start={{ $window['start'] }}&days={{ $window['selected_days'] }}&new=1">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        New reservation
                    </a>
                    @endcan
                @endif
            </div>
        </div>

        @php $notice = session('new_reservation_notice') ?? session('folio_notice') ?? session('group_notice'); @endphp
        @if ($notice)
            <div class="vy-flash vy-rack-notice vy-fade" role="status">{{ $notice }}</div>
        @endif

        {{-- NEW RESERVATION — the dialog (rebuilt 2026-10-04: `partials/rack-new-reservation`). ⚠️ OPERATOR FIX 1 (audit Finding
             2, extended): the dialog is the real write surface, so it opens only for someone who may book (`reservations.create`,
             the permission its POST requires) in a workspace that is not paused — `?new=1` alone opens nothing. ⚠️ ONE guard, in
             `ReservationRack::newReservation()`: a second `$canBook` here could never change anything, so no test could prove it. --}}
        @if ($newRes['open'])
            @include('livewire.operator.partials.rack-new-reservation')
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
            {{-- GATE 5.3 — drag-to-reschedule. A booking bar is draggable; the drop cells (only on ACTIVE unit
                 rows) POST to the certified AssignmentService::reschedule. The drop cell's date is a
                 SERVER-RENDERED string → the server preserves the stay length (no client date math, no
                 toISOString day-shift). The drop layer is click-through except while a drag is in flight. --}}
            <div class="vy-card vy-card--flush vy-fade"
                 x-data="{
                     dragging: false, dragRes: '', dragLine: '',
                     base: @js(url('/app/reservations')), csrf: @js(csrf_token()),
                     start: @js($window['start']), days: @js((string) $window['selected_days']),
                     onStart(e, resId, lineId) { this.dragRes = resId; this.dragLine = lineId; this.dragging = true; e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', resId); },
                     onDrop(e, unitId, date) {
                         this.dragging = false;
                         if (! this.dragRes || ! this.dragLine) return;
                         const f = document.createElement('form');
                         f.method = 'POST'; f.action = this.base + '/' + this.dragRes + '/reschedule';
                         const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
                         add('_token', this.csrf); add('reservation_unit_id', this.dragLine);
                         add('new_check_in', date); add('unit_id', unitId);
                         add('return_start', this.start); add('return_days', this.days);
                         document.body.appendChild(f); f.submit();
                     }
                 }">
                <div class="vy-rack-scroll">
                    {{-- --night-cols inherits to every descendant grid so all night columns stay aligned. --}}
                    <div class="vy-rack" style="--night-cols: repeat({{ $window['days'] }}, minmax(38px, 1fr));">

                        {{-- Date header (REAL window) --}}
                        <div class="vy-rack-flexrow vy-rack-head">
                            <div class="vy-rack-corner vy-rack-labelcol">Room type</div>
                            <div class="vy-nights">
                                @foreach ($dates as $d)
                                    {{-- The design marks today with a short gradient rule across the top of
                                         the column head. Real comparison against the window's own today. --}}
                                    <div class="vy-day @if ($d['weekend']) is-weekend @endif @if ($d['date'] === $window['today']) is-today @endif">
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
                                        {{-- D2-1 — the unit's real sleeping capacity, so a booking can be sized against
                                             the row without leaving the rack. Uses the product's EXISTING label, the
                                             "Sleeps N" of the operator Owner-detail unit list and the owner Unit-detail
                                             property row - matched rather than reinvented (§N.8f). Rendered in the row's
                                             existing muted meta register (.vy-unit-name), so it adds a fact without a
                                             new visual language on an already dense row.
                                             ⚠️ OMITTED WHEN THE PAYLOAD SAYS null, which it does for 0. "Sleeps 0"
                                             would assert that the unit sleeps nobody; 0 means nobody recorded a
                                             capacity. Absent is the honest rendering of an unrecorded fact.
                                             ⚠️ This is the UNIT's capacity, never the reservation's party size. --}}
                                        @if ($unit['sleeps'])<span class="vy-unit-name">Sleeps {{ $unit['sleeps'] }}</span>@endif
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

                                            {{-- ⚠️ 2026-10-04 — CLICK AN EMPTY NIGHT TO BOOK IT: one link per night a new booking could start on this
                                                 unit (from the property's today, not under a booking or a block), opening the dialog with this
                                                 unit picked. Under the bars (which stay clickable and draggable); out of the tab order — the
                                                 toolbar's New reservation is the keyboard's way in. Only for someone who may book. --}}
                                            @if ($canBook && $unit['state'] === 'active')
                                                <div class="vy-lane-book" aria-hidden="true">
                                                    @foreach ($dates as $d)
                                                        @if (isset($unit['free_nights'][$d['date']]))
                                                            <a class="vy-lane-book-cell" tabindex="-1" href="?start={{ $window['start'] }}&days={{ $window['selected_days'] }}&new=1&ci={{ $d['date'] }}&co={{ $unit['free_nights'][$d['date']] }}&unit={{ $unit['unit_id'] }}" title="Book {{ $unit['code'] }} from {{ $d['dow'] }} {{ $d['day'] }} {{ $d['mon'] }}" aria-label="Book {{ $unit['code'] }} from {{ $d['dow'] }} {{ $d['day'] }} {{ $d['mon'] }}"><span aria-hidden="true">+</span></a>
                                                        @else
                                                            <span class="vy-lane-book-cell is-off" aria-hidden="true"></span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- Drop targets — one per night, only on a BOOKABLE (active) unit row, click-through
                                                 except while a drag is in flight. The date is server-rendered (no client date math). --}}
                                            @if (! $readOnly && $unit['state'] === 'active')
                                                <div class="vy-lane-drop" :class="dragging ? 'is-dragging' : ''">
                                                    @foreach ($dates as $d)
                                                        <div data-date="{{ $d['date'] }}" @dragover.prevent @drop.prevent="onDrop($event, '{{ $unit['unit_id'] }}', '{{ $d['date'] }}')"></div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            {{-- REAL booking bars for THIS unit (a unit never double-books → no lanes). --}}
                                            <div class="vy-lane-bars">
                                                @foreach ($unit['bars'] as $bar)
                                                    {{-- #104 GATE 3d — the bar links to the reservation detail; GATE 5.3 — it's draggable
                                                         to reschedule (unless the workspace is read-only). --}}
                                                    <a class="vy-bar tone-{{ $bar['tone'] }} @if ($bar['clip_start']) is-clip-start @endif @if ($bar['clip_end']) is-clip-end @endif @if (! $readOnly) is-draggable @endif"
                                                       href="{{ route('app.reservations.show', $bar['id']) }}"
                                                       @if (! $readOnly) draggable="true" @dragstart="onStart($event, '{{ $bar['id'] }}', '{{ $bar['line_id'] }}')" @dragend="dragging = false" @endif
                                                       style="grid-column: {{ $bar['offset'] + 1 }} / span {{ $bar['span'] }}; text-decoration: none;"
                                                       title="{{ $bar['guest'] }} — {{ $bar['status_label'] }} · {{ $bar['source'] }} · {{ $bar['nights'] }} {{ \Illuminate\Support\Str::plural('night', $bar['nights']) }} · {{ $bar['ref'] }}{{ ! $readOnly ? ' · drag to reschedule' : '' }}">
                                                        <span class="vy-bar-txt">
                                                            <span class="vy-bar-guest">{{ $bar['guest'] }}</span>
                                                            {{-- The design's second line. Status stays as TEXT here, not colour-only:
                                                                 the design encodes status purely by bar colour, which would make the
                                                                 legend the sole key (WCAG 1.4.1). Same two-line shape, still readable. --}}
                                                            <span class="vy-bar-sub">{{ $bar['status_label'] }} · {{ $bar['nights'] }} {{ \Illuminate\Support\Str::plural('night', $bar['nights']) }}</span>
                                                        </span>
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
                                                        <span class="vy-bar-txt">
                                                            <span class="vy-bar-guest">{{ $bar['guest'] }}</span>
                                                            {{-- The design's second line. Status stays as TEXT here, not colour-only:
                                                                 the design encodes status purely by bar colour, which would make the
                                                                 legend the sole key (WCAG 1.4.1). Same two-line shape, still readable. --}}
                                                            <span class="vy-bar-sub">{{ $bar['status_label'] }} · {{ $bar['nights'] }} {{ \Illuminate\Support\Str::plural('night', $bar['nights']) }}</span>
                                                        </span>
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
                    {{-- Drag-to-reschedule is LIVE (→ the certified AssignmentService::reschedule, availability
                         locked, no-overlap). Each row is a physical unit; hatched bars are real blocks. --}}
                    {{-- ⚠️ one sentence: inside the old inline-flex note "Drag a booking" sat in one column and the rest in another --}}
                    @if (! $readOnly)
                        <p class="vy-rack-hint"><strong>Drag a booking</strong> to a new date or unit to reschedule it — the stay length is kept and availability is re-checked.@if ($canBook) Click an empty night on a unit’s row to book it there.@endif Hatched bars are real out-of-order / owner blocks; the header strip shows units available per night.</p>
                    @else
                        <p class="vy-rack-hint">Each row is a physical unit; hatched bars are real out-of-order / owner blocks; the header strip shows units available per night. (The workspace is paused — moves are off.)</p>
                    @endif
                </div>
            </div>
        @endif

    </x-operator.shell>
</div>
