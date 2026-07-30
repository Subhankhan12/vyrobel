{{-- Operator Channels (exact-match) — the OTA channel-manager surface, matched to "Operator Channels.html".
     Tenant-scoped; Pro-gated. ⚠️ HONEST-GATED (founder decision): the whole page is OTA sync, which is
     ACCOUNT-GATED (no real Channex integration). The DESIGN is matched faithfully, but:
       • the Direct channel is REAL (Vyrobel's storefront);
       • an OTA channel's STATUS is real, but live two-way sync/import is honestly gated — NEVER a fabricated
         "Synced / N imported / last sync 3 min ago";
       • the booking mix is REAL by `reservations.source` (Direct real; OTA the real count).
     Nothing here fabricates a sync/import/OTA-booking (#39). --}}
<div>
    <x-operator.shell active="channels" title="Channels" :viewer="$viewer">

        {{-- Header --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <span class="vy-range" style="font-weight:600">Channels</span>
            </div>
            <div class="vy-right">
                <button type="button" class="vy-btn vy-btn--accent" onclick="document.getElementById('vy-connect-panel').scrollIntoView({behavior:'smooth'})">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    Connect channel
                </button>
            </div>
        </div>

        {{-- ⚠️ THE HONEST CORE — real channel sync is account-gated. This replaces the wireframe's fabricated
             "Expedia disconnected · 3 bookings waiting to import" alert with the honest reality. --}}
        <section class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber);margin-bottom:16px">
            <div class="vy-eyebrow" style="color:var(--vy-amber)">Channel sync — setup required</div>
            <p style="font-size:13.5px;color:var(--vy-ink);margin:6px 0 0;line-height:1.55">
                Live two-way sync with the OTAs runs through <strong>Channex</strong>, our channel-manager partner.
                Once your Channex account is connected, Vyrobel maps your listings, pushes your rates &amp;
                availability, and imports OTA bookings automatically — two-way sync every few minutes. Until then,
                your <strong>Direct</strong> channel (your own storefront) is live and taking bookings.
            </p>
        </section>

        {{-- Stats row (REAL) --}}
        <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade" style="margin-bottom:18px">
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">OTA bookings · {{ $monthLabel }}</div>
                <div class="vy-dk-val">{{ $stats['ota_bookings'] }}</div>
                <div class="vy-dk-sub">of {{ $stats['total_bookings'] }} total</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Direct share</div>
                <div class="vy-dk-val">{{ $stats['direct_share'] }}<span class="vy-dk-unit">%</span></div>
                <div class="vy-dk-sub">{{ $direct['bookings'] }} direct this month</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">Sync status</div>
                <div class="vy-dk-val">{{ $stats['connected'] }}<span class="vy-dk-unit">/{{ $stats['channel_count'] }}</span></div>
                <div class="vy-dk-sub">{{ $stats['disconnected'] > 0 ? $stats['disconnected'].' disconnected' : 'all connected' }}</div>
            </div>
        </div>

        <div class="vy-dash-cols">
            <div class="vy-stack">
                {{-- Connected channels (REAL) — the Direct channel + real OTA channel rows --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom:12px">Connected channels</h2>

                    {{-- Direct — always REAL + live (Vyrobel's storefront; 0% commission) --}}
                    <div class="vy-chan-item">
                        <span class="vy-chan-badge" style="background:var(--vy-grad)">D</span>
                        <div style="flex:1;min-width:0">
                            <div class="vy-chan-name">Direct <span class="vy-chan-status is-synced" style="margin-left:6px"><span class="vy-dot"></span>Live</span></div>
                            <div class="vy-chan-sub">Your own storefront &amp; booking engine · no commission</div>
                        </div>
                        <div style="text-align:right">
                            <div class="vy-chan-count">{{ $direct['bookings'] }}</div>
                            <div class="vy-chan-count-sub">bookings · {{ $monthLabel }}</div>
                        </div>
                    </div>

                    @forelse ($channels as $c)
                        <div class="vy-chan-item">
                            <span class="vy-chan-badge">{{ $c['initial'] }}</span>
                            <div style="flex:1;min-width:0">
                                <div class="vy-chan-name">
                                    {{ $c['name'] }}
                                    @if ($c['status'] === 'connected')
                                        <span class="vy-chan-status is-pending" style="margin-left:6px"><span class="vy-dot"></span>Connected</span>
                                    @elseif ($c['status'] === 'error')
                                        <span class="vy-chan-status is-error" style="margin-left:6px"><span class="vy-dot"></span>Action needed</span>
                                    @else
                                        <span class="vy-chan-status is-off" style="margin-left:6px"><span class="vy-dot"></span>{{ $c['status_label'] }}</span>
                                    @endif
                                </div>
                                {{-- HONEST: the connection STATE is real; live sync is gated (never "last sync 3 min ago") --}}
                                <div class="vy-chan-sub">
                                    @if ($c['last_synced'])
                                        Last sync {{ $c['last_synced'] }}
                                    @elseif ($c['status'] === 'connected')
                                        Mapped &amp; ready — live sync activates with your Channex account
                                    @elseif ($c['status'] === 'error')
                                        {{ $c['last_error'] ?? 'Connection needs attention' }} · reconnect via your Channex account
                                    @else
                                        Not connected — connect via your Channex account to sync
                                    @endif
                                </div>
                            </div>
                            <div style="text-align:right">
                                <div class="vy-chan-count">{{ $c['bookings'] }}</div>
                                <div class="vy-chan-count-sub">bookings{{ $c['commission'] ? ' · '.$c['commission'].' comm.' : '' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty" style="padding:18px 12px">No OTA channels connected yet — connect one below to reach the marketplaces.</div>
                    @endforelse
                </div>

                {{-- Connect a channel (HONEST-GATED) --}}
                <div class="vy-card" id="vy-connect-panel">
                    <h2 class="vy-card-title" style="margin-bottom:8px">Connect a channel</h2>
                    <p class="vy-line-sub" style="margin-bottom:12px">Pick a marketplace to add. Vyrobel maps your listings, pushes rates &amp; availability, and imports bookings — two-way sync every few minutes, all through Channex.</p>
                    <div class="vy-connect-grid">
                        @foreach ([['B', 'Booking.com', '15% comm.'], ['A', 'Airbnb', '3% comm.'], ['E', 'Expedia', '15% comm.'], ['V', 'Vrbo', '8% comm.']] as [$ini, $name, $comm])
                            <div class="vy-connect-opt">
                                <span class="vy-chan-badge">{{ $ini }}</span>
                                <div style="flex:1"><div class="vy-chan-name">{{ $name }}</div><div class="vy-chan-sub">{{ $comm }}</div></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="vy-deferred-note" style="margin-top:14px">
                        <span class="vy-soon-tag">Setup required</span>
                        Connecting a marketplace needs your Channex partner account + API credentials. Add them in
                        Channex, then each channel maps and syncs here — Vyrobel never fabricates a sync it can’t run.
                    </div>
                </div>
            </div>

            {{-- Right rail --}}
            <div class="vy-stack">
                {{-- Booking mix (REAL by source) --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom:10px">Booking mix · {{ $monthLabel }}</h2>
                    @forelse ($bookingMix as $m)
                        <div class="vy-mini-row">
                            <span class="vy-mini-k">{{ $m['label'] }}</span>
                            <span class="vy-mini-v">{{ $m['count'] }} · {{ $m['share'] }}%</span>
                        </div>
                    @empty
                        <div class="vy-empty" style="padding:16px 12px">No bookings yet this month.</div>
                    @endforelse
                    <div class="vy-line-sub" style="margin-top:10px;font-size:11.5px">Every direct booking keeps the OTA commission in house. OTA figures populate once channel sync is live.</div>
                </div>

                {{-- Sync activity (HONEST — real state, no fabricated feed) --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom:8px">Sync activity</h2>
                    @if ($stats['channel_count'] > 0)
                        <div class="vy-mini-row"><span class="vy-mini-k">Channels mapped</span><span class="vy-mini-v">{{ $stats['channel_count'] }}</span></div>
                        <div class="vy-line-sub" style="margin-top:8px;font-size:12px">Live sync events (rate pushes, booking imports) appear here once your Channex connection is active.</div>
                    @else
                        <div class="vy-empty" style="padding:16px 12px">No sync activity yet — connect a channel to begin.</div>
                    @endif
                </div>
            </div>
        </div>

    </x-operator.shell>
</div>
