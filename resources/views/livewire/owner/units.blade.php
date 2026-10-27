{{--
    Owner My Units — rebuilt to the decoded `docs/design/raw/Owner My Units.html` wireframe (exact design match).
    GATE 1 = the visual layer + the REAL read data the component already supplies (units count, per-unit occupancy /
    nights / revenue / nightly rate, the occupancy heatmap, the best performer, the real OwnerAttention items) +
    client-side interactions (the collapsible action-center, tabs, unit sort, calendar day-detail). The write actions
    — Edit pricing (real rate change), Message guest (operator relay), Block dates, snooze/dismiss, Export — are
    wired to their real, audited, owner-scoped controller routes in GATE 2. Nothing fabricated (#39); guest name-only
    (#28); integer money (#17). Shell + chrome from <x-owner.shell>.
--}}
<x-owner.shell active="units" :title="__('livewire/owner/units.title')" :title-lg="true" :viewer="$viewer">

    @if (session('rate_notice'))
        <div class="vy-flash is-ok" style="margin-bottom:16px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>{{ session('rate_notice') }}</div>
    @elseif (session('rate_error'))
        <div class="vy-flash is-err" style="margin-bottom:16px">{{ session('rate_error') }}</div>
    @endif

    {{-- Header: the real "Add unit" link (co-owner only — never invites a 403) --}}
    <div class="vy-mu-top vy-fade" style="animation-delay:.02s">
        <span></span>
        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
            <a class="vy-mu-addbtn" href="{{ route('owner.add-unit') }}">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                {{ __('livewire/owner/units.add_unit') }}
            </a>
        @endownercan
    </div>

    {{-- Stats band — all REAL, owner-scoped --}}
    <div class="vy-mu-stats vy-fade" style="animation-delay:.06s">
        <div class="vy-mu-stat">
            <div class="vy-mu-stat-label">{{ __('livewire/owner/units.stat_units') }}</div>
            <div class="vy-mu-stat-val">{{ $unitsCount }}</div>
        </div>
        <div class="vy-mu-stat">
            <div class="vy-mu-stat-label">{{ __('livewire/owner/units.avg_occupancy') }}</div>
            <div style="display:flex;align-items:baseline;gap:8px">
                <span class="vy-mu-stat-val">{{ $occupancy['pct'] }}%</span>
                @if (! empty($occDelta))<span class="vy-mu-stat-delta" @unless ($occDelta['positive']) style="color:var(--vy-muted)" @endunless>{{ $occDelta['label'] }}</span>@endif
            </div>
            <div class="vy-mu-stat-label" style="margin:6px 0 0;text-transform:none;letter-spacing:0;font-weight:400;color:var(--vy-muted)">{{ $month['label'] }}</div>
        </div>
        <div class="vy-mu-stat">
            <div class="vy-mu-stat-label">{{ __('livewire/owner/units.earned_this_month') }}</div>
            <div class="vy-mu-stat-val">{{ $earned['display'] }}</div>
            <div class="vy-mu-stat-label" style="margin:6px 0 0;text-transform:none;letter-spacing:0;font-weight:400;color:var(--vy-muted)">{{ __('livewire/owner/units.net_month', ['month' => $month['label']]) }}</div>
        </div>
    </div>

    {{-- Action center — REAL OwnerAttention items (fix-it links) with REAL snooze/dismiss/undo
         (owner_attention_states, CAP_ACTIONS controller POSTs #20, shared with the Overview feed).
         Collapsible (client-side). No fabricated approvals (#39). --}}
    @php
        $attnCount = count($attention);
        $acSub = $attnCount > 0
            ? trans_choice('livewire/owner/units.items_need_you', $attnCount, ['count' => $attnCount])
            : __('livewire/owner/units.all_caught_up_sub');
    @endphp
    <section class="vy-mu-ac vy-fade" style="animation-delay:.1s" x-data="{ open: {{ $attnCount > 0 ? 'true' : 'false' }}, filter: 'all' }">
        <div class="vy-mu-ac-head" @click="open = !open">
            <span class="vy-mu-ac-icon">
                @if ($attnCount > 0)<span class="vy-mu-ac-ping"></span>@endif
                <span class="m">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 5-2.5 6-2.5 6h17S18 13 18 8z"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>
                </span>
            </span>
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:9px">
                    <h2 class="vy-mu-ac-title">{{ __('livewire/owner/units.needs_attention') }}</h2>
                    @if ($attnCount > 0)<span class="vy-mu-ac-count">{{ $attnCount }}</span>@endif
                </div>
                <div class="vy-mu-ac-sub">{{ $acSub }}</div>
            </div>
            {{-- ⚠️⚠️ THE KEYBOARD PATH INTO THE ACTION CENTRE — which holds RATE PROPOSALS and EXPENSE
                 APPROVALS. Those are money decisions, and until now the only way to reveal them was to
                 click a `<div>`. A keyboard user could not reach them at all.
                 ⚠️ THIS WAS ALREADY A BUTTON IN EVERYTHING BUT ELEMENT — a pill with a hover lift reading
                 "Expand"/"Collapse". Only the tag was wrong, so making it a real `<button>` adds the
                 keyboard path without changing one pixel or removing the head's whole-area click.
                 ⚠️ `.stop` because the head's own `@click` would otherwise toggle a second time and undo
                 this one; `aria-expanded`/`aria-controls` because a disclosure must say what it controls
                 and whether it is open. --}}
            <button type="button" class="vy-mu-ac-toggle" @click.stop="open = ! open"
                    :aria-expanded="open ? 'true' : 'false'" aria-controls="vy-mu-ac-panel">
                <span x-text="open ? @js(__('livewire/owner/units.collapse')) : @js(__('livewire/owner/units.expand'))">{{ __('livewire/owner/units.expand') }}</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" :style="open ? 'transform:rotate(180deg)' : ''"><path d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>

        <div class="vy-mu-ac-reveal" id="vy-mu-ac-panel" :class="open ? 'is-open' : ''">
            <div>
                @if ($attnCount > 0)
                    {{-- Wave-B: Status filter chips (client-side, over the REAL items by tone) + BULK actions (mark-all-read /
                         snooze-all — real store writes, CAP_ACTIONS). There is no per-UNIT attention signal (the real signals
                         are account-level: tax hold, payout method, agreement renewal), so a Unit filter is honestly omitted —
                         only a Status filter maps to real data (#39). --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:10px">
                        <div style="display:flex;gap:6px">
                            @foreach (['all' => __('common.all'), 'warn' => __('livewire/owner/units.tone_action'), 'info' => __('livewire/owner/units.tone_review')] as $val => $lbl)
                                <button type="button" @click="filter = '{{ $val }}'" :style="filter === '{{ $val }}' ? 'background:var(--vy-grad);color:#fff;border-color:transparent' : ''" style="padding:5px 12px;border-radius:var(--vy-r-pill);border:1px solid var(--vy-hairline);background:rgba(255,255,255,.6);color:var(--vy-muted);font-family:var(--vy-sans);font-size:11.5px;font-weight:600;cursor:pointer">{{ $lbl }}</button>
                            @endforeach
                        </div>
                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                            <div style="display:flex;gap:6px">
                                <form method="POST" action="{{ route('owner.attention.read-all') }}" style="margin:0">
                                    @csrf
                                    <button type="submit" style="padding:5px 11px;border-radius:var(--vy-r-pill);border:1px solid var(--vy-hairline);background:rgba(255,255,255,.6);color:var(--vy-muted);font-family:var(--vy-sans);font-size:11.5px;font-weight:600;cursor:pointer">{{ __('livewire/owner/units.mark_all_read') }}</button>
                                </form>
                                <form method="POST" action="{{ route('owner.attention.snooze-all') }}" style="margin:0">
                                    @csrf
                                    <button type="submit" style="padding:5px 11px;border-radius:var(--vy-r-pill);border:1px solid var(--vy-hairline);background:rgba(255,255,255,.6);color:var(--vy-muted);font-family:var(--vy-sans);font-size:11.5px;font-weight:600;cursor:pointer">{{ __('livewire/owner/units.snooze_all') }}</button>
                                </form>
                            </div>
                        @endownercan
                    </div>
                    <div style="margin-top:8px">
                        @foreach ($attention as $item)
                            @php $warn = $item['tone'] === 'warn'; @endphp
                            <div class="vy-mu-item" x-show="filter === 'all' || filter === '{{ $item['tone'] }}'"{!! $item['read'] ? ' style="opacity:.62"' : '' !!}>
                                <span class="vy-mu-item-ic">
                                    @if ($warn)
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                                    @else
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M9 13h6"/><path d="M9 17h4"/></svg>
                                    @endif
                                </span>
                                <div style="flex:1;min-width:0">
                                    <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap">
                                        <span class="vy-mu-item-title">{{ $item['title'] }}</span>
                                        <span class="vy-mu-item-tag" style="background:{{ $warn ? 'rgba(224,146,30,.15)' : 'rgba(232,112,58,.13)' }};color:{{ $warn ? 'var(--vy-amber-text)' : 'var(--vy-accent-tint-text)' }}">{{ $warn ? __('livewire/owner/units.tone_action') : __('livewire/owner/units.tone_review') }}</span>
                                        @if ($item['read'])<span class="vy-mu-item-tag" style="background:rgba(90,120,80,.13);color:#4a7a3d">{{ __('livewire/owner/units.read_tag') }}</span>@endif
                                    </div>
                                    <div class="vy-mu-item-sub">{{ $item['body'] }}</div>
                                </div>
                                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
                                    {{-- Wave C — inline DECISION actions (Approve/Decline a rate proposal): REAL POST forms, CAP_ACTIONS-gated. --}}
                                    @if (! empty($item['actions']))
                                        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                            @foreach ($item['actions'] as $action)
                                                <form method="POST" action="{{ $action['route'] }}" data-guard-submit style="margin:0">
                                                    @csrf
                                                    <button type="submit" class="{{ $action['primary'] ? 'vy-btn vy-btn--accent' : 'vy-btn vy-btn--ghost' }}" style="padding:6px 13px;font-size:12px">{{ $action['label'] }}</button>
                                                </form>
                                            @endforeach
                                        @endownercan
                                    @endif
                                    <a class="vy-mu-item-link" href="{{ $item['cta']['route'] }}">{{ $item['cta']['label'] }}</a>
                                    {{-- Snooze / dismiss — REAL (owner_attention_states), CAP_ACTIONS-gated (a view-only sub-user sees the feed but can't act) --}}
                                    @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                        <div style="position:relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                                            <button type="button" @click="open = !open" aria-label="{{ __('livewire/owner/units.snooze_or_dismiss') }}" style="width:28px;height:28px;border-radius:8px;border:1px solid var(--vy-hairline);background:rgba(255,255,255,.6);color:var(--vy-muted);cursor:pointer;display:grid;place-items:center">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg>
                                            </button>
                                            <div x-show="open" x-cloak @click.outside="open = false" style="position:absolute;right:0;top:calc(100% + 6px);z-index:20;background:#fff;border:1px solid var(--vy-glass-border);border-radius:var(--vy-r-md);box-shadow:0 14px 40px rgba(150,90,50,.2);padding:6px;min-width:170px">
                                                <div style="font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-muted);padding:6px 9px 4px">{{ __('livewire/owner/units.snooze_heading') }}</div>
                                                @foreach (['tomorrow' => __('livewire/owner/units.snooze_tomorrow'), 'week' => __('livewire/owner/units.snooze_week')] as $dur => $lbl)
                                                    <form method="POST" action="{{ route('owner.attention.snooze') }}" style="margin:0">
                                                        @csrf
                                                        <input type="hidden" name="attention_key" value="{{ $item['key'] }}">
                                                        <input type="hidden" name="duration" value="{{ $dur }}">
                                                        <button type="submit" style="display:block;width:100%;text-align:left;padding:8px 9px;border:none;background:none;border-radius:8px;font:inherit;font-size:12.5px;color:var(--vy-ink);cursor:pointer" onmouseover="this.style.background='rgba(217,102,46,.08)'" onmouseout="this.style.background='none'">{{ $lbl }}</button>
                                                    </form>
                                                @endforeach
                                                <div style="border-top:1px solid var(--vy-hairline);margin:5px 4px"></div>
                                                @unless ($item['read'])
                                                    <form method="POST" action="{{ route('owner.attention.read') }}" style="margin:0">
                                                        @csrf
                                                        <input type="hidden" name="attention_key" value="{{ $item['key'] }}">
                                                        <button type="submit" style="display:block;width:100%;text-align:left;padding:8px 9px;border:none;background:none;border-radius:8px;font:inherit;font-size:12.5px;color:var(--vy-ink);cursor:pointer" onmouseover="this.style.background='rgba(217,102,46,.08)'" onmouseout="this.style.background='none'">{{ __('livewire/owner/units.mark_read') }}</button>
                                                    </form>
                                                @endunless
                                                <form method="POST" action="{{ route('owner.attention.dismiss') }}" style="margin:0">
                                                    @csrf
                                                    <input type="hidden" name="attention_key" value="{{ $item['key'] }}">
                                                    <button type="submit" style="display:block;width:100%;text-align:left;padding:8px 9px;border:none;background:none;border-radius:8px;font:inherit;font-size:12.5px;color:#8A2F27;cursor:pointer" onmouseover="this.style.background='rgba(190,32,28,.07)'" onmouseout="this.style.background='none'">{{ __('livewire/owner/units.dismiss') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endownercan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-mu-ac-empty">
                        <span class="vy-mu-ac-empty-ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:15px">{{ __('livewire/owner/units.all_caught_up') }}</div>
                        <div class="vy-line-sub">{{ __('livewire/owner/units.all_caught_up_body') }}</div>
                    </div>
                @endif

                {{-- Snoozed panel — REAL snoozed items + Undo (restore). Rendered regardless of the active count,
                     so an all-snoozed feed still shows what's snoozed. --}}
                @if (count($snoozedAttention) > 0)
                    <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--vy-hairline)">
                        <div style="font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-muted);margin-bottom:8px">{{ __('livewire/owner/units.snoozed_heading') }}</div>
                        @foreach ($snoozedAttention as $sn)
                            <div style="display:flex;align-items:center;gap:10px;padding:8px 0">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-faint)" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                <span style="flex:1;min-width:0;font-size:12.5px;color:var(--vy-muted)">{{ $sn['title'] }} <span style="color:var(--vy-muted)">{{ __('livewire/owner/units.snoozed_back', ['until' => $sn['until']]) }}</span></span>
                                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                    <form method="POST" action="{{ route('owner.attention.restore') }}" style="margin:0">
                                        @csrf
                                        <input type="hidden" name="attention_key" value="{{ $sn['key'] }}">
                                        <button type="submit" style="border:none;background:none;color:var(--vy-accent);font:inherit;font-size:12px;font-weight:600;cursor:pointer">{{ __('livewire/owner/units.undo') }}</button>
                                    </form>
                                @endownercan
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Your units --}}
    <div class="vy-mu-secttl vy-fade" style="animation-delay:.14s">
        <h2>{{ __('livewire/owner/units.your_units') }}</h2>
    </div>
    <div class="vy-mu-grid">
        @forelse ($units as $i => $u)
            <div class="vy-mu-card vy-fade" style="animation-delay:{{ 0.16 + $i * 0.05 }}s" x-data="{ panel: null, rate: {{ $u['rate_units'] ?? 0 }}, weekend: {{ $u['weekend_pct'] !== null ? (float) $u['weekend_pct'] : 0 }}, minstay: {{ $u['min_stay'] ?? 1 }} }">
                <div class="vy-mu-thumb">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/></svg>
                </div>
                <div class="vy-mu-card-body">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px">
                        <div style="min-width:0">
                            <div class="vy-mu-card-name">{{ $u['name'] }}</div>
                            <div class="vy-mu-card-sub">{{ $u['type'] }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                            @if ($u['is_best'])<span class="vy-mu-badge vy-mu-badge--best" title="{{ __('livewire/owner/units.best_title') }}">{{ __('livewire/owner/units.best_badge') }}</span>@endif
                            <span class="vy-mu-badge">{{ $u['active'] ? __('livewire/owner/units.status_active') : __('livewire/owner/units.status_paused') }}</span>
                            {{-- Wave C · GATE 3 — REAL owner pause/resume (removes/restores capacity via the certified recompute) --}}
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                <form method="POST" action="{{ route('owner.units.toggle-status', $u['id']) }}" data-guard-submit style="margin:0">
                                    @csrf
                                    <button type="submit" title="{{ $u['active'] ? __('livewire/owner/units.pause_title') : __('livewire/owner/units.resume_title') }}" style="border:1px solid var(--vy-hairline);background:rgba(255,255,255,.6);color:var(--vy-muted);border-radius:8px;padding:2px 8px;font-size:11px;font-weight:600;cursor:pointer">{{ $u['active'] ? __('livewire/owner/units.pause') : __('livewire/owner/units.resume') }}</button>
                                </form>
                            @endownercan
                        </div>
                    </div>

                    <div style="margin-top:16px">
                        <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:6px">
                            <span class="vy-mu-uplabel">{{ __('livewire/owner/units.occupancy') }}</span>
                            <span style="font-size:13px;font-weight:500;font-variant-numeric:tabular-nums">{{ $u['occupancy_pct'] }}%</span>
                        </div>
                        <div class="vy-track"><span style="width:{{ $u['occupancy_pct'] }}%"></span></div>
                        <div style="font-size:11px;color:var(--vy-muted);margin-top:5px">{{ $month['label'] }}</div>
                    </div>

                    <div class="vy-mu-stat2">
                        <div><div class="k">{{ __('livewire/owner/units.nights_sold') }}</div><div class="v">{{ $u['nights'] }}</div></div>
                        <div><div class="k">{{ __('livewire/owner/units.revenue') }}</div><div class="v">{{ $u['revenue'] }}</div></div>
                    </div>

                    <div class="vy-mu-rate-row">
                        <div style="display:flex;align-items:baseline;gap:8px">
                            <span style="font-size:11px;color:var(--vy-muted)">{{ __('livewire/owner/units.nightly_rate') }}</span>
                            <span style="font-family:var(--vy-display);font-weight:500;font-size:15px;font-variant-numeric:tabular-nums">{{ $u['nightly_rate'] ?? '—' }}</span>
                            {{-- WAVE 2 · #12 — the ACHIEVED average, under its own name. It used to be printed
                                 as "Nightly rate", which is the configured price, not what the nights sold for. --}}
                            @if ($u['adr'] !== null)
                                <span style="font-size:11px;color:var(--vy-muted)">{{ __('livewire/owner/units.adr', ['amount' => $u['adr']]) }}</span>
                            @endif
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;margin-top:11px">
                            <button type="button" class="vy-mu-cardbtn" title="{{ __('livewire/owner/units.edit_pricing') }}" @click="panel = panel === 'price' ? null : 'price'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7" cy="7" r="1.1" fill="currentColor"/></svg>
                                {{ __('livewire/owner/units.edit_pricing') }}
                            </button>
                            <button type="button" class="vy-mu-cardbtn" title="{{ __('livewire/owner/units.message_team_title') }}" @click="panel = panel === 'msg' ? null : 'msg'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3 7 9 6 9-6"/></svg>
                                {{ __('livewire/owner/units.message_guest') }}
                            </button>
                        </div>

                        {{-- Edit pricing — REAL rate change via OwnerRateService (per-room-type, exclusive-owned only,
                             CAP_ACTIONS, integer money #17). A shared room type is honestly non-editable. --}}
                        <div x-show="panel === 'price'" x-cloak class="vy-mu-panel" style="margin-top:12px">
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                @if ($u['rate_editable'])
                                    <form method="POST" action="{{ route('owner.rates.apply') }}" data-guard-submit>
                                        @csrf
                                        <input type="hidden" name="room_type_id" value="{{ $u['room_type_id'] }}">
                                        <input type="hidden" name="redirect" value="units">
                                        <input type="hidden" name="start_date" value="{{ \Carbon\CarbonImmutable::today()->toDateString() }}">
                                        <input type="hidden" name="end_date" value="{{ \Carbon\CarbonImmutable::today()->addDays(179)->toDateString() }}">
                                        <div style="font-size:11px;font-weight:500;letter-spacing:.08em;text-transform:uppercase;color:var(--vy-muted);margin-bottom:9px">{{ __('livewire/owner/units.set_nightly_rate') }}</div>
                                        <div style="display:flex;align-items:center;gap:8px">
                                            <button type="button" class="vy-mu-step" aria-label="{{ __('livewire/owner/units.decrease_rate') }}" @click="rate = Math.max(0, rate - 5)">−</button>
                                            <div class="vy-mu-rate-input">
                                                <span style="font-family:var(--vy-display);font-weight:500;font-size:16px;color:var(--vy-muted)">{{ $currencySymbol }}</span>
                                                <input type="text" inputmode="numeric" name="rate" x-model="rate" aria-label="{{ __('livewire/owner/units.rate_aria', ['unit' => $u['name']]) }}">
                                                <span style="font-size:12px;color:var(--vy-muted);white-space:nowrap">{{ __('livewire/owner/units.per_night') }}</span>
                                            </div>
                                            <button type="button" class="vy-mu-step" aria-label="{{ __('livewire/owner/units.increase_rate') }}" @click="rate = rate + 5">+</button>
                                        </div>

                                        {{-- Wave-B: WEEKEND UPLIFT + MIN STAY (consume OwnerRateService::setRateWeekendAware). The uplift
                                             writes the higher price onto Fri/Sat nights — a real change, not a flag (#39). --}}
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px">
                                            <div>
                                                <label style="font-size:11px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-muted);display:block;margin-bottom:6px">{{ __('livewire/owner/units.weekend_uplift') }}</label>
                                                <div class="vy-mu-rate-input" style="padding:6px 10px">
                                                    <input type="text" inputmode="numeric" name="weekend_uplift" x-model="weekend" aria-label="{{ __('livewire/owner/units.weekend_uplift_aria', ['unit' => $u['name']]) }}" style="width:100%;border:none;background:none;outline:none;font-family:var(--vy-display);font-weight:500;font-size:15px;color:var(--vy-ink)">
                                                    <span style="font-size:12px;color:var(--vy-muted)">%</span>
                                                </div>
                                            </div>
                                            <div>
                                                <label style="font-size:11px;font-weight:500;letter-spacing:.06em;text-transform:uppercase;color:var(--vy-muted);display:block;margin-bottom:6px">{{ __('livewire/owner/units.min_stay') }}</label>
                                                <div class="vy-mu-rate-input" style="padding:6px 10px">
                                                    <input type="text" inputmode="numeric" name="min_stay" x-model="minstay" aria-label="{{ __('livewire/owner/units.min_stay_aria', ['unit' => $u['name']]) }}" style="width:100%;border:none;background:none;outline:none;font-family:var(--vy-display);font-weight:500;font-size:15px;color:var(--vy-ink)">
                                                    <span style="font-size:12px;color:var(--vy-muted);white-space:nowrap">{{ __('livewire/owner/units.nights_suffix') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- Live preview — a real derived figure (base × (1 + uplift%)) so the owner sees the Fri/Sat price before saving --}}
                                        <div x-show="weekend > 0" x-cloak style="margin-top:9px;font-size:12px;color:#6E635C">
                                            {!! __('livewire/owner/units.weekend_preview', ['symbol' => e($currencySymbol)]) !!}
                                        </div>

                                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:12px">
                                            <button type="button" @click="rate = {{ $u['rate_units'] }}; weekend = {{ $u['weekend_pct'] !== null ? (float) $u['weekend_pct'] : 0 }}; minstay = {{ $u['min_stay'] ?? 1 }}" style="background:none;border:none;padding:0;color:var(--vy-muted);font-family:var(--vy-sans);font-size:12px;cursor:pointer;text-decoration:underline;text-underline-offset:2px">{{ __('livewire/owner/units.reset_to', ['amount' => $u['base_display']]) }}</button>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <button type="button" @click="panel = null" style="padding:7px 14px;border-radius:var(--vy-r-pill);background:transparent;border:1px solid rgba(120,70,40,.16);color:#6B5D54;font-family:var(--vy-sans);font-size:12.5px;font-weight:500;cursor:pointer">{{ __('common.cancel') }}</button>
                                                <button type="submit" class="vy-btn vy-btn--accent" style="padding:7px 16px;font-size:12.5px">{{ __('common.save') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                @else
                                    <div style="font-size:12.5px;color:#6E635C;line-height:1.5">{!! __('livewire/owner/units.shared_room_type', ['url' => e(route('owner.rates'))]) !!}</div>
                                @endif
                            @else
                                <div style="font-size:12.5px;color:#6E635C;line-height:1.5">{{ __('livewire/owner/units.rate_by_account_owner') }}</div>
                            @endownercan
                        </div>

                        {{-- Message guest — REAL relay to the operator team (D2): owner↔operator channel, name-only
                             (#28), NEVER owner→guest directly. --}}
                        <div x-show="panel === 'msg'" x-cloak class="vy-mu-panel" style="margin-top:12px">
                            <form method="POST" action="{{ route('owner.messages.start') }}" data-guard-submit>
                                @csrf
                                <input type="hidden" name="subject" value="About {{ $u['name'] }}">
                                <div style="font-size:12.5px;line-height:1.5;color:#6E635C;margin-bottom:11px">{!! __('livewire/owner/units.message_intro', ['unit' => e($u['name'])]) !!}</div>
                                <label class="vy-sr-only" for="vy-lbl-units-body">{{ __('livewire/owner/units.message_placeholder', ['unit' => $u['name']]) }}</label>
                                <textarea id="vy-lbl-units-body" name="body" rows="3" required x-ref="mbody{{ $i }}" placeholder="{{ __('livewire/owner/units.message_placeholder', ['unit' => $u['name']]) }}" style="width:100%;box-sizing:border-box;resize:none;border:1px solid rgba(217,102,46,.28);border-radius:11px;background:#fff;padding:10px 12px;font-family:var(--vy-sans);font-size:12.5px;line-height:1.5;color:var(--vy-ink);outline:none" class="vy-mu-msg-input"></textarea>
                                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:9px">
                                    @foreach ([__('livewire/owner/units.chip_update'), __('livewire/owner/units.chip_stay_ok'), __('livewire/owner/units.chip_visit')] as $chip)
                                        <button type="button" @click="$refs.mbody{{ $i }}.value = @js($chip)" style="padding:5px 10px;border-radius:var(--vy-r-pill);background:rgba(255,255,255,.7);border:1px solid rgba(120,70,40,.16);color:#7A2E08;font-family:var(--vy-sans);font-size:11px;font-weight:500;cursor:pointer">{{ $chip }}</button>
                                    @endforeach
                                </div>
                                <div style="display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:12px">
                                    <button type="button" @click="panel = null" style="padding:7px 14px;border-radius:var(--vy-r-pill);background:transparent;border:1px solid rgba(120,70,40,.16);color:#6B5D54;font-family:var(--vy-sans);font-size:12.5px;font-weight:500;cursor:pointer">{{ __('common.cancel') }}</button>
                                    <button type="submit" class="vy-btn vy-btn--accent" style="padding:7px 16px;font-size:12.5px;gap:6px">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg>
                                        {{ __('livewire/owner/units.send') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <a class="vy-mu-view" href="{{ route('owner.units.show', $u['id']) }}">
                        {{ __('livewire/owner/units.view_unit') }}
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="vy-empty" style="grid-column:1/-1">{{ __('livewire/owner/units.no_units') }}</div>
        @endforelse

        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
            <a class="vy-mu-addcard vy-fade" style="animation-delay:.42s" href="{{ route('owner.add-unit') }}">
                <span style="width:44px;height:44px;border-radius:50%;background:rgba(217,102,46,.13);display:flex;align-items:center;justify-content:center"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg></span>
                <span style="font-family:var(--vy-display);font-weight:500;font-size:15px">{{ __('livewire/owner/units.add_a_unit') }}</span>
                <span style="font-size:12px;color:var(--vy-muted)">{{ __('livewire/owner/units.list_new_property') }}</span>
            </a>
        @endownercan
    </div>

    {{-- Calendar + best performer --}}
    <div class="vy-mu-cols" style="margin-top:2px"
         x-data="{ sel: null, selLabel: '', selCount: 0, dayDetails: {{ \Illuminate\Support\Js::from($dayDetails) }}, get selStays() { return this.sel !== null ? (this.dayDetails[this.sel] || []) : []; } }">
        <section class="vy-card vy-fade" style="animation-delay:.44s">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:15px;gap:12px;flex-wrap:wrap">
                <div style="display:inline-flex;align-items:center;gap:4px">
                    <a class="vy-mu-calnav" href="{{ route('owner.units', ['month' => $month['prev']]) }}" aria-label="{{ __('livewire/owner/units.previous_month') }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></a>
                    <div style="font-family:var(--vy-display);font-weight:500;font-size:16px;min-width:130px;text-align:center">{{ $month['label'] }}</div>
                    <a class="vy-mu-calnav" href="{{ route('owner.units', ['month' => $month['next']]) }}" aria-label="{{ __('livewire/owner/units.next_month') }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></a>
                </div>
                <div style="display:inline-flex;align-items:center;gap:8px">
                    <a class="vy-mu-calbtn vy-mu-calbtn--accent" href="{{ route('owner.calendar') }}" title="{{ __('livewire/owner/units.block_dates_title') }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/><path d="M15 15l-4 4"/><path d="M11 15l4 4"/></svg>
                        {{ __('livewire/owner/units.block_dates') }}
                    </a>
                    {{-- Export → REAL owner-scoped month occupancy CSV (co-owner + accountant; counts only, #28) --}}
                    @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                        <a class="vy-mu-calbtn" href="{{ route('owner.units.export', array_filter(['month' => request('month')])) }}" title="{{ __('livewire/owner/units.export_title') }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v11"/><path d="M8 11l4 4 4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                            {{ __('common.export') }}
                        </a>
                    @endownercan
                </div>
            </div>

            @php
                // Occupancy HEAT — matches the wireframe's calendar exactly: a SOLID terracotta ramp (never a
                // translucent wash), free = a warm pale peach, deepening to the deep accent #C2410C at full
                // occupancy; numerals flip to white once a cell is dark enough for contrast. ONE definition, used
                // by both the day cells and the legend swatches so they can't drift.
                $heatBg = function (int $count, int $total): string {
                    if ($count <= 0) {
                        return '#F1E4DB';                                   // free — warm pale peach
                    }
                    $t = $total > 0 ? min(1.0, $count / $total) : 1.0;      // 0 < t ≤ 1 as more units book
                    $lerp = fn (int $a, int $b): int => (int) round($a + ($b - $a) * $t);
                    return sprintf('rgb(%d,%d,%d)', $lerp(242, 194), $lerp(215, 65), $lerp(196, 12));  // #F2D7C4 → #C2410C
                };
                $heatFg = function (int $count, int $total): string {
                    $t = $total > 0 ? $count / $total : ($count > 0 ? 1 : 0);

                    // ⚠️ #7A2E08 measured 3.57 on the mid tint (a half-booked day) — and these numerals are
                    // the DATES, the one piece of information every cell must carry. Darkened to #4A1B05:
                    // 5.47 on the darkest non-white cell and 7.09-9.07 on the lighter ones. White numerals
                    // still only on the deep cells, where they measure well above AA.
                    return $t >= 0.6 ? '#fff' : '#4A1B05';
                };
            @endphp

            <div class="vy-mu-calgrid" style="margin-bottom:6px">
                @foreach ([__('livewire/owner/units.weekday_mon'), __('livewire/owner/units.weekday_tue'), __('livewire/owner/units.weekday_wed'), __('livewire/owner/units.weekday_thu'), __('livewire/owner/units.weekday_fri'), __('livewire/owner/units.weekday_sat'), __('livewire/owner/units.weekday_sun')] as $wd)
                    <div class="vy-mu-calhead">{{ $wd }}</div>
                @endforeach
            </div>
            <div class="vy-mu-calgrid">
                @for ($i = 1; $i < $month['first_weekday']; $i++)
                    <div class="vy-mu-day is-empty"></div>
                @endfor
                @for ($dom = 1; $dom <= $occupancy['days']; $dom++)
                    @php
                        $count = $occupancy['heat'][$dom] ?? 0;
                        $bg = $heatBg($count, $occupancy['unit_count']);
                        $fg = $heatFg($count, $occupancy['unit_count']);
                        $lbl = $count === 0 ? __('livewire/owner/units.day_free') : __('livewire/owner/units.day_booked', ['booked' => $count, 'total' => $occupancy['unit_count']]);
                    @endphp
                    <div class="vy-mu-day" style="background:{{ $bg }};color:{{ $fg }}" title="{{ $lbl }}"
                         :class="sel === {{ $dom }} ? 'is-sel' : ''"
                         @click="sel = {{ $dom }}; selLabel = @js(__('livewire/owner/units.day_label', ['month' => $month['label_short'] ?? $month['label'], 'day' => $dom])); selCount = {{ $count }}">{{ $dom }}</div>
                @endfor
            </div>

            <div class="vy-mu-cal-foot">
                {{-- summary (no day selected) --}}
                <div x-show="sel === null" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <div style="display:inline-flex;align-items:center;gap:10px">
                        <span style="font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--vy-muted)">{{ __('livewire/owner/units.legend_booked') }}</span>
                        <div style="display:inline-flex;gap:4px">
                            @for ($n = 0; $n <= min($occupancy['unit_count'], 4); $n++)
                                @php $sw = $heatBg($n, $occupancy['unit_count']); @endphp
                                <span style="display:inline-flex;flex-direction:column;align-items:center;gap:4px"><span style="width:22px;height:11px;border-radius:3px;background:{{ $sw }};border:{{ $n === 0 ? '1px solid rgba(120,70,40,.12)' : 'none' }};box-sizing:border-box"></span><span style="font-size:9.5px;color:var(--vy-muted)">{{ $n }}</span></span>
                            @endfor
                        </div>
                        <span style="font-size:10px;color:var(--vy-muted)">{{ __('livewire/owner/units.of_units', ['total' => $occupancy['unit_count']]) }}</span>
                    </div>
                    <div style="font-size:12.5px;color:#6B5F57;font-weight:500;font-variant-numeric:tabular-nums">{!! trans_choice('livewire/owner/units.month_summary', $occupancy['nights'], ['count' => e($occupancy['nights']), 'pct' => e($occupancy['pct']), 'month' => e($month['label'])]) !!}</div>
                </div>
                {{-- day detail (a day selected) — the RICHER day-detail (P2-3): the booked units that night, each
                     with the guest NAME-ONLY (#28), the stay dates and the owner's net. Real data from dayDetails;
                     no guest contact ever. Falls back to the count when a stay's detail isn't attributed. --}}
                <div x-show="sel !== null" x-cloak>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                        <div style="min-width:0">
                            <div style="font-family:var(--vy-display);font-weight:600;font-size:14px" x-text="selLabel"></div>
                            <div style="font-size:12px;color:var(--vy-muted);margin-top:2px">{!! __('livewire/owner/units.selected_booked', ['total' => e($occupancy['unit_count'])]) !!}</div>
                        </div>
                        <button type="button" @click="sel = null" style="padding:5px 12px;border-radius:8px;border:1px solid rgba(120,70,40,.16);background:rgba(255,252,248,.6);color:var(--vy-accent);font-family:var(--vy-sans);font-size:11.5px;font-weight:600;cursor:pointer">{{ __('common.clear') }}</button>
                    </div>
                    <template x-if="selStays.length">
                        <div style="margin-top:12px;display:flex;flex-direction:column;gap:8px">
                            <template x-for="(stay, i) in selStays" :key="i">
                                <div style="padding:10px 12px;border-radius:10px;background:rgba(255,255,255,.5);border:1px solid var(--vy-hairline)">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                                        <span style="font-weight:600;font-size:13px" x-text="stay.guest"></span>
                                        <span style="font-size:11px;color:var(--vy-muted)" x-text="stay.unit"></span>
                                    </div>
                                    <div style="font-size:11.5px;color:var(--vy-muted);margin-top:3px"><span x-text="stay.stay"></span> · <span x-text="stay.nights_label"></span></div>
                                    <template x-if="stay.net && stay.net.length">
                                        <div style="font-size:11.5px;color:var(--vy-ink);margin-top:4px">{!! __('livewire/owner/units.your_net') !!}</div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <div class="vy-stack">
            <div class="vy-ov-payout vy-fade" style="animation-delay:.48s">
                <div class="vy-ov-payout-halo"></div>
                <section class="vy-card vy-card--dark">
                    <span class="vy-eyebrow">{{ __('livewire/owner/units.best_performer') }}</span>
                    @if ($best)
                        <div class="vy-dark-figure" style="margin-top:10px;font-size:22px">{{ $best['name'] }}</div>
                        <div style="display:flex;align-items:baseline;gap:8px;margin-top:8px">
                            <span style="font-family:var(--vy-display);font-weight:600;font-size:30px;letter-spacing:-.02em;color:#fff;font-variant-numeric:tabular-nums">{{ $best['occupancy_pct'] }}%</span>
                            <span style="font-size:12.5px;color:var(--vy-peach);font-weight:500">{{ __('livewire/owner/units.best_occupancy') }}</span>
                        </div>
                        <div style="font-size:13px;color:rgba(255,255,255,.72);margin-top:5px">{{ trans_choice('livewire/owner/units.best_nights_revenue', $best['nights'], ['count' => $best['nights'], 'revenue' => $best['revenue']]) }}</div>
                    @else
                        <div style="margin-top:12px;font-size:13px;color:rgba(255,255,255,.7);line-height:1.6">{{ __('livewire/owner/units.best_empty') }}</div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-owner.shell>
