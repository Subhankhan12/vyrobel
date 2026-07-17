{{-- Operator Reservations LIST (Wave 2 · Sub-gate 1) — every booking in the tenant, styled on the shared
     operator design system via <x-operator.shell>. READ-ONLY: every row links to the built ReservationDetail,
     which is where the real writes live. See App\Livewire\Operator\ReservationsList for the authoritative
     REAL / DERIVED / OMITTED map and the two decisions (ordering · stat-tile scope).

     Honesty map (short form):
       • REAL     — guest name, reference, stay dates, unit (or a genuinely-null assignment → "Unassigned"),
                    source via the shared sourceLabel(), total_minor / balance_minor.
       • DERIVED  — the stage badge + chips (BOTH from the shared ReservationStage — a chip can never drift
                    from the badge it filters on); nights; the three tiles, per currency, never summed across.
       • OMITTED  — the paused-billing banner, the bulk composer + Send, bulk select, the month picker, sort
                    controls, the "Refunded" badge. The component docblock names each and why.
       • PII      — the guest NAME only. Encrypted email/phone are never rendered and never queried.

     VOCABULARY: "Completed" and "Booking.com"/"Expedia"/"Airbnb" are design fictions — neither the status
     enum nor any column holds them. The test asserts their literal ABSENCE from the response, so they must
     not appear ANYWHERE in this file, including in a comment: Blade comments are stripped at compile time
     (so they would hide from the test today) but would leak the moment one became an HTML comment. The full
     triage therefore lives in the component docblock, which never renders at all.

     RAW-BLADE RULE: never write `word@directive` — Blade guards with \B, so a directive whose `@` follows a
     word character ships the RAW SOURCE to the page as ordinary text, invisible to assertSee. Every `@`
     below follows a space, a newline or a tag. --}}
