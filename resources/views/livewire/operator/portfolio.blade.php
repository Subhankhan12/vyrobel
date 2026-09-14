{{-- Operator Portfolio (Phase 6a · Step 2) — the multi-property overview + property CRUD. Every figure is
     REAL/DERIVED: unit counts, occupancy from the Availability spine, arrivals from the front-desk read, a
     status derived from real inventory ("Finish setup to go live" when a property has no units yet). The
     ADR/RevPAR/revenue comparison is REAL via the SG1 metric layer (it was deferred in 6a, built in 6b —
     this docblock said "deferred" long after it shipped). Add/Edit render only for `properties.manage`.
     ⚠️ An average over ZERO room-nights renders "—", not a zero — a computed zero (revenue) still shows. --}}
<div x-data="{ addOpen: {{ $errors->any() && old('_form') === 'add' ? 'true' : 'false' }}, editOpen: false, editing: {} }">
    <x-operator.shell active="portfolio" title="Portfolio"
        :subtitle="$summary['properties'] . ' ' . \Illuminate\Support\Str::plural('property', $summary['properties']) . ' · ' . $summary['units'] . ' ' . \Illuminate\Support\Str::plural('unit', $summary['units'])"
        :viewer="$viewer">

        @if (session('portfolio_notice'))
            <div class="vy-flash vy-fade" role="status">{{ session('portfolio_notice') }}</div>
        @endif

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

        @if (session('portfolio_notice')) <div class="vy-flash is-ok vy-fade">{{ session('portfolio_notice') }}</div> @endif
        @if (session('portfolio_error')) <div class="vy-flash is-err vy-fade">{{ session('portfolio_error') }}</div> @endif

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
                    <div class="vy-prop-card vy-card--tappable @if ($p['is_current']) is-current @endif">
                        {{-- The design's full-bleed gradient band (deepened for AA — see .vy-prop-band). --}}
                        <div class="vy-prop-band">
                            <span class="vy-prop-mark">{{ $p['initial'] }}</span>
                            <div class="vy-prop-id">
                                <span class="vy-prop-name">{{ $p['name'] }}@if ($p['is_current'])<span class="vy-prop-current">Working here</span>@endif</span>
                                <span class="vy-prop-loc">{{ $p['location'] }} · {{ $p['type_label'] }} · {{ $p['units'] }} {{ \Illuminate\Support\Str::plural('unit', $p['units']) }}</span>
                            </div>
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

                            <div class="vy-prop-actions">
                                @if (! $p['is_current'])
                                    <form method="POST" action="{{ route('app.property.switch') }}">
                                        @csrf
                                        <input type="hidden" name="property_id" value="{{ $p['id'] }}">
                                        <button type="submit" class="vy-btn vy-btn--ghost">Open</button>
                                    </form>
                                @endif
                                @if ($canManage)
                                    <button type="button" class="vy-btn vy-btn--ghost"
                                        @click="editing = {{ \Illuminate\Support\Js::from(['id' => $p['id'], 'name' => $p['name'], 'type' => $p['type'], 'timezone' => $p['timezone'], 'status' => ($p['status']['label'] === 'Inactive' ? 'inactive' : 'active')]) }}; editOpen = true">Edit</button>
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
                <div class="vy-modal" x-show="addOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-add-title" tabindex="-1" x-trap.noscroll="addOpen">
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
                            <div class="vy-field" style="display: flex; gap: 12px;">
                                <div style="flex: 1;">
                                    <label class="vy-field-label" for="vy-add-type">Type</label>
                                    <select id="vy-add-type" name="type" class="vy-input">
                                        <option value="villa">Villas &amp; apartments</option>
                                        <option value="hotel">Hotel</option>
                                        <option value="guest_house">Guesthouse / lodge</option>
                                    </select>
                                </div>
                                <div style="flex: 1;">
                                    <label class="vy-field-label" for="vy-add-tz">Time zone</label>
                                    <select id="vy-add-tz" name="timezone" class="vy-input">
                                        @foreach (timezone_identifiers_list() as $tz)
                                            <option value="{{ $tz }}" @selected($tz === 'Europe/Lisbon')>{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                </div>
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
                <div class="vy-modal" x-show="editOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-edit-title" tabindex="-1" x-trap.noscroll="editOpen">
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
                            <div class="vy-field" style="display: flex; gap: 12px;">
                                <div style="flex: 1;">
                                    <label class="vy-field-label">Type</label>
                                    <select name="type" class="vy-input" x-model="editing.type">
                                        <option value="villa">Villas &amp; apartments</option>
                                        <option value="hotel">Hotel</option>
                                        <option value="guest_house">Guesthouse / lodge</option>
                                    </select>
                                </div>
                                <div style="flex: 1;">
                                    <label class="vy-field-label">Status</label>
                                    <select name="status" class="vy-input" x-model="editing.status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="vy-field">
                                <label class="vy-field-label">Time zone</label>
                                <select name="timezone" class="vy-input" x-model="editing.timezone">
                                    @foreach (timezone_identifiers_list() as $tz)
                                        <option value="{{ $tz }}">{{ $tz }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="vy-modal-foot">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="editOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </x-operator.shell>
</div>
