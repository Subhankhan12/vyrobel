{{-- Operator MESSAGES — the unified inbox (#90 exact-match), rebuilt to Operator Messages.html on the REAL
     MessageThread/Message models via InboxService. Rail (conversations) + active thread + Assign / New-message
     modals. FULL guest data (the manager) — name + channel + reservation context; the encrypted contact is
     NEVER rendered (#28). Reply/assign/new-message are POST routes (#20). RAW-BLADE RULE: every `@` follows a
     space, newline or tag. --}}
<div x-data="{
        assignOpen: false, linkOpen: false,
        composeOpen: false, composeGuest: null, composeTemplate: '', composeBody: '', composeFind: '',
        composeData: @js(collect($composeGuests)->mapWithKeys(fn ($g) => [$g['id'] => $g['templates']])),
        composeAccount: @js(collect($composeGuests)->mapWithKeys(fn ($g) => [$g['id'] => $g['has_account']])),
        composeNames: @js(collect($composeGuests)->mapWithKeys(fn ($g) => [$g['id'] => $g['name']])),
        composeName() { return this.composeGuest ? (this.composeNames[this.composeGuest] || 'This guest') : ''; },
        pickComposeGuest(id) { this.composeGuest = id; this.refillCompose(); },
        pickComposeTemplate(key) { this.composeTemplate = (this.composeTemplate === key ? '' : key); this.refillCompose(); },
        refillCompose() {
            // Pre-fill the editable body from the chosen template merged for the chosen guest; the operator then
            // reviews/edits and THAT text is what sends. Never overwrites text the operator has already typed for
            // a different template without an explicit template pick.
            if (this.composeGuest && this.composeTemplate && this.composeData[this.composeGuest]) {
                this.composeBody = this.composeData[this.composeGuest][this.composeTemplate] || '';
            }
        }
    }">
    <x-operator.shell active="inbox" title="Messages" :subtitle="$summary['unread'].' unread'" :viewer="$viewer">

        @if (session('inbox_notice')) <div class="vy-flash is-ok vy-fade">{{ session('inbox_notice') }}</div> @endif
        @if (session('inbox_error')) <div class="vy-flash is-err vy-fade">{{ session('inbox_error') }}</div> @endif

        @php
            // Compose the inbox GET params so filter / channel / sort / search / active-thread stay together as
            // the operator switches any one of them. A null override drops that key (e.g. filter → All). `sort`
            // only rides when non-default (oldest), keeping URLs clean.
            $inboxParams = array_filter([
                'filter' => $filter,
                'channel' => $channel,
                'sort' => $sort === 'oldest' ? 'oldest' : null,
                'q' => $query !== '' ? $query : null,
                'c' => request('c'),
            ], fn ($v) => $v !== null && $v !== '');
            $inboxUrl = function (array $over) use ($inboxParams) {
                return route('app.inbox', array_filter(array_merge($inboxParams, $over), fn ($v) => $v !== null && $v !== ''));
            };
        @endphp

        {{-- Stat strip — REAL derived counts (Unread / Open / Waiting on guest / Needs sending) --}}
        <div class="vy-inbox-stats vy-fade">
            <a class="vy-istat {{ $filter === 'unread' ? 'is-on' : '' }}" href="{{ $inboxUrl(['filter' => $filter === 'unread' ? null : 'unread']) }}">
                <span class="vy-istat-dot" style="background:#E8703A"></span>
                <span class="vy-istat-num" style="color:#B4400F">{{ $stats['unread'] }}</span><span class="vy-istat-label">Unread</span>
            </a>
            <div class="vy-istat">
                <span class="vy-istat-dot" style="background:#2A6FDB"></span>
                <span class="vy-istat-num">{{ $stats['open'] }}</span><span class="vy-istat-label">Open</span>
            </div>
            <div class="vy-istat">
                <span class="vy-istat-dot" style="background:#D99A3D"></span>
                <span class="vy-istat-num">{{ $stats['waiting'] }}</span><span class="vy-istat-label">Waiting on guest</span>
            </div>
            <div class="vy-istat {{ $stats['needs_sending'] > 0 ? 'is-alert' : '' }}">
                <span class="vy-istat-dot" style="background:#C0392B"></span>
                <span class="vy-istat-num" style="{{ $stats['needs_sending'] > 0 ? 'color:#C0392B' : '' }}">{{ $stats['needs_sending'] }}</span><span class="vy-istat-label">Needs sending</span>
            </div>
        </div>

        {{-- Toolbar: status tabs + New message --}}
        <div class="vy-rack-toolbar vy-ibx-bar vy-fade">
            <div class="vy-left">
                <div class="vy-seg vy-inbox-tabs">
                    @foreach ([[null, 'All'], ['unread', 'Unread'], ['arriving', 'Arriving today'], ['mine', 'Assigned to me'], ['closed', 'Closed']] as [$tabKey, $tabLabel])
                        <a class="vy-seg-opt {{ $filter === $tabKey ? 'is-checked' : '' }}" href="{{ $inboxUrl(['filter' => $tabKey]) }}" @if ($filter === $tabKey) aria-current="page" @endif>{{ $tabLabel }}</a>
                    @endforeach
                </div>
            </div>
            <div class="vy-right">
                @unless ($readOnly)
                    <button type="button" class="vy-btn vy-btn--accent" @click="composeOpen = true">New message</button>
                @else
                    <span class="vy-pill is-soon" aria-disabled="true">Read-only</span>
                @endunless
            </div>
        </div>

        {{-- Channel filter (only channels actually present) + sort toggle + active-filter chips --}}
        <div class="vy-inbox-subbar vy-fade">
            <div class="vy-inbox-chan">
                @foreach ($channelTabs as $ch)
                    <a class="vy-chan-tab {{ $ch['active'] ? 'is-on' : '' }}" href="{{ $inboxUrl(['channel' => $ch['key'] === 'all' ? null : $ch['key']]) }}">
                        {{ $ch['label'] }} <span class="vy-chan-n">{{ $ch['count'] }}</span>
                    </a>
                @endforeach
            </div>
            <div class="vy-inbox-subright">
                @if ($chips !== [])
                    <div class="vy-inbox-chips">
                        @foreach ($chips as $chip)
                            <a class="vy-fchip" href="{{ $inboxUrl([$chip['clear_filter'] ? 'filter' : 'channel' => null]) }}">
                                {{ $chip['label'] }}
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
                            </a>
                        @endforeach
                        <a class="vy-fchip-clear" href="{{ $inboxUrl(['filter' => null, 'channel' => null]) }}">Clear all</a>
                    </div>
                @endif
                <a class="vy-sort-toggle" href="{{ $inboxUrl(['sort' => $sort === 'oldest' ? null : 'oldest']) }}" title="Toggle sort order">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h13M3 12h9M3 18h5M17 8l4-4 4 4M21 4v16" transform="translate(-4)"/></svg>
                    {{ $sort === 'oldest' ? 'Oldest first' : 'Newest first' }}
                </a>
            </div>
        </div>

        {{-- 3-pane: rail + active thread --}}
        <div class="vy-inbox-grid vy-fade">

            {{-- Rail --}}
            <aside class="vy-card vy-card--flush vy-inbox-rail">
                <div class="vy-inbox-search">
                    {{-- 2026-10-05 — the shared search box (its icon, 40 px), as on every list page --}}
                    <form method="GET" action="{{ route('app.inbox') }}" class="vy-gl-search vy-ibx-search" role="search">
                        @if ($filter) <input type="hidden" name="filter" value="{{ $filter }}"> @endif
                        @if ($channel) <input type="hidden" name="channel" value="{{ $channel }}"> @endif
                        @if ($sort === 'oldest') <input type="hidden" name="sort" value="oldest"> @endif
                        <svg class="vy-gl-search-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                        <label class="vy-sr-only" for="vy-lbl-operator-inbox-q">Search guests and messages</label>
                        <input id="vy-lbl-operator-inbox-q" type="search" name="q" value="{{ $query }}" class="vy-gl-search-in" placeholder="Search guests &amp; messages" autocomplete="off">
                    </form>
                </div>
                <div class="vy-inbox-list">
                    @forelse ($rows as $row)
                        <a href="{{ $inboxUrl(['c' => $row['id']]) }}"
                           class="vy-conv {{ $active && $active['id'] === $row['id'] ? 'is-active' : '' }} {{ $row['is_unread'] ? 'is-unread' : '' }}">
                            <div class="vy-conv-avatar">{{ $row['initials'] }}</div>
                            <div class="vy-conv-body">
                                <div class="vy-conv-top">
                                    <span class="vy-conv-name">{{ $row['name'] }}</span>
                                    <span class="vy-conv-time">{{ $row['time'] }}</span>
                                </div>
                                <div class="vy-conv-preview">{{ $row['preview'] }}</div>
                                <div class="vy-conv-meta">
                                    <span class="vy-chan-badge vy-chan--{{ $row['channel_tone'] }}">{{ $row['channel_label'] }}</span>
                                    @if ($row['context']) <span class="vy-conv-ctx">{{ $row['context'] }}</span> @endif
                                    @if ($row['assignee']) <span class="vy-conv-assignee" title="Assigned">{{ $row['assignee'] }}</span> @endif
                                </div>
                            </div>
                            @if ($row['is_unread']) <span class="vy-conv-dot"></span> @endif
                        </a>
                    @empty
                        <div class="vy-empty" style="padding: 30px 16px;">{{ $query !== '' || $filter ? 'No conversations match.' : 'No conversations yet.' }}</div>
                    @endforelse
                </div>
            </aside>

            {{-- Active thread --}}
            <section class="vy-card vy-card--flush vy-inbox-thread">
                @if ($active)
                    <header class="vy-thread-head">
                        <div class="vy-thread-who">
                            <div class="vy-conv-avatar">{{ $active['initials'] }}</div>
                            <div>
                                {{-- D10-5 — VIP, the SAME badge the front desk shows for the same real
                                     guests.is_vip flag: identical classes, identical dot, identical word. An
                                     operational flag, not contact PII. Omitted for everyone else, which is
                                     almost everyone — a badge that appeared for all would say nothing. --}}
                                <div class="vy-thread-name">{{ $active['name'] }}@if ($active['guest']['is_vip'])<span class="vy-tag tone-vip" style="margin-left: 6px;"><span class="vy-dot"></span>VIP</span>@endif</div>
                                <div class="vy-thread-sub">
                                    <span class="vy-chan-badge vy-chan--{{ $active['channel_tone'] }}">{{ $active['channel_label'] }}</span>
                                    @if ($active['guest']['stat']) <span>· {{ $active['guest']['stat'] }}</span> @endif
                                    @if ($active['assignee']) <span>· Assigned to {{ $active['assignee']['name'] }}</span> @endif
                                </div>
                            </div>
                        </div>
                        <div class="vy-thread-actions">
                            @unless ($readOnly)
                                <button type="button" class="vy-btn vy-btn--ghost" @click="assignOpen = true">Assign</button>
                                {{-- #104 GATE 3a — snooze / reopen: the routes + InboxService methods existed but had no
                                     UI, leaving a CLOSED thread a dead-end. Both are POST → controller → PRG. --}}
                                @if ($active['can_snooze'])
                                    <form method="POST" action="{{ route('app.inbox.snooze', $active['id']) }}" style="display:inline">@csrf
                                        <button type="submit" class="vy-btn vy-btn--ghost">Snooze</button>
                                    </form>
                                @endif
                                @if ($active['can_reopen'])
                                    <form method="POST" action="{{ route('app.inbox.reopen', $active['id']) }}" style="display:inline">@csrf
                                        <button type="submit" class="vy-btn vy-btn--accent">Reopen</button>
                                    </form>
                                @endif
                            @endunless
                            @if ($active['reservation'])
                                <a class="vy-btn vy-btn--ghost" href="{{ route('app.reservations.show', $active['reservation']['id']) }}">{{ $active['reservation']['reference'] }}</a>
                            @endif
                            {{-- #90 — the link-reservation control (route + service existed; no UI until now). Only for a
                                 guest thread with reservations to link. --}}
                            @if (! $readOnly && $active['has_guest'] && $active['link_reservations'] !== [])
                                <button type="button" class="vy-btn vy-btn--ghost" @click="linkOpen = true">{{ $active['reservation'] ? 'Change link' : 'Link reservation' }}</button>
                            @endif
                        </div>
                    </header>

                    {{-- The design carries a reservation CONTEXT strip under the thread header, so a replier can
                         see who they are answering without leaving the conversation. Every field here is already
                         in reservationCard() — dates, unit, status — so this renders real data or nothing; the
                         design's "Party" and "Balance" are NOT in that payload and are logged, never invented
                         (#39). The guest-profile link is gated on a real guest_id and goes to the CRM, which
                         sits behind the SAME guests.view permission as this inbox — no widening (#28). --}}
                    @if ($active['reservation'])
                        <div class="vy-thread-context">
                            <span class="vy-tc-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 11h18"/></svg>
                                {{ $active['reservation']['dates'] }}
                            </span>
                            @if ($active['reservation']['unit'])
                                <span class="vy-tc-item">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5"/></svg>
                                    {{ $active['reservation']['unit'] }}
                                </span>
                            @endif
                            {{-- D10-2 — the stage chip the note here used to defer. The objection was exact and
                                 still stands: meta() needs a computed STAGE, not the raw status. So the card now
                                 carries `stage_meta`, resolved by ReservationStage in the COMPONENT, and this
                                 template renders it with the same markup the Reservations list uses — one
                                 resolver, one badge, no vocabulary re-derived in Blade (§N.8f). --}}
                            <span class="vy-tag tone-{{ $active['reservation']['stage_meta']['tone'] }}"><span class="vy-dot"></span>{{ $active['reservation']['stage_meta']['label'] }}</span>
                            @if ($active['guest']['guest_id'] ?? null)
                                <a class="vy-tc-link" href="{{ route('app.guests.show', $active['guest']['guest_id']) }}">View guest profile</a>
                            @endif
                        </div>
                    @endif

                    {{-- Latest INTERNAL note — staff-only (guests never see this); name-only author (#28). --}}
                    @if ($active['note'])
                        <div class="vy-thread-note" title="Internal — the guest never sees this">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12H7l-3 3z"/></svg>
                            <span><strong>Internal · {{ $active['note']['author'] }}</strong> · {{ $active['note']['time'] }} — {{ $active['note']['body'] }}</span>
                        </div>
                    @endif

                    <div class="vy-thread-scroll">
                        @php $lastDay = null; @endphp
                        @foreach ($messages as $m)
                            @if ($m['day_label'] !== $lastDay)
                                <div class="vy-day-divider"><span>{{ $m['day_label'] }}</span></div>
                                @php $lastDay = $m['day_label']; @endphp
                            @endif
                            <div class="vy-msg {{ $m['out'] ? 'is-out' : 'is-in' }}">
                                @if ($m['body'] !== '')
                                    <div class="vy-msg-bubble">{{ $m['body'] }}</div>
                                @endif
                                {{-- DEC-m (2026-09-30) — what the owner attached. Each file opens through the inbox's own signed-in
                                     route, for staff who may open this inbox; a photo shows in place, anything else downloads. --}}
                                @foreach ($m['attachments'] as $att)
                                    @if ($att['is_image'])
                                        <a class="vy-msg-att-img-link" href="{{ $att['url'] }}" target="_blank" rel="noopener" title="Open {{ $att['name'] }}"><img class="vy-msg-att-img" src="{{ $att['url'] }}" alt="{{ $att['name'] }}" loading="lazy"></a>
                                    @else
                                        <a class="vy-msg-att-file" href="{{ $att['url'] }}" title="Download {{ $att['name'] }}">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.4 11.1l-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg>
                                            <span class="vy-msg-att-name">{{ $att['name'] }}</span>
                                            <span class="vy-msg-att-size">{{ $att['size'] }}</span>
                                        </a>
                                    @endif
                                @endforeach
                                <div class="vy-msg-meta">
                                    <span>{{ $m['author'] }} · {{ $m['time'] }}</span>
                                    @if ($m['delivery'])
                                        <span class="vy-msg-delivery{{ $m['delivery']['failed'] ? ' is-failed' : '' }}">{{ $m['delivery']['label'] }}</span>
                                    @endif
                                    @if ($m['delivery'] && $m['delivery']['failed'] && ! $readOnly)
                                        {{-- REAL re-send (never a fake flip-to-delivered) — delivers on manual, fails again honestly on a deferred channel. --}}
                                        <form method="POST" action="{{ route('app.inbox.retry', [$active['id'], $m['id']]) }}" style="display:inline">@csrf
                                            <button type="submit" class="vy-msg-retry">Retry</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if (count($messages) === 0)
                            <div class="vy-empty" style="padding: 30px;">No messages yet — start the conversation below.</div>
                        @endif
                    </div>

                    {{-- Reply composer --}}
                    <footer class="vy-thread-composer">
                        @if ($readOnly)
                            <div class="vy-composer-note">Workspace paused — replies are read-only.</div>
                        @elseif ($active['status'] === 'closed')
                            {{-- #104 GATE 3a — a closed thread is no longer a dead-end: an honest closed note + Reopen (header). --}}
                            <div class="vy-composer-note">This conversation is closed — reopen it to reply.</div>
                        @elseif (! $active['can_reply'])
                            <div class="vy-composer-note">{{ $active['channel_label'] }} isn’t connected yet — you can’t send on this channel.</div>
                        @else
                            <form method="POST" action="{{ route('app.inbox.reply', $active['id']) }}" class="vy-composer-form">
                                @csrf
                                @php $replyWord = $active['is_direct'] ? 'Write a reply…' : 'Reply via '.$active['channel_label'].'…'; @endphp
                                <label class="vy-sr-only" for="vy-lbl-operator-inbox-body">{{ $replyWord }}</label>
                                <textarea id="vy-lbl-operator-inbox-body" name="body" rows="2" class="vy-input" placeholder="{{ $replyWord }}" required></textarea>
                                <div class="vy-composer-actions">
                                    @if ($active['can_close'])
                                        <button form="close-{{ $active['id'] }}" class="vy-btn vy-btn--ghost">Close</button>
                                    @endif
                                    <button type="submit" class="vy-btn vy-btn--accent">Reply</button>
                                </div>
                            </form>
                            @if ($active['can_close'])
                                <form id="close-{{ $active['id'] }}" method="POST" action="{{ route('app.inbox.close', $active['id']) }}">@csrf</form>
                            @endif
                        @endif
                        <p class="vy-composer-hint">
                            @if ($active['is_owner_thread'])
                                Your owner replies in their portal · this thread is not visible to the guest.
                            @elseif ($active['is_direct'])
                                {{-- ⚠️ 2026-10-05 — Direct sends nothing anywhere: the guest reads it in their own guest account, if they have one --}}
                                @if ($active['guest']['has_account'])
                                    {{ $active['name'] }} reads this in their guest account — it isn’t emailed.
                                @else
                                    {{ $active['name'] }} has no guest account, so this stays here as a record — it isn’t emailed.
                                @endif
                            @else
                                The guest replies wherever they booked · contact details stay private.
                            @endif
                        </p>
                    </footer>
                @else
                    <div class="vy-ibx-pane-empty">
                        <span class="vy-ibx-pane-ico" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H8l-4 4z"/></svg></span>
                        <strong>Pick a conversation</strong>
                        <span>Or start one with a guest who is staying, arriving or has just left.</span>
                        @unless ($readOnly)
                            <button type="button" class="vy-btn vy-btn--accent" @click="composeOpen = true">New message</button>
                        @endunless
                    </div>
                @endif
            </section>
        </div>

        {{-- ── Assign — INTERNAL hand-to-staff (guests never see this); the shared solid card (2026-10-05) ───────────── --}}
        @if ($active)
        <div x-show="assignOpen" x-cloak class="vy-modal-backdrop" @click.self="assignOpen = false" @keydown.escape.window="assignOpen = false">
            <div class="vy-modal vy-modal-card vy-ibx-dlg" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="assignOpen" aria-labelledby="vy-dlg-operator-inbox-assignopen-3">
                <form method="POST" action="{{ route('app.inbox.assign', $active['id']) }}">
                    @csrf
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M19 8v6M16 11h6"/></svg></span>
                        <div class="vy-ibx-head-text">
                            <h2 class="vy-modal-title" id="vy-dlg-operator-inbox-assignopen-3">Assign conversation</h2>
                            <p class="vy-modal-sub">{{ $active['name'] }} · they get a notification in Vyrobel, and the conversation stays in this shared inbox.</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="assignOpen = false" aria-label="Close"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="vy-modal-body">
                        <fieldset class="vy-ibx-pick">
                            <legend class="vy-field-label">Assign to</legend>
                            <div class="vy-ibx-options">
                                <label class="vy-ibx-option">
                                    <input type="radio" name="user_id" value="">
                                    <span><strong>Unassigned</strong><span class="vy-ibx-option-sub">No one in particular</span></span>
                                </label>
                                @foreach ($staff as $member)
                                    <label class="vy-ibx-option">
                                        <input type="radio" name="user_id" value="{{ $member['id'] }}" @checked($active['assignee'] && $active['assignee']['name'] === $member['name'])>
                                        <span><strong>{{ $member['name'] }}</strong><span class="vy-ibx-option-sub">{{ $member['department'] }} · {{ $member['role_label'] }}</span></span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        {{-- REAL editable internal note (staff-only). Persists as a thread Note on submit; empty → no note. --}}
                        <div class="vy-field vy-ibx-field">
                            <label class="vy-field-label" for="vy-ibx-note">Note for the team · optional</label>
                            @if ($active['note'])
                                <div class="vy-assign-lastnote">Latest · {{ $active['note']['author'] }}: {{ \Illuminate\Support\Str::limit($active['note']['body'], 120) }}</div>
                            @endif
                            <textarea id="vy-ibx-note" name="note" rows="2" class="vy-input vy-ibx-textarea" placeholder="Why you’re handing this over"></textarea>
                            <span class="vy-ibx-hint">Staff only — the guest never sees notes.</span>
                        </div>
                    </div>
                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="assignOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Assign</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Link a reservation — attach/change/detach the thread's reservation (real, tenant-scoped) ── --}}
        @if ($active['has_guest'] && $active['link_reservations'] !== [])
        <div x-show="linkOpen" x-cloak class="vy-modal-backdrop" @click.self="linkOpen = false" @keydown.escape.window="linkOpen = false">
            <div class="vy-modal vy-modal-card vy-ibx-dlg" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="linkOpen" aria-labelledby="vy-dlg-operator-inbox-linkopen-2">
                <form method="POST" action="{{ route('app.inbox.link-reservation', $active['id']) }}">
                    @csrf
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 11h18"/></svg></span>
                        <div class="vy-ibx-head-text">
                            <h2 class="vy-modal-title" id="vy-dlg-operator-inbox-linkopen-2">Link a reservation</h2>
                            <p class="vy-modal-sub">{{ $active['name'] }} · pick which booking this conversation is about.</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="linkOpen = false" aria-label="Close"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="vy-modal-body">
                        <fieldset class="vy-ibx-pick">
                            <legend class="vy-sr-only">Reservation</legend>
                            <div class="vy-ibx-options">
                                <label class="vy-ibx-option">
                                    <input type="radio" name="reservation_id" value="" @checked(! $active['reservation'])>
                                    <span><strong>Not linked</strong><span class="vy-ibx-option-sub">No reservation</span></span>
                                </label>
                                @foreach ($active['link_reservations'] as $r)
                                    <label class="vy-ibx-option">
                                        <input type="radio" name="reservation_id" value="{{ $r['id'] }}" @checked($r['linked'])>
                                        <span><strong>{{ $r['label'] }}</strong></span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>
                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="linkOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Save link</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @endif

        {{-- ── New message — the shared solid card (2026-10-05, his "2 text is repeated … must have better design"): each thing
             said once, a guest picker with a finder, templates as optional chips, one label per field, and what really happens to
             the message — Direct keeps it in this inbox and the guest's own account; nothing is emailed. ───────────────────── --}}
        <div x-show="composeOpen" x-cloak class="vy-modal-backdrop" @click.self="composeOpen = false" @keydown.escape.window="composeOpen = false">
            <div class="vy-modal vy-modal-card vy-ibx-dlg vy-ibx-compose" role="dialog" aria-modal="true" tabindex="-1" x-trap.noscroll="composeOpen" aria-labelledby="vy-dlg-operator-inbox-composeopen-1">
                @if ($composeGuests !== [])
                    <form method="POST" action="{{ route('app.inbox.start') }}">
                        @csrf
                        <input type="hidden" name="guest_id" :value="composeGuest">
                        <input type="hidden" name="template" :value="composeTemplate">
                        <div class="vy-modal-head">
                            <span class="vy-modal-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H8l-4 4z"/></svg></span>
                            <div class="vy-ibx-head-text">
                                <h2 class="vy-modal-title" id="vy-dlg-operator-inbox-composeopen-1">New message</h2>
                                <p class="vy-modal-sub">Guests staying now, arriving soon, or who left in the last 14 days.</p>
                            </div>
                            <button type="button" class="vy-modal-x" @click="composeOpen = false" aria-label="Close"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                        </div>
                        <div class="vy-modal-body">
                            <fieldset class="vy-ibx-pick">
                                <legend class="vy-field-label">Guest</legend>
                                <div class="vy-gl-search vy-ibx-find">
                                    <svg class="vy-gl-search-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                                    <label class="vy-sr-only" for="vy-ibx-find">Find a guest</label>
                                    <input id="vy-ibx-find" type="search" class="vy-gl-search-in" placeholder="Find a guest" x-model="composeFind" autocomplete="off" @keydown.enter.prevent>
                                </div>
                                <div class="vy-ibx-options vy-ibx-guests">
                                    @foreach ($composeGuests as $g)
                                        <label class="vy-ibx-option" x-show="composeFind.trim() === '' || @js(mb_strtolower($g['name'])).includes(composeFind.trim().toLowerCase())">
                                            <input type="radio" name="_guest_pick" value="{{ $g['id'] }}" @click="pickComposeGuest(@js($g['id']))">
                                            <span class="vy-conv-avatar vy-ibx-avatar" aria-hidden="true">{{ $g['initials'] }}</span>
                                            <span class="vy-ibx-option-text"><strong>{{ $g['name'] }}</strong><span class="vy-ibx-option-sub">{{ $g['meta'] }}</span></span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <fieldset class="vy-ibx-pick vy-ibx-tpls">
                                <legend class="vy-field-label">Start from a template · optional</legend>
                                <div class="vy-template-row">
                                    @foreach ($templates as $t)
                                        <button type="button" class="vy-template-chip" :class="composeTemplate === @js($t['key']) ? 'is-on' : ''" :aria-pressed="composeTemplate === @js($t['key']) ? 'true' : 'false'" @click="pickComposeTemplate(@js($t['key']))">{{ $t['label'] }}</button>
                                    @endforeach
                                </div>
                            </fieldset>
                            {{-- The REAL editable body — a template pre-fills it (merged for the picked guest); the operator reviews + edits,
                                 and this exact text is what sends (`body` wins over `template` server-side). --}}
                            <div class="vy-field vy-ibx-field">
                                <label class="vy-field-label" for="vy-ibx-compose-body">Message</label>
                                <textarea id="vy-ibx-compose-body" name="body" x-model="composeBody" rows="5" class="vy-input vy-ibx-textarea" placeholder="Write your message…"></textarea>
                            </div>
                            <p class="vy-ibx-note" aria-live="polite">
                                <span x-show="! composeGuest">Kept in this inbox — pick a guest to see where they’ll read it.</span>
                                <span x-show="composeGuest && composeAccount[composeGuest]" x-cloak><span x-text="composeName()"></span> can read it in their guest account.</span>
                                <span x-show="composeGuest && ! composeAccount[composeGuest]" x-cloak><span x-text="composeName()"></span> has no guest account, so it stays here as a record.</span>
                                It isn’t emailed.
                            </p>
                        </div>
                        <div class="vy-modal-foot">
                            <button type="button" class="vy-btn vy-btn--ghost" @click="composeOpen = false">Cancel</button>
                            <button type="submit" class="vy-btn vy-btn--accent" :disabled="! composeGuest">Start conversation</button>
                        </div>
                    </form>
                @else
                    {{-- nobody qualifies: say why and offer the way on — the guest profile's Message works for any guest --}}
                    <div class="vy-modal-head">
                        <span class="vy-modal-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H8l-4 4z"/></svg></span>
                        <div class="vy-ibx-head-text">
                            <h2 class="vy-modal-title" id="vy-dlg-operator-inbox-composeopen-1">New message</h2>
                            <p class="vy-modal-sub">No guest to message from here yet.</p>
                        </div>
                        <button type="button" class="vy-modal-x" @click="composeOpen = false" aria-label="Close"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                    <div class="vy-modal-body">
                        <div class="vy-ibx-empty">
                            <p>A guest shows up here while they’re staying, when they arrive soon, or for 14 days after they leave.</p>
                            <p>To write to anyone else, open them in Guests and use <strong>Message</strong> on their profile.</p>
                        </div>
                    </div>
                    <div class="vy-modal-foot">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="composeOpen = false">Close</button>
                        <a class="vy-btn vy-btn--accent" href="{{ route('app.guests') }}">Go to Guests</a>
                    </div>
                @endif
            </div>
        </div>

    </x-operator.shell>

    <style>
        [x-cloak] { display: none !important; }
        /* stat strip */
        .vy-inbox-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px; }
        .vy-istat { display: flex; align-items: center; gap: 9px; padding: 12px 15px; border-radius: 14px; background: rgba(255, 252, 248, 0.66); backdrop-filter: blur(20px) saturate(1.1); -webkit-backdrop-filter: blur(20px) saturate(1.1); border: 1px solid var(--vy-glass-border, rgba(150,90,50,.12)); text-decoration: none; color: var(--vy-ink); transition: border-color .14s, transform .1s; }
        a.vy-istat:hover { transform: translateY(-1px); }
        a.vy-istat.is-on { border-color: var(--vy-accent, #E8703A); box-shadow: 0 0 0 1px var(--vy-accent, #E8703A) inset; }
        .vy-istat.is-alert { border-color: rgba(192,57,43,.4); }
        .vy-istat-dot { width: 9px; height: 9px; border-radius: 50%; flex: none; }
        .vy-istat-num { font-family: 'Sora', sans-serif; font-size: 20px; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; }
        .vy-istat-label { font-size: 12px; color: var(--vy-muted); line-height: 1.2; }
        /* channel filter + sort + chips subbar */
        .vy-inbox-subbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; min-height: 30px; }
        .vy-inbox-chan { display: flex; gap: 6px; flex-wrap: wrap; }
        .vy-chan-tab { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; border: 1px solid var(--vy-hairline); background: none; font-size: 12px; font-weight: 500; color: var(--vy-muted); text-decoration: none; }
        .vy-chan-tab:hover { color: var(--vy-ink); }
        .vy-chan-tab.is-on { background: var(--vy-accent, #E8703A); color: #fff; border-color: var(--vy-accent, #E8703A); }
        .vy-chan-n { font-size: 10.5px; opacity: .8; font-variant-numeric: tabular-nums; }
        .vy-inbox-subright { display: flex; align-items: center; gap: 10px; margin-left: auto; }
        .vy-inbox-chips { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .vy-fchip { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; background: rgba(217,102,46,.1); color: #B4400F; font-size: 11.5px; font-weight: 600; text-decoration: none; }
        .vy-fchip:hover { background: rgba(217,102,46,.16); }
        .vy-fchip-clear { padding: 4px 10px; font-size: 11.5px; color: var(--vy-faint); text-decoration: none; }
        .vy-fchip-clear:hover { color: #B4400F; }
        .vy-sort-toggle { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; border: 1px solid var(--vy-hairline); background: none; font-size: 12px; font-weight: 500; color: var(--vy-muted); text-decoration: none; white-space: nowrap; }
        .vy-sort-toggle:hover { color: var(--vy-ink); border-color: var(--vy-accent-border, rgba(217,102,46,.3)); }
        @media (max-width: 720px) { .vy-inbox-stats { grid-template-columns: repeat(2, 1fr); } }
        /* the filter tabs (2026-10-06 — 🖥 seen live on rel_AY at 390 px: "Closed" cut to "Clos"; the shared box's overflow: hidden let
           it shrink under its own words). Five in one row above 560 px, where the toolbar always gives them a line of their own (they
           need ~394 px); at 560 px and below two even rows — All · Unread · Arriving today over Assigned to me · Closed — every word
           shown, a label wrapping rather than cut on the narrowest phones, and New message across the line beneath. */
        .vy-rack-toolbar.vy-ibx-bar { margin-bottom: 10px; }
        .vy-inbox-tabs { max-width: 560px; }
        @media (max-width: 560px) {
            .vy-ibx-bar > .vy-left, .vy-ibx-bar > .vy-right { flex: 1 1 100%; }
            .vy-ibx-bar > .vy-right .vy-btn { flex: 1; justify-content: center; }
            .vy-inbox-tabs { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); width: 100%; max-width: none; }
            .vy-inbox-tabs > .vy-seg-opt { grid-column: span 2; white-space: normal; padding: 9px 6px; line-height: 1.25; }
            .vy-inbox-tabs > .vy-seg-opt:nth-child(n + 4) { grid-column: span 3; }
        }
        .vy-inbox-grid { display: grid; grid-template-columns: 340px 1fr; gap: 16px; align-items: start; }
        .vy-inbox-rail { display: flex; flex-direction: column; max-height: 72vh; }
        .vy-inbox-list { overflow-y: auto; }
        .vy-conv { display: flex; gap: 11px; align-items: flex-start; padding: 12px 14px; border-bottom: 1px solid var(--vy-hairline); text-decoration: none; color: var(--vy-ink); position: relative; transition: background .12s; }
        .vy-conv:hover { background: rgba(120,70,40,.04); }
        .vy-conv.is-active { background: rgba(217,102,46,.09); }
        .vy-conv-avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--vy-accent-wash, #F6E4D6); color: var(--vy-accent); font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .vy-conv-body { flex: 1; min-width: 0; }
        .vy-conv-top { display: flex; justify-content: space-between; gap: 8px; }
        .vy-conv-name { font-size: 13.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-conv.is-unread .vy-conv-name { font-weight: 700; }
        .vy-conv-time { font-size: 11px; color: var(--vy-faint); white-space: nowrap; }
        .vy-conv-preview { font-size: 12.5px; color: var(--vy-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 2px 0 5px; }
        .vy-conv-meta { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .vy-conv-ctx { font-size: 10.5px; color: var(--vy-faint); }
        .vy-conv-assignee { margin-left: auto; width: 20px; height: 20px; border-radius: 50%; background: var(--vy-hairline); color: var(--vy-muted); font-size: 9px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .vy-conv-dot { position: absolute; top: 16px; right: 12px; width: 8px; height: 8px; border-radius: 50%; background: var(--vy-accent); }
        .vy-chan-badge { font-size: 10px; font-weight: 600; padding: 1px 7px; border-radius: 20px; background: rgba(120,70,40,.08); color: var(--vy-muted); }
        .vy-chan--direct, .vy-chan--manual { background: rgba(31,110,75,.12); color: #1F6E4B; }
        .vy-inbox-thread { display: flex; flex-direction: column; max-height: 72vh; }
        .vy-thread-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--vy-hairline); }
        .vy-thread-who { display: flex; gap: 11px; align-items: center; }
        .vy-thread-name { font-size: 15px; font-weight: 700; }
        .vy-thread-sub { font-size: 11.5px; color: var(--vy-faint); display: flex; gap: 5px; align-items: center; flex-wrap: wrap; margin-top: 2px; }
        .vy-thread-actions { display: flex; gap: 8px; }
        .vy-thread-scroll { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
        .vy-day-divider { text-align: center; font-size: 11px; color: var(--vy-faint); margin: 6px 0; }
        .vy-day-divider span { background: var(--vy-hairline); padding: 2px 12px; border-radius: 20px; }
        .vy-msg { max-width: 74%; }
        .vy-msg.is-out { align-self: flex-end; }
        .vy-msg.is-in { align-self: flex-start; }
        .vy-msg-bubble { padding: 9px 13px; border-radius: 14px; font-size: 13.5px; line-height: 1.45; }
        .vy-msg.is-in .vy-msg-bubble { background: var(--vy-hairline); color: var(--vy-ink); border-bottom-left-radius: 4px; }
        .vy-msg.is-out .vy-msg-bubble { background: linear-gradient(140deg, #E8703A, #C2410C); color: #fff; border-bottom-right-radius: 4px; }
        .vy-msg-meta { font-size: 10.5px; color: var(--vy-faint); margin-top: 3px; display: flex; gap: 6px; }
        .vy-msg.is-out .vy-msg-meta { justify-content: flex-end; }
        /* DEC-m — an owner's attachments, under the message they came with */
        .vy-msg-att-img-link { display: block; margin-top: 6px; line-height: 0; border-radius: 12px; }
        .vy-msg-att-img { display: block; max-width: 220px; max-height: 180px; border-radius: 12px; border: 1px solid var(--vy-hairline); object-fit: cover; }
        .vy-msg-att-file { display: inline-flex; align-items: center; gap: 8px; max-width: 280px; margin-top: 6px; padding: 8px 12px; border-radius: 11px; background: rgba(255, 255, 255, .72); border: 1px solid var(--vy-hairline); color: var(--vy-ink); font-size: 12.5px; text-decoration: none; }
        .vy-msg-att-file svg { flex-shrink: 0; color: var(--vy-accent, #E8703A); }
        .vy-msg-att-file:hover { border-color: var(--vy-accent, #E8703A); }
        .vy-msg-att-file:focus-visible, .vy-msg-att-img-link:focus-visible { outline: 2px solid var(--vy-accent, #E8703A); outline-offset: 2px; }
        .vy-msg-att-name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-msg-att-size { flex-shrink: 0; color: var(--vy-faint); font-size: 11px; }
        .vy-msg-delivery.is-failed { color: #B4400F; font-weight: 600; }
        .vy-msg-retry { border: none; background: none; padding: 0; color: var(--vy-accent, #E8703A); font-size: 10.5px; font-weight: 700; cursor: pointer; text-decoration: underline; }
        .vy-thread-composer { border-top: 1px solid var(--vy-hairline); padding: 12px 16px; }
        .vy-composer-form { display: flex; gap: 10px; align-items: flex-end; }
        .vy-composer-form textarea { flex: 1; resize: vertical; }
        .vy-composer-actions { display: flex; gap: 8px; }
        .vy-composer-note { font-size: 12.5px; color: var(--vy-faint); padding: 8px 0; }
        .vy-composer-hint { font-size: 11px; color: var(--vy-faint); margin: 8px 0 0; }
        .vy-thread-note { display: flex; align-items: flex-start; gap: 8px; margin: 0 16px; padding: 8px 12px; border-radius: 10px; background: rgba(217,158,61,.1); border: 1px solid rgba(217,158,61,.28); color: #7A5A1E; font-size: 12px; line-height: 1.45; }
        .vy-thread-note svg { flex: none; margin-top: 1px; }
        .vy-thread-note strong { color: #6A4E1A; }
        .vy-assign-lastnote { margin: 6px 0; padding: 7px 10px; border-radius: 8px; background: rgba(217,158,61,.1); color: #7A5A1E; font-size: 11.5px; line-height: 1.4; }
        .vy-template-row { display: flex; gap: 8px; margin-top: 6px; flex-wrap: wrap; }
        .vy-template-chip { padding: 7px 12px; border-radius: 20px; border: 1px solid var(--vy-hairline); background: none; font-size: 12px; cursor: pointer; color: var(--vy-ink); }
        .vy-template-chip.is-on { background: var(--vy-accent); color: #fff; border-color: var(--vy-accent); }
        .vy-template-chip:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        /* ── 2026-10-05 — the Inbox's dialogs on the shared solid card, the search box, the empty pane ─────────────────────── */
        .vy-inbox-search { padding: 12px; border-bottom: 1px solid var(--vy-hairline); }
        .vy-ibx-search .vy-gl-search-in { min-width: 0; }
        .vy-ibx-dlg { max-width: 540px; }
        .vy-ibx-head-text { flex: 1; min-width: 0; }
        .vy-ibx-head-text .vy-modal-title { overflow-wrap: anywhere; }
        .vy-ibx-pick { border: 0; margin: 0 0 16px; padding: 0; min-width: 0; }
        .vy-ibx-pick legend { padding: 0; margin-bottom: 8px; }
        .vy-ibx-find { height: 40px; margin-bottom: 8px; }
        .vy-ibx-options { display: flex; flex-direction: column; gap: 4px; max-height: 260px; overflow-y: auto; }
        .vy-ibx-guests { max-height: 232px; }
        .vy-ibx-option { display: flex; align-items: center; gap: 11px; padding: 9px 11px; border-radius: 12px; border: 1px solid transparent; cursor: pointer; font-size: 13px; }
        .vy-ibx-option:hover { background: rgba(120, 70, 40, .05); }
        .vy-ibx-option:has(input:checked) { border-color: rgba(217, 102, 46, .35); background: rgba(217, 102, 46, .07); }
        .vy-ibx-option:has(input:focus-visible) { outline: 2px solid var(--vy-accent); outline-offset: 1px; }
        .vy-ibx-option input { accent-color: var(--vy-accent); flex: none; margin: 0; }
        .vy-ibx-option strong { display: block; font-weight: 600; overflow-wrap: anywhere; }
        .vy-ibx-option-text { min-width: 0; }
        .vy-ibx-option-sub { display: block; font-size: 11.5px; color: var(--vy-on-grad); margin-top: 1px; }
        .vy-ibx-avatar { width: 32px; height: 32px; font-size: 11px; }
        .vy-ibx-field { margin-bottom: 0; }
        .vy-ibx-textarea { width: 100%; box-sizing: border-box; resize: vertical; }
        .vy-ibx-hint { display: block; font-size: 11.5px; color: var(--vy-on-grad); margin-top: 6px; }
        .vy-ibx-note { font-size: 12px; color: var(--vy-on-grad); margin: 14px 0 0; padding-top: 12px; border-top: 1px solid var(--vy-hairline); line-height: 1.5; }
        .vy-ibx-empty p { margin: 0 0 8px; font-size: 13px; line-height: 1.5; color: var(--vy-ink); }
        .vy-ibx-dlg .vy-modal-foot .vy-btn { height: 42px; justify-content: center; }
        .vy-ibx-dlg .vy-modal-foot .vy-btn--ghost { flex: 0 0 auto; min-width: 110px; }
        .vy-ibx-dlg .vy-btn--accent:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; transform: none; }
        .vy-ibx-pane-empty { display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; padding: 56px 28px; color: var(--vy-on-grad); font-size: 13px; }
        .vy-ibx-pane-empty strong { color: var(--vy-ink); font-size: 15px; font-weight: 600; }
        .vy-ibx-pane-empty .vy-btn { margin-top: 10px; }
        .vy-ibx-pane-ico { width: 46px; height: 46px; border-radius: 50%; display: grid; place-items: center; background: rgba(217, 102, 46, .1); color: var(--vy-accent); margin-bottom: 4px; }
        @media (max-width: 900px) { .vy-inbox-grid { grid-template-columns: 1fr; } }
    </style>
</div>
