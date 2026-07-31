{{-- Operator Portfolio (Phase 6a · Step 2) — the multi-property overview + property CRUD. Every figure is
     REAL/DERIVED: unit counts, occupancy from the Availability spine, arrivals from the front-desk read, a
     status derived from real inventory ("Finish setup to go live" when a property has no units yet). The
     prototype's ADR/RevPAR/revenue comparison is ANALYTICS — deferred honestly to Phase 6b, never faked.
     Add/Edit render only for `properties.manage`. --}}
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
                        <div class="vy-prop-head">
                            <span class="vy-prop-mark">{{ $p['initial'] }}</span>
                            <div class="vy-prop-id">
                                <span class="vy-prop-name">{{ $p['name'] }}@if ($p['is_current'])<span class="vy-prop-current">Working here</span>@endif</span>
                                <span class="vy-prop-loc">{{ $p['location'] }} · {{ $p['type_label'] }}</span>
                            </div>
                        </div>

                        <div class="vy-prop-stats">
                            <div><span class="vy-ps-val">{{ $p['occupancy_pct'] }}%</span><span class="vy-ps-lab">Occupancy</span></div>
                            <div><span class="vy-ps-val">{{ $p['adr'] }}</span><span class="vy-ps-lab">ADR</span></div>
                            <div><span class="vy-ps-val">{{ $p['revenue'] }}</span><span class="vy-ps-lab">Revenue</span></div>
                        </div>

                        <div class="vy-prop-status vy-status-{{ $p['status']['tone'] }}">
                            <span class="vy-status-dot"></span>{{ $p['status']['label'] }}
                        </div>

                        {{-- Fix-sync — HONEST: the REAL channel status (never a fabricated "synced", #85/#39). --}}
                        <div class="vy-prop-sync vy-status-{{ $p['sync']['tone'] }}" style="font-size: 11.5px; margin-top: 6px; display: flex; justify-content: space-between; align-items: center;">
                            <span><span class="vy-status-dot"></span>{{ $p['sync']['label'] }}</span>
                            @if ($p['sync']['needs_fix'])
                                <a href="{{ route('app.channels') }}" style="color: var(--vy-accent); font-weight: 600;">Fix sync →</a>
                            @endif
                        </div>

                        <div class="vy-prop-actions">
                            @if (! $p['is_current'])
                                <form method="POST" action="{{ route('app.property.switch') }}">
                                    @csrf
                                    <input type="hidden" name="property_id" value="{{ $p['id'] }}">
                                    <button type="submit" class="vy-btn vy-btn--ghost">Open</button>
                                </form>
                            @else
                                <a class="vy-btn vy-btn--ghost" href="{{ route('app.dashboard') }}">Dashboard</a>
                            @endif
                            @if ($canManage)
                                <button type="button" class="vy-btn vy-btn--ghost"
                                    @click="editing = {{ \Illuminate\Support\Js::from(['id' => $p['id'], 'name' => $p['name'], 'type' => $p['type'], 'timezone' => $p['timezone'], 'status' => ($p['status']['label'] === 'Inactive' ? 'inactive' : 'active')]) }}; editOpen = true">Edit</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- SIDE-BY-SIDE COMPARISON (deferred from 6a, real in 6b) — ADR/RevPAR/revenue per property, this
             year, all REAL via the SG1 metric layer. A property with no data shows honest zeros. --}}
        @if (count($comparison['rows']) > 0)
            <div class="vy-card vy-fade" style="margin-top: 14px;">
                <div class="vy-card-head"><h3>This year, side by side</h3><span class="vy-card-note">occupancy · ADR · RevPAR · room revenue</span></div>
                <table class="vy-table">
                    <thead><tr><th>Property</th><th class="vy-num">Occupancy</th><th class="vy-num">ADR</th><th class="vy-num">RevPAR</th><th class="vy-num">Room revenue</th></tr></thead>
                    <tbody>
                        @foreach ($comparison['rows'] as $r)
                            <tr>
                                <td>{{ $r['name'] }}</td>
                                <td class="vy-num">{{ $r['occupancy_pct'] }}%</td>
                                <td class="vy-num">{{ $r['adr'] }}</td>
                                <td class="vy-num">{{ $r['revpar'] }}</td>
                                <td class="vy-num">{{ $r['revenue'] }}</td>
                            </tr>
                        @endforeach
                        @if (count($comparison['rows']) > 1)
                            <tr style="font-weight:700;border-top:1.5px solid var(--vy-glass-border)">
                                <td>Portfolio</td>
                                <td class="vy-num">{{ $comparison['total']['occupancy_pct'] }}%</td>
                                <td class="vy-num">{{ $comparison['total']['adr'] }}</td>
                                <td class="vy-num">{{ $comparison['total']['revpar'] }}</td>
                                <td class="vy-num">{{ $comparison['total']['revenue'] }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ADD PROPERTY modal --}}
        @if ($canManage)
            <div class="vy-modal-backdrop" x-cloak x-show="addOpen" x-transition.opacity
                 @keydown.escape.window="addOpen = false" @click.self="addOpen = false">
                <div class="vy-modal" x-show="addOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-add-title">
                    <form method="POST" action="{{ route('app.portfolio.store') }}">
                        @csrf
                        <input type="hidden" name="_form" value="add">
                        <div class="vy-modal-head">
                            <span class="vy-modal-ico">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>
                            </span>
                            <div style="flex: 1;">
                                <h3 class="vy-modal-title" id="vy-add-title">Add a property</h3>
                                <p class="vy-modal-sub">It joins your organization — billed per unit. You'll set up units, rates &amp; channels next; all your team roles carry over.</p>
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
                            <div class="vy-help">A new property starts empty — finish setup (units, rates &amp; channels) to go live.</div>
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
                <div class="vy-modal" x-show="editOpen" x-transition role="dialog" aria-modal="true" aria-labelledby="vy-edit-title">
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
