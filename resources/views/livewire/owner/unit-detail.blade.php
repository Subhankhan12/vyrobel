{{--
    Owner Unit Detail (Wave 1 · Sub-gate 3) — ONE of the owner's units, styled from the catalogued
    "Owner Unit Detail" design and wired to LIVE owner-scoped data. Shell from <x-owner.shell>.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11; see App\Livewire\Owner\OwnerUnitDetail, whose
    docblock carries the full triage + every omission and why):
      • REAL      — name / code / room type / unit_kind / Sleeps (max_occupancy) / listing state /
                    condition_status; the owner_ledger rows for THIS unit; each stay's reference, dates,
                    source and status (the REAL enums only); the effective agreement's commission_pct;
                    the statements this unit's rows landed on.
      • DERIVED   — occupancy nights + pct for THIS UNIT (OwnerOccupancy — never the room-type-wide read,
                    which is shared with hotel rooms and other owners' units); the per-entry_type nets;
                    the month net; each stay's attributed net. Credited + Deductions = Net, to the cent.
      • CURRENCY  — every figure is per currency; nothing is summed or converted across currencies.
      • NULLS     — no commission → "Not set", NEVER "0%". No effective agreement → no card. A stay with
                    no ledger rows → "Not yet attributed", never a zero dressed up as a figure.
      • GUEST PII — none. A stay is identified by its REAL reference only; no guest field is even loaded.
      • READ-ONLY — this screen writes NOTHING: no pricing form, no day-blocking, no status toggle.
      • REAL (Batch B) — the 6-month NET revenue trend + the trailing-12-month occupancy trend (both from
                    the real ledger / OwnerOccupancy, NEVER the design's fabricated prior years), the
                    best-performer badge (the real cross-unit ranking), and the channel mix (the real
                    reservations.source enum — NOT an OTA brand, which the model does not carry).
      • OMITTED   — owner-editable pricing (nightly rate / weekend uplift / min stay), day-blocking + the
                    fabricated "Blocked" split, the Live/Snoozed toggle, calendar sync, reviews & rating,
                    the cleaning schedule, guest messages, "Direct bookings saved you €X" (the design
                    computes it as a fabricated directRev×0.15), the per-unit payout timeline (payouts are
                    per-STATEMENT, never per-unit — the rail links the real Payouts screen instead), base
                    rate / cleaning fee (no such column — the design's €140/€45 are literals). Each is
                    dropped, NOT stubbed: rendering them even disabled would imply state that has no schema.
--}}
<x-owner.shell active="units" :title="$unit['name']" :viewer="$viewer">
    <div style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <a href="{{ route('owner.units') }}" style="color:var(--vy-muted);font-size:12.5px;font-weight:500;text-decoration:none">← My units</a>
        {{-- Best performer — REAL (Batch B): shown only when this unit genuinely leads the owner's units. --}}
        @if ($bestBadge)
            <span class="vy-pill" style="background:var(--vy-accent);color:#fff;font-weight:600" title="Your best-performing unit this month by revenue">★ Best performer of your {{ $bestBadge['count'] }} units · {{ $bestBadge['month'] }}</span>
        @endif
    </div>

    {{-- Condition — REAL (units.condition_status). Rendered only when the unit is NOT in service; the
         consequence stated is a certified AvailabilityService fact, not a guess. No cause/ETA/ticket:
         none of those live on `units`. --}}
    @if ($unit['condition'])
        <section class="vy-card vy-card--accent vy-fade" style="margin-bottom:22px;display:flex;align-items:center;gap:12px">
            <span class="vy-brand-mark" style="border-radius:12px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
            </span>
            <div>
                <div style="font-family:var(--vy-display);font-weight:600;font-size:14.5px">{{ $unit['condition']['label'] }}</div>
                <div class="vy-line-sub">Your operator has marked this unit {{ \Illuminate\Support\Str::lower($unit['condition']['label']) }}. It is not being offered for new bookings while that lasts.</div>
            </div>
        </section>
    @endif

    {{-- KPIs — occupancy + nights DERIVED for THIS unit; net DERIVED from this unit's real ledger rows --}}
    <div class="vy-kpi-grid vy-fade" style="animation-delay:.02s">
        <div class="vy-kpi">
            <div class="vy-kpi-label">Occupancy</div>
            <div class="vy-kpi-val">{{ $occupancy['pct'] }}%</div>
            <div class="vy-kpi-sub">{{ $month['label'] }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Nights sold</div>
            <div class="vy-kpi-val">{{ $occupancy['nights'] }}</div>
            <div class="vy-kpi-sub">{{ $month['label'] }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Net attributed</div>
            @if ($attribution)
                <div class="vy-kpi-val">{{ $attribution[0]['net_display'] }}</div>
                <div class="vy-kpi-sub">{{ $month['label'] }} · {{ $attribution[0]['currency'] }}</div>
            @else
                <div class="vy-kpi-val">—</div>
                <div class="vy-kpi-sub">No attributed activity · {{ $month['label'] }}</div>
            @endif
        </div>
    </div>

    <div class="vy-cols" style="margin-top:22px">
        {{-- ── Main ── --}}
        <div class="vy-stack">
            {{-- Occupancy calendar — READ-ONLY, THIS unit only. Booked / No booking are the only two
                 states this screen can honestly know: a day with no booking is NOT necessarily
                 "available" (the unit may be out of service or the room type blocked), so that word is
                 deliberately not used. Month paging is a plain GET link, never a wire-action. --}}
            <section class="vy-card vy-fade" style="animation-delay:.04s">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:14px">
                    <h2 class="vy-card-title">Occupancy calendar</h2>
                    <div style="display:flex;align-items:center;gap:8px">
                        <a class="vy-period-btn" href="{{ route('owner.units.show', ['unit' => $unit['id'], 'month' => $month['prev']]) }}" aria-label="Previous month"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></a>
                        <span class="vy-period-cur">{{ $month['label'] }}</span>
                        <a class="vy-period-btn" href="{{ route('owner.units.show', ['unit' => $unit['id'], 'month' => $month['next']]) }}" aria-label="Next month"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a>
                    </div>
                </div>

                <div class="vy-cal-grid" style="margin-bottom:6px">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $wd)
                        <div class="vy-cal-head">{{ $wd }}</div>
                    @endforeach
                </div>
                <div class="vy-cal-grid">
                    @for ($i = 1; $i < $month['first_weekday']; $i++)
                        <div class="vy-cal-empty"></div>
                    @endfor
                    @for ($dom = 1; $dom <= $occupancy['days']; $dom++)
                        @php $booked = ($occupancy['heat'][$dom] ?? 0) > 0; @endphp
                        <div class="vy-cal-cell"
                             style="background:{{ $booked ? 'rgba(194,65,12,.85)' : 'rgba(120,70,40,.05)' }};color:{{ $booked ? '#fff' : 'var(--vy-ink)' }}"
                             title="{{ $booked ? 'Booked' : 'No booking' }}">{{ $dom }}</div>
                    @endfor
                </div>

                <div class="vy-cal-legend">
                    <span class="vy-cal-swatch" style="background:rgba(194,65,12,.85)"></span><span>Booked</span>
                    <span class="vy-cal-swatch" style="background:rgba(120,70,40,.05)"></span><span>No booking</span>
                </div>

                <div class="vy-line-sub" style="margin-top:12px;font-size:13px">
                    <strong style="color:var(--vy-ink)">{{ $occupancy['nights'] }} nights</strong> · {{ $occupancy['pct'] }}% occupancy · {{ $month['label'] }}
                </div>
                <div class="vy-line-sub" style="margin-top:6px">This calendar is read-only — your operator manages bookings and availability.</div>
            </section>

            {{-- Revenue · 6 months — REAL (Batch B): this unit's NET "to you" per month, straight from the
                 owner ledger (Σ signedMinor by month). Shared bar component. No fabricated prior years (#39):
                 an empty month is an honest zero and a unit with no earnings shows an honest empty state. --}}
            <section class="vy-card vy-fade" style="animation-delay:.05s">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;margin-bottom:8px">
                    <h2 class="vy-card-title">Revenue · 6 months</h2>
                    <span class="vy-line-sub">net to you</span>
                </div>
                @if ($revenueTrend['has_data'])
                    <x-charts.bars :bars="$revenueTrend['bars']" height="130px" />
                @else
                    <div class="vy-deferred" style="margin-top:6px"><span class="vy-eyebrow">No revenue yet</span>This unit's monthly net will chart here once it has attributed earnings.</div>
                @endif
            </section>

            {{-- Occupancy · 12 months — REAL (Batch B): THIS unit's occupancy per month over the trailing
                 year, via the SHARED OwnerOccupancy (unit-scoped, never room-type-wide), with the real
                 average + peak. Shared bar component. --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:12px;margin-bottom:8px">
                    <h2 class="vy-card-title">Occupancy · 12 months</h2>
                    @if ($occupancyTrend['has_data'])
                        <span class="vy-line-sub">avg {{ $occupancyTrend['avg'] }}% · peak {{ $occupancyTrend['peak_pct'] }}% in {{ $occupancyTrend['peak_label'] }}</span>
                    @endif
                </div>
                @if ($occupancyTrend['has_data'])
                    <x-charts.bars :bars="$occupancyTrend['bars']" height="130px" />
                @else
                    <div class="vy-deferred" style="margin-top:6px"><span class="vy-eyebrow">No bookings yet</span>This unit's monthly occupancy will chart here once it has bookings.</div>
                @endif
            </section>

            {{-- Attribution — every ledger row for THIS unit in the month, bucketed by entry_type and
                 summed signed. Credited + Deductions = Net exactly (see the component docblock). --}}
            <section class="vy-card vy-fade" style="animation-delay:.06s">
                <h2 class="vy-card-title">Attribution · {{ $month['label'] }}</h2>
                <div class="vy-line-sub">What this unit earned you, and what was deducted.</div>

                @forelse ($attribution as $block)
                    <div style="margin-top:14px">
                        @if (count($attribution) > 1)
                            <span class="vy-eyebrow">{{ $block['currency'] }}</span>
                        @endif
                        <div style="overflow-x:auto">
                            <table class="vy-table">
                                <tbody>
                                    @foreach ($block['credits'] as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="vy-num vy-mono">{{ $row['display'] }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td class="vy-row-month">Credited</td>
                                        <td class="vy-num vy-mono vy-row-month">{{ $block['credited_display'] }}</td>
                                    </tr>
                                    @foreach ($block['deductions'] as $row)
                                        <tr>
                                            <td>{{ $row['label'] }}</td>
                                            <td class="vy-num vy-mono vy-net">{{ $row['display'] }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td class="vy-row-month">Deductions</td>
                                        <td class="vy-num vy-mono vy-net vy-row-month">{{ $block['deducted_display'] }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border-top:1.5px solid rgba(120,70,40,.16)"><strong style="font-family:var(--vy-display);font-size:14.5px">Net</strong></td>
                                        <td class="vy-num vy-mono" style="border-top:1.5px solid rgba(120,70,40,.16);font-size:15px">{{ $block['net_display'] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="vy-deferred" style="margin-top:10px">No attributed activity for this unit in {{ $month['label'] }}.</div>
                @endforelse

                @if (count($attribution) > 1)
                    <div class="vy-fx-note">Each currency is totalled separately — nothing is converted.</div>
                @endif
            </section>

            {{-- Stays — REAL reservation_units lines on this unit touching the month, joined to the real
                 reservation. Reference / source / status are the REAL enums. NO guest name, ever. --}}
            <section class="vy-card vy-fade" style="animation-delay:.08s">
                <h2 class="vy-card-title">Stays · {{ $month['label'] }}</h2>
                <div class="vy-line-sub">Bookings on this unit that touch this month</div>

                <div style="overflow-x:auto;margin-top:10px">
                    <table class="vy-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Stay</th>
                                <th class="vy-num">Nights</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th class="vy-num">Your net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $b)
                                <tr>
                                    <td class="vy-row-month vy-mono">
                                        @if ($b['reference'])
                                            {{ $b['reference'] }}
                                        @else
                                            <span class="vy-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="white-space:nowrap">{{ $b['stay'] }}</td>
                                    <td class="vy-num vy-mono">{{ $b['nights'] }}</td>
                                    <td>
                                        @if ($b['source_label'])
                                            <span class="vy-pill">{{ $b['source_label'] }}</span>
                                        @else
                                            <span class="vy-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($b['status_meta'])
                                            <span class="vy-status vy-status--{{ $b['status_meta']['tone'] }}">{{ $b['status_meta']['label'] }}</span>
                                        @else
                                            <span class="vy-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="vy-num vy-mono">
                                        @forelse ($b['net_displays'] as $display)
                                            <div>{{ $display }}</div>
                                        @empty
                                            <span class="vy-muted" style="font-size:11.5px">Not yet attributed</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><div class="vy-empty">No stays on this unit in {{ $month['label'] }}.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="vy-line-sub" style="margin-top:10px">
                    “Your net” covers each whole stay, so a stay that runs past {{ $month['label'] }} is counted in full here.
                </div>
            </section>
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- Unit details — the unit's REAL columns only. There is NO per-unit base rate or cleaning
                 fee column in the schema, so neither is rendered (the design's figures are literals). --}}
            <section class="vy-card vy-fade" style="animation-delay:.05s">
                <span class="vy-eyebrow">Unit details</span>
                <div class="vy-list-row">
                    <span class="vy-muted">Code</span>
                    <span class="vy-mono" style="font-weight:500">{{ $unit['code'] }}</span>
                </div>
                <div class="vy-list-row">
                    <span class="vy-muted">Room type</span>
                    <span style="font-weight:500">{{ $unit['type'] }}</span>
                </div>
                <div class="vy-list-row">
                    <span class="vy-muted">Type</span>
                    <span style="font-weight:500">{{ $unit['kind_label'] }}</span>
                </div>
                <div class="vy-list-row">
                    <span class="vy-muted">Sleeps</span>
                    <span style="font-weight:500">{{ $unit['max_occupancy'] }}</span>
                </div>
                <div class="vy-list-row">
                    <span class="vy-muted">Listing</span>
                    <span class="vy-unit-badge">{{ $unit['active'] ? 'Active' : 'Inactive' }}</span>
                </div>
                @if ($unit['condition'])
                    <div class="vy-list-row">
                        <span class="vy-muted">Condition</span>
                        <span style="font-weight:500">{{ $unit['condition']['label'] }}</span>
                    </div>
                @endif
            </section>

            {{-- Commission — the REAL commission_pct on the agreement governing THIS unit today. The card
                 is absent entirely when no agreement is in effect; a null pct says "Not set", never "0%". --}}
            @if ($commission)
                <section class="vy-card vy-fade" style="animation-delay:.07s">
                    <span class="vy-eyebrow">Commission</span>
                    <div class="vy-list-row">
                        <span style="font-size:20px;font-family:var(--vy-display);font-weight:600">
                            @if ($commission['display'])
                                {{ $commission['display'] }}
                            @else
                                <span class="vy-muted" style="font-size:13.5px">Not set</span>
                            @endif
                        </span>
                        <a href="{{ route('owner.agreement') }}" style="color:var(--vy-accent);font-size:12.5px;font-weight:600">Agreement →</a>
                    </div>
                    <div class="vy-line-sub">{{ $commission['scope_label'] }}, in effect today.</div>
                </section>
            @endif

            {{-- Channel mix — REAL (Batch B): this month's room-nights by the real reservations.source enum
                 (Direct / OTA / Corporate …), NOT an OTA brand — the model carries no brand, so none is
                 invented (#39). Share is of this unit's own room-nights. Absent when no bookings this month. --}}
            @if ($channelMix['has_data'])
                <section class="vy-card vy-fade" style="animation-delay:.1s">
                    <span class="vy-eyebrow">Channel mix · {{ $month['label'] }}</span>
                    <div style="margin-top:12px;display:flex;flex-direction:column;gap:12px">
                        @foreach ($channelMix['rows'] as $row)
                            <div>
                                <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:5px">
                                    <span style="font-weight:500">{{ $row['label'] }}</span>
                                    <span class="vy-muted">{{ $row['nights'] }} {{ \Illuminate\Support\Str::plural('night', $row['nights']) }} · {{ $row['pct'] }}%</span>
                                </div>
                                <div class="vy-track"><span style="width:{{ $row['pct'] }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="vy-line-sub" style="margin-top:10px">Share of this unit's room-nights by booking source.</div>
                </section>
            @endif

            {{-- Statements — the REAL statements this unit's rows for the month landed on. --}}
            <section class="vy-card vy-fade" style="animation-delay:.09s">
                <span class="vy-eyebrow">On your statements</span>
                @forelse ($statements as $s)
                    <a class="vy-list-row vy-row-link" href="{{ route('owner.statements.show', $s['id']) }}">
                        <span>
                            <span style="font-size:13px;font-weight:500">{{ $s['period'] }}</span>
                            <span class="vy-status vy-status--{{ $s['status_meta']['tone'] }}" style="margin-left:8px">{{ $s['status_meta']['label'] }}</span>
                        </span>
                        <span style="color:var(--vy-accent);font-size:12.5px;font-weight:600">View →</span>
                    </a>
                @empty
                    <div class="vy-line-sub" style="margin-top:8px">None of this month's activity is on a statement yet.</div>
                @endforelse

                @if ($statements && $unstatemented > 0)
                    <div class="vy-line-sub" style="margin-top:8px">
                        {{ $unstatemented }} {{ \Illuminate\Support\Str::plural('entry', $unstatemented) }} from {{ $month['label'] }} {{ $unstatemented === 1 ? 'is' : 'are' }} not on a statement yet.
                    </div>
                @endif
            </section>

            {{-- Real links only — every destination below is a built, owner-scoped screen. --}}
            <section class="vy-card vy-fade" style="animation-delay:.11s">
                <span class="vy-eyebrow">Your money</span>
                <a class="vy-list-row vy-row-link" href="{{ route('owner.balance') }}">
                    <span style="font-size:13px;font-weight:500">Balance</span>
                    <span style="color:var(--vy-accent);font-size:12.5px;font-weight:600">Open →</span>
                </a>
                <a class="vy-list-row vy-row-link" href="{{ route('owner.payouts') }}">
                    <span style="font-size:13px;font-weight:500">Payouts</span>
                    <span style="color:var(--vy-accent);font-size:12.5px;font-weight:600">Open →</span>
                </a>
                <div class="vy-line-sub" style="margin-top:8px">Payouts are made per statement across your whole portfolio, not per unit.</div>
            </section>
        </div>
    </div>
</x-owner.shell>
