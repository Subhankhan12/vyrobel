{{-- Operator GLOBAL SEARCH (Phase 1 · Sub-gate 2) — find any reservation / guest / unit / owner. READ view;
     the query rides a GET (?q=) so the tenant-scoped reads never leak (invariant #20). Work is SearchService.
     Guests are searched by NAME only (email/phone are encrypted — blind index deferred); the limitation is
     surfaced, never a silent empty result. Every row is a real record — nothing fabricated.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. Alpine uses x-on:/x-model/x-bind (no `@click`). --}}
<div>
    <x-operator.shell active="search" title="Search" :subtitle="'Everything, in one place'" :viewer="$viewer">

        {{-- ── The query (GET → drives the scoped search) ──────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('app.search') }}" class="vy-card vy-fade" style="margin-bottom: 16px;">
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="q" class="vy-input" value="{{ $query }}" autofocus autocomplete="off"
                    placeholder="Reference, guest name, unit code, owner…" style="flex: 1;">
                <button type="submit" class="vy-btn vy-btn--accent" style="padding: 11px 20px;">Search</button>
            </div>
            <div style="font-size: 11.5px; color: var(--vy-faint); margin-top: 9px;">
                Guests are found by <strong>name</strong>. Email &amp; phone search is coming soon — contact details are encrypted, so they can’t be searched by value yet.
                Source / status / date filters are coming soon too.
            </div>
        </form>

        {{-- ⚠️ Encrypted-contact honesty callout — the operator typed something that looks like an email/phone. --}}
        @if ($contactNote)
            <div class="vy-card vy-fade" style="border-left: 3px solid var(--vy-amber); margin-bottom: 16px;">
                <div style="font-size: 13px; color: var(--vy-ink);">
                    That looks like an <strong>email or phone number</strong>. Contact details are stored encrypted, so they can’t be searched by value yet — search the guest by <strong>name</strong> instead. (A searchable blind index is planned.)
                </div>
            </div>
        @endif

        @unless ($searched)
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
                                ['all', 'All', $total],
                                ['reservations', 'Reservations', count($results['reservations'])],
                                ['guests', 'Guests', count($results['guests'])],
                                ['units', 'Units', count($results['units'])],
                                ['owners', 'Owners', count($results['owners'])],
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
