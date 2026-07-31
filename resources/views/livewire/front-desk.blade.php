{{-- Operator Front Desk — arrivals / departures / in-house (design pass · Operator Screen 2).
     Styled on the shared operator design system via <x-operator.shell>. Lists + balances are REAL
     and tenant-scoped (FrontDeskService + folio balance). The board only READS; check-in/out post to
     the real staff action (FrontDeskActionController) and persist. Presentation only otherwise. --}}
<div>
    <x-operator.shell active="front-desk" title="Front desk"
        :subtitle="($property['name'] ?? 'No property yet') . ' · ' . $day['label']" :viewer="$viewer">

        {{-- Flash notice from a real check-in/out (PRG) --}}
        @if (session('desk_notice'))
            <div class="vy-toast vy-fade" role="status">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('desk_notice') }}
            </div>
        @endif

        {{-- Toolbar: REAL day navigation (GET links) + honest NO-BACKEND affordances --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <a class="vy-icobtn" href="?date={{ $day['prev'] }}" aria-label="Previous day" title="Previous day">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
                <span class="vy-range">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                    {{ $day['label'] }}
                </span>
                <a class="vy-icobtn" href="?date={{ $day['next'] }}" aria-label="Next day" title="Next day">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </a>
                @unless ($day['is_today'])
                    <a class="vy-pill" href="?date={{ $day['today'] }}">Today</a>
                @endunless
            </div>
            <div class="vy-right">
                <span class="vy-pill is-soon" aria-disabled="true">Walk-in <span class="vy-soon-tag">Soon</span></span>
                <span class="vy-pill is-soon" aria-disabled="true">Filters <span class="vy-soon-tag">Soon</span></span>
                <span class="vy-pill is-soon" aria-disabled="true">Print <span class="vy-soon-tag">Soon</span></span>
            </div>
        </div>

        @if ($property === null)
            <div class="vy-card vy-fade"><div class="vy-empty">No property yet — add a property to run the front desk.</div></div>
        @else
            {{-- DERIVED summary chips (from the real rows only) --}}
            <div class="vy-summary vy-fade">
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $summary['arrivals'] }}</span><span class="vy-sc-label">Arrivals</span></div>
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $summary['departures'] }}</span><span class="vy-sc-label">Departures</span></div>
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $summary['in_house'] }}</span><span class="vy-sc-label">In-house</span></div>
                <div class="vy-summary-chip @if ($summary['due_count'] > 0) is-due @endif">
                    <span class="vy-sc-num">{{ $summary['due_count'] }}</span><span class="vy-sc-label">Balance due</span>
                </div>
            </div>

            <div class="vy-fd-cols">
                @foreach (['Arrivals' => $arrivals, 'Departures' => $departures, 'In-house' => $inHouse] as $heading => $rows)
                    <div class="vy-card vy-card--flush vy-fade">
                        <div class="vy-fd-head">
                            <span class="vy-fd-title">{{ $heading }}</span>
                            <span class="vy-fd-count">{{ count($rows) }}</span>
                        </div>

                        @forelse ($rows as $row)
                            <div class="vy-fd-row">
                                <div class="vy-fd-main">
                                    <div class="vy-fd-guest">
                                        {{ $row['guest'] }}
                                        {{-- #87 — VIP flags the front desk (real is_vip). An operational flag, not contact PII. --}}
                                        @if ($row['is_vip'])
                                            <span class="vy-tag tone-vip" style="margin-left: 6px;"><span class="vy-dot"></span>VIP</span>
                                        @endif
                                    </div>
                                    @if ($row['preferences'] !== [])
                                        {{-- #87 — the guest's stay preferences, shown to the front desk so arrivals get the right room. --}}
                                        <div class="vy-fd-meta" style="margin-top: 4px;">
                                            @foreach ($row['preferences'] as $pref)
                                                <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $pref }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    {{-- #104 GATE 3 — the guest's estimated arrival (real, captured at booking; was dropped). --}}
                                    @if ($row['expected_arrival'])
                                        <div class="vy-fd-meta" style="margin-top: 4px; font-size: 12px; color: var(--vy-muted);">Arrival · {{ $row['expected_arrival'] }}</div>
                                    @endif
                                    <div class="vy-fd-meta">
                                        <span class="vy-tag tone-{{ $row['tone'] }}"><span class="vy-dot"></span>{{ $row['status_label'] }}</span>
                                        @if ($row['unassigned'])
                                            <span class="vy-unassigned">Unassigned</span>
                                            <a class="vy-assign-link" href="{{ route('app.rack') }}?start={{ $row['check_in'] }}">Assign →</a>
                                        @elseif ($row['unit'])
                                            <span class="vy-unit-chip">{{ $row['unit'] }}</span>
                                        @endif
                                        <span class="vy-sep">·</span>
                                        <span>{{ $row['source'] }}</span>
                                        <span class="vy-sep">·</span>
                                        <span>{{ $row['nights'] }} {{ \Illuminate\Support\Str::plural('night', $row['nights']) }}</span>
                                        <span class="vy-sep">·</span>
                                        <span>{{ $row['guests'] }} {{ \Illuminate\Support\Str::plural('guest', $row['guests']) }}</span>
                                    </div>
                                </div>

                                <div class="vy-fd-right">
                                    @if ($row['has_balance'])
                                        <span class="vy-balance is-due" title="Outstanding folio balance">{{ $row['balance'] }} due</span>
                                    @else
                                        <span class="vy-balance is-settled">Settled</span>
                                    @endif

                                    <div class="vy-fd-actions">
                                        @if ($row['can_check_in'])
                                            <form method="POST" action="{{ route('app.front-desk.check-in', $row['id']) }}">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ $day['date'] }}">
                                                <button type="submit" class="vy-act vy-act--accent">Check in</button>
                                            </form>
                                        @elseif ($row['can_check_out'])
                                            <form method="POST" action="{{ route('app.front-desk.check-out', $row['id']) }}">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ $day['date'] }}">
                                                <button type="submit" class="vy-act vy-act--ghost">Check out</button>
                                            </form>
                                        @endif

                                        {{-- DEFERRED: collecting a payment needs an amount + card token + gateway flow.
                                             Shown real-looking but flagged — it never fakes a payment. --}}
                                        @if ($row['has_balance'])
                                            <span class="vy-act is-soon" aria-disabled="true" title="Payment collection is not yet wired to this screen">Collect <span class="vy-soon-tag">Soon</span></span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="vy-empty">No {{ \Illuminate\Support\Str::lower($heading) }} on this day.</div>
                        @endforelse
                    </div>
                @endforeach
            </div>

            <div class="vy-deferred-note" style="margin-top: 18px;">
                <span class="vy-soon-tag">Not yet available</span>
                Room moves &amp; on-desk payment collection are planned; check-in and check-out are live.
                Balances are the real folio figures.
            </div>
        @endif

    </x-operator.shell>
</div>