<div>
    <x-operator.shell active="reservations" title="Reservations" :subtitle="$subtitle" :viewer="$viewer">

        {{-- ── Stat strip — DERIVED. The CURRENT CALENDAR MONTH, keyed on check-in, excluding cancelled and
             no-shows, PER CURRENCY. Each label states its own scope, so the tiles are deliberately NOT
             re-scoped by the chips or the search below — a tile that silently tracked the filter would make
             its own label false. Every figure the design shipped here was a hard-coded constant that did not
             reconcile to its own rows; not one was ported. ─────────────────────────────────────────────── --}}
        <div class="vy-dash-kpis vy-fade">
            <div class="vy-dash-kpi is-feature">
                <div class="vy-dk-label">Arrivals this month</div>
                <div class="vy-dk-val">{{ $stats['count'] }}</div>
                <div class="vy-dk-sub">checking in this calendar month · cancellations and no-shows excluded</div>
            </div>

            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Booked value · arrivals this month</div>
                @forelse ($stats['booked'] as $line)
                    <div class="vy-dk-val" @if (! $loop->first) style="font-size: 20px;" @endif>{{ $line }}</div>
                @empty
                    {{-- No rows → no currency to render a zero IN. "—" is the honest figure. --}}
                    <div class="vy-dk-val">—</div>
                @endforelse
                <div class="vy-dk-sub">
                    billed on this month's arrivals
                    {{ $stats['multi_currency'] ? '· shown per currency (never summed across)' : '' }}
                </div>
            </div>

            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Balances due · arrivals this month</div>
                @forelse ($stats['due'] as $line)
                    <div class="vy-dk-val" @if (! $loop->first) style="font-size: 20px;" @endif>{{ $line }}</div>
                @empty
                    <div class="vy-dk-val">—</div>
                @endforelse
                <div class="vy-dk-sub">
                    {{ $stats['due_count'] === 0 ? 'nothing due' : $stats['due_count'].' '.\Illuminate\Support\Str::plural('folio', $stats['due_count']).' still owing' }}
                </div>
            </div>
        </div>

        {{-- ── Control row — a GET search (a scoped read must re-run the route middleware, invariant #20) ── --}}
        <div class="vy-rack-toolbar vy-fade" style="margin-top: 22px;">
            <div class="vy-left" style="flex: 1; min-width: 260px;">
                <form method="GET" action="{{ route('app.reservations') }}" class="vy-input-group" style="width: 100%; max-width: 420px;">
                    {{-- Searching must not silently drop the active stage. --}}
                    @if ($stage !== null)
                        <input type="hidden" name="stage" value="{{ $stage }}">
                    @endif
                    {{-- The placeholder promises EXACTLY what the query does: reference + guest name. It does
                         not offer email/phone (encrypted — a LIKE would match nothing) or unit (not in this
                         gate's scope). --}}
                    <input class="vy-input" type="search" name="q" value="{{ $query }}"
                           placeholder="Search by guest or reference…" aria-label="Search reservations">
                    <button type="submit" class="vy-btn vy-btn--ghost">Search</button>
                    @if ($query !== '')
                        <a class="vy-pill" href="{{ $stage === null ? route('app.reservations') : route('app.reservations').'?stage='.$stage }}">Clear</a>
                    @endif
                </form>
            </div>
            <div class="vy-right">
                {{-- Built in Wave 4. Gated on reservations.create (the control, not just the route) so a role
                     that can't create one is never offered the button — no dead-end 403 (the Gate-2 lesson). --}}
                @can('reservations.create')
                    <a class="vy-btn vy-btn--accent" href="{{ route('app.reservations.create') }}">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        New reservation
                    </a>
                @endcan
            </div>
        </div>

        {{-- ── Stage chips — plain GET links. Every label comes from the SHARED ReservationStage::meta(), and
             every count is the REAL count of what clicking it renders (the active search included), so a chip
             can never promise a number the click does not deliver. ─────────────────────────────────────── --}}
        <div class="vy-seg vy-fade" style="margin-bottom: 18px; flex-wrap: wrap;">
            @foreach ($chips as $chip)
                <a class="vy-seg-opt @if ($chip['active']) is-checked @endif"
                   href="{{ $chip['href'] }}"
                   @if ($chip['active']) aria-current="page" @endif>
                    {{ $chip['label'] }}
                    <span class="vy-muted">{{ $chip['count'] }}</span>
                </a>
            @endforeach
        </div>

        @if ($total === 0)
            {{-- Empty state 1 — a genuinely fresh tenant. The design's copy promised OTA brand connectors the
                 product does not have (the real integration is Channex, and its staging checkpoint is still
                 outstanding), so it was rewritten to promise only what exists. --}}
            <div class="vy-card vy-fade">
                <div class="vy-empty">
                    <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No reservations yet</strong>
                    Every booking — direct, walk-in or imported — lands here once it is taken.
                </div>
            </div>
        @elseif (count($rows) === 0)
            {{-- Empty state 2 — real rows exist, this filter/search matched none. The link clears BOTH, which
                 is what the words say (the design's button said "clear the filters" and cleared only the
                 search, leaving the chip active). --}}
            <div class="vy-card vy-fade">
                <div class="vy-empty">
                    <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">
                        @if ($query !== '')
                            No reservations match “{{ $query }}”
                        @else
                            No reservations in this view
                        @endif
                    </strong>
                    <span style="display: block; margin-bottom: 14px;">
                        Try a guest name or a reservation reference{{ $stage !== null ? ', or clear the filter' : '' }}.
                        {{ $total }} {{ \Illuminate\Support\Str::plural('reservation', $total) }} in total.
                    </span>
                    <a class="vy-btn vy-btn--ghost" href="{{ route('app.reservations') }}">Clear search and filters</a>
                </div>
            </div>
        @else
            <div class="vy-card vy-card--flush vy-fade">
                <div class="vy-utable-wrap">
                    <table class="vy-utable">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Stay</th>
                                <th>Unit</th>
                                <th>Source</th>
                                <th>Stage</th>
                                <th style="text-align: right;">Total</th>
                                <th style="text-align: right;">Balance due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>
                                        <div class="vy-fd-guest">
                                            <span class="vy-avatar">{{ $row['initials'] }}</span>
                                            <div>
                                                {{-- The guest NAME only. This is not the contact surface. --}}
                                                <a class="vy-u-code vy-arr-link" href="{{ route('app.reservations.show', $row['id']) }}">{{ $row['guest'] }}</a>
                                                <div class="vy-u-sub">{{ $row['reference'] }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $row['check_in'] }} – {{ $row['check_out'] }}</div>
                                        <div class="vy-u-sub">
                                            {{ $row['nights'] }} {{ \Illuminate\Support\Str::plural('night', $row['nights']) }}
                                            · {{ $row['guests'] }} {{ \Illuminate\Support\Str::plural('guest', $row['guests']) }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($row['unit'] !== null)
                                            <span class="vy-u-code">{{ $row['unit'] }}</span>
                                        @endif
                                        {{-- A real state (reservation_units.unit_id is nullable by design),
                                             not a missing-data placeholder. --}}
                                        @if ($row['unassigned'])
                                            <span class="vy-unassigned">Unassigned</span>
                                        @endif
                                        @if ($row['unit'] === null && ! $row['unassigned'])
                                            <span class="vy-muted">—</span>
                                        @endif
                                    </td>
                                    {{-- The real source enum via the shared map. The OTA brand is not modelled
                                         anywhere in the schema, so an OTA booking reads "OTA" — nothing more
                                         specific can be claimed. --}}
                                    <td><span class="vy-muted">{{ $row['source'] }}</span></td>
                                    <td>
                                        <span class="vy-tag tone-{{ $row['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['stage_meta']['label'] }}</span>
                                    </td>
                                    <td style="text-align: right;"><span class="vy-u-code">{{ $row['total'] }}</span></td>
                                    <td style="text-align: right;">
                                        {{-- balance_minor, kept in sync with the append-only ledger by
                                             FolioService::recompute() — it reconciles to the folio. --}}
                                        @if ($row['has_balance'])
                                            <span class="vy-balance is-due">{{ $row['balance'] }}</span>
                                        @else
                                            <span class="vy-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- The ordering claim below is the one this code actually keeps (check-in desc, then ULID
                     desc) — the design claimed "most recent first" and implemented no sort at all. --}}
                <div class="vy-fl-note" style="padding: 13px 16px; border-top: 1px solid var(--vy-hairline);">{{ $footer }}</div>
            </div>
        @endif

    </x-operator.shell>
</div>
