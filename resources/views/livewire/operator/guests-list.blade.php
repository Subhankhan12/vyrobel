{{-- Operator GUESTS LIST — the CRM (Wave 2 · Sub-gate 4), styled on the shared operator design system via
     <x-operator.shell>. READ-ONLY: the writes (contact edit, VIP/risk toggles, notes, tags) land on the
     guest PROFILE in Sub-gate 5. See App\Livewire\Operator\GuestsList for the authoritative REAL / DERIVED /
     OMITTED map and the four decisions (name-only search · nationality-not-country · stays exclude
     cancelled · A–Z ordering).

     ⚠️ THIS IS THE PII SURFACE, AND IT IS THE ONLY ONE (locked decision 4). Email and phone are encrypted
     casts, decrypted here deliberately because the CRM is where an operator legitimately needs to reach a
     guest. The front desk, the reservations list and the inbox stay NAME-ONLY and the test asserts they
     still are. The component holds NO public props, so none of this round-trips over /livewire/update.

     Honesty map (short form):
       • REAL     — name, is_vip, risk_flag, tags (the shared polymorphic pivot), email, phone, nationality.
       • DERIVED  — "Total billed" + "Open balance" (the SHARED GuestSpend — the profile reads the same
                    helper, so the two screens cannot disagree); the status badge and the stay count (the
                    SHARED ReservationStage); the chip counts; the three tiles, each labelled with its scope.
       • OMITTED  — the paused-billing banner, the bulk bar + its Send/Delete modals, the CSV export, "Add
                    guest", pagination, sort controls, and the "+4%" delta. The component docblock names
                    each and why. Note DELETE especially: a guest is erased via Guest::anonymize() (strip
                    PII, keep the row), never deleted — a delete button here would keep the PII while
                    looking like an erase.
       • SOON     — the row link to the profile: Sub-gate 5, no route yet, so the name is plain text rather
                    than a link to nowhere.

     RAW-BLADE RULE: never write `word@directive` — Blade guards with \B, so a directive whose `@` follows a
     word character ships the RAW SOURCE to the page as ordinary text, invisible to assertSee. Every `@`
     below follows a space, a newline or a tag. ⚠️ Guest EMAIL ADDRESSES render on this page and contain a
     literal `@` — they are echoed through {{ }} as data, never written into the template, so they cannot
     trip the guard. --}}
