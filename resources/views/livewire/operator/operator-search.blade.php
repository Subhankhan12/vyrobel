{{-- Operator GLOBAL SEARCH (Phase 1 · Sub-gate 2) — find any reservation / guest / unit / owner. READ view;
     the query rides a GET (?q=) so the tenant-scoped reads never leak (invariant #20). Work is SearchService.
     Guests are searched by NAME only (email/phone are encrypted — blind index deferred); the limitation is
     surfaced, never a silent empty result. Every row is a real record — nothing fabricated.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. Alpine uses x-on:/x-model/x-bind (no `@click`). --}}
<div>
    <x-operator.shell active="search" title="Search" :subtitle="'Everything, in one place'" :viewer="$viewer">

        {{-- ── The query + real filters (GET → drives the scoped search) ───────────────────────────── --}}
        <form method="GET" action="{{ route('app.search') }}" class="vy-card vy-fade" style="margin-bottom: 16px;"
              x-data="searchBox()" x-on:submit="remember()">
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="q" class="vy-input" value="{{ $query }}" x-ref="q" autofocus autocomplete="off"
                    placeholder="Search reservations, guests, units, owners…" style="flex: 1;">
                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Search</button>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;">
                <select name="status" class="vy-input" style="width: auto; font-size: 12.5px;">
                    <option value="">Any status</option>
                    @foreach ($statuses as $st)
                        <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>
                <select name="source" class="vy-input" style="width: auto; font-size: 12.5px;">
                    <option value="">Any source</option>
                    @foreach ($sources as $sc)
                        <option value="{{ $sc }}" @selected(($filters['source'] ?? '') === $sc)>{{ ucfirst(str_replace('_', ' ', $sc)) }}</option>
                    @endforeach
                </select>
                <select name="occupancy" class="vy-input" style="width: auto; font-size: 12.5px;">
                    <option value="">Any occupancy</option>
                    <option value="1" @selected(($filters['occupancy'] ?? '') === '1')>1 guest</option>
                    <option value="2" @selected(($filters['occupancy'] ?? '') === '2')>2 guests</option>
                    <option value="3plus" @selected(($filters['occupancy'] ?? '') === '3plus')>3+ guests</option>
                </select>
                <input type="date" name="from" class="vy-input" value="{{ $filters['from'] ?? '' }}" style="width: auto; font-size: 12.5px;" title="Check-in from">
                <input type="date" name="to" class="vy-input" value="{{ $filters['to'] ?? '' }}" style="width: auto; font-size: 12.5px;" title="Check-in to">
                <select name="sort" class="vy-input" style="width: auto; font-size: 12.5px;">
                    <option value="recent" @selected(($filters['sort'] ?? 'recent') === 'recent')>Sort · newest</option>
                    <option value="checkin" @selected(($filters['sort'] ?? '') === 'checkin')>Sort · check-in</option>
                    <option value="reference" @selected(($filters['sort'] ?? '') === 'reference')>Sort · reference</option>
                </select>
                @if ($searched)
                    <a href="{{ route('app.search') }}" class="vy-btn" style="padding: 8px 12px; font-size: 12px;">Clear all filters</a>
                @endif
            </div>
            <div style="font-size: 11.5px; color: var(--vy-faint); margin-top: 9px;">
                Search a guest by name or email, a reservation by reference (R-…), or a unit. Phone search isn’t available (numbers are stored encrypted).
            </div>
        </form>

        {{-- ⚠️ Encrypted-contact honesty callout — an email typed by a viewer without guest access. --}}
        @if ($contactNote)
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-amber); margin-bottom: 16px;">
                <div style="font-size: 13px; color: var(--vy-ink);">Searching a guest by email needs guest access — you can search by <strong>name</strong> here.</div>
            </div>
        @endif

        @unless ($searched)
            {{-- Recent searches (client-side localStorage — real, per-browser) + suggested arrivals --}}
            <div x-data="searchBox()" x-init="load()">
                <div class="vy-card vy-fade" style="margin-bottom: 16px;" x-show="recent.length > 0" x-cloak>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 12.5px; font-weight: 600; color: var(--vy-ink);">Recent searches</span>
                        <button type="button" class="vy-btn" style="padding: 4px 10px; font-size: 11px;" x-on:click="clear()">Clear</button>
                    </div>
                    <template x-for="t in recent" x-bind:key="t">
                        <a x-bind:href="'{{ route('app.search') }}?q=' + encodeURIComponent(t)" style="display: inline-block; margin: 0 6px 6px 0; padding: 5px 11px; border: 1px solid var(--vy-hairline); border-radius: 16px; font-size: 12.5px; color: var(--vy-ink); text-decoration: none;" x-text="t"></a>
                    </template>
                </div>
            </div>

            @if (count($suggested) > 0)
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <span style="font-size: 12.5px; font-weight: 600; color: var(--vy-ink);">Upcoming arrivals</span>
                    <div style="margin-top: 8px;">
                        @foreach ($suggested as $sug)
                            <a href="{{ route('app.search', ['q' => $sug['reference']]) }}" style="display: flex; justify-content: space-between; padding: 7px 0; border-top: 1px solid var(--vy-hairline); font-size: 13px; color: var(--vy-ink); text-decoration: none;">
                                <span>{{ $sug['guest'] }} <span style="color: var(--vy-faint);">· {{ $sug['reference'] }}</span></span>
                                <span style="color: var(--vy-muted);">{{ $sug['when'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="vy-card vy-fade vys-empty">Type a reference, name, unit code or owner above to search across the whole property.</div>
        @else
            @if ($total === 0)
                <div class="vy-card vy-fade vys-empty">No matches for “{{ $query }}”. Try a reference, a guest or owner name, or a unit code.</div>
            @else
                <div x-data="{ tab: 'all' }">
                    {{-- Result-type tabs (client-side filter of the groups already loaded) --}}
                    <div class="vys-tabs vy-fade">
                        @php
                            $tabs = [
                                ['all', 'All', $counts['all']],
                                ['reservations', 'Reservations', $counts['reservations']],
                                ['guests', 'Guests', $counts['guests']],
                                ['units', 'Units', $counts['units']],
                                ['owners', 'Owners', $counts['owners']],
                            ];
                        @endphp
                        @foreach ($tabs as [$key, $label, $n])
                            <button type="button" class="vys-tab" x-bind:class="tab === '{{ $key }}' ? 'is-on' : ''" x-on:click="tab = '{{ $key }}'">
                                {{ $label }} <span class="vys-tab-n">{{ $n }}</span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Reservations --}}
                    @if ($results['reservations'] !== [])
                        <div class="vy-card vy-fade" style="margin-bottom: 14px;" x-show="tab === 'all' || tab === 'reservations'">
                            <h2 class="vys-group-title">Reservations</h2>
                            @foreach ($results['reservations'] as $r)
                                <a class="vys-row" href="{{ $r['url'] }}">
                                    <span class="vys-row-main">
                                        <strong>{{ $r['reference'] }}</strong>
                                        <span class="vys-row-sub">{{ $r['guest'] }}</span>
                                    </span>
                                    <span class="vys-row-meta">
                                        <span class="vys-badge">{{ ucfirst(str_replace('_', ' ', $r['status'])) }}</span>
                                        <span class="vys-badge vys-badge--soft">{{ ucfirst(str_replace('_', ' ', $r['source'])) }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Guests — NAME only --}}
                    @if ($results['guests'] !== [])
                        <div class="vy-card vy-fade" style="margin-bottom: 14px;" x-show="tab === 'all' || tab === 'guests'">
                            <h2 class="vys-group-title">Guests</h2>
                            @foreach ($results['guests'] as $g)
                                <a class="vys-row" href="{{ $g['url'] }}">
                                    <span class="vys-row-main">
                                        <span class="vys-av">{{ $g['initials'] }}</span>
                                        <strong>{{ $g['name'] }}</strong>
                                    </span>
                                    <span class="vys-row-meta"><span class="vys-badge vys-badge--soft">Guest profile →</span></span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Units --}}
                    @if ($results['units'] !== [])
                        <div class="vy-card vy-fade" style="margin-bottom: 14px;" x-show="tab === 'all' || tab === 'units'">
                            <h2 class="vys-group-title">Units</h2>
                            @foreach ($results['units'] as $u)
                                <a class="vys-row" href="{{ $u['url'] }}">
                                    <span class="vys-row-main">
                                        <strong>{{ $u['code'] }}</strong>
                                        <span class="vys-row-sub">{{ $u['name'] }}</span>
                                    </span>
                                    <span class="vys-row-meta"><span class="vys-badge vys-badge--soft">Units →</span></span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Owners --}}
                    @if ($results['owners'] !== [])
                        <div class="vy-card vy-fade" style="margin-bottom: 14px;" x-show="tab === 'all' || tab === 'owners'">
                            <h2 class="vys-group-title">Owners</h2>
                            @foreach ($results['owners'] as $o)
                                <a class="vys-row" href="{{ $o['url'] }}">
                                    <span class="vys-row-main">
                                        <span class="vys-av">{{ $o['initials'] }}</span>
                                        <strong>{{ $o['name'] }}</strong>
                                    </span>
                                    <span class="vys-row-meta"><span class="vys-badge vys-badge--soft">Owner detail →</span></span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        @endunless

    </x-operator.shell>

    <script>
        function searchBox() {
            return {
                recent: [],
                load() { try { this.recent = JSON.parse(localStorage.getItem('vy_recent_search') || '[]'); } catch (e) { this.recent = []; } },
                remember() {
                    const q = (this.$refs.q ? this.$refs.q.value : '').trim();
                    if (!q) return;
                    let list = [];
                    try { list = JSON.parse(localStorage.getItem('vy_recent_search') || '[]'); } catch (e) { list = []; }
                    list = [q].concat(list.filter((t) => t !== q)).slice(0, 6);
                    localStorage.setItem('vy_recent_search', JSON.stringify(list));
                },
                clear() { localStorage.removeItem('vy_recent_search'); this.recent = []; },
            };
        }
    </script>

    <style>
        .vys-empty { text-align: center; padding: 32px 24px; color: var(--vy-muted); font-size: 13px; }
        .vys-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .vys-tab { border: 1px solid var(--vy-hairline); background: rgba(255,255,255,0.7); border-radius: 20px; padding: 6px 13px; font-size: 12.5px; color: var(--vy-muted); cursor: pointer; }
        .vys-tab.is-on { background: var(--vy-grad); color: #fff; border-color: transparent; }
        .vys-tab-n { font-weight: 700; margin-left: 3px; opacity: 0.85; }
        .vys-group-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--vy-muted); margin: 0 0 10px; }
        .vys-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--vy-r-md, 12px); text-decoration: none; }
        .vys-row:hover { background: rgba(232,112,58,0.06); }
        .vys-row + .vys-row { border-top: 1px solid var(--vy-hairline); }
        .vys-row-main { display: flex; align-items: center; gap: 10px; font-size: 13.5px; color: var(--vy-ink); }
        .vys-row-sub { color: var(--vy-muted); font-size: 12.5px; }
        .vys-row-meta { display: flex; gap: 6px; align-items: center; flex: none; }
        .vys-av { width: 30px; height: 30px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 11px; font-weight: 600; display: grid; place-items: center; flex: none; }
        .vys-badge { font-size: 10.5px; font-weight: 600; color: var(--vy-accent); background: rgba(232,112,58,0.10); border-radius: 6px; padding: 3px 8px; white-space: nowrap; }
        .vys-badge--soft { color: var(--vy-muted); background: rgba(120,70,40,0.06); }
    </style>
</div>
