{{-- Operator MESSAGES — the unified inbox (#90 exact-match), rebuilt to Operator Messages.html on the REAL
     MessageThread/Message models via InboxService. Rail (conversations) + active thread + Assign / New-message
     modals. FULL guest data (the manager) — name + channel + reservation context; the encrypted contact is
     NEVER rendered (#28). Reply/assign/new-message are POST routes (#20). RAW-BLADE RULE: every `@` follows a
     space, newline or tag. --}}
<div x-data="{ assignOpen: false, composeOpen: false, composeGuest: null, composeTemplate: '' }">
    <x-operator.shell active="inbox" title="Messages" :subtitle="$summary['unread'].' unread'" :viewer="$viewer">

        @if (session('inbox_notice')) <div class="vy-flash is-ok vy-fade">{{ session('inbox_notice') }}</div> @endif
        @if (session('inbox_error')) <div class="vy-flash is-err vy-fade">{{ session('inbox_error') }}</div> @endif

        {{-- Toolbar: filters + New message --}}
        <div class="vy-rack-toolbar vy-fade" style="margin-bottom: 12px;">
            <div class="vy-left">
                <div class="vy-seg" style="max-width: 460px;">
                    <a class="vy-seg-opt {{ $filter === null ? 'is-checked' : '' }}" href="{{ route('app.inbox', array_filter(['c' => request('c')])) }}">All</a>
                    <a class="vy-seg-opt {{ $filter === 'unread' ? 'is-checked' : '' }}" href="{{ route('app.inbox', ['filter' => 'unread']) }}">Unread</a>
                    <a class="vy-seg-opt {{ $filter === 'mine' ? 'is-checked' : '' }}" href="{{ route('app.inbox', ['filter' => 'mine']) }}">Assigned to me</a>
                    <a class="vy-seg-opt {{ $filter === 'closed' ? 'is-checked' : '' }}" href="{{ route('app.inbox', ['filter' => 'closed']) }}">Closed</a>
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

        {{-- 3-pane: rail + active thread --}}
        <div class="vy-inbox-grid vy-fade">

            {{-- Rail --}}
            <aside class="vy-card vy-card--flush vy-inbox-rail">
                <form method="GET" action="{{ route('app.inbox') }}" class="vy-inbox-search">
                    @if ($filter) <input type="hidden" name="filter" value="{{ $filter }}"> @endif
                    <input type="search" name="q" value="{{ $query }}" class="vy-input" placeholder="Search guests…" autocomplete="off">
                </form>
                <div class="vy-inbox-list">
                    @forelse ($rows as $row)
                        <a href="{{ route('app.inbox', ['c' => $row['id']] + ($filter ? ['filter' => $filter] : []) + ($query !== '' ? ['q' => $query] : [])) }}"
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
                                <div class="vy-thread-name">{{ $active['name'] }}</div>
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
                        </div>
                    </header>

                    <div class="vy-thread-scroll">
                        @php $lastDay = null; @endphp
                        @foreach ($messages as $m)
                            @if ($m['day_label'] !== $lastDay)
                                <div class="vy-day-divider"><span>{{ $m['day_label'] }}</span></div>
                                @php $lastDay = $m['day_label']; @endphp
                            @endif
                            <div class="vy-msg {{ $m['out'] ? 'is-out' : 'is-in' }}">
                                <div class="vy-msg-bubble">{{ $m['body'] }}</div>
                                <div class="vy-msg-meta">
                                    <span>{{ $m['author'] }} · {{ $m['time'] }}</span>
                                    @if ($m['delivery'])
                                        <span class="vy-msg-delivery {{ $m['delivery']['failed'] ? 'is-failed' : '' }}">{{ $m['delivery']['label'] }}</span>
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
                                <textarea name="body" rows="2" class="vy-input" placeholder="Reply via {{ $active['channel_label'] }}…" required></textarea>
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
                        <p class="vy-composer-hint">The guest replies wherever they booked · contact details stay private.</p>
                    </footer>
                @else
                    <div class="vy-empty" style="padding: 60px 30px;">Pick a conversation, or start a new message.</div>
                @endif
            </section>
        </div>

        {{-- ── Assign modal — INTERNAL hand-to-staff (guests never see this) ─────────────────────────── --}}
        @if ($active)
        <div x-show="assignOpen" x-cloak class="vy-modal-backdrop" @click.self="assignOpen = false">
            <div class="vy-modal">
                <h2 class="vy-modal-title">Assign conversation</h2>
                <p class="vy-modal-sub">{{ $active['name'] }} · they're notified in-app · the thread stays in this shared inbox.</p>
                <form method="POST" action="{{ route('app.inbox.assign', $active['id']) }}">
                    @csrf
                    <div class="vy-assign-list">
                        <label class="vy-assign-opt">
                            <input type="radio" name="user_id" value="">
                            <span><strong>Unassigned</strong><br><span class="vy-muted">No one in particular</span></span>
                        </label>
                        @foreach ($staff as $member)
                            <label class="vy-assign-opt">
                                <input type="radio" name="user_id" value="{{ $member['id'] }}" @checked($active['assignee'] && $active['assignee']['name'] === $member['name'])>
                                <span><strong>{{ $member['name'] }}</strong><br><span class="vy-muted">{{ $member['department'] }} · {{ $member['role_label'] }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    <p class="vy-modal-note">Internal note · guests never see this. They're notified on their device; the thread stays in this shared inbox.</p>
                    <div class="vy-modal-actions">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="assignOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent">Assign</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- ── New message modal — pick a guest with a current/upcoming stay + start from template ──────── --}}
        <div x-show="composeOpen" x-cloak class="vy-modal-backdrop" @click.self="composeOpen = false">
            <div class="vy-modal">
                <h2 class="vy-modal-title">New message</h2>
                <p class="vy-modal-sub">Guests with a current or upcoming stay.</p>
                <form method="POST" action="{{ route('app.inbox.start') }}">
                    @csrf
                    <input type="hidden" name="guest_id" :value="composeGuest">
                    <input type="hidden" name="template" :value="composeTemplate">
                    <div class="vy-assign-list" style="max-height: 240px;">
                        @forelse ($composeGuests as $g)
                            <label class="vy-assign-opt">
                                <input type="radio" name="_guest_pick" value="{{ $g['id'] }}" @click="composeGuest = '{{ $g['id'] }}'">
                                <span class="vy-conv-avatar" style="width:30px;height:30px;font-size:11px;">{{ $g['initials'] }}</span>
                                <span><strong>{{ $g['name'] }}</strong><br><span class="vy-muted">{{ $g['meta'] }}</span></span>
                            </label>
                        @empty
                            <div class="vy-empty" style="padding: 16px;">No guests with a current or upcoming stay.</div>
                        @endforelse
                    </div>
                    <div class="vy-compose-templates">
                        <span class="vy-field-label">Start from</span>
                        <div class="vy-template-row">
                            @foreach ($templates as $t)
                                <button type="button" class="vy-template-chip" :class="composeTemplate === '{{ $t['key'] }}' ? 'is-on' : ''" @click="composeTemplate = (composeTemplate === '{{ $t['key'] }}' ? '' : '{{ $t['key'] }}')">{{ $t['label'] }}</button>
                            @endforeach
                        </div>
                    </div>
                    <p class="vy-modal-note">Starts a thread in this inbox · the guest replies wherever they booked.</p>
                    <div class="vy-modal-actions">
                        <button type="button" class="vy-btn vy-btn--ghost" @click="composeOpen = false">Cancel</button>
                        <button type="submit" class="vy-btn vy-btn--accent" :disabled="!composeGuest" :style="!composeGuest ? 'opacity:.5;pointer-events:none;' : ''">Start conversation</button>
                    </div>
                </form>
            </div>
        </div>

    </x-operator.shell>

    <style>
        [x-cloak] { display: none !important; }
        .vy-inbox-grid { display: grid; grid-template-columns: 340px 1fr; gap: 16px; align-items: start; }
        .vy-inbox-rail { display: flex; flex-direction: column; max-height: 72vh; }
        .vy-inbox-search { padding: 12px; border-bottom: 1px solid var(--vy-hairline); }
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
        .vy-msg-delivery.is-failed { color: #B4400F; font-weight: 600; }
        .vy-thread-composer { border-top: 1px solid var(--vy-hairline); padding: 12px 16px; }
        .vy-composer-form { display: flex; gap: 10px; align-items: flex-end; }
        .vy-composer-form textarea { flex: 1; resize: vertical; }
        .vy-composer-actions { display: flex; gap: 8px; }
        .vy-composer-note { font-size: 12.5px; color: var(--vy-faint); padding: 8px 0; }
        .vy-composer-hint { font-size: 11px; color: var(--vy-faint); margin: 8px 0 0; }
        .vy-modal-backdrop { position: fixed; inset: 0; background: rgba(43,35,32,.42); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 60; padding: 20px; }
        .vy-modal { background: var(--vy-surface, #FFFCF8); border-radius: 16px; padding: 22px; width: 440px; max-width: 100%; box-shadow: 0 24px 60px rgba(150,90,50,.24); }
        .vy-modal-title { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; margin: 0 0 3px; }
        .vy-modal-sub { font-size: 12.5px; color: var(--vy-muted); margin: 0 0 14px; }
        .vy-assign-list { display: flex; flex-direction: column; gap: 2px; max-height: 300px; overflow-y: auto; }
        .vy-assign-opt { display: flex; gap: 10px; align-items: center; padding: 10px 12px; border-radius: 10px; cursor: pointer; font-size: 13px; }
        .vy-assign-opt:hover { background: rgba(120,70,40,.05); }
        .vy-modal-note { font-size: 11px; color: var(--vy-faint); margin: 12px 0; border-top: 1px solid var(--vy-hairline); padding-top: 10px; }
        .vy-modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .vy-compose-templates { margin-top: 14px; }
        .vy-template-row { display: flex; gap: 8px; margin-top: 6px; flex-wrap: wrap; }
        .vy-template-chip { padding: 7px 12px; border-radius: 20px; border: 1px solid var(--vy-hairline); background: none; font-size: 12px; cursor: pointer; color: var(--vy-ink); }
        .vy-template-chip.is-on { background: var(--vy-accent); color: #fff; border-color: var(--vy-accent); }
        @media (max-width: 900px) { .vy-inbox-grid { grid-template-columns: 1fr; } }
    </style>
</div>
