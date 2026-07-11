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
                <a class="vy-icobtn" href="?start={{ $window['prev'] }}" aria-label="Previous {{ $window['days'] }} nights" title="Previous {{ $window['days'] }} nights">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
                <span class="vy-range">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                    {{ $window['label'] }}
                </span>
                <a class="vy-icobtn" href="?start={{ $window['next'] }}" aria-label="Next {{ $window['days'] }} nights" title="Next {{ $window['days'] }} nights">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </a>
                <a class="vy-pill" href="?start={{ $window['today'] }}">Today</a>
            </div>
            <div class="vy-right">
                {{-- NO-BACKEND: creating/filtering/exporting from the rack isn't wired this pass. --}}
                <span class="vy-pill is-soon" aria-disabled="true">Filters <span class="vy-soon-tag">Soon</span></span>
                <span class="vy-pill is-soon" aria-disabled="true">Export <span class="vy-soon-tag">Soon</span></span>
                <span class="vy-btn is-soon" aria-disabled="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    New reservation <span class="vy-soon-tag">Soon</span>
                </span>
            </div>
        </div>

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

                        {{-- One group per room type: sticky label + availability strip + booking lanes --}}
                        @foreach ($rows as $row)
                            <div class="vy-rack-flexrow">
                                <div class="vy-rt-label vy-rack-labelcol">
                                    <span class="vy-rt-name">{{ $row['room_type'] }}</span>
                                    <span class="vy-rt-kind">{{ $row['kind'] }}</span>
                                    <span class="vy-rt-meta">{{ $row['units'] }} {{ \Illuminate\Support\Str::plural('unit', $row['units']) }}</span>
                                </div>

                                <div style="flex: 1; min-width: 0;">
                                    {{-- REAL availability per night (AvailabilityService). --}}
                                    <div class="vy-nights">
                                        @foreach ($dates as $d)
                                            @php $avail = $row['availability'][$d['date']] ?? 0; @endphp
                                            <div class="vy-avail @if ($d['weekend']) is-weekend @endif @if ($avail === 0) is-zero @elseif ($avail === 1) is-tight @endif"
                                                 title="{{ $avail }} available on {{ $d['date'] }}">{{ $avail }}</div>
                                        @endforeach
                                    </div>

                                    {{-- REAL booking bars, packed into non-overlapping lanes. --}}
                                    <div class="vy-rt-lanes">
                                        @forelse ($row['lanes'] as $lane)
                                            <div class="vy-lane">
                                                <div class="vy-lane-bg">
                                                    @foreach ($dates as $d)
                                                        <div class="@if ($d['weekend']) is-weekend @endif"></div>
                                                    @endforeach
                                                </div>
                                                <div class="vy-lane-bars">
                                                    @foreach ($lane as $bar)
                                                        <div class="vy-bar tone-{{ $bar['tone'] }} @if ($bar['clip_start']) is-clip-start @endif @if ($bar['clip_end']) is-clip-end @endif"
                                                             style="grid-column: {{ $bar['offset'] + 1 }} / span {{ $bar['span'] }};"
                                                             title="{{ $bar['guest'] }} — {{ $bar['status_label'] }} · {{ $bar['source'] }} · {{ $bar['nights'] }} {{ \Illuminate\Support\Str::plural('night', $bar['nights']) }} · {{ $bar['ref'] }}">
                                                            <span class="vy-bar-guest">{{ $bar['guest'] }}</span>
                                                            <span class="vy-bar-flag">{{ $bar['status_label'] }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @empty
                                            {{-- No bookings in this window: a clean empty track (honest, not faked). --}}
                                            <div class="vy-lane">
                                                <div class="vy-lane-bg">
                                                    @foreach ($dates as $d)
                                                        <div class="@if ($d['weekend']) is-weekend @endif"></div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
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
                    </div>
                    {{-- DEFERRED: drag-to-reschedule mutates inventory (AssignmentService) — not part of a
                         presentation pass. Stated honestly rather than shown as a broken interaction. --}}
                    <div class="vy-deferred-note" style="margin-top: 14px;">
                        <span class="vy-soon-tag">Not yet available</span>
                        Drag-to-reschedule &amp; per-channel filtering are planned; the rack is read-only for now.
                        Cells show units available per night; a sold-out night is tinted.
                    </div>
                </div>
            </div>
        @endif

    </x-operator.shell>
</div>
