{{-- Operator Front Desk — arrivals / departures / in-house (GATE 2 exact-match rebuild).
     Styled on the shared operator design system via <x-operator.shell>. Lists + balances are REAL and
     tenant-scoped (FrontDeskService + folio balance). The board only READS; check-in/out post to the real
     staff action (FrontDeskActionController) and persist. The segmented Arrivals/Departures/In-house tabs are
     Alpine tab-state (presentation); the KPI band + counts are DERIVED from the real rows only. The wireframe's
     invented "Filters"/"Print" affordances are NOT rendered (they were never in the design). Room moves, on-desk
     Collect, the check-in/out modals and Walk-in are wired to their real routes in the following GATE-2 sub-gates.
     RAW-BLADE RULE: Alpine uses x-on:/x-model/x-bind (no `@click`). --}}
<div class="vy-fd">
    <x-operator.shell active="front-desk" :title="__('livewire/front-desk.page_title')"
        :subtitle="($property['name'] ?? __('livewire/front-desk.no_property_yet')) . ' · ' . $day['label']" :viewer="$viewer">

        {{-- Flash notice from a real check-in/out (PRG) --}}
        {{-- ⚠️ ONE component decides refusal-vs-success (audit D4). This view used to hardcode a tick, so
             a refused refund or a lost inventory race rendered as a receipt. --}}
        @if (session('desk_notice'))
            <x-operator.flash-notice :message="session('desk_notice')" :refused="(bool) session('desk_refused', false)" />
        @endif

        {{-- Toolbar: REAL day navigation (GET links). Walk-in is wired to its real create-and-check-in flow in
             a later GATE-2 sub-gate; until then it is an honest disabled affordance (never a faked booking). --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <a class="vy-icobtn" href="?date={{ $day['prev'] }}" aria-label="{{ __('livewire/front-desk.previous_day') }}" title="{{ __('livewire/front-desk.previous_day') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
                <span class="vy-range">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                    {{ $day['label'] }}
                </span>
                <a class="vy-icobtn" href="?date={{ $day['next'] }}" aria-label="{{ __('livewire/front-desk.next_day') }}" title="{{ __('livewire/front-desk.next_day') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </a>
                @unless ($day['is_today'])
                    <a class="vy-pill" href="?date={{ $day['today'] }}">{{ __('common.today') }}</a>
                @endunless
                {{-- The design carries a relative-day pill beside the date. Derived from the two real dates
                     already in scope — no new data, and it says nothing the date doesn't. --}}
                @php
                    $vyRel = (int) \Illuminate\Support\Carbon::parse($day['today'])->diffInDays(\Illuminate\Support\Carbon::parse($day['date']), false);
                    $vyRelLabel = match (true) {
                        $vyRel === 0 => __('common.today'),
                        $vyRel === 1 => __('livewire/front-desk.rel_tomorrow'),
                        $vyRel === -1 => __('livewire/front-desk.rel_yesterday'),
                        $vyRel > 1 => trans_choice('livewire/front-desk.rel_in_days', $vyRel, ['count' => $vyRel]),
                        default => trans_choice('livewire/front-desk.rel_days_ago', abs($vyRel), ['count' => abs($vyRel)]),
                    };
                @endphp
                <span class="vy-fd-rel">{{ $vyRelLabel }}</span>
            </div>
            <div class="vy-right">
                @if ($property !== null && auth()->user()?->can('reservations.create'))
                    @if ($walkin['units'] !== [])
                        {{-- REAL walk-in: creates a source=walk_in booking + checks it in, via the certified path. --}}
                        <button type="button" x-data x-on:click="$dispatch('vy-walkin-open')" class="vy-pill vy-pill--accent">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                            {{ __('livewire/front-desk.walk_in') }}
                        </button>
                    @else
                        <span class="vy-pill is-soon" aria-disabled="true" title="{{ __('livewire/front-desk.no_unit_free_tonight') }}">{{ __('livewire/front-desk.walk_in_none_free') }}</span>
                    @endif
                @endif
            </div>
        </div>

        @if ($property === null)
            <div class="vy-card vy-fade"><div class="vy-empty">{{ __('livewire/front-desk.no_property_empty') }}</div></div>
        @else
            <div x-data="{
                    tab: 'arrivals',
                    modal: null, row: {}, keycards: 2, collectAmt: '', payKey: '', checks: { id: false, reg: false }, moveTarget: '',
                    walkinUnits: @js($walkin['units']), walkinToday: @js($walkin['today']),
                    wi: { first: '', last: '', nights: 1, unit: '', roomType: '', collect: false, amt: '', method: 'cash' },
                    newKey() { return (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : ('k' + Date.now() + Math.floor(Math.random() * 1e6)); },
                    defaultAmt(r) { return r.balance_minor ? (r.balance_minor / 100).toFixed(2) : ''; },
                    openCheckin(r) { this.row = r; this.keycards = 2; this.checks = { id: false, reg: false }; this.collectAmt = this.defaultAmt(r); this.payKey = this.newKey(); this.modal = 'checkin'; },
                    openCheckout(r) { this.row = r; this.collectAmt = this.defaultAmt(r); this.payKey = this.newKey(); this.modal = 'checkout'; },
                    openCollect(r) { this.row = r; this.collectAmt = this.defaultAmt(r); this.payKey = this.newKey(); this.modal = 'collect'; },
                    openMove(r) { this.row = r; this.moveTarget = ''; this.modal = 'move'; },
                    openWalkIn() { this.wi = { first: '', last: '', nights: 1, unit: '', roomType: '', collect: false, amt: '', method: 'cash' }; this.modal = 'walkin'; },
                    walkCheckout() { const d = new Date(this.walkinToday + 'T00:00:00'); d.setDate(d.getDate() + Math.max(1, parseInt(this.wi.nights) || 1)); const y = d.getFullYear(), m = String(d.getMonth() + 1).padStart(2, '0'), day = String(d.getDate()).padStart(2, '0'); return y + '-' + m + '-' + day; },
                    close() { this.modal = null; }
                }" x-on:keydown.escape.window="close()" x-on:vy-walkin-open.window="openWalkIn()">
                {{-- Segmented control — Arrivals / Departures / In-house (sliding-pill tab state) --}}
                <div class="vy-fd-seg vy-fade" role="tablist">
                    <button type="button" class="vy-fd-seg-btn" role="tab" x-bind:class="tab === 'arrivals' ? 'is-on' : ''" x-on:click="tab = 'arrivals'">
                        {{ __('livewire/front-desk.tab_arrivals') }} <span class="vy-fd-seg-n">{{ $summary['arrivals'] }}</span>
                    </button>
                    <button type="button" class="vy-fd-seg-btn" role="tab" x-bind:class="tab === 'departures' ? 'is-on' : ''" x-on:click="tab = 'departures'">
                        {{ __('livewire/front-desk.tab_departures') }} <span class="vy-fd-seg-n">{{ $summary['departures'] }}</span>
                    </button>
                    <button type="button" class="vy-fd-seg-btn" role="tab" x-bind:class="tab === 'inhouse' ? 'is-on' : ''" x-on:click="tab = 'inhouse'">
                        {{ __('livewire/front-desk.tab_in_house') }} <span class="vy-fd-seg-n">{{ $summary['in_house'] }}</span>
                    </button>
                </div>

                {{-- DERIVED KPI band (real rows only): Arriving / Departing / In-house / Unassigned. The first
                     three double as tab affordances; Unassigned is a stat tile. --}}
                <div class="vy-summary vy-fade">
                    <button type="button" class="vy-summary-chip vy-fd-kpi" x-bind:class="tab === 'arrivals' ? 'is-active' : ''" x-on:click="tab = 'arrivals'">
                        <span class="vy-sc-num">{{ $summary['arrivals'] }}</span><span class="vy-sc-label">{{ __('livewire/front-desk.kpi_arriving') }}</span>
                    </button>
                    <button type="button" class="vy-summary-chip vy-fd-kpi" x-bind:class="tab === 'departures' ? 'is-active' : ''" x-on:click="tab = 'departures'">
                        <span class="vy-sc-num">{{ $summary['departures'] }}</span><span class="vy-sc-label">{{ __('livewire/front-desk.kpi_departing') }}</span>
                    </button>
                    <button type="button" class="vy-summary-chip vy-fd-kpi" x-bind:class="tab === 'inhouse' ? 'is-active' : ''" x-on:click="tab = 'inhouse'">
                        <span class="vy-sc-num">{{ $summary['in_house'] }}</span><span class="vy-sc-label">{{ __('livewire/front-desk.kpi_in_house') }}</span>
                    </button>
                    <div class="vy-summary-chip @if ($summary['unassigned'] > 0) is-due @endif">
                        <span class="vy-sc-num">{{ $summary['unassigned'] }}</span><span class="vy-sc-label">{{ __('livewire/front-desk.kpi_unassigned') }}</span>
                    </div>
                </div>

                {{-- TODAY'S TIMELINE — real SCHEDULED times: arrivals above the axis, departures below; the
                     now-line is the property's real local time (only on today, live-ticked). Dots are NAME-only
                     (#28). No fabricated "live arrival" — an unparseable window falls back to the scheduled default. --}}
                @if ($timeline['has_points'])
                    <div class="vy-card vy-fade vy-tl-card"
                        x-data="{
                            nowMin: @js($timeline['now_min']),
                            start: {{ $timeline['start_min'] }}, span: {{ $timeline['span'] }},
                            get nowPos() { return this.nowMin === null ? 0 : Math.max(0, Math.min(100, ((this.nowMin - this.start) / this.span) * 100)); },
                            get nowLabel() { return this.nowMin === null ? '' : String(Math.floor(this.nowMin / 60)).padStart(2, '0') + ':' + String(this.nowMin % 60).padStart(2, '0'); },
                            init() { if (this.nowMin !== null) { setInterval(() => { this.nowMin = (this.nowMin + 1) % 1440; }, 60000); } }
                        }">
                        <div class="vy-tl-head">
                            <span class="vy-tl-title">{{ __('livewire/front-desk.timeline_title') }}</span>
                            <span class="vy-tl-legend"><span class="vy-tl-key is-arr"></span>{{ __('livewire/front-desk.legend_arrivals') }}<span class="vy-tl-key is-dep"></span>{{ __('livewire/front-desk.legend_departures') }}</span>
                        </div>
                        <div class="vy-tl-rail">
                            @foreach ($timeline['marks'] as $mark)
                                <div class="vy-tl-mark" style="left: {{ $mark['pos'] }}%"><span>{{ $mark['label'] }}</span></div>
                            @endforeach
                            <div class="vy-tl-axis"></div>
                            <template x-if="nowMin !== null">
                                <div class="vy-tl-now" x-bind:style="'left: ' + nowPos + '%'">
                                    <span class="vy-tl-nowlabel" x-text="@js(__('livewire/front-desk.now_at', ['time' => ':time'])).replace(':time', nowLabel)"></span>
                                </div>
                            </template>
                            @foreach ($timeline['points'] as $pt)
                                <div class="vy-tl-dot {{ $pt['type'] === 'arrival' ? 'is-arr' : 'is-dep' }} @if ($pt['is_vip']) is-vip @endif"
                                    style="left: {{ $pt['pos'] }}%"
                                    title="{{ $pt['type'] === 'arrival'
                                        ? ($pt['in_window'] ? __('livewire/front-desk.dot_arrives', ['guest' => $pt['guest'], 'time' => $pt['time']]) : __('livewire/front-desk.dot_arrives_outside', ['guest' => $pt['guest'], 'time' => $pt['time']]))
                                        : ($pt['in_window'] ? __('livewire/front-desk.dot_departs', ['guest' => $pt['guest'], 'time' => $pt['time']]) : __('livewire/front-desk.dot_departs_outside', ['guest' => $pt['guest'], 'time' => $pt['time']])) }}">
                                    <span class="vy-tl-dotlabel">{{ $pt['time'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="vy-tl-foot">{{ __('livewire/front-desk.timeline_foot') }}</div>
                    </div>
                @endif

                {{-- The active tab's list. One at a time (x-show), each over the REAL rows. --}}
                @foreach (['arrivals' => [__('livewire/front-desk.tab_arrivals'), $arrivals, __('livewire/front-desk.empty_title_arrivals'), __('livewire/front-desk.empty_body_arrivals', ['date' => $day['label']])], 'departures' => [__('livewire/front-desk.tab_departures'), $departures, __('livewire/front-desk.empty_title_departures'), __('livewire/front-desk.empty_body_departures', ['date' => $day['label']])], 'inhouse' => [__('livewire/front-desk.tab_in_house'), $inHouse, __('livewire/front-desk.empty_title_in_house'), __('livewire/front-desk.empty_body_in_house', ['date' => $day['label']])]] as $key => [$heading, $rows, $emptyTitle, $emptyBody])
                    <div class="vy-card vy-card--flush vy-fade" role="tabpanel" x-show="tab === '{{ $key }}'" x-cloak>
                        <div class="vy-fd-head">
                            <span class="vy-fd-title">{{ $heading }}</span>
                            <span class="vy-fd-count">{{ count($rows) }}</span>
                        </div>

                        @forelse ($rows as $row)
                            @php
                                // Compact row payload for the shared modals (NAME-only #28; no contact PII).
                                $rowModal = [
                                    'id' => $row['id'], 'guest' => $row['guest'], 'unit' => $row['unit'],
                                    'nights' => $row['nights'], 'guests' => $row['guests'], 'source' => $row['source'],
                                    'nights_label' => trans_choice('livewire/front-desk.nights', $row['nights'], ['count' => $row['nights']]),
                                    'check_in_label' => $row['check_in_label'], 'check_out_label' => $row['check_out_label'],
                                    'balance' => $row['balance'], 'balance_minor' => $row['balance_minor'],
                                    'has_balance' => $row['has_balance'], 'can_collect' => $row['can_collect'],
                                    // ⚠️⚠️ THE MODAL READS THIS OFF THE PAYLOAD, NOT OFF THE PAGE. A flag the row
                                    // mapper computes but this array omits arrives in Alpine as `undefined`, which
                                    // is falsy — so the control renders in the HTML (it always does, it lives in a
                                    // <template>) and NEVER APPEARS TO A HUMAN. That is how it shipped for one run
                                    // of this pass, and why dropping the permission from the mapper did not redden
                                    // a single test: nothing read the flag. Pinned now by the payload assertions in
                                    // BillToAccountReachableTest.
                                    'can_bill_to_account' => $row['can_bill_to_account'],
                                    'is_vip' => $row['is_vip'], 'status_label' => $row['status_label'], 'tone' => $row['tone'],
                                    'can_move' => $row['can_move'], 'move' => $row['move'], 'unassigned' => $row['unassigned'],
                                ];
                            @endphp
                            <div class="vy-fd-row">
                                {{-- The design leads each row with an initials avatar. Derived from the guest
                                     NAME already shown — no contact PII is added to this surface (#28). --}}
                                <span class="vy-fd-av" aria-hidden="true">{{ \Illuminate\Support\Str::of($row['guest'])->explode(' ')->filter()->take(2)->map(fn ($p) => \App\Support\I18n\LocalText::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->implode('') }}</span>
                                <div class="vy-fd-main">
                                    <div class="vy-fd-guest">
                                        {{ $row['guest'] }}
                                        {{-- #87 — VIP flags the front desk (real is_vip). An operational flag, not contact PII. --}}
                                        @if ($row['is_vip'])
                                            <span class="vy-tag tone-vip" style="margin-left: 6px;"><span class="vy-dot"></span>{{ __('livewire/front-desk.vip') }}</span>
                                        @endif
                                    </div>
                                    @if ($row['preferences'] !== [])
                                        <div class="vy-fd-meta" style="margin-top: 4px;">
                                            @foreach ($row['preferences'] as $pref)
                                                <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $pref }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if ($row['expected_arrival'])
                                        <div class="vy-fd-meta" style="margin-top: 4px; font-size: 12px; color: var(--vy-on-grad);">{{ __('livewire/front-desk.expected_arrival', ['window' => $row['expected_arrival']]) }}</div>
                                    @endif
                                    <div class="vy-fd-meta">
                                        <span class="vy-tag tone-{{ $row['tone'] }}"><span class="vy-dot"></span>{{ $row['status_label'] }}</span>
                                        @if ($row['unassigned'])
                                            <span class="vy-unassigned">{{ __('livewire/front-desk.unassigned') }}</span>
                                            @if ($row['can_move'])
                                                {{-- In-place ASSIGN — real free same-type units → app.reservations.move --}}
                                                <button type="button" class="vy-assign-link" x-on:click="openMove(@js($rowModal))">{{ __('livewire/front-desk.assign_link') }}</button>
                                            @else
                                                <a class="vy-assign-link" href="{{ route('app.rack') }}?start={{ $row['check_in'] }}">{{ __('livewire/front-desk.assign_link') }}</a>
                                            @endif
                                        @elseif ($row['unit'])
                                            <span class="vy-unit-chip">{{ $row['unit'] }}</span>
                                            @if ($row['can_move'])
                                                <button type="button" class="vy-move-link" x-on:click="openMove(@js($rowModal))">{{ __('livewire/front-desk.move_link') }}</button>
                                            @endif
                                        @endif
                                        <span class="vy-sep">·</span>
                                        <span>{{ $row['source'] }}</span>
                                        <span class="vy-sep">·</span>
                                        <span>{{ trans_choice('livewire/front-desk.nights', $row['nights'], ['count' => $row['nights']]) }}</span>
                                        <span class="vy-sep">·</span>
                                        <span>{{ trans_choice('livewire/front-desk.guests', $row['guests'], ['count' => $row['guests']]) }}</span>
                                    </div>
                                </div>

                                <div class="vy-fd-right">
                                    @if ($row['has_balance'])
                                        <span class="vy-balance is-due" title="{{ __('livewire/front-desk.outstanding_balance') }}">{{ __('livewire/front-desk.balance_due', ['amount' => $row['balance']]) }}</span>
                                    @else
                                        <span class="vy-balance is-settled">{{ __('livewire/front-desk.settled') }}</span>
                                    @endif

                                    <div class="vy-fd-actions">
                                        @if ($row['can_check_in'])
                                            <button type="button" class="vy-act vy-act--accent" x-on:click="openCheckin(@js($rowModal))">{{ __('livewire/front-desk.check_in') }}</button>
                                        @elseif ($row['can_check_out'])
                                            <button type="button" class="vy-act vy-act--ghost" x-on:click="openCheckout(@js($rowModal))">{{ __('livewire/front-desk.check_out') }}</button>
                                        @endif

                                        {{-- On-desk Collect — REAL (cash / bank transfer) via the folio payment route,
                                             gated on payments.record. Card stays the deferred Stripe seam (never faked). --}}
                                        @if ($row['can_collect'])
                                            <button type="button" class="vy-act" x-on:click="openCollect(@js($rowModal))">{{ __('livewire/front-desk.collect') }}</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            {{-- The design gives this an illustrated empty state. Its STRUCTURE is matched;
                                 its copy is NOT — the wireframe asserts "the last guest was welcomed at 15:00
                                 · tomorrow brings 9 arrivals", which are invented figures this page does not
                                 compute (#39). The line below states only what is true. --}}
                            <div class="vy-fd-empty">
                                <span class="vy-fd-empty-ico" aria-hidden="true">
                                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/><path d="M3 20h18"/></svg>
                                </span>
                                <div class="vy-fd-empty-t">{{ $emptyTitle }}</div>
                                <div class="vy-fd-empty-b">{{ $emptyBody }}</div>
                                <a class="vy-fd-empty-cta" href="?date={{ $day['next'] }}">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M3 9.5h18"/><path d="M8 2.5v4"/><path d="M16 2.5v4"/></svg>
                                    {{ __('livewire/front-desk.see_next_day') }}
                                </a>
                            </div>
                        @endforelse
                    </div>
                @endforeach

                {{-- ── CHECK-IN modal — wraps the REAL, gated check-in action. The checklist + key-card counter are
                     a local staff aide-memoire (they persist nothing — no fake ID "verification"/KYC). Collect is
                     the REAL folio payment (cash/bank), shown only to a payments.record holder. ── --}}
                <div class="vy-fdm-overlay" x-show="modal === 'checkin'" x-cloak x-transition.opacity x-on:click.self="close()" x-on:keydown.escape.window="modal = null">
                    <div class="vy-fdm" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="modal === 'checkin'" aria-labelledby="vy-dlg-front-desk-modal-checkin-5">
                        <div class="vy-fdm-head">
                            <div>
                                <div class="vy-fdm-title" id="vy-dlg-front-desk-modal-checkin-5">{!! __('livewire/front-desk.checkin_title') !!}</div>
                                <div class="vy-fdm-sub"><span x-text="row.unit || @js(__('livewire/front-desk.unassigned'))"></span> · <span x-text="row.nights_label"></span> · <span x-text="@js(__('livewire/front-desk.date_range', ['from' => ':from', 'to' => ':to'])).replace(':from', () => row.check_in_label).replace(':to', () => row.check_out_label)"></span></div>
                            </div>
                            <button type="button" class="vy-fdm-x" x-on:click="close()" aria-label="{{ __('common.close') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                            </button>
                        </div>
                        <div class="vy-fdm-body">
                            <div class="vy-fdm-label">{{ __('livewire/front-desk.before_handing_keys') }}</div>
                            <label class="vy-fdm-check"><input type="checkbox" x-model="checks.id"><span>{{ __('livewire/front-desk.photo_id_checked') }}</span></label>
                            <label class="vy-fdm-check"><input type="checkbox" x-model="checks.reg"><span>{{ __('livewire/front-desk.registration_signed') }}</span></label>
                            <div class="vy-fdm-keycards">
                                <span>{{ __('livewire/front-desk.key_cards') }}</span>
                                <div class="vy-fdm-counter">
                                    <button type="button" x-on:click="keycards = Math.max(0, keycards - 1)" aria-label="{{ __('livewire/front-desk.fewer_key_cards') }}">−</button>
                                    <span x-text="keycards"></span>
                                    <button type="button" x-on:click="keycards = Math.min(9, keycards + 1)" aria-label="{{ __('livewire/front-desk.more_key_cards') }}">+</button>
                                </div>
                            </div>
                            <template x-if="row.can_collect">
                                <div>
                                    <div class="vy-fdm-label">{!! __('livewire/front-desk.collect_balance_now') !!}</div>
                                    @include('livewire.partials.fd-collect-form')
                                </div>
                            </template>
                        </div>
                        <div class="vy-fdm-foot">
                            <button type="button" class="vy-act vy-act--ghost" x-on:click="close()">{{ __('common.cancel') }}</button>
                            <form method="POST" x-bind:action="'/app/front-desk/' + row.id + '/check-in'">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="date" value="{{ $day['date'] }}">
                                <button type="submit" class="vy-act vy-act--accent">{{ __('livewire/front-desk.check_in') }}</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ── CHECK-OUT modal — wraps the REAL, gated check-out action + the real folio PDF + an honest
                     settle-now (real payment) when a balance remains. ── --}}
                <div class="vy-fdm-overlay" x-show="modal === 'checkout'" x-cloak x-transition.opacity x-on:click.self="close()" x-on:keydown.escape.window="modal = null">
                    <div class="vy-fdm" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="modal === 'checkout'" aria-labelledby="vy-dlg-front-desk-modal-checkout-4">
                        <div class="vy-fdm-head">
                            <div>
                                <div class="vy-fdm-title" id="vy-dlg-front-desk-modal-checkout-4">{!! __('livewire/front-desk.checkout_title') !!}</div>
                                <div class="vy-fdm-sub"><span x-text="row.unit || @js(__('livewire/front-desk.unassigned'))"></span> · <span x-text="@js(__('livewire/front-desk.date_range', ['from' => ':from', 'to' => ':to'])).replace(':from', () => row.check_in_label).replace(':to', () => row.check_out_label)"></span></div>
                            </div>
                            <button type="button" class="vy-fdm-x" x-on:click="close()" aria-label="{{ __('common.close') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                            </button>
                        </div>
                        <div class="vy-fdm-body">
                            <template x-if="row.has_balance">
                                <div>
                                    <div class="vy-fdm-warn">{!! __('livewire/front-desk.balance_still_due') !!}</div>
                                    <template x-if="row.can_collect">
                                        <div>
                                            <div class="vy-fdm-label">{{ __('livewire/front-desk.settle_now') }}</div>
                                            @include('livewire.partials.fd-collect-form')
                                        </div>
                                    </template>

                                    {{-- ⚠️⚠️ THE SECOND REMEDY, AND UNTIL 2026-09-22 IT HAD NOWHERE TO CLICK.
                                         The check-out refusal (OutstandingBalanceException) names three ways
                                         forward — take the payment, BILL IT TO A COMPANY ACCOUNT if they settle
                                         later, or post an adjustment — and 🔬 only the first had a control. The
                                         bill-to-account route had no view, no component and no URI anywhere.
                                         ⚠️ IT BELONGS HERE, NOT ON A TIDIER SCREEN: this modal is where the
                                         balance is read and where the refusal lands (backToBoard → desk_notice).
                                         A remedy two screens from the problem is the dead end with extra steps. --}}
                                    <template x-if="row.can_bill_to_account">
                                        <div style="margin-top:14px">
                                            <div class="vy-fdm-label">{{ __('livewire/front-desk.bill_to_company') }}</div>
                                            @if ($companyAccounts === [])
                                                {{-- ⚠️ This branch is reached in TWO ways — no accounts exist, or
                                                     the viewer may not bill. The control itself is already
                                                     Alpine-gated on `can_bill_to_account`, so a role without the
                                                     permission never sees this text at all; what renders here is
                                                     only ever the genuine "none are set up" case. --}}
                                                {{-- ⚠️ HONEST EMPTY STATE, NOT AN EMPTY DROPDOWN (#39). 🔬 The only
                                                     place a company account is created is the demo seeder — there is
                                                     no operator UI — so a real tenant has none and a picker here
                                                     would be a second dead end. Say that, rather than offer it. --}}
                                                <div class="vy-fdm-settled" style="margin-top:6px">
                                                    {{ __('livewire/front-desk.no_company_accounts') }}
                                                </div>
                                            @else
                                                <form method="POST" x-bind:action="'/app/reservations/' + row.id + '/bill-to-account'" style="margin-top:6px">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <select class="vy-input" name="company_account_id" required aria-label="{{ __('livewire/front-desk.company_account') }}">
                                                        <option value="">{{ __('livewire/front-desk.choose_account') }}</option>
                                                        @foreach ($companyAccounts as $acct)
                                                            <option value="{{ $acct['id'] }}">{{ $acct['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="vy-act" style="margin-top:8px">{{ __('livewire/front-desk.bill_to_account') }}</button>
                                                </form>
                                                <div class="vy-fdm-sub" style="margin-top:6px">{{ __('livewire/front-desk.bill_to_account_note') }}</div>
                                            @endif
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!row.has_balance">
                                <div class="vy-fdm-settled">{{ __('livewire/front-desk.folio_settled') }}</div>
                            </template>
                            <a class="vy-fdm-folio" x-bind:href="'/app/reservations/' + row.id + '/folio.pdf'" target="_blank" rel="noopener">{{ __('livewire/front-desk.view_folio_pdf') }}</a>
                        </div>
                        <div class="vy-fdm-foot">
                            <button type="button" class="vy-act vy-act--ghost" x-on:click="close()">{{ __('common.cancel') }}</button>
                            <form method="POST" x-bind:action="'/app/front-desk/' + row.id + '/check-out'">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="date" value="{{ $day['date'] }}">
                                <button type="submit" class="vy-act vy-act--accent">{{ __('livewire/front-desk.check_out') }}</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- ── COLLECT modal — standalone on-desk payment for a due row (departures / in-house). ── --}}
                {{-- ⚠️⚠️ D2 — THE DIALOG CONTRACT. `x-trap` gives all three focus properties at once: focus moves IN
     on open, is TRAPPED while open, and is RETURNED to the trigger on close. ⚠️ IT NEEDS NO NEW
     DEPENDENCY — Livewire already bundles Alpine's focus plugin (`directive("trap")` /
     `createFocusTrap` in livewire.esm.js); it was simply never used anywhere in 243 files.
     ⚠️ `.noscroll` also stops the page behind scrolling. ⚠️ It does NOT make the rest of the page
     INERT — without `inert` support or a dependency, focus is trapped and no more, and that is
     stated rather than implied. ⚠️ Focus lands on the panel itself (`tabindex="-1"`), never on the
     destructive button. --}}
                <div class="vy-fdm-overlay" x-show="modal === 'collect'" x-cloak x-transition.opacity x-on:click.self="close()" x-on:keydown.escape.window="close()">
                    <div class="vy-fdm vy-fdm--sm" role="dialog" aria-modal="true" aria-labelledby="vy-dlg-collect-t" tabindex="-1" x-trap.noscroll="modal === 'collect'">
                        <div class="vy-fdm-head">
                            <div>
                                <div class="vy-fdm-title" id="vy-dlg-collect-t">{{ __('livewire/front-desk.collect_payment') }}</div>
                                <div class="vy-fdm-sub">{!! __('livewire/front-desk.collect_sub') !!}</div>
                            </div>
                            <button type="button" class="vy-fdm-x" x-on:click="close()" aria-label="{{ __('common.close') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                            </button>
                        </div>
                        <div class="vy-fdm-body">
                            @include('livewire.partials.fd-collect-form')
                        </div>
                        <div class="vy-fdm-foot">
                            <button type="button" class="vy-act vy-act--ghost" x-on:click="close()">{{ __('common.cancel') }}</button>
                        </div>
                    </div>
                </div>

                {{-- ── MOVE / ASSIGN modal — REAL free same-type units → the certified app.reservations.move
                     (AssignmentService::assignUnit re-checks Level B under a lock). "Open rack" is the full
                     assignment surface for a cross-type move. ── --}}
                <div class="vy-fdm-overlay" x-show="modal === 'move'" x-cloak x-transition.opacity x-on:click.self="close()" x-on:keydown.escape.window="modal = null">
                    <div class="vy-fdm" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="modal === 'move'" aria-labelledby="vy-dlg-front-desk-modal-move-2">
                        <div class="vy-fdm-head">
                            <div>
                                <div class="vy-fdm-title" id="vy-dlg-front-desk-modal-move-2"><span x-text="row.unassigned ? @js(__('livewire/front-desk.assign_a_unit')) : @js(__('livewire/front-desk.move_guest', ['guest' => ':guest'])).replace(':guest', () => (row.guest || ''))"></span></div>
                                <div class="vy-fdm-sub">{!! __('livewire/front-desk.move_sub') !!}</div>
                            </div>
                            <button type="button" class="vy-fdm-x" x-on:click="close()" aria-label="{{ __('common.close') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                            </button>
                        </div>
                        <template x-if="row.move && row.move.candidates && row.move.candidates.length">
                            <form method="POST" x-bind:action="'/app/reservations/' + row.id + '/move'">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="reservation_unit_id" x-bind:value="row.move ? row.move.line_id : ''">
                                <input type="hidden" name="return_to" value="front-desk">
                                <input type="hidden" name="return_date" value="{{ $day['date'] }}">
                                <div class="vy-fdm-body">
                                    <div class="vy-fdm-units">
                                        <template x-for="u in (row.move ? row.move.candidates : [])" x-bind:key="u.id">
                                            <label class="vy-fdm-unit">
                                                <input type="radio" name="unit_id" x-bind:value="u.id" x-model="moveTarget">
                                                <span class="vy-fdm-unit-code" x-text="u.name"></span>
                                                <span class="vy-fdm-unit-free">{{ __('livewire/front-desk.unit_free') }}</span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                                <div class="vy-fdm-foot">
                                    <a class="vy-fdm-folio" href="{{ route('app.rack') }}?start={{ $day['date'] }}" style="margin-top: 0; margin-right: auto;">{{ __('livewire/front-desk.open_rack') }}</a>
                                    <button type="button" class="vy-act vy-act--ghost" x-on:click="close()">{{ __('common.cancel') }}</button>
                                    <button type="submit" class="vy-act vy-act--accent" x-bind:disabled="!moveTarget">{{ __('livewire/front-desk.assign_button') }}</button>
                                </div>
                            </form>
                        </template>
                        <template x-if="!(row.move && row.move.candidates && row.move.candidates.length)">
                            <div>
                                <div class="vy-fdm-body">
                                    <div class="vy-fdm-settled">{{ __('livewire/front-desk.no_free_same_type') }}</div>
                                </div>
                                <div class="vy-fdm-foot">
                                    <a class="vy-fdm-folio" href="{{ route('app.rack') }}?start={{ $day['date'] }}" style="margin-top: 0; margin-right: auto;">{{ __('livewire/front-desk.open_rack') }}</a>
                                    <button type="button" class="vy-act vy-act--ghost" x-on:click="close()">{{ __('common.cancel') }}</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ── WALK-IN modal — a guest at the desk NOW. Posts to the CERTIFIED app.reservations.create
                     (source=walk_in, today's dates) which prices + creates + posts the folio, then checks in and
                     returns here. Free-tonight units are real + priced; a longer stay is re-checked under the
                     booking lock. Optional "collect first night" is the real folio payment; card is deferred. ── --}}
                <div class="vy-fdm-overlay" x-show="modal === 'walkin'" x-cloak x-transition.opacity x-on:click.self="close()" x-on:keydown.escape.window="modal = null">
                    <div class="vy-fdm" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="modal === 'walkin'" aria-labelledby="vy-dlg-front-desk-modal-walkin-1">
                        <div class="vy-fdm-head">
                            <div>
                                <div class="vy-fdm-title" id="vy-dlg-front-desk-modal-walkin-1">{{ __('livewire/front-desk.walk_in') }}</div>
                                <div class="vy-fdm-sub">{{ __('livewire/front-desk.walkin_sub', ['date' => \Carbon\CarbonImmutable::parse($walkin['today'])->translatedFormat(\App\Support\I18n\LocalDate::pattern('D j M'))]) }}</div>
                            </div>
                            <button type="button" class="vy-fdm-x" x-on:click="close()" aria-label="{{ __('common.close') }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('app.reservations.create') }}">
                            @csrf
                            <input type="hidden" name="source" value="walk_in">
                            <input type="hidden" name="walk_in" value="1">
                            <input type="hidden" name="check_in" value="{{ $walkin['today'] }}">
                            <input type="hidden" name="check_out" x-bind:value="walkCheckout()">
                            <input type="hidden" name="room_type_id" x-bind:value="wi.roomType">
                            <input type="hidden" name="unit_id" x-bind:value="wi.unit">
                            <input type="hidden" name="adults" value="1">
                            <input type="hidden" name="send_confirmation" value="0">
                            <div class="vy-fdm-body">
                                <div class="vy-fdm-row2">
                                    <label class="vy-sr-only" for="vy-lbl-front-desk-guest-first-name">{{ __('livewire/front-desk.first_name') }}</label>
                                    <input id="vy-lbl-front-desk-guest-first-name" type="text" name="guest_first_name" x-model="wi.first" class="vy-input" placeholder="{{ __('livewire/front-desk.first_name') }}" autocomplete="off">
                                    <label class="vy-sr-only" for="vy-lbl-front-desk-guest-last-name">{{ __('livewire/front-desk.last_name') }}</label>
                                    <input id="vy-lbl-front-desk-guest-last-name" type="text" name="guest_last_name" x-model="wi.last" class="vy-input" placeholder="{{ __('livewire/front-desk.last_name') }}" autocomplete="off">
                                </div>
                                <div class="vy-fdm-keycards" style="margin-top: 10px;">
                                    <span>{{ __('livewire/front-desk.nights_label') }}</span>
                                    <div class="vy-fdm-counter">
                                        <button type="button" x-on:click="wi.nights = Math.max(1, wi.nights - 1)" aria-label="{{ __('livewire/front-desk.fewer_nights') }}">−</button>
                                        <span x-text="wi.nights"></span>
                                        <button type="button" x-on:click="wi.nights = Math.min(14, wi.nights + 1)" aria-label="{{ __('livewire/front-desk.more_nights') }}">+</button>
                                    </div>
                                </div>
                                <div class="vy-fdm-label">{{ __('livewire/front-desk.free_tonight') }}</div>
                                <div class="vy-fdm-units">
                                    <template x-for="u in walkinUnits" x-bind:key="u.unit_id">
                                        <label class="vy-fdm-unit">
                                            <input type="radio" x-bind:value="u.unit_id" x-model="wi.unit" x-on:change="wi.roomType = u.room_type_id">
                                            <span class="vy-fdm-unit-code" x-text="u.label"></span>
                                            <span class="vy-fdm-unit-free" x-text="u.price"></span>
                                        </label>
                                    </template>
                                </div>
                                @can('payments.record')
                                    <label class="vy-fdm-check" style="margin-top: 12px;"><input type="checkbox" x-model="wi.collect"><span>{{ __('livewire/front-desk.collect_first_night') }}</span></label>
                                    <div x-show="wi.collect" x-cloak>
                                        <div class="vy-fdm-collect-row">
                                            <input type="text" name="payment_amount" x-model="wi.amt" x-bind:disabled="! wi.collect" inputmode="decimal" class="vy-input vy-fdm-amt" placeholder="0.00" aria-label="{{ __('livewire/front-desk.first_night_amount') }}">
                                            <select name="payment_method" x-bind:disabled="! wi.collect" class="vy-input vy-fdm-method" aria-label="{{ __('livewire/front-desk.method') }}">
                                                <option value="cash">{{ __('livewire/front-desk.method_cash') }}</option>
                                                <option value="bank">{{ __('livewire/front-desk.method_bank') }}</option>
                                            </select>
                                        </div>
                                        <p class="vy-money-readout" aria-live="polite" x-text="window.vyLocalNumber ? window.vyLocalNumber.readout(wi.amt, @js($property['currency_symbol'] ?? ''), @js(__('components/money-readout.records', ['amount' => ':amount'])), @js(__('components/money-readout.write_like', ['example' => \App\Support\I18n\LocalNumber::example()]))) : ''"></p>
                                        @once @include('partials.local-number-script') @endonce
                                        <div class="vy-fdm-hint">{{ __('livewire/front-desk.card_terminal_hint') }}</div>
                                    </div>
                                @endcan
                            </div>
                            <div class="vy-fdm-foot">
                                <button type="button" class="vy-act vy-act--ghost" x-on:click="close()">{{ __('common.cancel') }}</button>
                                <button type="submit" class="vy-act vy-act--accent" x-bind:disabled="! wi.unit || ! wi.first.trim() || ! wi.last.trim()">{{ __('livewire/front-desk.check_in_walk_in') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="vy-deferred-note" style="margin-top: 18px;">
                <span class="vy-soon-tag">{{ __('livewire/front-desk.live_integrations') }}</span>
                {{ __('livewire/front-desk.deferred_note') }}
            </div>
        @endif

    </x-operator.shell>

    {{-- GATE-2 board chrome: the segmented control + KPI-as-tab affordance. Presentation only. --}}
    <style>
        /* ---- Front Desk — wireframe parity ------------------------------------------------------
           Scoped to .vy-fd: .vy-summary / .vy-summary-chip are shared with Units and the public
           booking views, which keep their compact chip form. */
        .vy-fd .vy-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 18px; }
        .vy-fd .vy-summary-chip {
            /* column-reverse seats the label ABOVE the figure, as the design does, without reordering
               the markup (the figure stays first in the DOM, so it is read first). */
            display: flex; flex-direction: column-reverse; align-items: flex-start; justify-content: flex-end;
            gap: 5px; padding: 15px 18px; border-radius: var(--vy-r-lg); box-shadow: var(--vy-shadow);
            /* restated because .vy-fd-kpi (a later rule, for the three tab-switching tiles) zeroes the
               background — all four tiles must read as the design's glass cards */
            background: var(--vy-glass); border: 1px solid var(--vy-glass-border);
        }
        .vy-fd .vy-summary-chip .vy-sc-num { font-size: 26px; font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-fd .vy-summary-chip .vy-sc-label { font-size: 11px; font-weight: 500; letter-spacing: .08em; }
        /* The design tints the Unassigned tile. Kept CONDITIONAL on a real non-zero count — tinting a
           "0 unassigned" tile would draw attention to nothing. */
        .vy-fd .vy-summary-chip.is-due { background: rgba(217, 102, 46, 0.13); border-color: rgba(232, 112, 58, 0.30); }
        .vy-fd .vy-summary-chip.is-due .vy-sc-label { color: var(--vy-accent-text); }
        @media (max-width: 900px) { .vy-fd .vy-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        /* Relative-day pill beside the date. */
        .vy-fd-rel { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: var(--vy-r-pill); background: rgba(217, 102, 46, 0.13); border: 1px solid rgba(232, 112, 58, 0.30); color: var(--vy-on-grad)   /* accent-text measured 3.57 here: this chip sits on the page GRADIENT, outside that token's card/tint scope */; font-size: 11px; font-weight: 500; white-space: nowrap; }
        /* Row initials avatar. */
        /* ⚠️ A SIXTH COPY OF THE AVATAR CONCEPT, found in DO-RESTORE-5 and missed by the two passes before it —
           both searched the SHELLS, and this one is page-local (§N.8f: a copy hides where you did not look).
           It carried the worst pairing in the family, WHITE on the peach gradient (2.03 at the light stop).
           Now the same flat plate + dark ink as every other avatar. The initials are aria-hidden and the guest
           NAME is rendered beside them, so this is a redundant mark — but 2.03 is not de-emphasis (§N.16.6). */
        .vy-fd-av { width: 32px; height: 32px; border-radius: 50%; flex: none; display: flex; align-items: center; justify-content: center; background: var(--vy-avatar-bg); color: var(--vy-on-peach); font-size: 11px; font-weight: 500; }
        /* The design's illustrated empty board. */
        .vy-fd-empty { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 40px 24px 30px; }
        .vy-fd-empty-ico { position: relative; width: 72px; height: 72px; border-radius: 22px; background: rgba(217, 102, 46, 0.12); color: var(--vy-accent-text); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .vy-fd-empty-ico::before { content: ''; position: absolute; inset: -12px; border-radius: 50%; background: radial-gradient(circle, rgba(232, 112, 58, .18), transparent 70%); filter: blur(9px); pointer-events: none; }
        .vy-fd-empty-t { font-family: var(--vy-display); font-weight: 600; font-size: 17px; letter-spacing: -.01em; color: var(--vy-ink); }
        .vy-fd-empty-b { font-size: 13px; color: var(--vy-muted); margin-top: 7px; max-width: 340px; line-height: 1.55; }
        .vy-fd-empty-cta { display: inline-flex; align-items: center; gap: 7px; margin-top: 20px; padding: 10px 18px; border-radius: var(--vy-r-pill); background: rgba(255, 255, 255, .65); border: 1px solid var(--vy-hairline); color: var(--vy-ink); font-size: 13px; font-weight: 500; text-decoration: none; transition: transform .14s; }
        .vy-fd-empty-cta:hover { transform: translateY(-1px); }

        .vy-fd-kpi { cursor: pointer; border: 1px solid transparent; background: none; font: inherit; text-align: left; transition: border-color 0.14s, background 0.14s, transform 0.1s; }
        .vy-fd-kpi:hover { transform: translateY(-1px); }
        .vy-fd-kpi.is-active { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.06); box-shadow: 0 0 0 1px var(--vy-accent-border) inset; }
        .vy-fd-seg { display: inline-flex; gap: 4px; padding: 4px; margin: 4px 0 16px; border-radius: 13px; background: rgba(255, 252, 248, 0.62); backdrop-filter: blur(20px) saturate(1.1); -webkit-backdrop-filter: blur(20px) saturate(1.1); border: 1px solid var(--vy-glass-border); }
        .vy-fd-seg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; border: none; border-radius: 10px; background: transparent; color: var(--vy-on-grad)   /* muted measured 4.23/4.36 on the segmented control's tint */; font-family: var(--vy-sans); font-size: 13.5px; font-weight: 600; cursor: pointer; transition: background 0.16s, color 0.16s, box-shadow 0.16s; }
        .vy-fd-seg-btn:hover { color: var(--vy-ink); }
        .vy-fd-seg-btn.is-on { background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vy-fd-seg-n { display: inline-grid; place-items: center; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px; background: rgba(60, 40, 24, 0.1); color: inherit; font-size: 11.5px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .vy-fd-seg-btn.is-on .vy-fd-seg-n { background: rgba(255, 255, 255, 0.26); }
        /* Today's timeline rail */
        .vy-tl-card { margin-bottom: 16px; padding: 16px 20px 12px; }
        .vy-tl-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
        .vy-tl-title { font-family: var(--vy-display); font-weight: 600; font-size: 14px; color: var(--vy-ink); }
        .vy-tl-legend { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; color: var(--vy-muted); }
        .vy-tl-key { width: 9px; height: 9px; border-radius: 50%; display: inline-block; margin-left: 8px; }
        .vy-tl-key.is-arr { background: var(--vy-accent, #e8703a); }
        .vy-tl-key.is-dep { background: #6b8caf; }
        .vy-tl-rail { position: relative; height: 92px; margin: 8px 4px 2px; }
        .vy-tl-axis { position: absolute; left: 0; right: 0; top: 50%; height: 1px; background: var(--vy-hairline); }
        .vy-tl-mark { position: absolute; top: 0; bottom: 0; width: 0; border-left: 1px dashed rgba(60, 40, 24, 0.09); transform: translateX(-0.5px); }
        .vy-tl-mark span { position: absolute; top: 50%; left: 4px; transform: translateY(-50%); font-size: 10.5px; color: var(--vy-faint); background: var(--vy-card-bg, rgba(255, 252, 248, 0.9)); padding: 0 2px; font-variant-numeric: tabular-nums; }
        .vy-tl-now { position: absolute; top: 0; bottom: 0; width: 2px; background: var(--vy-accent, #e8703a); transform: translateX(-1px); z-index: 3; }
        .vy-tl-now::before { content: ''; position: absolute; top: -3px; left: -3px; width: 8px; height: 8px; border-radius: 50%; background: var(--vy-accent, #e8703a); box-shadow: 0 0 0 0 rgba(232, 112, 58, 0.5); animation: vyTlPulse 2s ease-out infinite; }
        @keyframes vyTlPulse { to { box-shadow: 0 0 0 9px rgba(232, 112, 58, 0); } }
        .vy-tl-nowlabel { position: absolute; top: -18px; left: 5px; white-space: nowrap; font-size: 10.5px; font-weight: 700; color: var(--vy-accent-text); }
        .vy-tl-dot { position: absolute; width: 11px; height: 11px; border-radius: 50%; transform: translate(-50%, -50%); border: 2px solid var(--vy-card-bg, #fff); box-shadow: 0 1px 4px rgba(60, 40, 24, 0.22); z-index: 2; }
        .vy-tl-dot.is-arr { top: 30%; background: var(--vy-accent, #e8703a); }
        .vy-tl-dot.is-dep { top: 70%; background: #6b8caf; }
        .vy-tl-dot.is-vip { box-shadow: 0 0 0 3px rgba(232, 112, 58, 0.22), 0 1px 4px rgba(60, 40, 24, 0.22); }
        .vy-tl-dotlabel { position: absolute; left: 50%; transform: translateX(-50%); font-size: 9.5px; font-weight: 600; color: var(--vy-muted); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .vy-tl-dot.is-arr .vy-tl-dotlabel { bottom: 14px; }
        .vy-tl-dot.is-dep .vy-tl-dotlabel { top: 14px; }
        .vy-tl-foot { font-size: 11px; color: var(--vy-muted); line-height: 1.45; margin-top: 8px; }
        @media (prefers-reduced-motion: reduce) { .vy-tl-now::before { animation: none; } }
        /* front-desk modals */
        .vy-fdm-overlay { position: fixed; inset: 0; z-index: 60; display: grid; place-items: center; padding: 20px; background: rgba(46, 30, 18, 0.32); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); }
        .vy-fdm { width: 100%; max-width: 440px; max-height: 90vh; overflow-y: auto; border-radius: 20px; background: rgba(255, 252, 248, 0.98); border: 1px solid var(--vy-glass-border); box-shadow: 0 30px 80px -28px rgba(46, 30, 18, 0.5); }
        .vy-fdm--sm { max-width: 380px; }
        .vy-fdm-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 20px 22px 12px; }
        .vy-fdm-title { font-family: var(--vy-display); font-weight: 600; font-size: 17px; letter-spacing: -0.02em; color: var(--vy-ink); }
        .vy-fdm-sub { margin-top: 3px; font-size: 12.5px; color: var(--vy-muted); }
        .vy-fdm-x { flex: none; width: 30px; height: 30px; border-radius: 50%; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.6); color: var(--vy-muted); display: grid; place-items: center; cursor: pointer; transition: background 0.14s; }
        .vy-fdm-x:hover { background: rgba(255, 255, 255, 0.95); color: var(--vy-ink); }
        .vy-fdm-body { padding: 6px 22px 8px; }
        .vy-fdm-label { font-size: 12px; font-weight: 600; color: var(--vy-ink); margin: 14px 0 8px; }
        .vy-fdm-check { display: flex; align-items: center; gap: 10px; padding: 9px 12px; margin-bottom: 6px; border-radius: 11px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.5); font-size: 13.5px; color: var(--vy-ink); cursor: pointer; }
        .vy-fdm-check input { accent-color: var(--vy-accent, #e8703a); width: 16px; height: 16px; }
        .vy-fdm-keycards { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; margin-top: 8px; border-radius: 11px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.5); font-size: 13.5px; color: var(--vy-ink); }
        .vy-fdm-counter { display: inline-flex; align-items: center; gap: 12px; }
        .vy-fdm-counter button { width: 26px; height: 26px; border-radius: 8px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.7); color: var(--vy-ink); font-size: 16px; line-height: 1; cursor: pointer; }
        .vy-fdm-counter span { min-width: 16px; text-align: center; font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-fdm-collect { margin-top: 4px; }
        .vy-fdm-collect-row { display: flex; gap: 8px; }
        .vy-fdm-amt { flex: 1; }
        .vy-fdm-method { flex: 0 0 130px; }
        .vy-fdm-hint { margin-top: 8px; font-size: 11.5px; color: var(--vy-faint); line-height: 1.45; }
        .vy-fdm-warn { padding: 10px 12px; border-radius: 11px; background: rgba(232, 112, 58, 0.08); border: 1px solid var(--vy-accent-border); font-size: 13px; color: var(--vy-ink); }
        .vy-fdm-warn strong { font-variant-numeric: tabular-nums; }
        .vy-fdm-settled { padding: 10px 12px; border-radius: 11px; background: rgba(60, 140, 90, 0.08); border: 1px solid rgba(60, 140, 90, 0.25); font-size: 13px; color: var(--vy-ink); }
        .vy-fdm-folio { display: inline-block; margin-top: 12px; font-size: 13px; font-weight: 600; color: var(--vy-accent, #e8703a); text-decoration: none; }
        .vy-fdm-folio:hover { text-decoration: underline; }
        .vy-fdm-foot { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 14px 22px 20px; }
        .vy-move-link { margin-left: 8px; padding: 0; border: none; background: none; color: var(--vy-accent, #e8703a); font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; }
        .vy-move-link:hover { text-decoration: underline; }
        .vy-fdm-units { display: flex; flex-direction: column; gap: 6px; max-height: 260px; overflow-y: auto; }
        .vy-fdm-unit { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 11px; border: 1px solid var(--vy-hairline); background: rgba(255, 255, 255, 0.5); font-size: 13.5px; color: var(--vy-ink); cursor: pointer; transition: border-color 0.14s, background 0.14s; }
        .vy-fdm-unit:has(input:checked) { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.07); }
        .vy-fdm-unit input { accent-color: var(--vy-accent, #e8703a); width: 16px; height: 16px; }
        .vy-fdm-unit-code { flex: 1; font-weight: 600; }
        .vy-fdm-unit-free { font-size: 11px; color: #3c8c5a; font-weight: 600; }
        .vy-fdm-row2 { display: flex; gap: 8px; }
        .vy-fdm-row2 .vy-input { flex: 1; min-width: 0; }
        .vy-pill--accent { display: inline-flex; align-items: center; gap: 6px; background: var(--vy-grad); color: #fff; border-color: transparent; box-shadow: var(--vy-shadow-accent); cursor: pointer; }
        .vy-pill--accent:hover { filter: brightness(1.04); }
        [x-cloak] { display: none !important; }
    </style>
</div>
