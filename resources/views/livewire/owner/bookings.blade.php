{{-- Owner Bookings (Phase 2 · Sub-gate 1) — the owner's reservations across their OWN units, owner-scoped and
     read-only. Guest NAME only (no contact — invariant #28); real source enum (OTA, never a brand); stage via
     the shared ReservationStage; "Net to you" via the ONE shared OwnerLedgerAttribution rule. Filters ride GET
     (a wire-action would bypass the owner scope middleware). The design's goal chart + Export are fabricated /
     unbacked and are NOT rendered.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="bookings" title="Bookings" :subtitle="$unitsCount.' '.\Illuminate\Support\Str::plural('unit', $unitsCount)" :viewer="$viewer">

    {{-- Portfolio summary — DERIVED from the owner's full booking set (per-currency net, never combined) --}}
    <div class="vy-kpi-grid vy-fade" style="margin-bottom:20px">
        <div class="vy-kpi">
            <div class="vy-kpi-label">Upcoming</div>
            <div class="vy-kpi-val">{{ $summary['upcoming'] }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Past</div>
            <div class="vy-kpi-val">{{ $summary['past'] }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Nights</div>
            <div class="vy-kpi-val">{{ $summary['nights'] }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Net to you</div>
            @forelse ($summary['net'] as $net)
                <div class="vy-kpi-val vy-mono">{{ $net['display'] }}</div>
            @empty
                <div class="vy-kpi-val" style="color:var(--vy-faint)">—</div>
            @endforelse
            @if (count($summary['net']) > 1)
                <div class="vy-kpi-sub">per payout currency</div>
            @endif
        </div>
    </div>

    {{-- Filters — ONE GET form so the owner scope middleware re-runs on every change (not a wire-action). --}}
    <form method="GET" action="{{ route('owner.bookings') }}" class="vy-card vy-fade" style="margin-bottom:18px">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            {{-- Tabs as submit buttons — carry the selects/search along on click --}}
            @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $value => $label)
                <button type="submit" name="tab" value="{{ $value }}"
                        class="vy-btn {{ $filters['tab'] === $value ? 'vy-btn--accent' : 'vy-btn--ghost' }}"
                        style="padding:7px 14px">{{ $label }}</button>
            @endforeach

            <span style="flex:1"></span>

            <select name="source" onchange="this.form.submit()" aria-label="Filter by source" class="vy-input" style="width:auto;padding:7px 10px">
                <option value="">All sources</option>
                @foreach ($sourceOptions as $opt)
                    <option value="{{ $opt['value'] }}" @selected($filters['source'] === $opt['value'])>{{ $opt['label'] }}</option>
                @endforeach
            </select>

            <select name="unit" onchange="this.form.submit()" aria-label="Filter by unit" class="vy-input" style="width:auto;padding:7px 10px">
                <option value="">All units</option>
                @foreach ($unitOptions as $opt)
                    <option value="{{ $opt['id'] }}" @selected($filters['unit'] === $opt['id'])>{{ $opt['label'] }}</option>
                @endforeach
            </select>

            <select name="month" onchange="this.form.submit()" aria-label="Filter by month" class="vy-input" style="width:auto;padding:7px 10px">
                <option value="">Any month</option>
                @foreach ($monthOptions as $opt)
                    <option value="{{ $opt['value'] }}" @selected($filters['month'] === $opt['value'])>{{ $opt['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div style="display:flex;gap:8px;margin-top:10px">
            <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search guest, unit or confirmation"
                   aria-label="Search bookings" class="vy-input" style="flex:1;padding:8px 12px">
            <button type="submit" class="vy-btn vy-btn--ghost" style="padding:8px 16px">Search</button>
            @if ($filters['q'] !== null || $filters['source'] !== null || $filters['unit'] !== null || $filters['month'] !== null)
                <a href="{{ route('owner.bookings', ['tab' => $filters['tab']]) }}" class="vy-btn vy-btn--ghost" style="padding:8px 16px">Clear</a>
            @endif
        </div>
    </form>

    {{-- The bookings table --}}
    <section class="vy-card vy-fade" style="animation-delay:.04s">
        @if ($rows === [])
            <div class="vy-empty">
                @if ($unitsCount === 0)
                    No units are linked to your account yet, so there are no bookings to show.
                @else
                    No bookings match this view.
                    <div class="vy-line-sub" style="margin-top:6px">Try “All”, or clear the filters above.</div>
                @endif
            </div>
        @else
            <div style="overflow-x:auto">
                <table class="vy-table">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Unit</th>
                            <th>Dates</th>
                            <th class="vy-num">Nts</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th class="vy-num">Net to you</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="vy-row-clickable" onclick="window.location='{{ route('owner.bookings.show', $row['id']) }}'" style="cursor:pointer">
                                <td>
                                    <a class="vy-row-link" href="{{ route('owner.bookings.show', $row['id']) }}">
                                        <div class="vy-row-month">{{ $row['guest'] ?? 'Guest' }}</div>
                                        <div class="vy-row-sub">{{ $row['reference'] }}</div>
                                    </a>
                                </td>
                                <td>{{ $row['unit_label'] }}</td>
                                <td style="white-space:nowrap">{{ $row['stay'] }}</td>
                                <td class="vy-num">{{ $row['nights'] }}</td>
                                <td>{{ $row['source_label'] }}</td>
                                <td>
                                    <span class="vy-status vy-status--{{ $row['stage_badge']['tone'] }}"><span class="vy-dot"></span>{{ $row['stage_badge']['label'] }}</span>
                                </td>
                                <td class="vy-num vy-mono">
                                    @forelse ($row['net_displays'] as $display)
                                        <div>{{ $display }}</div>
                                    @empty
                                        <span class="vy-line-sub" title="No ledger entries for this booking yet">Not yet attributed</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="vy-line-sub" style="margin-top:12px">
                Showing {{ count($rows) }} {{ \Illuminate\Support\Str::plural('booking', count($rows)) }}{{ $filters['tab'] === 'upcoming' ? ' · upcoming' : ($filters['tab'] === 'past' ? ' · past' : '') }}.
            </div>
        @endif
    </section>

</x-owner.shell>
