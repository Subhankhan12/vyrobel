{{-- Operator GLOBAL SEARCH (Phase 1 · Sub-gate 2) — find any reservation / guest / unit / owner. READ view;
     the query rides a GET (?q=) so the tenant-scoped reads never leak (invariant #20). Work is SearchService.
     Guests are searched by NAME (or the deterministic email BLIND INDEX — matching, never displaying); the
     limitation is surfaced, never a silent empty result. Every row is a real record — nothing fabricated.

     ⚠️ #28 — THE PII PERIMETER IS THE WHOLE POINT OF THIS PAGE. Search is the one surface that aggregates
     guests, reservations, owners and units into a single list, so it is the easiest place to widen the
     perimeter by accident. The design's guest row carries `lukas.meyer@mail.com · Munich, Germany`; contact
     PII renders on the Guests CRM ONLY (list + profile) — a search row is NOT a permitted PII surface, so the
     email and the city are REFUSED here. SearchService returns name-only rows by construction; do not add a
     contact field to a row because the design shows one.

     ⚠️ FILTER CHIPS ARE LINKS, NOT A WIRE ACTION. Every chip is a plain GET href carrying q + the other
     filters, so the property/tenant middleware re-runs on each change (#20). Never convert these to
     wire:click — /livewire/update does not run the property-context middleware.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. Alpine uses x-on:/x-model/x-bind (no `@click`). --}}
<div>
    <x-operator.shell active="search" title="Search" :subtitle="'Everything, in one place'" :viewer="$viewer">

        @php
            // A filter chip's href — carries the current query + every OTHER active filter, so a chip never
            // silently drops the rest of the state. An empty value clears that one key ("Any …").
            $chipUrl = function (string $key, string $value) use ($query, $filters): string {
                $params = array_merge(['q' => $query], $filters);
                if ($value === '') {
                    unset($params[$key]);
                } else {
                    $params[$key] = $value;
                }

                return route('app.search', array_filter($params, fn ($v): bool => $v !== '' && $v !== null));
            };
            $label = fn (string $v): string => ucfirst(str_replace('_', ' ', $v));
        @endphp

        {{-- ── The query bar (GET → drives the scoped search) ──────────────────────────────────────── --}}
        <form method="GET" action="{{ route('app.search') }}" class="vy-fade" x-data="searchBox()" x-on:submit="remember()">
            {{-- Carry the active filters so a NEW query keeps the filter state (the chips carry q the other way). --}}
            @foreach (['status', 'source', 'occupancy', 'sort'] as $carry)
                @if (($filters[$carry] ?? '') !== '')
                    <input type="hidden" name="{{ $carry }}" value="{{ $filters[$carry] }}">
                @endif
            @endforeach

            <div class="vys-bar">
                <span class="vys-bar-ico" aria-hidden="true">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                </span>
                <input type="text" name="q" class="vys-bar-input" value="{{ $query }}" x-ref="q" autofocus autocomplete="off"
                    aria-label="Search reservations, guests, units and owners"
                    placeholder="Search reservations, guests, units, owners…">
                @if ($query !== '')
                    <a class="vys-bar-x" href="{{ route('app.search') }}" aria-label="Clear the search">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </a>
                @endif
                <button type="submit" class="vy-btn vy-btn--accent vys-bar-go">Search</button>
            </div>

            {{-- DATES — a REAL check-in range. The design's month presets (September / October / Upcoming)
                 are not filter semantics the service computes; the honest control is the range itself. --}}
            <div class="vys-frow">
                <span class="vys-flabel">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                    Dates
                </span>
                <input type="date" name="from" class="vys-date" value="{{ $filters['from'] ?? '' }}" aria-label="Check-in from">
                <span class="vys-dash">→</span>
                <input type="date" name="to" class="vys-date" value="{{ $filters['to'] ?? '' }}" aria-label="Check-in to">
            </div>
        </form>

        {{-- STATUS / SOURCE / OCCUPANCY — the design's chip rows. Each chip is a GET link (#20). --}}
        <div class="vys-frow vy-fade">
            <span class="vys-flabel">Status</span>
            <a class="vys-chip @if (($filters['status'] ?? '') === '') is-on @endif" href="{{ $chipUrl('status', '') }}">Any status</a>
            @foreach ($statuses as $st)
                <a class="vys-chip @if (($filters['status'] ?? '') === $st) is-on @endif" href="{{ $chipUrl('status', $st) }}">{{ $label($st) }}</a>
            @endforeach
        </div>
        <div class="vys-frow vy-fade">
            <span class="vys-flabel">Source</span>
            <a class="vys-chip @if (($filters['source'] ?? '') === '') is-on @endif" href="{{ $chipUrl('source', '') }}">Any source</a>
            @foreach ($sources as $sc)
                <a class="vys-chip @if (($filters['source'] ?? '') === $sc) is-on @endif" href="{{ $chipUrl('source', $sc) }}">{{ $label($sc) }}</a>
            @endforeach
        </div>
        <div class="vys-frow vy-fade" style="margin-bottom: 14px;">
            <span class="vys-flabel">Occupancy</span>
            <a class="vys-chip @if (($filters['occupancy'] ?? '') === '') is-on @endif" href="{{ $chipUrl('occupancy', '') }}">Any</a>
            @foreach (['1' => '1 guest', '2' => '2 guests', '3plus' => '3+ guests'] as $val => $lab)
                <a class="vys-chip @if (($filters['occupancy'] ?? '') === (string) $val) is-on @endif" href="{{ $chipUrl('occupancy', (string) $val) }}">{{ $lab }}</a>
            @endforeach
        </div>

        <p class="vys-hint vy-fade">
            Search a guest by name or email, a reservation by reference (R-…), or a unit. Phone search isn’t available (numbers are stored encrypted).
        </p>

        {{-- ⚠️ Encrypted-contact honesty callout — an email typed by a viewer without guest access. --}}
        @if ($contactNote)
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-amber); margin-bottom: 16px;">
                <div style="font-size: 13px; color: var(--vy-ink);">Searching a guest by email needs guest access — you can search by <strong>name</strong> here.</div>
            </div>
        @endif

        @unless ($searched)
            {{-- Recent searches (client-side localStorage — REAL, per-browser; nothing is persisted server-side) --}}
            <div x-data="searchBox()" x-init="load()">
                <div class="vy-card vy-fade" style="margin-bottom: 16px;" x-show="recent.length > 0" x-cloak>
                    <div class="vys-cardhead">
                        <span class="vys-cardtitle">Recent searches</span>
                        <button type="button" class="vys-textbtn" x-on:click="clear()">Clear</button>
                    </div>
                    <template x-for="t in recent" x-bind:key="t">
                        <a class="vys-recent" x-bind:href="'{{ route('app.search') }}?q=' + encodeURIComponent(t)">
                            <span class="vys-recent-ico" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                            </span>
                            <span class="vys-recent-t" x-text="t"></span>
                            <span class="vys-chev" aria-hidden="true">↗</span>
                        </a>
                    </template>
                </div>
            </div>

            @if (count($suggested) > 0)
                {{-- Suggested = REAL upcoming arrivals, never a fabricated suggestion (#39). --}}
                <div class="vy-card vy-fade" style="margin-bottom: 16px;">
                    <div class="vys-cardhead"><span class="vys-cardtitle">Upcoming arrivals</span></div>
                    @foreach ($suggested as $sug)
                        <a class="vys-row" href="{{ route('app.search', ['q' => $sug['reference']]) }}">
                            <span class="vys-tile" aria-hidden="true">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                            </span>
                            <span class="vys-row-main">
                                <span class="vys-row-title">{{ $sug['guest'] }}</span>
                                <span class="vys-row-sub">{{ $sug['reference'] }}</span>
                            </span>
                            <span class="vys-row-meta"><span class="vys-badge vys-badge--soft">{{ $sug['when'] }}</span><span class="vys-chev" aria-hidden="true">›</span></span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="vy-card vy-fade vys-empty">Type a reference, name, unit code or owner above to search across the whole property.</div>
        @else
            @if ($total === 0)
                <div class="vy-card vy-fade vys-empty">No matches for “{{ $query }}”. Try a reference, a guest or owner name, or a unit code.</div>
            @else
                <div x-data="{ tab: 'all' }">
                    {{-- Results header — the design's count line + SORT + clear.
                         ⚠️ "Relevance" is REFUSED: nothing scores relevance here. The three real orders stay. --}}
                    <div class="vys-head vy-fade">
                        <span class="vys-count">{{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }} for “{{ $query }}”</span>
                        <span class="vys-headright">
                            <span class="vys-flabel">Sort</span>
                            <span class="vys-seg">
                                @foreach (['recent' => 'Newest', 'checkin' => 'Check-in', 'reference' => 'Reference'] as $val => $lab)
                                    <a class="vys-seg-i @if (($filters['sort'] ?? 'recent') === $val) is-on @endif" href="{{ $chipUrl('sort', $val) }}">{{ $lab }}</a>
                                @endforeach
                            </span>
                            <a class="vys-textbtn" href="{{ route('app.search', ['q' => $query]) }}">× Clear all filters</a>
                        </span>
                    </div>

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
                        @foreach ($tabs as [$key, $tabLabel, $n])
                            <button type="button" class="vys-tab" x-bind:class="tab === '{{ $key }}' ? 'is-on' : ''" x-on:click="tab = '{{ $key }}'">
                                {{ $tabLabel }} <span class="vys-tab-n">{{ $n }}</span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Reservations --}}
                    @if ($results['reservations'] !== [])
                        <div class="vy-card vy-fade" style="margin-bottom: 14px;" x-show="tab === 'all' || tab === 'reservations'">
                            <h2 class="vys-group-title">Reservations <span class="vys-group-n">{{ $counts['reservations'] }}</span></h2>
                            @foreach ($results['reservations'] as $r)
                                <a class="vys-row" href="{{ $r['url'] }}">
                                    <span class="vys-tile" aria-hidden="true">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                                    </span>
                                    <span class="vys-row-main">
                                        {{-- NAME only — a reservation row never carries guest contact (#28). --}}
                                        <span class="vys-row-title">{{ $r['guest'] }}</span>
                                        <span class="vys-row-sub">{{ $r['reference'] }} · {{ $label($r['source']) }}</span>
                                    </span>
                                    <span class="vys-row-meta">
                                        {{-- Status carries its WORD, never colour alone (1.4.1). --}}
                                        <span class="vys-badge">{{ $label($r['status']) }}</span>
                                        <span class="vys-chev" aria-hidden="true">›</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Guests — ⚠️ NAME ONLY. The design's email + city are REFUSED here (#28). --}}
                    @if ($results['guests'] !== [])
                        <div class="vy-card vy-fade" style="margin-bottom: 14px;" x-show="tab === 'all' || tab === 'guests'">
                            <h2 class="vys-group-title">Guests <span class="vys-group-n">{{ $counts['guests'] }}</span></h2>
                            @foreach ($results['guests'] as $g)
                                <a class="vys-row" href="{{ $g['url'] }}">
                                    <span class="vys-av">{{ $g['initials'] }}</span>
                                    <span class="vys-row-main">
                                        <span class="vys-row-title">{{ $g['name'] }}</span>
                                    </span>
                                    <span class="vys-row-meta"><span class="vys-badge vys-badge--soft">Guest profile</span><span class="vys-chev" aria-hidden="true">›</span></span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Units --}}
                    @if ($results['units'] !== [])
                        <div class="vy-card vy-fade" style="margin-bottom: 14px;" x-show="tab === 'all' || tab === 'units'">
                            <h2 class="vys-group-title">Units <span class="vys-group-n">{{ $counts['units'] }}</span></h2>
                            @foreach ($results['units'] as $u)
                                <a class="vys-row" href="{{ $u['url'] }}">
                                    <span class="vys-tile" aria-hidden="true">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                                    </span>
                                    <span class="vys-row-main">
                                        <span class="vys-row-title">{{ $u['code'] }}</span>
                                        <span class="vys-row-sub">{{ $u['name'] }}</span>
                                    </span>
                                    <span class="vys-row-meta"><span class="vys-badge vys-badge--soft">Units</span><span class="vys-chev" aria-hidden="true">›</span></span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Owners — name only; no owner contact and NEVER a bank detail on a search row (#28/#42). --}}
                    @if ($results['owners'] !== [])
                        <div class="vy-card vy-fade" style="margin-bottom: 14px;" x-show="tab === 'all' || tab === 'owners'">
                            <h2 class="vys-group-title">Owners <span class="vys-group-n">{{ $counts['owners'] }}</span></h2>
                            @foreach ($results['owners'] as $o)
                                <a class="vys-row" href="{{ $o['url'] }}">
                                    <span class="vys-av">{{ $o['initials'] }}</span>
                                    <span class="vys-row-main">
                                        <span class="vys-row-title">{{ $o['name'] }}</span>
                                    </span>
                                    <span class="vys-row-meta"><span class="vys-badge vys-badge--soft">Owner detail</span><span class="vys-chev" aria-hidden="true">›</span></span>
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
        /* ── The design's single query bar ─────────────────────────────────────────────────────── */
        .vys-bar { display: flex; align-items: center; gap: 10px; background: rgba(255,252,248,.72); border: 1px solid var(--vy-glass-border); border-radius: 999px; padding: 7px 8px 7px 18px; box-shadow: var(--vy-shadow-sm); }
        .vys-bar-ico { color: var(--vy-accent-text); display: flex; flex: none; }
        .vys-bar-input { flex: 1; min-width: 0; border: none; background: transparent; font-family: inherit; font-size: 14.5px; color: var(--vy-ink); padding: 8px 0; outline: none; }
        .vys-bar-input::placeholder { color: var(--vy-faint); }
        .vys-bar-x { display: grid; place-items: center; width: 26px; height: 26px; border-radius: 50%; color: var(--vy-muted); background: rgba(120,70,40,.07); text-decoration: none; flex: none; }
        .vys-bar-x:hover { color: var(--vy-ink); }
        .vys-bar-go { padding: 9px 20px; flex: none; }

        /* ── Filter rows (chips are LINKS — the GET boundary, #20) ─────────────────────────────── */
        .vys-frow { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-top: 10px; }
        .vys-flabel { display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--vy-faint); margin-right: 3px; }
        .vys-chip { border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.6); border-radius: 999px; padding: 5px 13px; font-size: 12.5px; color: var(--vy-muted); text-decoration: none; }
        .vys-chip:hover { color: var(--vy-accent-text); border-color: var(--vy-accent-border); }
        .vys-chip.is-on { background: var(--vy-grad); color: #fff; border-color: transparent; font-weight: 600; }
        .vys-date { border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,.6); border-radius: 999px; padding: 5px 12px; font-family: inherit; font-size: 12.5px; color: var(--vy-ink); }
        .vys-dash { color: var(--vy-faint); font-size: 12px; }
        .vys-hint { font-size: 11.5px; color: var(--vy-muted); margin: 10px 0 16px; }

        /* ── Results header ────────────────────────────────────────────────────────────────────── */
        .vys-head { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; }
        .vys-count { font-family: var(--vy-display, inherit); font-size: 15px; font-weight: 600; color: var(--vy-ink); }
        .vys-headright { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .vys-seg { display: inline-flex; background: rgba(255,255,255,.6); border: 1px solid var(--vy-glass-border); border-radius: 999px; padding: 2px; }
        .vys-seg-i { padding: 5px 12px; border-radius: 999px; font-size: 12px; color: var(--vy-muted); text-decoration: none; }
        .vys-seg-i.is-on { background: #fff; color: var(--vy-ink); font-weight: 600; box-shadow: var(--vy-shadow-sm); }
        .vys-textbtn { border: none; background: transparent; font-family: inherit; font-size: 12px; font-weight: 600; color: var(--vy-accent-text); cursor: pointer; text-decoration: none; padding: 5px 6px; }

        /* ── Groups + rows ─────────────────────────────────────────────────────────────────────── */
        .vys-empty { text-align: center; padding: 32px 24px; color: var(--vy-muted); font-size: 13px; }
        .vys-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .vys-tab { border: 1px solid var(--vy-glass-border); background: rgba(255,255,255,0.7); border-radius: 999px; padding: 6px 13px; font-size: 12.5px; color: var(--vy-muted); cursor: pointer; font-family: inherit; }
        .vys-tab.is-on { background: var(--vy-grad); color: #fff; border-color: transparent; }
        .vys-tab-n { font-weight: 700; margin-left: 3px; opacity: 0.85; }
        .vys-cardhead { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .vys-cardtitle { font-size: 12.5px; font-weight: 600; color: var(--vy-ink); }
        .vys-group-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--vy-muted); margin: 0 0 8px; }
        .vys-group-n { color: var(--vy-faint); margin-left: 4px; }
        .vys-row { display: flex; align-items: center; gap: 12px; padding: 11px 12px; border-radius: var(--vy-r-md, 12px); text-decoration: none; }
        .vys-row:hover { background: rgba(232,112,58,0.06); }
        .vys-row + .vys-row { border-top: 1px solid var(--vy-hairline); }
        .vys-tile { width: 34px; height: 34px; border-radius: 10px; background: rgba(232,112,58,.12); color: var(--vy-accent-text); display: grid; place-items: center; flex: none; }
        .vys-row-main { display: flex; flex-direction: column; gap: 2px; min-width: 0; flex: 1; }
        .vys-row-title { font-size: 13.5px; font-weight: 600; color: var(--vy-ink); }
        .vys-row-sub { color: var(--vy-muted); font-size: 12px; }
        .vys-row-meta { display: flex; gap: 7px; align-items: center; flex: none; }
        .vys-chev { color: var(--vy-faint); font-size: 15px; }
        .vys-av { width: 34px; height: 34px; border-radius: 50%; background: var(--vy-grad); color: #fff; font-size: 11.5px; font-weight: 600; display: grid; place-items: center; flex: none; }
        /* ⚠️ --vy-accent-text, not --vy-accent: the badge measured 4.41 on the card wash (10.5px → 4.5 bar). */
        .vys-badge { font-size: 10.5px; font-weight: 600; color: var(--vy-accent-text); background: rgba(232,112,58,0.10); border-radius: 6px; padding: 3px 8px; white-space: nowrap; }
        .vys-badge--soft { color: var(--vy-muted); background: rgba(120,70,40,0.06); }
        .vys-recent { display: flex; align-items: center; gap: 10px; padding: 9px 4px; text-decoration: none; border-top: 1px solid var(--vy-hairline); }
        .vys-recent:first-of-type { border-top: none; }
        .vys-recent-ico { width: 28px; height: 28px; border-radius: 8px; background: rgba(120,70,40,.06); color: var(--vy-muted); display: grid; place-items: center; flex: none; }
        .vys-recent-t { flex: 1; font-size: 13px; color: var(--vy-ink); }

        @media (max-width: 900px) {
            .vys-bar { flex-wrap: wrap; border-radius: var(--vy-r-md, 16px); padding: 10px 12px; }
            .vys-bar-input { flex: 1 1 60%; }
        }
    </style>
</div>
