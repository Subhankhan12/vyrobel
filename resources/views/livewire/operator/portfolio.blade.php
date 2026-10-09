{{-- Operator Portfolio (Phase 6a · Step 2) — the multi-property overview + property CRUD. Every figure is
     REAL/DERIVED: unit counts, occupancy from the Availability spine, arrivals from the front-desk read, a
     status derived from real inventory ("Finish setup to go live" when a property has no units yet). The
     ADR/RevPAR/revenue comparison is REAL via the SG1 metric layer (it was deferred in 6a, built in 6b —
     this docblock said "deferred" long after it shipped). Add/Edit render only for `properties.manage`.
     ⚠️ An average over ZERO room-nights renders "—", not a zero — a computed zero (revenue) still shows. --}}
<div x-data="{ addOpen: {{ $errors->any() && old('_form') === 'add' ? 'true' : 'false' }}, editOpen: false, editing: {}, removing: null }">
    <x-operator.shell active="portfolio" title="Portfolio"
        :subtitle="$summary['properties'] . ' ' . \Illuminate\Support\Str::plural('property', $summary['properties']) . ' · ' . $summary['units'] . ' ' . \Illuminate\Support\Str::plural('unit', $summary['units'])"
        :viewer="$viewer">

        {{-- Toolbar: Add property (managers only) --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <span class="vy-range">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>
                    Your properties
                </span>
            </div>
            <div class="vy-right">
                @if ($canManage)
                    <button type="button" class="vy-btn vy-btn--accent" @click="addOpen = true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        Add property
                    </button>
                @else
                    <span class="vy-pill is-soon" aria-disabled="true" title="Requires the Manage properties permission">Add property <span class="vy-soon-tag">Manager</span></span>
                @endif
            </div>
        </div>

        @if (session('portfolio_notice')) <div class="vy-flash is-ok vy-fade" role="status">{{ session('portfolio_notice') }}</div> @endif
        @if (session('portfolio_error')) <div class="vy-flash is-err vy-fade" role="alert">{{ session('portfolio_error') }}</div> @endif

        {{-- All-properties KPI strip — REAL aggregates over the accessible set (Revenue·month / Occupancy /
             Arrivals / Needs-attention), matching the wireframe. Every figure REAL/DERIVED (#39). --}}
        <div class="vy-dash-kpis vy-dash-kpis--4 vy-fade">
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Revenue · month</div>
                <div class="vy-dk-val">{{ $summary['revenue_month'] }}</div>
                <div class="vy-dk-sub">room revenue · {{ $summary['properties'] }} {{ \Illuminate\Support\Str::plural('property', $summary['properties']) }}</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Occupancy tonight</div>
                <div class="vy-dk-val">{{ $summary['occupancy_pct'] }}<span class="vy-dk-unit">%</span></div>
                <div class="vy-dk-track" style="background: rgba(120, 70, 40, 0.10);"><span style="width: {{ min(100, $summary['occupancy_pct']) }}%"></span></div>
                <div class="vy-dk-sub">{{ $summary['rooms_sold'] }} of {{ $summary['rooms_total'] }} rooms sold</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Arrivals today</div>
                <div class="vy-dk-val">{{ $summary['arrivals'] }}</div>
                <div class="vy-dk-sub">across {{ $summary['properties'] }} {{ \Illuminate\Support\Str::plural('property', $summary['properties']) }}</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Needs attention</div>
                <div class="vy-dk-val">{{ $summary['needs_attention'] }}</div>
                <div class="vy-dk-sub">{{ $summary['needs_attention'] === 0 ? 'all good' : 'setup / sync' }}</div>
            </div>
        </div>

        {{-- Property cards --}}
        @if (count($cards) === 0)
            <div class="vy-card vy-fade"><div class="vy-empty">No properties yet.@if ($canManage) Add your first property to get started.@endif</div></div>
        @else
            <div class="vy-portfolio-grid vy-fade">
                @foreach ($cards as $p)
                    <div class="vy-prop-card vy-card--tappable @if ($p['is_current']) is-current @endif @if ($p['removal']['scheduled']) is-leaving @endif">
                        {{-- The design's full-bleed gradient band (deepened for AA — see .vy-prop-band). --}}
                        <div class="vy-prop-band">
                            <span class="vy-prop-mark">{{ $p['initial'] }}</span>
                            <div class="vy-prop-id">
                                <span class="vy-prop-name">{{ $p['name'] }}@if ($p['is_current'])<span class="vy-prop-current">Working here</span>@endif</span>
                                <span class="vy-prop-loc">{{ $p['location'] }} · {{ $p['type_label'] }} · {{ $p['units'] }} {{ \Illuminate\Support\Str::plural('unit', $p['units']) }}</span>
                            </div>
                            @if ($p['removal']['scheduled'])
                                <span class="vy-prop-leaving-badge">Leaving {{ \Illuminate\Support\Str::beforeLast((string) $p['removal']['leaves_on'], ' ') }}</span>
                            @endif
                        </div>

                        <div class="vy-prop-body">
                            {{-- ⚠️ The stat labels carry their PERIOD. The comparison table below spans the YEAR while
                                 these are THIS MONTH — a bare "Revenue" beside it reads as the same figure and they
                                 differ by an order of magnitude. Occupancy is tonight (the availability spine). --}}
                            <div class="vy-prop-stats">
                                <div><span class="vy-ps-lab">Occupancy</span><span class="vy-ps-val">{{ $p['occupancy_display'] }}</span></div>
                                <div><span class="vy-ps-lab">ADR · mo</span><span class="vy-ps-val">{{ $p['adr'] }}</span></div>
                                <div><span class="vy-ps-lab">Revenue · mo</span><span class="vy-ps-val">{{ $p['revenue'] }}</span></div>
                            </div>

                            {{-- The design's occupancy rail — the REAL tonight figure, nothing fabricated. --}}
                            @if ($p['rooms_total'] > 0)
                                <div class="vy-prop-occ" role="img"
                                     aria-label="Occupancy tonight {{ $p['occupancy_pct'] }}% — {{ $p['rooms_sold'] }} of {{ $p['rooms_total'] }} units sold">
                                    <span style="width: {{ min(100, $p['occupancy_pct']) }}%"></span>
                                </div>
                            @endif

                            <div class="vy-prop-status vy-status-{{ $p['status']['tone'] }}">
                                <span class="vy-status-dot"></span>{{ $p['status']['label'] }}
                            </div>

                            {{-- Fix-sync — HONEST: the REAL channel status (never a fabricated "synced", #85/#39). --}}
                            <div class="vy-prop-sync vy-status-{{ $p['sync']['tone'] }}" style="font-size: 11.5px; display: flex; justify-content: space-between; align-items: center;">
                                <span><span class="vy-status-dot"></span>{{ $p['sync']['label'] }}</span>
                                @if ($p['sync']['needs_fix'])
                                    <a href="{{ route('app.channels') }}" style="color: var(--vy-accent-text); font-weight: 600;">Fix sync →</a>
                                @endif
                            </div>

                            {{-- The design's chip row. ⚠️ SCOPE-SAFETY (#38): the prototype deep-links each card to
                                 "Dashboard · Calendar", but OUR property scope is the session PropertyContext — a
                                 /app/calendar link from a NON-current card would silently render the property you
                                 are actually working in. So the navigation chips render only on the CURRENT card;
                                 elsewhere the card offers "Open" (the real, validated scope switch) instead. The
                                 arrivals chip is a REAL count and never a link. --}}
                            <div class="vy-prop-chips">
                                @if ($p['is_current'])
                                    <a class="vy-prop-chip" href="{{ route('app.dashboard') }}">Dashboard</a>
                                    <a class="vy-prop-chip" href="{{ route('app.calendar') }}">Calendar</a>
                                @endif
                                <span class="vy-prop-chip is-static">{{ $p['arrivals'] }} {{ \Illuminate\Support\Str::plural('arrival', $p['arrivals']) }} today</span>
                            </div>

                            {{-- ⚠️ 2026-09-29 — a property that is LEAVING says so, and when. The date is the service's own
                                 (`removal_effective_at`, re-derived nightly — F-57), never a guess made here. --}}
                            @if ($p['removal']['scheduled'])
                                <div class="vy-prop-leaving" role="status">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    <span>
                                        @if ($p['removal']['stays'] > 0 || $p['removal']['in_house'] > 0)
                                            <strong>Leaving after the last checkout on {{ $p['removal']['leaves_on'] }}.</strong>
                                            It stays open for its guests until then.
                                        @else
                                            <strong>Leaving on {{ $p['removal']['leaves_on'] }}, at the nightly removal.</strong>
                                        @endif
                                        Bookings, invoices and payments are kept.
                                    </span>
                                </div>
                            @endif

                            <div class="vy-prop-actions">
                                @if (! $p['is_current'])
                                    {{-- ⚠️ 2026-09-29 — Open takes you INTO the property (its Dashboard), not back to this page. --}}
                                    <form method="POST" action="{{ route('app.property.switch') }}">
                                        @csrf
                                        <input type="hidden" name="property_id" value="{{ $p['id'] }}">
                                        <input type="hidden" name="open" value="dashboard">
                                        <button type="submit" class="vy-btn vy-btn--ghost">Open</button>
                                    </form>
                                @endif
                                @if ($canManage)
                                    <button type="button" class="vy-btn vy-btn--ghost"
                                        @click="editing = {{ \Illuminate\Support\Js::from(['id' => $p['id'], 'name' => $p['name'], 'type' => $p['type'], 'timezone' => $p['timezone'], 'status' => ($p['status']['label'] === 'Inactive' ? 'inactive' : 'active')]) }}; editOpen = true">Edit</button>
                                    @if ($p['removal']['scheduled'])
                                        @if (! $readOnly)
                                            <form method="POST" action="{{ route('app.portfolio.keep', $p['id']) }}">
                                                @csrf
                                                <button type="submit" class="vy-btn vy-btn--ghost">Keep this property</button>
                                            </form>
                                        @endif
                                    @elseif ($readOnly)
                                        <button type="button" class="vy-btn vy-btn--ghost vy-prop-remove" disabled aria-disabled="true" title="Your workspace is read-only right now" aria-label="Remove {{ $p['name'] }} (unavailable while the workspace is read-only)">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                                        </button>
                                    @else
                                        <button type="button" class="vy-btn vy-btn--ghost vy-prop-remove" data-remove-property="{{ $p['id'] }}"
                                            @click="removing = @js($p['id'])" aria-label="Remove {{ $p['name'] }}" title="Remove property">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- SIDE-BY-SIDE COMPARISON (deferred from 6a, real in 6b) — ADR/RevPAR/revenue per property, this
             year, all REAL via the SG1 metric layer. A property with no data shows honest zeros. --}}
        @if (count($comparison['rows']) > 0)
            <div class="vy-card vy-fade" style="margin-top: 14px;">
                <div class="vy-card-head">
                    <h3>This year, side by side</h3>
                    {{-- The design's "Full report ›". The gate was already computed by the component and simply
                         never rendered — so the control is gated, not merely hidden-if-403 (#30). --}}
                    @if ($canViewAnalytics)
                        <a href="{{ route('app.reports') }}" style="font-size:12px;font-weight:600;color:var(--vy-accent-text);text-decoration:none">Full report ›</a>
                    @else
                        <span class="vy-card-note">occupancy · ADR · RevPAR · room revenue</span>
                    @endif
                </div>
                {{-- ⚠️⚠️ THE HOUSE SCROLL WRAPPER, AND IT IS THE FIX THE SHELL ASKED FOR BY NAME.
                     `operator.blade.php:1694` records a `.vy-card:has(> .vy-table) { overflow-x: auto }`
                     rule that cleared this page's 98px and was then WITHDRAWN, because making a whole card
                     scroll sideways can push a card's action button out of reach — and that pass could not
                     establish that none does. Its stated durable fix was "wrap that table in the house
                     `.vy-utable-wrap`, as `/app/units` already does". That is this, and the shell rule is
                     NOT restored.
                     ⚠️ THE QUESTION THAT PASS COULD NOT ANSWER IS ANSWERED BY READING THE TABLE. Its five
                     columns are Property · Occupancy · ADR · RevPAR · Room revenue and the `<tbody>` below
                     holds no `<button>`, no `<a>` and no control of any kind — the far edge is a NUMBER.
                     Nothing can be pushed out of reach because there is nothing there to push. The card's
                     own action ("Full report ›", :153) sits in the header ABOVE this wrapper and does not
                     move.
                     ⚠️ `.vy-utable-wrap` only supplies `overflow-x: auto`; the `min-width: 720px` floor
                     belongs to `.vy-utable`, which this table does not carry. So the table keeps its own
                     434px width and simply scrolls when the page is narrower than that. --}}
                <div class="vy-utable-wrap">
                <table class="vy-table">
                    <thead><tr><th>Property</th><th class="vy-num">Occupancy</th><th class="vy-num">ADR</th><th class="vy-num">RevPAR</th><th class="vy-num">Room revenue</th></tr></thead>
                    <tbody>
                        @foreach ($comparison['rows'] as $r)
                            <tr>
                                <td>
                                    <span style="display:inline-flex;align-items:center;gap:9px">
                                        <span class="vy-prop-mark" style="width:26px;height:26px;border-radius:8px;font-size:11px" aria-hidden="true">{{ $r['initial'] }}</span>
                                        <span>{{ $r['name'] }}<span style="display:block;font-size:11px;color:var(--vy-faint)">{{ $r['units'] }} {{ \Illuminate\Support\Str::plural('unit', $r['units']) }}</span></span>
                                    </span>
                                </td>
                                <td class="vy-num">{{ $r['occupancy_pct'] }}</td>
                                <td class="vy-num">{{ $r['adr'] }}</td>
                                <td class="vy-num">{{ $r['revpar'] }}</td>
                                <td class="vy-num">{{ $r['revenue'] }}</td>
                            </tr>
                        @endforeach
                        @if (count($comparison['rows']) > 1)
                            <tr style="font-weight:700;border-top:1.5px solid var(--vy-glass-border)">
                                <td>Portfolio</td>
                                <td class="vy-num">{{ $comparison['total']['occupancy_pct'] }}</td>
                                <td class="vy-num">{{ $comparison['total']['adr'] }}</td>
                                <td class="vy-num">{{ $comparison['total']['revpar'] }}</td>
                                <td class="vy-num">{{ $comparison['total']['revenue'] }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                </div>
            </div>
        @endif

        {{-- ADD PROPERTY modal --}}
        @if ($canManage)
            <div class="vy-modal-backdrop" x-cloak x-show="addOpen" x-transition.opacity
                 @keydown.escape.window="addOpen = false" @click.self="addOpen = false">
                <div class="vy-modal vy-modal-card" x-show="addOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-add-title" tabindex="-1" x-trap.noscroll="addOpen">
                    <form method="POST" action="{{ route('app.portfolio.store') }}">
                        @csrf
                        <input type="hidden" name="_form" value="add">
                        <div class="vy-modal-head">
                            <span class="vy-modal-ico">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>
                            </span>
                            <div style="flex: 1;">
                                <h3 class="vy-modal-title" id="vy-add-title">Add a property</h3>
                                {{-- ⚠️ DO-BILL-2 — the billing consequence is KEYED BY THE PLAN'S SHAPE (§N.16.3).
                                     This said "billed per unit" to EVERY org, which is false on a FLAT tier:
                                     adding a property to Essential/Pro changes the bill by nothing. The demo
                                     org is flat, so it was live-false on the default demo — the same §N.8d
                                     billing pass 3 retired on /app/billing, one page over (§N.17.4).

                                     ⚠️ The per-unit claim lives INSIDE the per-unit branch, so a flat org
                                     CANNOT render it — a property of the markup, not a hide we remembered to
                                     add. `$planIsPerUnit` is resolved from the org's current plan, the SAME
                                     source the Billing page reads, so the two cannot disagree (§N.8f). --}}
                                <p class="vy-modal-sub">It joins your organization —
                                    @if ($planIsPerUnit)
                                        billed per unit.
                                    @else
                                        included in your plan.
                                    @endif
                                    {{-- ⚠️ P-6 · Gate 1 — A SIXTH SITE, found by sweeping for the phrase rather than trusting the
                         five already fixed (§N.27.1). It sits 34 lines above one of them, in the same modal
                         (§N.13.1). Channels dropped: still no creation path (decision ④). --}}
                    You'll set up a room type, a unit and a rate plan next; all your team roles carry over.</p>
                            </div>
                            <button type="button" class="vy-modal-x" @click="addOpen = false" aria-label="Close">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="vy-modal-body">
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-add-name">Property name</label>
                                <input type="text" id="vy-add-name" name="name" class="vy-input" required maxlength="255" placeholder="e.g. Seaside Villas" value="{{ old('name') }}">
                                @error('name') <div class="vy-help" style="color: var(--vy-accent);">{{ $message }}</div> @enderror
                            </div>
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-add-loc">Location <span class="vy-fl-note">· optional</span></label>
                                <input type="text" id="vy-add-loc" name="location" class="vy-input" maxlength="255" placeholder="e.g. Faro, Portugal" value="{{ old('location') }}">
                            </div>
                            {{-- 2026-09-29 — the type as three tiles, the time zone as a searchable list with offsets (the same
                                 posted fields as before; see the components). --}}
                            <x-operator.choices name="type" label="Type" :options="$typeChoices" :selected="old('type', 'villa')" />
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-add-tz">Time zone</label>
                                <x-select name="timezone" id="vy-add-tz" :options="$timezones" :value="old('timezone', 'Europe/Lisbon')" search-placeholder="Search time zones" empty-text="No time zone matches — try a city or “UTC+4”" :device-timezone="true" :min-width="340" />
                            </div>
                            {{-- ⚠️ P-6 · Gate 1 — channels dropped: they still have no creation path (decision ④), and naming a
                                 step the tenant cannot take is the defect this gate exists to remove. --}}
                            <div class="vy-help">A new property starts empty — add a room type and a unit, then a rate plan with prices, and it can take bookings.</div>
                        </div>
                        <div class="vy-modal-foot">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="addOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                Add property
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- EDIT PROPERTY modal --}}
            <div class="vy-modal-backdrop" x-cloak x-show="editOpen" x-transition.opacity
                 @keydown.escape.window="editOpen = false" @click.self="editOpen = false">
                <div class="vy-modal vy-modal-card" x-show="editOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-edit-title" tabindex="-1" x-trap.noscroll="editOpen">
                    <form method="POST" action="{{ route('app.portfolio.update') }}">
                        @csrf
                        <input type="hidden" name="property_id" :value="editing.id">
                        <div class="vy-modal-head">
                            <span class="vy-modal-ico">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                            </span>
                            <div style="flex: 1;">
                                <h3 class="vy-modal-title" id="vy-edit-title">Edit property</h3>
                                <p class="vy-modal-sub" x-text="editing.name"></p>
                            </div>
                            <button type="button" class="vy-modal-x" @click="editOpen = false" aria-label="Close">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="vy-modal-body">
                            <div class="vy-field">
                                <label class="vy-field-label">Property name</label>
                                <input type="text" name="name" class="vy-input" required maxlength="255" x-model="editing.name">
                            </div>
                            <x-operator.choices name="type" label="Type" :options="$typeChoices" model="editing.type" />
                            <x-operator.choices name="status" label="Status" variant="segmented" model="editing.status"
                                :options="[['value' => 'active', 'label' => 'Active'], ['value' => 'inactive', 'label' => 'Inactive']]" />
                            <div class="vy-field">
                                <label class="vy-field-label" for="vy-edit-tz">Time zone</label>
                                <x-select name="timezone" id="vy-edit-tz" :options="$timezones" x-model="editing.timezone" search-placeholder="Search time zones" empty-text="No time zone matches — try a city or “UTC+4”" :device-timezone="true" :min-width="340" />
                            </div>
                        </div>
                        <div class="vy-modal-foot">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ⚠️⚠️ REMOVE A PROPERTY (2026-09-29) — one confirmation per card, rendered on the server from the SAME read the
                 action acts on (PropertyRemovalService::outlook), so it can never promise "right away" while the action
                 schedules. The name typed is checked here as you type, and again on the server. --}}
            @foreach ($cards as $p)
                @continue($p['removal']['scheduled'] || $readOnly)
                <div class="vy-modal-backdrop" x-cloak x-show="removing === @js($p['id'])" x-transition.opacity
                     @keydown.escape.window="removing = null" @click.self="removing = null">
                    <div class="vy-modal vy-modal-card" x-show="removing === @js($p['id'])" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-rm-title-{{ $p['id'] }}" tabindex="-1" x-trap.noscroll="removing === @js($p['id'])" x-data="{ typed: '' }">
                        @if ($p['removal']['last_remaining'])
                            <div class="vy-modal-head">
                                <span class="vy-modal-ico">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>
                                </span>
                                <div style="flex: 1;">
                                    <h3 class="vy-modal-title" id="vy-rm-title-{{ $p['id'] }}">{{ $p['name'] }} is your only property</h3>
                                    <p class="vy-modal-sub">{{ $p['location'] }} · {{ $p['type_label'] }}</p>
                                </div>
                                <button type="button" class="vy-modal-x" @click="removing = null" aria-label="Close">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="vy-modal-body">
                                <p class="vy-rm-only">Vyrobel needs at least one property to work in, and this is the only one that isn’t leaving.
                                    <strong>Add another property first, then you can remove this one.</strong></p>
                            </div>
                            <div class="vy-modal-foot">
                                <button type="button" class="vy-btn vy-btn--ghost" @click="removing = null">Close</button>
                                <button type="button" class="vy-btn vy-btn--accent" @click="removing = null; addOpen = true">Add a property</button>
                            </div>
                        @else
                            <form method="POST" action="{{ route('app.portfolio.remove', $p['id']) }}">
                                @csrf
                                <div class="vy-modal-head">
                                    <span class="vy-modal-ico is-danger">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                                    </span>
                                    <div style="flex: 1; min-width: 0;">
                                        <h3 class="vy-modal-title" id="vy-rm-title-{{ $p['id'] }}">Remove {{ $p['name'] }}?</h3>
                                        <p class="vy-modal-sub">{{ $p['location'] }} · {{ $p['units'] }} {{ \Illuminate\Support\Str::plural('unit', $p['units']) }}</p>
                                    </div>
                                    <button type="button" class="vy-modal-x" @click="removing = null" aria-label="Close">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div class="vy-modal-body">
                                    <ol class="vy-rm-path">
                                        @if ($p['removal']['clear'])
                                            <li class="vy-rm-step is-end">
                                                <span class="vy-rm-when">Removed as soon as you confirm</span>
                                                <span class="vy-rm-what">Nobody is staying and nothing is booked ahead.{{ $p['units'] > 0 ? ' Owners of its units are told.' : '' }}</span>
                                            </li>
                                        @else
                                            <li class="vy-rm-step">
                                                <span class="vy-rm-when">Today</span>
                                                <span class="vy-rm-what">
                                                    @if ($p['removal']['stays'] > 0)
                                                        {{ $p['removal']['stays'] }} current or upcoming {{ \Illuminate\Support\Str::plural('stay', $p['removal']['stays']) }}@if ($p['removal']['in_house'] > 0), {{ $p['removal']['in_house'] }} checked in @endif.
                                                    @else
                                                        {{ $p['removal']['in_house'] }} {{ \Illuminate\Support\Str::plural('guest', $p['removal']['in_house']) }} still checked in, past the checkout date.
                                                    @endif
                                                    It stays open for them, and a new booking moves the date later.
                                                </span>
                                            </li>
                                            <li class="vy-rm-step is-end">
                                                <span class="vy-rm-when">
                                                    @if ($p['removal']['last_checkout'] !== null)
                                                        Stays open until the last checkout on {{ $p['removal']['last_checkout'] }}
                                                    @else
                                                        Stays open until they check out
                                                    @endif
                                                </span>
                                                <span class="vy-rm-what">Then it’s removed at the nightly run{{ $p['units'] > 0 ? ', and owners of its units are told' : '' }}. You can keep it any time before.</span>
                                            </li>
                                        @endif
                                    </ol>

                                    <div class="vy-rm-ledger">
                                        <div class="vy-rm-col is-kept">
                                            <h4>Kept</h4>
                                            <ul><li>Bookings and guest history</li><li>Invoices, payments and statements</li></ul>
                                        </div>
                                        <div class="vy-rm-col is-goes">
                                            <h4>Goes</h4>
                                            <ul><li>Its storefront page</li><li>Its channel connections</li><li>This card — it can’t be restored</li></ul>
                                        </div>
                                    </div>

                                    <div class="vy-field" style="margin-bottom: 0;">
                                        <label class="vy-field-label" for="vy-rm-name-{{ $p['id'] }}">Type “{{ $p['name'] }}” to confirm</label>
                                        <input type="text" id="vy-rm-name-{{ $p['id'] }}" name="confirm_name" class="vy-input" x-model="typed" autocomplete="off" spellcheck="false" required>
                                        <span class="vy-rm-check" :class="typed.trim() === @js($p['name']) && 'is-ok'" aria-live="polite"
                                              x-text="typed.trim() === @js($p['name']) ? 'Name matches' : (typed.trim() === '' ? '' : 'It must match exactly')"></span>
                                    </div>
                                </div>
                                <div class="vy-modal-foot">
                                    <button type="button" class="vy-btn vy-btn--ghost" @click="removing = null">Cancel</button>
                                    <button type="submit" class="vy-btn vy-btn--danger" disabled :disabled="typed.trim() !== @js($p['name'])">
                                        {{ $p['removal']['clear'] ? 'Remove property' : 'Remove after last checkout' }}
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </x-operator.shell>
</div>
