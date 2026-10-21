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

        {{-- Guests | Team (team chat, 2026-10-06) — the two kinds of conversation, each with its live unread count --}}
        @include('partials.messages-toggle', ['current' => 'guests', 'guestsLabel' => $guestsLabel, 'guestUnread' => $stats['unread'], 'teamUnread' => $teamUnread])

        {{-- Stat strip — REAL derived counts (Unread / Open / Waiting on guest / Needs sending) --}}
        <div class="vy-inbox-stats vy-fade" data-ibx-live="stats">
            <a class="vy-istat {{ $filter === 'unread' ? 'is-on' : '' }}" href="{{ $inboxUrl(['filter' => $filter === 'unread' ? null : 'unread']) }}">
                <span class="vy-istat-dot" style="background:#E8703A"></span>
                <span class="vy-istat-text">
                    <span class="vy-istat-num" style="color:#B4400F">{{ $stats['unread'] }}</span><span class="vy-istat-label">Unread</span>
                </span>
            </a>
            <div class="vy-istat">
                <span class="vy-istat-dot" style="background:#2A6FDB"></span>
                <span class="vy-istat-text">
                    <span class="vy-istat-num">{{ $stats['open'] }}</span><span class="vy-istat-label">Open</span>
                </span>
            </div>
            <div class="vy-istat">
                <span class="vy-istat-dot" style="background:#D99A3D"></span>
                <span class="vy-istat-text">
                    <span class="vy-istat-num">{{ $stats['waiting'] }}</span><span class="vy-istat-label">Waiting on guest</span>
                </span>
            </div>
            <div class="vy-istat {{ $stats['needs_sending'] > 0 ? 'is-alert' : '' }}">
                <span class="vy-istat-dot" style="background:#C0392B"></span>
                <span class="vy-istat-text">
                    <span class="vy-istat-num" style="{{ $stats['needs_sending'] > 0 ? 'color:#C0392B' : '' }}">{{ $stats['needs_sending'] }}</span><span class="vy-istat-label">Needs sending</span>
                </span>
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
                <div class="vy-inbox-list" data-ibx-live="rail">
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
                    <header class="vy-thread-head" data-ibx-live="head">
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
                                <button type="button" class="vy-btn vy-btn--ghost vy-ta-btn" @click="assignOpen = true">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                                    Assign
                                </button>
                                {{-- #104 GATE 3a — snooze / reopen: the routes + InboxService methods existed but had no
                                     UI, leaving a CLOSED thread a dead-end. Both are POST → controller → PRG. --}}
                                @if ($active['can_snooze'])
                                    <form method="POST" action="{{ route('app.inbox.snooze', $active['id']) }}" class="vy-ta-form">@csrf
                                        <button type="submit" class="vy-btn vy-btn--ghost vy-ta-btn">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2M5 3L2 6M22 6l-3-3"/></svg>
                                            Snooze
                                        </button>
                                    </form>
                                @endif
                                @if ($active['can_reopen'])
                                    <form method="POST" action="{{ route('app.inbox.reopen', $active['id']) }}" class="vy-ta-form">@csrf
                                        <button type="submit" class="vy-btn vy-btn--accent vy-ta-btn">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg>
                                            Reopen
                                        </button>
                                    </form>
                                @endif
                            @endunless
                            @if ($active['reservation'])
                                <a class="vy-ta-ref" href="{{ route('app.reservations.show', $active['reservation']['id']) }}" title="Open reservation {{ $active['reservation']['reference'] }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                    <span class="vy-ta-ref-code">{{ $active['reservation']['reference'] }}</span>
                                </a>
                            @endif
                            {{-- #90 — the link-reservation control (route + service existed; no UI until now). Only for a
                                 guest thread with reservations to link. --}}
                            @if (! $readOnly && $active['has_guest'] && $active['link_reservations'] !== [])
                                <button type="button" class="vy-btn vy-btn--ghost vy-ta-btn" @click="linkOpen = true">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1.5 1.5M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1.5-1.5"/></svg>
                                    {{ $active['reservation'] ? 'Change link' : 'Link reservation' }}
                                </button>
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

                    <div class="vy-thread-scroll" data-ibx-live="thread" data-thread-id="{{ $active['id'] }}" data-unread="{{ $active['unread'] }}">
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
                                    @elseif (($att['kind'] ?? '') === 'voice')
                                        {{-- a voice note (2026-10-06) — the shared player (partials/chat-media) --}}
                                        <div class="vy-msg-voice" x-data="vyVoice({{ \Illuminate\Support\Js::from(['url' => $att['url'], 'seconds' => $att['seconds']]) }})">
                                            <button type="button" class="vy-msg-play" @click="toggle()" :aria-label="playing ? 'Pause voice message' : 'Play voice message'">
                                                <svg x-show="! playing" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg>
                                                <svg x-show="playing" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>
                                            </button>
                                            <span class="vy-msg-track" @click="seek($event)" role="slider" tabindex="0" aria-label="Position" aria-valuemin="0" :aria-valuemax="Math.round(length)" :aria-valuenow="Math.round(at)" :aria-valuetext="spoken()" @keydown="key($event)"><span class="vy-msg-track-fill" :style="{ width: pct + '%' }"></span></span>
                                            <span class="vy-msg-voice-time" x-text="clock()">{{ $att['seconds'] !== null ? intdiv((int) $att['seconds'], 60).':'.str_pad((string) ((int) $att['seconds'] % 60), 2, '0', STR_PAD_LEFT) : '' }}</span>
                                        </div>
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
                            {{-- (2026-10-06) pictures, documents and a voice note where the other side can open them — an owner's portal, a
                                 guest's own account (Direct) — and sent without a reload; without JavaScript it is the plain form it was. --}}
                            @php $carriesFiles = $active['is_owner_thread'] || $active['is_direct']; @endphp
                            <form method="POST" action="{{ route('app.inbox.reply', $active['id']) }}" class="vy-composer-form" enctype="multipart/form-data"
                                  x-data="vyInboxComposer({{ \Illuminate\Support\Js::from(['files' => $carriesFiles, 'max' => \App\Support\Messaging\ChatFiles::MAX_FILES, 'mb' => \App\Support\Messaging\ChatFiles::limits()['mb'], 'post' => \App\Support\Messaging\ChatFiles::limits()['post_bytes'], 'voice' => \App\Support\Messaging\ChatFiles::MAX_VOICE_SECONDS]) }})"
                                  @submit="submit($event)">
                                @csrf
                                @php $replyWord = $active['is_direct'] ? 'Write a reply…' : 'Reply via '.$active['channel_label'].'…'; @endphp
                                <p class="vy-composer-error" x-show="error" x-text="error" role="alert"></p>
                                @if ($carriesFiles)
                                    <div class="vy-composer-tray" x-show="pending.length">
                                        <template x-for="(p, i) in pending" :key="p.key">
                                            <span class="vy-composer-chip" :class="{ 'is-image': p.preview }">
                                                <template x-if="p.preview"><img :src="p.preview" :alt="p.name"></template>
                                                <template x-if="! p.preview"><span class="vy-composer-chip-name" x-text="p.name"></span></template>
                                                <button type="button" class="vy-composer-chip-x" @click="remove(i)" :aria-label="'Remove ' + p.name">×</button>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="vy-composer-rec" x-show="rec.state !== 'idle'">
                                        <template x-if="rec.state === 'starting'">
                                            <div class="vy-composer-rec-row">
                                                <span class="vy-composer-rec-time">Starting the microphone…</span>
                                                <button type="button" class="vy-btn vy-btn--ghost" @click="cancelRec()">Cancel</button>
                                            </div>
                                        </template>
                                        <template x-if="rec.state === 'recording'">
                                            <div class="vy-composer-rec-row">
                                                <button type="button" class="vy-composer-tool" @click="cancelRec()" aria-label="Cancel recording"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg></button>
                                                <span class="vy-composer-rec-dot" aria-hidden="true"></span>
                                                <span class="vy-composer-rec-time" x-text="'Recording ' + mmss(rec.seconds)"></span>
                                                <button type="button" class="vy-btn vy-btn--accent" @click="stopRec()">Stop</button>
                                            </div>
                                        </template>
                                        <template x-if="rec.state === 'ready'">
                                            <div class="vy-composer-rec-row">
                                                <button type="button" class="vy-composer-tool" @click="cancelRec()" aria-label="Discard voice note"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg></button>
                                                <div class="vy-msg-voice vy-composer-preview" x-data="vyVoice({ url: rec.url, seconds: rec.seconds })">
                                                    <button type="button" class="vy-msg-play" @click="toggle()" :aria-label="playing ? 'Pause' : 'Listen before sending'">
                                                        <svg x-show="! playing" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg>
                                                        <svg x-show="playing" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>
                                                    </button>
                                                    <span class="vy-msg-track" @click="seek($event)" role="slider" tabindex="0" aria-label="Position" aria-valuemin="0" :aria-valuemax="Math.round(length)" :aria-valuenow="Math.round(at)" :aria-valuetext="spoken()" @keydown="key($event)"><span class="vy-msg-track-fill" :style="{ width: pct + '%' }"></span></span>
                                                    <span class="vy-msg-voice-time" x-text="clock()"></span>
                                                </div>
                                                <span class="vy-composer-rec-note">Voice note ready — it goes with your reply.</span>
                                            </div>
                                        </template>
                                    </div>
                                @endif
                                <label class="vy-sr-only" for="vy-lbl-operator-inbox-body">{{ $replyWord }}</label>
                                <textarea id="vy-lbl-operator-inbox-body" name="body" rows="2" class="vy-input" placeholder="{{ $replyWord }}" x-model="body" @keydown.enter="enter($event)" @paste="paste($event)" aria-keyshortcuts="Control+Enter Meta+Enter" @if (! $carriesFiles) required @endif></textarea>
                                <div class="vy-composer-actions">
                                    @if ($carriesFiles)
                                        <div class="vy-composer-tools">
                                            <label class="vy-composer-tool" title="Attach documents">
                                                <input type="file" name="attachments[]" multiple accept=".{{ str_replace(',', ',.', \App\Support\Messaging\ChatFiles::FILE_EXTENSIONS) }}" class="vy-sr-only" @change="add($event)">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.4 11.1-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg>
                                                <span class="vy-sr-only">Attach documents</span>
                                            </label>
                                            <label class="vy-composer-tool" title="Add pictures">
                                                <input type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="vy-sr-only" @change="add($event)">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                                                <span class="vy-sr-only">Add pictures</span>
                                            </label>
                                            <button type="button" class="vy-composer-tool" x-cloak x-show="canRecord && rec.state === 'idle'" @click="startRec()" title="Record a voice note" aria-label="Record a voice note">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5M8 22h8"/></svg>
                                            </button>
                                        </div>
                                    @endif
                                    <span class="vy-composer-hint" x-text="hint"></span>
                                    @if ($active['can_close'])
                                        <button form="close-{{ $active['id'] }}" class="vy-btn vy-btn--ghost">Close</button>
                                    @endif
                                    <button type="submit" class="vy-btn vy-btn--accent" :disabled="sending" x-text="sending ? 'Sending…' : 'Reply'">Reply</button>
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

    {{-- THE GUESTS SIDE, LIVE (2026-10-06) — every 5 seconds while the tab is visible (30 when hidden; nothing after 30 minutes
         without a key or click) the page asks a small stamp; when it moves, it reads ITSELF again — the conversation it shows,
         flagged `live=1`, so nothing is read for the person — and swaps the counts, the list and the open conversation's header
         and messages in place. The reply being written, its dialogs and their state are never touched. Signed out (401, 419, a
         redirect) stops it; busy or failing (429, 5xx, no connection) only slows it. --}}
    @include('partials.chat-media')
    <script type="application/json" id="vy-inbox-live">@json($live)</script>
    <script>
        // THE INBOX, LIVE (2026-10-06; rewritten the same day after the independent review). Every few seconds the page asks a
        // small stamp; when it moved, it re-reads ITSELF — the conversation it shows (`c`), flagged `live=1`, so the server
        // reads nothing for the person — and swaps the parts that changed. ⚠️ Reading is the person's own act: the page posts
        // `app.inbox.read` only for a conversation they opened, only while they are looking at it.
        (() => {
            const cfg = JSON.parse(document.getElementById('vy-inbox-live').textContent);
            const IDLE_MS = 30 * 60 * 1000, PRESENT_MS = 2 * 60 * 1000;
            const KEYS = ['stats', 'rail', 'head', 'thread'];
            let stamp = null, timer = null, busy = false, stopped = false, lastActive = Date.now(), fails = 0, deferred = false;
            // what the SERVER last sent for each part — the page's own copy is changed by its players and buttons, so
            // comparing with it would redraw every part on every change
            const snap = {};
            KEYS.forEach((key) => { const el = document.querySelector('[data-ibx-live="' + key + '"]'); if (el) snap[key] = el.innerHTML; });

            // a conversation opens at its NEWEST message (it opened at the oldest, so a live arrival landed out of sight) — and
            // stays there while its pictures arrive, unless the person has started scrolling
            const opening = document.querySelector('[data-ibx-live="thread"]');
            if (opening) {
                let moved = false;
                const toEnd = () => { if (!moved) opening.scrollTop = opening.scrollHeight; };
                ['wheel', 'touchstart', 'keydown'].forEach((type) => opening.addEventListener(type, () => { moved = true; }, { passive: true, once: true }));
                toEnd();
                opening.querySelectorAll('img').forEach((img) => { if (!img.complete) img.addEventListener('load', toEnd, { once: true }); });
            }

            const present = () => !document.hidden && document.hasFocus() && Date.now() - lastActive < PRESENT_MS;
            const signedOut = (res) => res.redirected || res.status === 401 || res.status === 419;
            const token = () => { const el = document.querySelector('input[name=_token]'); return el ? el.value : ''; };
            const playing = (el) => !!window.Alpine && Array.from(el.querySelectorAll('.vy-msg-voice')).some((v) => {
                try { return !!window.Alpine.$data(v).playing; } catch (e) { return false; }
            });

            async function readIfThere() {
                const region = document.querySelector('[data-ibx-live="thread"]');
                if (stopped || !cfg.opened || !cfg.read || !region || Number(region.dataset.unread || 0) < 1 || !present()) return;
                region.dataset.unread = '0';
                const body = new FormData();
                body.append('_token', token());
                const res = await fetch(cfg.read, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => null);
                if (res && signedOut(res)) { stopped = true; return; }
                if (res && res.ok && window.VyLive) window.VyLive.refresh();
            }

            async function refresh() {
                const page = new URL(location.href);
                if (cfg.c) page.searchParams.set('c', cfg.c);
                page.searchParams.set('live', '1');
                const res = await fetch(page, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store' });
                if (signedOut(res)) { stopped = true; return false; }
                if (!res.ok) { fails++; return false; }
                const next = new DOMParser().parseFromString(await res.text(), 'text/html');
                const scroll = document.querySelector('[data-ibx-live="thread"]');
                const nearBottom = scroll ? scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 120 : false;
                const nextThread = next.querySelector('[data-ibx-live="thread"]');
                const sameThread = !!nextThread && nextThread.dataset.threadId === cfg.c;
                deferred = false;
                KEYS.forEach((key) => {
                    const here = document.querySelector('[data-ibx-live="' + key + '"]');
                    const there = next.querySelector('[data-ibx-live="' + key + '"]');
                    if (!here || !there) return;
                    // the open conversation's parts only ever take THAT conversation — never whichever became newest
                    if (key === 'thread' && there.dataset.threadId !== cfg.c) return;
                    if (key === 'head' && !sameThread) return;
                    if (key === 'thread') here.dataset.unread = there.dataset.unread || '0';
                    if (there.innerHTML === snap[key]) return;
                    // a voice note being listened to is never cut off: its part waits, and the next check brings it
                    if (key === 'thread' && playing(here)) { deferred = true; return; }
                    here.innerHTML = there.innerHTML;
                    snap[key] = there.innerHTML;
                });
                // the title's "N unread" moves with the counts (it is the shell's, outside the page's own parts)
                const sub = document.querySelector('.vy-topbar-sub'), nextSub = next.querySelector('.vy-topbar-sub');
                if (sub && nextSub) sub.textContent = nextSub.textContent;
                if (scroll && nearBottom) scroll.scrollTop = scroll.scrollHeight;
                if (window.VyLive) window.VyLive.refresh();
                readIfThere();
                return true;
            }
            // the composer, after a reply went out: show it now, at the bottom
            window.VyInboxLive = {
                async sent() {
                    await refresh();
                    const scroll = document.querySelector('[data-ibx-live="thread"]');
                    if (scroll) scroll.scrollTop = scroll.scrollHeight;
                },
            };

            async function check() {
                if (busy || stopped) return;
                busy = true;
                try {
                    const url = new URL(cfg.live, location.origin);
                    if (cfg.c) url.searchParams.set('c', cfg.c);
                    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', cache: 'no-store' });
                    if (signedOut(res)) { stopped = true; return; }
                    // busy or failing (429, 5xx, a page that is not JSON): ask again later, never give up
                    if (!res.ok || !(res.headers.get('content-type') || '').includes('application/json')) { fails++; return; }
                    fails = 0;
                    const data = await res.json();
                    if (stamp !== null && (data.stamp !== stamp || deferred) && !(await refresh())) return;   // retried next time
                    stamp = data.stamp;
                } catch (e) {
                    fails++;   // offline for a moment — the next check tries again, a little later
                } finally {
                    busy = false;
                }
            }

            function schedule() {
                clearTimeout(timer);
                if (stopped || Date.now() - lastActive > IDLE_MS) return;
                const wait = (document.hidden ? 30000 : 5000) * Math.pow(2, Math.min(fails, 4));
                timer = setTimeout(() => { check().finally(schedule); }, Math.min(wait, 5 * 60 * 1000));
            }

            ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach((type) => window.addEventListener(type, () => {
                const wasIdle = Date.now() - lastActive > IDLE_MS;
                lastActive = Date.now();
                if (wasIdle) check().finally(schedule);
                readIfThere();
            }, { passive: true }));
            window.addEventListener('focus', () => readIfThere());
            document.addEventListener('visibilitychange', () => { if (!document.hidden) { check().finally(schedule); readIfThere(); } });
            check().finally(schedule);
        })();

        // THE REPLY, LIVE (2026-10-06) — sent without a reload: the words, up to five pictures or documents and a voice note
        // (where the other side can open them). Refused, it says why and keeps everything; sent, the conversation shows it.
        // ⚠️ Enter is a new line here — these replies go to guests and owners; Ctrl+Enter (⌘+Enter on a Mac) sends.
        document.addEventListener('alpine:init', () => {
            Alpine.data('vyInboxComposer', (cfg) => {
                let recording = null, recToken = 0, seq = 0;
                const PICTURES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                const IDLE = () => ({ state: 'idle', seconds: 0, url: null, mime: '' });
                const mac = /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent || '');
                return {
                    body: '',
                    pending: [],
                    error: '',
                    sending: false,
                    rec: IDLE(),
                    hint: (mac ? '⌘' : 'Ctrl') + ' + Enter to send',
                    canRecord: cfg.files && !!(window.VyRecorder && window.VyRecorder.supported()),
                    mmss(n) { n = Math.max(0, Math.round(n || 0)); return Math.floor(n / 60) + ':' + String(n % 60).padStart(2, '0'); },
                    add(e) {
                        this.error = '';
                        for (const file of Array.from(e.target.files || [])) {
                            if (this.pending.length >= cfg.max) { this.error = 'Send up to ' + cfg.max + ' files at a time.'; break; }
                            if (file.size > cfg.mb * 1048576) { this.error = file.name + ' is over ' + cfg.mb + ' MB.'; continue; }
                            this.pending.push({ key: 'f' + (++seq), file, name: file.name, preview: PICTURES.includes(file.type) ? URL.createObjectURL(file) : null });
                        }
                        e.target.value = '';   // the files travel in the list above, never twice
                    },
                    remove(i) {
                        const p = this.pending[i];
                        if (p && p.preview) URL.revokeObjectURL(p.preview);
                        this.pending.splice(i, 1);
                    },
                    paste(e) {
                        if (!cfg.files) return;
                        const files = Array.from((e.clipboardData && e.clipboardData.files) || []).filter((f) => PICTURES.includes(f.type));
                        if (files.length) { e.preventDefault(); this.add({ target: { files, value: '' } }); }
                    },
                    enter(e) {
                        if (!(e.ctrlKey || e.metaKey) || e.isComposing) return;   // a plain Enter writes a new line
                        e.preventDefault();
                        this.$root.requestSubmit();
                    },
                    async startRec() {
                        if (this.rec.state !== 'idle') return;   // one microphone at a time
                        this.error = '';
                        const mine = ++recToken;
                        this.rec = Object.assign(IDLE(), { state: 'starting' });
                        let started;
                        try {
                            started = await window.VyRecorder.start({ limit: cfg.voice, onTick: (n) => { if (recToken === mine) this.rec.seconds = n; } });
                        } catch (err) {
                            if (recToken === mine) { this.rec = IDLE(); this.error = err.message; }
                            return;
                        }
                        if (recToken !== mine) { started.cancel(); return; }   // cancelled while the browser was asking
                        recording = started;
                        this.rec = Object.assign(IDLE(), { state: 'recording' });
                        started.finished.then((r) => {
                            if (recToken !== mine || this.rec.state !== 'recording') return;   // cancelled, or a newer one
                            if (!r) { this.rec = IDLE(); this.error = 'Nothing was recorded — try again.'; return; }
                            this.rec = { state: 'ready', seconds: r.seconds, url: URL.createObjectURL(r.blob), mime: r.mime, blob: r.blob };
                        });
                    },
                    stopRec() { if (recording && this.rec.state === 'recording') recording.stop(); },
                    cancelRec() {
                        recToken++;
                        const was = this.rec.state;
                        if (this.rec.url) URL.revokeObjectURL(this.rec.url);
                        this.rec = IDLE();
                        if (was === 'recording' && recording) recording.cancel();
                        recording = null;
                    },
                    /** Why a reply was not sent, in words — our own refusal (422, 404) as the server said it, else what went wrong. */
                    reason(res, json) {
                        if (!res) return 'No connection — your reply was kept. Try again.';
                        if (json && json.message && (res.status === 422 || res.status === 404)) return json.message;
                        if (res.redirected || res.status === 401 || res.status === 419) return 'You have been signed out — reload to continue. Your reply was kept.';
                        if (res.status === 413) return 'Together these files are too large to send at once — send fewer at a time.';
                        if (res.status === 429) return 'Too many replies at once — wait a moment, then try again. Your reply was kept.';
                        return 'Your reply was not sent — something went wrong. It was kept; try again.';
                    },
                    async submit(e) {
                        e.preventDefault();
                        if (this.sending) return;
                        if (this.rec.state === 'recording' || this.rec.state === 'starting') { this.error = 'Stop the recording first.'; return; }
                        const size = this.pending.reduce((n, p) => n + p.file.size, 0) + (this.rec.blob ? this.rec.blob.size : 0);
                        if (size > cfg.post) { this.error = 'Together these files are over ' + Math.floor(cfg.post / 1048576) + ' MB — send fewer at a time.'; return; }
                        const form = this.$root;
                        const data = new FormData();
                        data.append('_token', form.querySelector('input[name=_token]').value);
                        data.append('body', this.body);
                        this.pending.forEach((p) => data.append('attachments[]', p.file, p.name));
                        if (this.rec.state === 'ready' && this.rec.blob) {
                            data.append('voice', this.rec.blob, window.VyRecorder.fileName(this.rec.mime));
                            data.append('voice_seconds', String(this.rec.seconds));
                        }
                        this.sending = true;
                        this.error = '';
                        let res = null;
                        try {
                            res = await fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        } catch (err) {
                            res = null;
                        }
                        this.sending = false;
                        const json = res && (res.headers.get('content-type') || '').includes('application/json') ? await res.json().catch(() => null) : null;
                        if (!res || !json || !json.ok) { this.error = this.reason(res, json); return; }
                        this.body = '';
                        if (json.files_kept) {
                            // the words went, the files did not: they stay here to send again, and the reason is said
                            this.error = json.message;
                        } else {
                            this.pending.forEach((p) => p.preview && URL.revokeObjectURL(p.preview));
                            this.pending = [];
                            if (this.rec.url) URL.revokeObjectURL(this.rec.url);
                            this.rec = IDLE();
                        }
                        if (window.VyInboxLive) await window.VyInboxLive.sent();
                    },
                };
            });
        });
    </script>

    <style>
        [x-cloak] { display: none !important; }
        /* (2026-10-06) voice notes in the conversation, and the composer's files, pictures and recorder */
        .vy-msg-voice { display: flex; align-items: center; gap: 9px; min-width: 210px; max-width: 280px; margin-top: 4px; padding: 7px 11px 7px 7px; border-radius: 16px; background: #fff; border: 1px solid var(--vy-hairline); }
        .vy-msg.is-out .vy-msg-voice { margin-left: auto; }
        .vy-msg-play { width: 30px; height: 30px; flex: none; border: 0; border-radius: 50%; display: grid; place-items: center; background: var(--vy-accent); color: #fff; cursor: pointer; }
        .vy-msg-play:focus-visible, .vy-composer-tool:focus-within, .vy-composer-tool:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 2px; }
        .vy-msg-track { position: relative; flex: 1; height: 5px; border-radius: 5px; background: rgba(120, 70, 40, 0.14); cursor: pointer; overflow: hidden; }
        .vy-msg-track:focus-visible { outline: 2px solid var(--vy-accent); outline-offset: 3px; }
        /* the shortcut sits beside the tools; the buttons keep the right edge (no hint on a phone: there is no Ctrl) */
        .vy-composer-hint { align-self: center; margin: 0 auto 0 6px; font-size: 11.5px; color: var(--vy-muted); white-space: nowrap; }
        .vy-composer-actions .vy-composer-tools { margin-right: 0; }
        @media (max-width: 560px), (pointer: coarse) {
            .vy-composer-hint { display: none; }
            .vy-composer-actions .vy-composer-tools { margin-right: auto; }
        }
        .vy-msg-track-fill { position: absolute; inset: 0 auto 0 0; background: var(--vy-accent); border-radius: 5px; }
        .vy-msg-voice-time { flex: none; font-size: 11.5px; color: var(--vy-muted); font-variant-numeric: tabular-nums; min-width: 30px; text-align: right; }
        .vy-composer-error { margin: 0 0 8px; font-size: 12.5px; color: #8E2A1F; }
        .vy-composer-tray { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 8px; }
        .vy-composer-chip { position: relative; display: inline-flex; align-items: center; height: 48px; max-width: 200px; padding: 0 28px 0 11px; border-radius: 11px; background: #fff; border: 1px solid var(--vy-hairline); font-size: 12.5px; font-weight: 600; }
        .vy-composer-chip.is-image { width: 48px; padding: 0; overflow: hidden; }
        .vy-composer-chip.is-image img { width: 100%; height: 100%; object-fit: cover; }
        .vy-composer-chip-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .vy-composer-chip-x { position: absolute; top: 3px; right: 3px; width: 19px; height: 19px; border: 0; border-radius: 50%; background: rgba(43, 35, 32, 0.72); color: #fff; font-size: 12px; line-height: 1; display: grid; place-items: center; cursor: pointer; }
        .vy-composer-rec { margin: 0 0 8px; }
        .vy-composer-rec-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .vy-composer-rec-dot { width: 9px; height: 9px; border-radius: 50%; background: #D93A2B; animation: vy-ibx-rec 1.4s ease-out infinite; }
        .vy-composer-rec-time { flex: 1; font-size: 13.5px; font-weight: 600; font-variant-numeric: tabular-nums; }
        .vy-composer-rec-note { font-size: 12px; color: var(--vy-muted); }
        .vy-composer-preview { margin: 0; flex: 1; max-width: 320px; }
        @keyframes vy-ibx-rec { 0% { box-shadow: 0 0 0 0 rgba(217, 58, 43, .5); } 100% { box-shadow: 0 0 0 9px rgba(217, 58, 43, 0); } }
        @media (prefers-reduced-motion: reduce) { .vy-composer-rec-dot { animation: none; } }
        .vy-composer-tools { display: flex; align-items: center; gap: 2px; margin-right: auto; }
        .vy-composer-tool { position: relative; width: 36px; height: 36px; border: 0; border-radius: 50%; display: inline-grid; place-items: center; background: transparent; color: var(--vy-muted); cursor: pointer; }
        .vy-composer-tool:hover { background: rgba(120, 70, 40, 0.07); color: var(--vy-ink); }
        /* stat strip */
        .vy-inbox-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
        .vy-istat { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 16px; background: rgba(255, 252, 248, 0.66); backdrop-filter: blur(20px) saturate(1.1); -webkit-backdrop-filter: blur(20px) saturate(1.1); border: 1px solid var(--vy-glass-border, rgba(150,90,50,.12)); text-decoration: none; color: var(--vy-ink); transition: border-color .14s, transform .1s, box-shadow .14s; }
        a.vy-istat:hover { transform: translateY(-1px); box-shadow: var(--vy-shadow-sm); }
        a.vy-istat.is-on { border-color: var(--vy-accent, #E8703A); box-shadow: 0 0 0 1px var(--vy-accent, #E8703A) inset; }
        .vy-istat.is-alert { border-color: rgba(192,57,43,.4); }
        .vy-istat-dot { width: 10px; height: 10px; border-radius: 50%; flex: none; }
        .vy-istat-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .vy-istat-num { font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 700; line-height: 1; font-variant-numeric: tabular-nums; }
        .vy-istat-label { font-size: 12px; color: var(--vy-muted); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        /* channel filter + sort + chips subbar */
        .vy-inbox-subbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; min-height: 30px; }
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
        .vy-rack-toolbar.vy-ibx-bar { margin-bottom: 16px; }
        .vy-inbox-tabs { max-width: 560px; }
        @media (max-width: 560px) {
            .vy-ibx-bar > .vy-left, .vy-ibx-bar > .vy-right { flex: 1 1 100%; }
            .vy-ibx-bar > .vy-right .vy-btn { flex: 1; justify-content: center; }
            .vy-inbox-tabs { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); width: 100%; max-width: none; }
            .vy-inbox-tabs > .vy-seg-opt { grid-column: span 2; white-space: normal; padding: 9px 6px; line-height: 1.25; }
            .vy-inbox-tabs > .vy-seg-opt:nth-child(n + 4) { grid-column: span 3; }
        }
        .vy-inbox-grid { display: grid; grid-template-columns: 344px 1fr; gap: 18px; align-items: start; }
        .vy-inbox-rail { display: flex; flex-direction: column; max-height: 72vh; }
        .vy-inbox-list { overflow-y: auto; }
        .vy-conv { display: flex; gap: 12px; align-items: flex-start; padding: 13px 15px; border-bottom: 1px solid var(--vy-hairline); text-decoration: none; color: var(--vy-ink); position: relative; transition: background .12s; }
        .vy-conv::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: transparent; transition: background .12s; }
        .vy-conv:hover { background: rgba(120,70,40,.04); }
        .vy-conv.is-active { background: rgba(217,102,46,.09); }
        .vy-conv.is-active::before { background: var(--vy-accent, #E8703A); }
        .vy-conv-avatar { width: 38px; height: 38px; border-radius: 50%; background: var(--vy-accent-wash, #F6E4D6); color: var(--vy-accent); font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .vy-conv-body { flex: 1; min-width: 0; }
        .vy-conv-top { display: flex; justify-content: space-between; gap: 8px; }
        .vy-conv-name { font-size: 13.5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .vy-conv.is-unread .vy-conv-name { font-weight: 700; }
        .vy-conv.is-unread .vy-conv-time { margin-right: 14px; }   /* (2026-10-06) room for the unread dot — it sat on the time's last digit */
        .vy-conv-time { font-size: 11px; color: var(--vy-faint); white-space: nowrap; }
        .vy-conv-preview { font-size: 12.5px; color: var(--vy-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 2px 0 5px; }
        .vy-conv-meta { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .vy-conv-ctx { font-size: 10.5px; color: var(--vy-faint); }
        .vy-conv-assignee { margin-left: auto; width: 20px; height: 20px; border-radius: 50%; background: var(--vy-hairline); color: var(--vy-muted); font-size: 9px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .vy-conv-dot { position: absolute; top: 16px; right: 12px; width: 8px; height: 8px; border-radius: 50%; background: var(--vy-accent); }
        /* ⚠️ (2026-10-06) the shell's `.vy-chan-badge` is the Channels page's 34 × 34 tile; this pill shares the name, so it
           undoes the tile's size and font — "Direct" had been squeezed into a square, its word spilling out */
        .vy-chan-badge { width: auto; height: auto; display: inline-block; font-family: inherit; font-size: 10px; font-weight: 600; padding: 1px 7px; border-radius: 20px; background: rgba(120,70,40,.08); color: var(--vy-muted); }
        .vy-chan--direct, .vy-chan--manual { background: rgba(31,110,75,.12); color: #1F6E4B; }
        .vy-inbox-thread { display: flex; flex-direction: column; max-height: 72vh; }
        .vy-thread-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--vy-hairline); }
        .vy-thread-who { display: flex; gap: 11px; align-items: center; }
        .vy-thread-name { font-size: 15px; font-weight: 700; }
        .vy-thread-sub { font-size: 11.5px; color: var(--vy-faint); display: flex; gap: 5px; align-items: center; flex-wrap: wrap; margin-top: 2px; }
        /* Thread header actions — one consistent height and baseline for buttons of mixed element types
           (a <button>, a form-wrapped <button>, an <a> reference chip). (2026-10-21) */
        .vy-thread-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .vy-thread-actions .vy-ta-form { display: inline-flex; margin: 0; }
        .vy-thread-actions .vy-ta-btn { height: 34px; padding: 0 13px; font-size: 12.5px; gap: 6px; white-space: nowrap; }
        .vy-thread-actions .vy-ta-btn svg { flex: none; opacity: .85; }
        .vy-thread-actions .vy-ta-btn.vy-btn--accent svg { opacity: 1; }
        /* the reservation reference reads as a distinct monospace-ish code chip, not a plain button */
        .vy-ta-ref { display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 12px; border-radius: var(--vy-r-pill); text-decoration: none; font-size: 12px; font-weight: 700; letter-spacing: .02em; color: var(--vy-accent-text); background: rgba(217, 102, 46, .1); border: 1px solid rgba(217, 102, 46, .22); transition: background .14s ease, border-color .14s ease; }
        .vy-ta-ref:hover { background: rgba(217, 102, 46, .16); border-color: rgba(217, 102, 46, .4); }
        .vy-ta-ref svg { flex: none; }
        .vy-ta-ref-code { font-variant-numeric: tabular-nums; }
        @media (max-width: 560px) { .vy-thread-actions .vy-ta-btn, .vy-ta-ref { height: 32px; } }
        .vy-thread-scroll { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
        .vy-day-divider { text-align: center; font-size: 11px; color: var(--vy-faint); margin: 6px 0; }
        .vy-day-divider span { background: var(--vy-hairline); padding: 2px 12px; border-radius: 20px; }
        .vy-msg { max-width: 74%; }
        .vy-msg.is-out { align-self: flex-end; }
        .vy-msg.is-in { align-self: flex-start; }
        .vy-msg-bubble { padding: 9px 13px; border-radius: 14px; font-size: 13.5px; line-height: 1.45; white-space: pre-wrap; overflow-wrap: anywhere; }   /* a reply's lines kept, as the guest's and the owner's pages show them (2026-10-06) */
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
