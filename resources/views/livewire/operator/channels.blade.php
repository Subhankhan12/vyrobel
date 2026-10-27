{{-- Operator Channels (exact-match) — the OTA channel-manager surface, matched to "Operator Channels.html".
     Tenant-scoped; Pro-gated. ⚠️ HONEST-GATED (founder decision): the whole page is OTA sync, which is
     ACCOUNT-GATED (no real Channex integration). The DESIGN is matched faithfully, but:
       • the Direct channel is REAL (Vyrobel's storefront);
       • an OTA channel's STATUS is real, but live two-way sync/import is honestly gated — NEVER a fabricated
         "Synced / N imported / last sync 3 min ago";
       • the booking mix is REAL by `reservations.source` (Direct real; OTA the real count).
     Nothing here fabricates a sync/import/OTA-booking (#39). --}}
<div>
    <x-operator.shell active="channels" :title="__('livewire/operator/channels.page_title')" :viewer="$viewer">

        {{-- Header --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <span class="vy-range" style="font-weight:600">{{ __('livewire/operator/channels.heading') }}</span>
            </div>
            <div class="vy-right">
                <button type="button" class="vy-btn vy-btn--accent" onclick="document.getElementById('vy-connect-panel').scrollIntoView({behavior:'smooth'})">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    {{ __('livewire/operator/channels.connect_channel_button') }}
                </button>
            </div>
        </div>

        {{-- ⚠️ THE HONEST CORE — real channel sync is account-gated. This replaces the wireframe's fabricated
             "Expedia disconnected · 3 bookings waiting to import" alert with the honest reality. --}}
        <section class="vy-card vy-fade" style="border-left:3px solid var(--vy-amber);margin-bottom:16px">
            <div class="vy-eyebrow" style="color:var(--vy-amber)">{{ __('livewire/operator/channels.sync_setup_eyebrow') }}</div>
            <p style="font-size:13.5px;color:var(--vy-ink);margin:6px 0 0;line-height:1.55">
                {!! __('livewire/operator/channels.sync_setup_body') !!}
            </p>
        </section>

        {{-- Stats row (REAL) --}}
        <div class="vy-dash-kpis vy-dash-kpis--3 vy-fade" style="margin-bottom:18px">
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">{{ __('livewire/operator/channels.ota_bookings_month', ['month' => $monthLabel]) }}</div>
                <div class="vy-dk-val">{{ $stats['ota_bookings'] }}</div>
                <div class="vy-dk-sub">{{ __('livewire/operator/channels.of_total', ['total' => $stats['total_bookings']]) }}</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">{{ __('livewire/operator/channels.direct_share') }}</div>
                <div class="vy-dk-val">{{ $stats['direct_share'] }}<span class="vy-dk-unit">%</span></div>
                <div class="vy-dk-sub">{{ __('livewire/operator/channels.direct_this_month', ['bookings' => $direct['bookings']]) }}</div>
            </div>
            <div class="vy-dash-kpi">
                <div class="vy-dk-label">{{ __('livewire/operator/channels.sync_status') }}</div>
                <div class="vy-dk-val">{{ $stats['connected'] }}<span class="vy-dk-unit">/{{ $stats['channel_count'] }}</span></div>
                <div class="vy-dk-sub">{{ $stats['disconnected'] > 0 ? __('livewire/operator/channels.n_disconnected', ['channels' => $stats['disconnected']]) : __('livewire/operator/channels.all_connected') }}</div>
            </div>
        </div>

        <div class="vy-dash-cols">
            <div class="vy-stack">
                {{-- Connected channels (REAL) — the Direct channel + real OTA channel rows --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom:12px">{{ __('livewire/operator/channels.connected_channels') }}</h2>

                    {{-- Direct — always REAL + live (Vyrobel's storefront; 0% commission) --}}
                    <div class="vy-chan-item">
                        <span class="vy-chan-badge" style="background:var(--vy-grad)">D</span>
                        <div style="flex:1;min-width:0">
                            <div class="vy-chan-name">{{ __('livewire/operator/channels.direct_name') }} <span class="vy-chan-status is-synced" style="margin-left:6px"><span class="vy-dot"></span>{{ __('livewire/operator/channels.live') }}</span></div>
                            <div class="vy-chan-sub">{{ __('livewire/operator/channels.direct_sub') }}</div>
                        </div>
                        <div style="text-align:right">
                            <div class="vy-chan-count">{{ $direct['bookings'] }}</div>
                            <div class="vy-chan-count-sub">{{ __('livewire/operator/channels.bookings_month', ['month' => $monthLabel]) }}</div>
                        </div>
                    </div>

                    @forelse ($channels as $c)
                        <div class="vy-chan-item">
                            <span class="vy-chan-badge">{{ $c['initial'] }}</span>
                            <div style="flex:1;min-width:0">
                                <div class="vy-chan-name">
                                    {{ $c['name'] }}
                                    @if ($c['status'] === 'connected')
                                        <span class="vy-chan-status is-pending" style="margin-left:6px"><span class="vy-dot"></span>{{ __('livewire/operator/channels.status_connected') }}</span>
                                    @elseif ($c['status'] === 'error')
                                        <span class="vy-chan-status is-error" style="margin-left:6px"><span class="vy-dot"></span>{{ __('livewire/operator/channels.status_action_needed') }}</span>
                                    @else
                                        <span class="vy-chan-status is-off" style="margin-left:6px"><span class="vy-dot"></span>{{ $c['status_label'] }}</span>
                                    @endif
                                </div>
                                {{-- HONEST: the connection STATE is real; live sync is gated (never "last sync 3 min ago") --}}
                                <div class="vy-chan-sub">
                                    @if ($c['last_synced'])
                                        {{ __('livewire/operator/channels.last_sync', ['time' => $c['last_synced']]) }}
                                    @elseif ($c['status'] === 'connected')
                                        {{ __('livewire/operator/channels.mapped_ready') }}
                                    @elseif ($c['status'] === 'error')
                                        {{ __('livewire/operator/channels.error_reconnect', ['error' => $c['last_error'] ?? __('livewire/operator/channels.connection_needs_attention')]) }}
                                    @else
                                        {{ __('livewire/operator/channels.not_connected') }}
                                    @endif
                                </div>
                            </div>
                            <div style="text-align:right">
                                <div class="vy-chan-count">{{ $c['bookings'] }}</div>
                                <div class="vy-chan-count-sub">{{ $c['commission'] ? __('livewire/operator/channels.bookings_commission', ['pct' => $c['commission']]) : __('livewire/operator/channels.bookings') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty" style="padding:18px 12px">{{ __('livewire/operator/channels.no_ota_channels') }}</div>
                    @endforelse
                </div>

                {{-- Connect a channel (HONEST-GATED) --}}
                <div class="vy-card" id="vy-connect-panel">
                    <h2 class="vy-card-title" style="margin-bottom:8px">{{ __('livewire/operator/channels.connect_a_channel') }}</h2>
                    <p class="vy-line-sub" style="margin-bottom:12px">{{ __('livewire/operator/channels.connect_intro') }}</p>
                    <div class="vy-connect-grid">
                        @foreach ([['B', 'Booking.com', '15%'], ['A', 'Airbnb', '3%'], ['E', 'Expedia', '15%'], ['V', 'Vrbo', '8%']] as [$ini, $name, $pct])
                            <div class="vy-connect-opt">
                                <span class="vy-chan-badge">{{ $ini }}</span>
                                <div style="flex:1"><div class="vy-chan-name">{{ $name }}</div><div class="vy-chan-sub">{{ __('livewire/operator/channels.commission_short', ['pct' => $pct]) }}</div></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="vy-deferred-note" style="margin-top:14px">
                        <span class="vy-soon-tag">{{ __('livewire/operator/channels.setup_required') }}</span>
                        {{ __('livewire/operator/channels.setup_required_body') }}
                    </div>
                </div>
            </div>

            {{-- Right rail --}}
            <div class="vy-stack">
                {{-- Booking mix (REAL by source) --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom:10px">{{ __('livewire/operator/channels.booking_mix_month', ['month' => $monthLabel]) }}</h2>
                    @forelse ($bookingMix as $m)
                        <div class="vy-mini-row">
                            <span class="vy-mini-k">{{ $m['label'] }}</span>
                            <span class="vy-mini-v">{{ $m['count'] }} · {{ $m['share'] }}%</span>
                        </div>
                    @empty
                        <div class="vy-empty" style="padding:16px 12px">{{ __('livewire/operator/channels.no_bookings_month') }}</div>
                    @endforelse
                    <div class="vy-line-sub" style="margin-top:10px;font-size:11.5px">{{ __('livewire/operator/channels.booking_mix_note') }}</div>
                </div>

                {{-- Sync activity (HONEST — real state, no fabricated feed) --}}
                <div class="vy-card">
                    <h2 class="vy-card-title" style="margin-bottom:8px">{{ __('livewire/operator/channels.sync_activity') }}</h2>
                    @if ($stats['channel_count'] > 0)
                        {{-- ⚠️ "configured", not "mapped". The figure is `$channels->count()` — how many channel
                             ROWS exist — and nothing in the product maps rooms or rate plans to an OTA yet
                             (that arrives with Channex). "Mapped" would claim a mapping step that has never
                             run, on the one surface where that claim matters most (#37). --}}
                        <div class="vy-mini-row"><span class="vy-mini-k">{{ __('livewire/operator/channels.channels_configured') }}</span><span class="vy-mini-v">{{ $stats['channel_count'] }}</span></div>
                        <div class="vy-line-sub" style="margin-top:8px;font-size:12px">{{ __('livewire/operator/channels.sync_activity_note') }}</div>
                    @else
                        <div class="vy-empty" style="padding:16px 12px">{{ __('livewire/operator/channels.no_sync_activity') }}</div>
                    @endif
                </div>
            </div>
        </div>

    </x-operator.shell>
</div>