<div>
    <x-operator.shell active="guests" title="Guests" :subtitle="$subtitle" :viewer="$viewer">

        {{-- ── Stat strip — DERIVED, recomputed from real rows. The design's 248 / 38 / 31% were hard-coded
             constants that reconciled to nothing (its "38 in-house" sat above 8 rows), and its "+4%" delta
             had no prior-period snapshot anywhere in the schema to compute against. Not one was ported.
             These tiles are deliberately NOT re-scoped by the chips or the search below — each label states
             its own scope, and a tile that silently tracked the filter would make its label false. ─────── --}}
        <div class="vy-dash-kpis vy-fade">
            <div class="vy-dash-kpi is-feature">
                <div class="vy-dk-label">Guest profiles</div>
                <div class="vy-dk-val">{{ $stats['profiles'] }}</div>
                <div class="vy-dk-sub">
                    in this workspace
                    {{-- Counted and named rather than quietly filtered out: an anonymized guest is still a
                         real profile carrying real financial history. --}}
                    @if ($stats['anonymized'] > 0)
                        · {{ $stats['anonymized'] }} anonymized
                    @endif
                </div>
            </div>

            <div class="vy-dash-kpi">
                <div class="vy-dk-label">In-house now</div>
                <div class="vy-dk-val">{{ $stats['in_house'] }}</div>
                {{-- DISTINCT GUESTS, not reservations — and every property, because there is no active-property
                     concept to scope by (the Property Switcher is deferred; see the wave's decision 6). --}}
                <div class="vy-dk-sub">guests checked in · across every property</div>
            </div>

            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Repeat guests</div>
                @if ($stats['repeat_pct'] === null)
                    {{-- No denominator → no percentage. "0%" would claim we measured something we cannot. --}}
                    <div class="vy-dk-val">—</div>
                    <div class="vy-dk-sub">nobody has stayed yet</div>
                @else
                    <div class="vy-dk-val">{{ $stats['repeat_pct'] }}<span class="vy-dk-unit">%</span></div>
                    {{-- The denominator is on the face of the tile: a percentage without one is a number
                         nobody can check. --}}
                    <div class="vy-dk-sub">
                        {{ $stats['repeat'] }} of {{ $stats['with_stays'] }} {{ \Illuminate\Support\Str::plural('guest', $stats['with_stays']) }}
                        who have stayed booked again · cancellations excluded
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Control row — a GET search (a scoped read must re-run the route middleware, invariant #20) ── --}}
        <div class="vy-rack-toolbar vy-fade" style="margin-top: 22px;">
            <div class="vy-left" style="flex: 1; min-width: 260px;">
                <form method="GET" action="{{ route('app.guests') }}" class="vy-input-group" style="width: 100%; max-width: 420px;">
                    {{-- The placeholder promises EXACTLY what the query does (decision 1). The design's field
                         offered "name, email or phone" — and was a <div>, not an input. Email/phone are
                         encrypted TEXT, never indexed by value, so a LIKE over them matches nothing. This
                         page SHOWS contact detail it cannot SEARCH; the footer says so outright. --}}
                    <input class="vy-input" type="search" name="q" value="{{ $query }}"
                           placeholder="Search by guest name…" aria-label="Search guests by name">
                    <button type="submit" class="vy-btn vy-btn--ghost">Search</button>
                    @if ($query !== '')
                        <a class="vy-pill" href="{{ route('app.guests') }}">Clear</a>
                    @endif
                </form>
            </div>
            <div class="vy-right">
                {{-- Inert in the design (no handler) and it supplies no form, so the field set is undefined.
                     Disabled rather than invented — the shipped "New reservation" precedent. --}}
                <span class="vy-btn is-soon" aria-disabled="true">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Add guest <span class="vy-soon-tag">Soon</span>
                </span>
            </div>
        </div>

        {{-- ── Filter chips — plain GET links. VIP and At-risk are REAL boolean columns; the tag chips are the
             tenant's REAL catalog (so a workspace with no tags renders none, rather than chips that filter
             nothing). Every count is the REAL count of what clicking it renders, the active search included,
             so a chip can never promise a number the click does not deliver. ──────────────────────────── --}}
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
            {{-- Empty state 1 — a genuinely fresh tenant. --}}
            <div class="vy-card vy-fade">
                <div class="vy-empty">
                    <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">No guests yet</strong>
                    A guest profile is created with their first booking — direct, walk-in or imported.
                </div>
            </div>
        @elseif (count($rows) === 0)
            {{-- Empty state 2 — real guests exist, this filter/search matched none. The link clears BOTH,
                 which is what the words say. --}}
            <div class="vy-card vy-fade">
                <div class="vy-empty">
                    <strong style="display: block; font-size: 15px; color: var(--vy-ink); margin-bottom: 6px;">
                        @if ($query !== '')
                            No guests match “{{ $query }}”
                        @else
                            No guests in this view
                        @endif
                    </strong>
                    <span style="display: block; margin-bottom: 14px;">
                        Search covers names only — email and phone are encrypted at rest, so they cannot be searched.
                        {{ $total }} {{ \Illuminate\Support\Str::plural('guest', $total) }} in total.
                    </span>
                    <a class="vy-btn vy-btn--ghost" href="{{ route('app.guests') }}">Clear search and filters</a>
                </div>
            </div>
        @else
            <div class="vy-card vy-card--flush vy-fade">
                <div class="vy-utable-wrap">
                    <table class="vy-utable">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Contact</th>
                                {{-- "Nationality", not "Country" (decision 2): guests.nationality is char(2) —
                                     a nationality. No residence column exists; the design's editable
                                     "Dublin, Ireland" conflates the two. --}}
                                <th>Nationality</th>
                                <th style="text-align: right;">Stays</th>
                                <th style="text-align: right;">Total billed</th>
                                <th style="text-align: right;">Open balance</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span class="vy-avatar">{{ $row['initials'] }}</span>
                                            <div style="min-width: 0;">
                                                {{-- The profile landed in Sub-gate 5, so the name is now a real
                                                     link (it was plain text while that route did not exist —
                                                     never a link to nowhere). --}}
                                                <a class="vy-u-code vy-arr-link" href="{{ route('app.guests.show', $row['id']) }}">{{ $row['name'] }}</a>
                                                <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap; margin-top: 4px;">
                                                    {{-- REAL boolean columns — not invented flags. --}}
                                                    @if ($row['is_vip'])
                                                        <span class="vy-tag tone-vip"><span class="vy-dot"></span>VIP</span>
                                                    @endif
                                                    @if ($row['risk_flag'])
                                                        <span class="vy-tag tone-risk"><span class="vy-dot"></span>At risk</span>
                                                    @endif
                                                    {{-- The real tenant catalog via the shared `taggables` pivot. --}}
                                                    @foreach ($row['tags'] as $tag)
                                                        <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $tag }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{-- ⚠️ PII, on this surface only (decision 4). Null on an anonymized
                                             guest — and nullable anyway (a walk-in may have neither). --}}
                                        @if ($row['email'] !== null)
                                            <div><a href="mailto:{{ $row['email'] }}">{{ $row['email'] }}</a></div>
                                        @endif
                                        @if ($row['phone'] !== null)
                                            <div class="vy-u-sub">{{ $row['phone'] }}</div>
                                        @endif
                                        @if ($row['email'] === null && $row['phone'] === null)
                                            {{-- A real state, stated: erasure keeps the row and nulls the PII. --}}
                                            <span class="vy-muted">{{ $row['anonymized'] ? 'Erased' : '—' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row['nationality'] !== null)
                                            {{ $row['nationality'] }}
                                        @else
                                            <span class="vy-muted">—</span>
                                        @endif
                                    </td>
                                    {{-- Decision 3 — a cancelled booking is not a stay. --}}
                                    <td style="text-align: right;"><span class="vy-u-code">{{ $row['stays'] }}</span></td>
                                    <td style="text-align: right;">
                                        {{-- Decision 1 (locked): BILLED — what we invoiced — never netted
                                             against refunds, and never summed across currencies. --}}
                                        @forelse ($row['billed'] as $line)
                                            <div class="vy-u-code">{{ $line }}</div>
                                        @empty
                                            {{-- No billing history → no currency to render a zero IN. "—" is
                                                 the honest figure. --}}
                                            <span class="vy-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td style="text-align: right;">
                                        {{-- BESIDE the billed figure, never folded into it — the design's
                                             "lifetime value" netted refunds off while counting unpaid
                                             charges, which cannot both be true. --}}
                                        @forelse ($row['balance'] as $line)
                                            <div class="vy-balance is-due">{{ $line }}</div>
                                        @empty
                                            <span class="vy-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if ($row['stage_meta'] !== null)
                                            <span class="vy-tag tone-{{ $row['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $row['stage_meta']['label'] }}</span>
                                        @else
                                            {{-- A real state: a profile that has never booked. Inventing a
                                                 stage for someone with no stay would be a fabricated status. --}}
                                            <span class="vy-muted">No bookings</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- The ordering claim below is the one this code actually keeps (surname, first name, ULID) —
                     the design implemented no sort at all. It also states, rather than hides, that the
                     contact detail on this page is not searchable. --}}
                <div class="vy-fl-note" style="padding: 13px 16px; border-top: 1px solid var(--vy-hairline);">{{ $footer }}</div>
            </div>
        @endif

    </x-operator.shell>
</div>
