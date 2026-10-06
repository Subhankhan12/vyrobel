{{-- Owner portal · MESSAGES (Wave 3 → exact-match 3-pane rebuild, GATE 2 rich features) — the owner↔OPERATOR
     relay as a full inbox, matched to the decoded "Owner Messages" wireframe on the REAL relay. See
     App\Livewire\Owner\OwnerMessages for the REAL/DERIVED/OMITTED map.
     🔒 #28 owner↔operator ONLY (OwnerScope on owner_id → a guest/cross-owner thread 404s) + staff correspondents
     named, NEVER owner→guest; #20 every write (send/compose/react/star/pin) is a controller POST, audited; #39
     honest — presence + typing OMITTED (no real-time infra), read receipt is REAL (operator_last_read_at).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="messages" :title="'Messages'" :subtitle="$subtitle" :viewer="$viewer">
    <style>
        .vy-msg-wrap { display:flex; gap:18px; align-items:stretch; height:calc(100vh - 150px); min-height:460px; }
        .vy-msg-rail { width:320px; flex-shrink:0; display:flex; flex-direction:column; gap:12px; min-height:0; }
        .vy-msg-pane { flex:1; min-width:0; display:flex; flex-direction:column; min-height:0; }
        /* ⚠️⚠️ 106px of sideways scroll at 375px: `.vy-msg-rail` is a hard `width:320px; flex-shrink:0`
           and `.vy-msg-wrap` has no `flex-wrap`, so 320 + 18 of gap + a pane that cannot reach zero was
           simply wider than the page. On a phone the two panes STACK — the rail (the conversation list)
           above, the open thread below.
           ⚠️ THE FIXED HEIGHT HAS TO GO WITH IT. `height: calc(100vh - 150px)` is right for two panes
           side by side and wrong for two stacked: it would squeeze both into one screen. The wrap goes
           auto-height and the thread keeps a bounded, scrollable height of its own, so the composer
           below it stays reachable instead of being pushed off the bottom.
           ⚠️ Nothing is hidden and no control is removed — the same list, thread and composer, in one
           column instead of two. */
        @media (max-width:860px) {
            .vy-msg-wrap { flex-direction:column; height:auto; min-height:0; }
            .vy-msg-rail { width:auto; min-width:0; }
            .vy-msg-thread { max-height:60vh; }
        }
        .vy-msg-search { display:flex; align-items:center; gap:9px; padding:8px 12px; border-radius:12px; background:rgba(255,252,248,.7); border:1px solid var(--vy-glass-border); box-shadow:inset 0 1px 0 rgba(255,255,255,.6); }
        .vy-msg-search input { flex:1; min-width:0; border:none; outline:none; background:transparent; font:inherit; font-size:13px; color:var(--vy-ink); }
        .vy-msg-tabs { display:flex; gap:3px; padding:3px; border-radius:12px; background:rgba(120,70,40,.06); border:1px solid rgba(255,255,255,.6); }
        .vy-msg-tab { flex:1; display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:7px 10px; border:none; border-radius:9px; cursor:pointer; font:inherit; font-size:12.5px; font-weight:500; color:var(--vy-ink); background:transparent; text-decoration:none; }
        .vy-msg-tab.is-on { background:rgba(255,255,255,.85); color:var(--vy-accent); font-weight:600; box-shadow:0 2px 8px rgba(150,90,50,.12); }
        .vy-msg-dot { width:9px; height:9px; border-radius:50%; background:var(--vy-grad); flex-shrink:0; }
        .vy-msg-badge { min-width:17px; height:17px; padding:0 5px; box-sizing:border-box; border-radius:20px; background:var(--vy-grad); color:#fff; font-size:10px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; }
        .vy-msg-list { flex:1; min-height:0; overflow-y:auto; display:flex; flex-direction:column; gap:3px; padding:2px; }
        .vy-msg-conv { display:flex; align-items:center; gap:11px; width:100%; text-align:left; padding:11px; border-radius:15px; background:transparent; border:1px solid transparent; cursor:pointer; text-decoration:none; color:inherit; transition:background .14s; }
        .vy-msg-conv:hover { background:rgba(255,255,255,.5); }
        .vy-msg-conv.is-active { background:rgba(255,255,255,.72); border-color:rgba(255,255,255,.85); box-shadow:0 4px 14px rgba(150,90,50,.12); }
        .vy-msg-av { border-radius:50%; background:var(--vy-grad); display:flex; align-items:center; justify-content:center; color:#fff; font-family:var(--vy-display); font-weight:500; flex-shrink:0; }
        .vy-msg-thread { flex:1; min-height:0; overflow-y:auto; background:rgba(255,252,248,.5); backdrop-filter:blur(22px) saturate(1.15); border:1px solid rgba(255,255,255,.7); border-radius:20px; box-shadow:inset 0 1px 0 rgba(255,255,255,.6); padding:22px; display:flex; flex-direction:column; gap:6px; }
        .vy-msg-item { display:flex; flex-direction:column; }
        .vy-msg-row { display:flex; align-items:flex-end; gap:7px; }
        .vy-msg-bubble { max-width:100%; padding:11px 15px; border-radius:16px; font-size:13.5px; line-height:1.5; box-shadow:0 4px 14px rgba(150,90,50,.08); text-wrap:pretty; white-space:pre-wrap; word-break:break-word; }
        .vy-msg-bubble--me { background:var(--vy-grad); color:#fff; border-radius:16px 16px 5px 16px; }
        .vy-msg-bubble--op { background:rgba(255,255,255,.85); color:var(--vy-ink); border:1px solid rgba(255,255,255,.8); border-radius:16px 16px 16px 5px; }
        .vy-msg-meta { font-size:10.5px; color:var(--vy-ink); margin-top:4px; display:flex; align-items:center; gap:6px; }
        .vy-msg-daysep { display:flex; justify-content:center; margin:8px 0 4px; }
        .vy-msg-daysep span { font-size:10.5px; font-weight:600; letter-spacing:.04em; color:var(--vy-faint); background:rgba(255,255,255,.72); border:1px solid rgba(255,255,255,.8); padding:4px 12px; border-radius:20px; }
        .vy-msg-composer { flex-shrink:0; margin-top:14px; display:flex; align-items:center; gap:8px; border:1px solid rgba(120,70,40,.14); background:rgba(255,255,255,.72); border-radius:26px; padding:6px 6px 6px 14px; }
        .vy-msg-composer input[type=text] { flex:1; min-width:0; border:none; outline:none; background:transparent; font:inherit; font-size:13.5px; color:var(--vy-ink); padding:9px 0; }
        .vy-msg-send { width:40px; height:40px; border-radius:50%; background:var(--vy-grad); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 6px 16px rgba(194,65,12,.35); }
        .vy-msg-attach { width:38px; height:38px; border-radius:50%; background:rgba(120,70,40,.08); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--vy-muted); }
        .vy-msg-attach:hover { background:rgba(120,70,40,.14); }
        .vy-msg-chip { padding:7px 14px; border-radius:30px; background:rgba(255,255,255,.55); border:1px solid rgba(120,70,40,.12); color:var(--vy-muted); font:inherit; font-size:12px; font-weight:500; cursor:pointer; }
        .vy-msg-chip:hover { background:rgba(255,255,255,.85); }
        .vy-msg-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; color:var(--vy-faint); gap:10px; padding:32px 18px; }
        /* GATE 2 — reply quote */
        .vy-msg-quote { border-left:2.5px solid rgba(217,102,46,.5); padding:3px 9px; margin-bottom:5px; border-radius:4px; background:rgba(120,70,40,.06); font-size:11.5px; line-height:1.4; }
        .vy-msg-bubble--me .vy-msg-quote { border-left-color:rgba(255,255,255,.7); background:rgba(255,255,255,.16); color:rgba(255,255,255,.92); }
        .vy-msg-quote b { font-weight:600; }
        /* GATE 2 — attachments */
        .vy-msg-att-img { display:block; max-width:230px; max-height:200px; border-radius:12px; margin-top:6px; border:1px solid rgba(255,255,255,.7); }
        .vy-msg-att-file { display:inline-flex; align-items:center; gap:8px; margin-top:6px; padding:8px 12px; border-radius:11px; background:rgba(255,255,255,.7); border:1px solid rgba(120,70,40,.12); text-decoration:none; color:var(--vy-ink); font-size:12px; }
        .vy-msg-bubble--me + * .vy-msg-att-file, .vy-msg-att-file:hover { background:rgba(255,255,255,.92); }
        .vy-msg-att-voice { display:block; margin-top:6px; width:250px; max-width:100%; height:38px; }
        /* GATE 2 — reactions */
        .vy-msg-reacts { display:flex; flex-wrap:wrap; gap:4px; margin-top:4px; }
        .vy-msg-react { display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:20px; background:rgba(255,255,255,.7); border:1px solid rgba(120,70,40,.12); font-size:12px; cursor:pointer; color:var(--vy-muted); }
        .vy-msg-react.is-mine { background:rgba(217,102,46,.14); border-color:rgba(217,102,46,.4); color:var(--vy-accent); font-weight:600; }
        .vy-msg-react:hover { background:rgba(255,255,255,.95); }
        .vy-react-pop { position:absolute; z-index:30; display:flex; gap:2px; padding:5px; border-radius:14px; background:rgba(255,252,248,.98); border:1px solid rgba(255,255,255,.85); box-shadow:0 14px 40px rgba(120,70,40,.24); }
        .vy-react-pop button { border:none; background:transparent; font-size:17px; cursor:pointer; padding:4px 5px; border-radius:8px; line-height:1; }
        .vy-react-pop button:hover { background:rgba(120,70,40,.1); }
        /* GATE 2 — per-message actions (hover) */
        .vy-msg-acts { display:flex; align-items:center; gap:2px; opacity:0; transition:opacity .12s; align-self:center; }
        /* ⚠️ DO-STAR-1 — :focus-within is NOT a nicety here. The row actions are opacity:0 until hover, but
           they are real <button>s: a keyboard user could TAB to the star, receive focus, and see nothing at
           all — neither the control nor its focus ring (WCAG 2.4.7). Hover alone makes a control mouse-only
           in practice while leaving it in the tab order, which is the worst of both. */
        .vy-msg-item:hover .vy-msg-acts,
        .vy-msg-item:focus-within .vy-msg-acts { opacity:1; }
        .vy-msg-act { width:26px; height:26px; border-radius:50%; border:none; background:rgba(255,255,255,.7); color:var(--vy-muted); cursor:pointer; display:flex; align-items:center; justify-content:center; padding:0; }
        .vy-msg-act:hover { background:rgba(255,255,255,.98); color:var(--vy-accent); }
        .vy-msg-act.is-on { color:var(--vy-accent); }
        /* GATE 2 — receipt tick */
        .vy-msg-receipt { display:inline-flex; align-items:center; gap:2px; }
        /* GATE 2 — pinned banner */
        .vy-pin-banner { flex-shrink:0; display:flex; align-items:center; gap:10px; margin-bottom:10px; padding:9px 13px; border-radius:13px; background:rgba(217,102,46,.08); border:1px solid rgba(217,102,46,.2); font-size:12.5px; }
        /* GATE 2 — starred panel */
        .vy-star-panel { position:absolute; top:calc(100% + 8px); right:0; z-index:41; width:300px; max-height:340px; overflow-y:auto; padding:8px; border-radius:16px; background:rgba(255,252,248,.98); backdrop-filter:blur(24px); border:1px solid rgba(255,255,255,.85); box-shadow:0 20px 54px rgba(120,70,40,.26); }
        .vy-star-row { display:flex; gap:9px; padding:9px 10px; border-radius:11px; text-decoration:none; color:inherit; }
        .vy-star-row:hover { background:rgba(255,255,255,.7); }
    </style>

    @php
        $avGrad = ['linear-gradient(140deg,#E8703A,#C2410C)', 'linear-gradient(140deg,#6FA764,#4F7A46)', 'linear-gradient(140deg,#E8A63A,var(--vy-amber-text))', 'linear-gradient(140deg,#8A6A55,#5E4636)'];
        $activeId = $active['id'] ?? null;
    @endphp

    @if (session('owner_notice'))<div style="margin-bottom:12px;padding:10px 14px;border-radius:12px;background:rgba(79,122,70,.12);color:#3F6A38;font-size:13px">{{ session('owner_notice') }}</div>@endif
    @if (session('owner_error'))<div style="margin-bottom:12px;padding:10px 14px;border-radius:12px;background:rgba(180,64,15,.1);color:#B4400F;font-size:13px">{{ session('owner_error') }}</div>@endif

    <div class="vy-msg-wrap vy-fade">

        {{-- ══ CONVERSATION RAIL ══ --}}
        <aside class="vy-msg-rail" x-data="{ compose: false }">
            {{-- Compose --}}
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;position:relative">
                <span style="font-size:12.5px;color:var(--vy-on-grad)">{{ $total }} {{ \Illuminate\Support\Str::plural('conversation', $total) }}</span>
                <button type="button" @click="compose = ! compose" title="New message" aria-label="New message" style="width:38px;height:38px;border-radius:11px;background:var(--vy-grad);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 16px rgba(194,65,12,.3)">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                </button>
                <div x-show="compose" x-cloak @click.outside="compose = false" style="position:absolute;top:calc(100% + 8px);right:0;z-index:41;width:264px;padding:6px;border-radius:16px;background:rgba(255,252,248,.98);backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.85);box-shadow:0 20px 54px rgba(120,70,40,.26)">
                    <div style="padding:8px 10px 7px;font-size:10.5px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--vy-faint)">New message</div>
                    @forelse ($staff as $i => $member)
                        <a href="{{ route('owner.messages', ['compose' => $member['id']]) }}" style="display:flex;align-items:center;gap:11px;width:100%;text-align:left;padding:9px 10px;border-radius:12px;text-decoration:none;color:inherit">
                            <span class="vy-msg-av" style="width:36px;height:36px;font-size:12.5px;background:{{ $avGrad[$i % count($avGrad)] }}">{{ $member['initials'] }}</span>
                            <span style="min-width:0;flex:1">
                                <span style="display:block;font-size:13.5px;font-weight:500;color:var(--vy-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $member['name'] }}</span>
                                <span style="display:block;font-size:11.5px;color:var(--vy-on-grad);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $member['role'] }}</span>
                            </span>
                        </a>
                    @empty
                        <div style="padding:12px 10px;font-size:12.5px;color:var(--vy-faint)">No management contacts yet.</div>
                    @endforelse
                </div>
            </div>

            {{-- Search (real, server-side ?q) --}}
            <form method="GET" action="{{ route('owner.messages') }}" class="vy-msg-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--vy-on-grad)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" value="{{ $query }}" placeholder="Search messages" aria-label="Search messages">
                @if ($query !== '')<a href="{{ route('owner.messages') }}" title="Clear" style="width:22px;height:22px;border-radius:50%;background:rgba(120,70,40,.1);color:var(--vy-muted);display:flex;align-items:center;justify-content:center;flex-shrink:0;text-decoration:none">&times;</a>@endif
            </form>

            {{-- All / Unread --}}
            <div class="vy-msg-tabs">
                <a href="{{ route('owner.messages', array_filter(['q' => $query ?: null])) }}" class="vy-msg-tab {{ $filter !== 'unread' ? 'is-on' : '' }}">All</a>
                <a href="{{ route('owner.messages', array_filter(['filter' => 'unread', 'q' => $query ?: null])) }}" class="vy-msg-tab {{ $filter === 'unread' ? 'is-on' : '' }}">Unread @if ($unread > 0)<span class="vy-msg-badge" aria-hidden="true">{{ $unread }}</span><span class="vy-sr-only">{{ $unread }} unread</span>@endif</a>
            </div>

            {{-- Conversation list --}}
            <div class="vy-msg-list">
                @forelse ($rows as $i => $row)
                    <a href="{{ route('owner.messages', array_filter(['c' => $row['id'], 'q' => $query ?: null, 'filter' => $filter])) }}" class="vy-msg-conv {{ $activeId === $row['id'] ? 'is-active' : '' }}">
                        <span class="vy-msg-av" style="width:44px;height:44px;font-size:14.5px;background:{{ $avGrad[$i % count($avGrad)] }}">{{ $row['initials'] }}</span>
                        <span style="min-width:0;flex:1">
                            <span style="display:flex;align-items:baseline;justify-content:space-between;gap:8px">
                                <span style="font-family:var(--vy-display);font-weight:600;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row['name'] }}</span>
                                <span style="font-size:10.5px;color:var(--vy-ink);flex-shrink:0">{{ $row['time'] }}</span>
                            </span>
                            <span style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:3px">
                                <span style="font-size:12px;color:{{ $row['unread'] ? 'var(--vy-ink)' : 'var(--vy-muted)' }};font-weight:{{ $row['unread'] ? 600 : 500 }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row['preview'] ?? 'No messages yet' }}</span>
                                @if ($row['unread'])
                                    {{-- A DOT, not a digit. `$row['unread']` is a BOOLEAN (isUnread), so the
                                         hardcoded "1" that used to sit here read as "one unread message" on a
                                         thread that might hold three. The tab badge beside it IS a real count,
                                         which made the two look identical while meaning different things.
                                         A real per-thread count is logged as missing backend. --}}
                                    <span class="vy-msg-dot" aria-hidden="true"></span><span class="vy-sr-only">Unread</span>
                                @endif
                            </span>
                        </span>
                    </a>
                @empty
                    <div class="vy-msg-empty">
                        @if ($query !== '')
                            <div style="font-size:13px;font-weight:500;color:#6B615A">No messages match “{{ $query }}”</div>
                            <div style="font-size:11.5px">Try another name or keyword</div>
                        @elseif ($filter === 'unread')
                            <div style="font-size:13px;font-weight:500;color:#6B615A">You're all caught up</div>
                            <div style="font-size:11.5px">No unread messages</div>
                        @else
                            <div style="font-size:13px;font-weight:500;color:#6B615A">No conversations yet</div>
                            <div style="font-size:11.5px">Use New message to reach your management team</div>
                        @endif
                    </div>
                @endforelse
            </div>
        </aside>

        {{-- ══ THREAD PANE ══ --}}
        <section class="vy-msg-pane">
            @if ($composeStaff !== null)
                {{-- NEW conversation with a real staff member --}}
                <div style="display:flex;align-items:center;gap:13px;margin-bottom:14px;flex-shrink:0">
                    <span class="vy-msg-av" style="width:46px;height:46px;font-size:15px">{{ $composeStaff['initials'] }}</span>
                    <div><h2 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:20px">{{ $composeStaff['name'] }}</h2><div style="font-size:12.5px;color:var(--vy-muted);margin-top:2px">{{ $composeStaff['role'] }}</div></div>
                </div>
                <div class="vy-msg-thread">
                    <div class="vy-msg-empty">
                        <div style="width:52px;height:52px;border-radius:16px;background:rgba(217,102,46,.1);color:var(--vy-accent);display:flex;align-items:center;justify-content:center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2z"/></svg></div>
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:15px;color:#6B615A">Start your conversation with {{ explode(' ', $composeStaff['name'])[0] }}</div>
                        <div style="font-size:12.5px;max-width:260px">Send a message below — they'll reply in your inbox.</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('owner.messages.start') }}" enctype="multipart/form-data" class="vy-msg-composer" x-data="{ files: 0 }" style="margin-top:14px">
                    @csrf
                    <input type="hidden" name="staff_id" value="{{ $composeStaff['id'] }}">
                    <button type="button" class="vy-msg-attach" title="Attach a file" aria-label="Attach a file" @click="$refs.file.click()"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.4 11.05 12.25 20.2a5 5 0 0 1-7.07-7.07l9.19-9.19a3 3 0 0 1 4.24 4.24l-9.2 9.19a1 1 0 0 1-1.41-1.41l8.48-8.49"/></svg></button>
                    <input type="file" name="attachments[]" multiple x-ref="file" class="sr-only" style="display:none" @change="files = $refs.file.files.length">
                    <input type="text" name="body" required maxlength="5000" placeholder="Message {{ explode(' ', $composeStaff['name'])[0] }}…" aria-label="Message">
                    <span x-show="files > 0" x-cloak style="font-size:11.5px;color:var(--vy-accent);white-space:nowrap"><span x-text="files"></span> file(s)</span>
                    <button type="submit" class="vy-msg-send" aria-label="Send"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg></button>
                </form>
            @elseif ($active !== null)
                <div x-data="{ replyTo: null, star: false, reactFor: null, files: 0 }">
                    {{-- Active thread header (staff correspondent — presence honestly omitted) + starred panel toggle --}}
                    <div style="display:flex;align-items:center;gap:13px;margin-bottom:14px;flex-shrink:0">
                        <span class="vy-msg-av" style="width:46px;height:46px;font-size:15px">{{ $active['initials'] }}</span>
                        <div style="flex:1;min-width:0"><h2 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:20px">{{ $active['name'] }}</h2><div style="font-size:12.5px;color:var(--vy-muted);margin-top:2px">{{ $active['role'] }}</div></div>
                        <div style="position:relative">
                            <button type="button" @click="star = ! star" title="Starred messages" aria-label="Starred messages" style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:11px;border:1px solid rgba(120,70,40,.14);background:rgba(255,255,255,.6);cursor:pointer;font:inherit;font-size:12.5px;color:var(--vy-muted)">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="{{ count($starred) > 0 ? '#E8A63A' : 'none' }}" stroke="var(--vy-amber-text)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
                                {{ count($starred) }}
                            </button>
                            <div x-show="star" x-cloak @click.outside="star = false" class="vy-star-panel">
                                <div style="padding:6px 10px 8px;font-size:10.5px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--vy-faint)">Starred</div>
                                @forelse ($starred as $s)
                                    <a href="#msg-{{ $s['id'] }}" @click="star = false" class="vy-star-row">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#E8A63A" stroke="var(--vy-amber-text)" stroke-width="1.5" style="flex-shrink:0;margin-top:2px"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
                                        <span style="min-width:0"><span style="display:block;font-size:11px;color:var(--vy-faint)">{{ $s['author'] }} · {{ $s['time'] }}</span><span style="display:block;font-size:12.5px;color:var(--vy-ink)">{{ $s['snippet'] }}</span></span>
                                    </a>
                                @empty
                                    <div style="padding:12px 10px;font-size:12.5px;color:var(--vy-faint)">No starred messages yet. Star a message to keep it here.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Pinned banner (real pinned_message_id) --}}
                    @if ($pinned !== null)
                        <div class="vy-pin-banner">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M12 17v5"/><path d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z"/></svg>
                            <span style="flex:1;min-width:0"><span style="color:var(--vy-faint)">Pinned · {{ $pinned['author'] }} — </span>{{ $pinned['snippet'] }}</span>
                            <form method="POST" action="{{ route('owner.messages.pin', $active['id']) }}" style="flex-shrink:0">
                                @csrf
                                <input type="hidden" name="message_id" value="{{ $pinned['id'] }}">
                                <button type="submit" title="Unpin" aria-label="Unpin" style="border:none;background:transparent;color:var(--vy-muted);cursor:pointer;font-size:12px">Unpin</button>
                            </form>
                        </div>
                    @endif

                    {{-- Thread --}}
                    <div class="vy-msg-thread" id="own-thread">
                        @forelse ($messages as $m)
                            @if ($m['day_label'] !== null)<div class="vy-msg-daysep"><span>{{ $m['day_label'] }}</span></div>@endif
                            <div class="vy-msg-item" id="msg-{{ $m['id'] }}" style="align-items:{{ $m['mine'] ? 'flex-end' : 'flex-start' }}">
                                <div class="vy-msg-row" style="flex-direction:{{ $m['mine'] ? 'row-reverse' : 'row' }};max-width:80%">
                                    <div style="min-width:0">
                                        {{-- reply-to quote --}}
                                        @if ($m['reply_to'] !== null)
                                            <div style="display:flex;justify-content:{{ $m['mine'] ? 'flex-end' : 'flex-start' }}">
                                                <div class="vy-msg-quote" style="max-width:100%"><b>{{ $m['reply_to']['author'] }}</b> · {{ $m['reply_to']['snippet'] }}</div>
                                            </div>
                                        @endif
                                        @if ($m['body'] !== '')
                                            <div class="vy-msg-bubble {{ $m['mine'] ? 'vy-msg-bubble--me' : 'vy-msg-bubble--op' }}" style="{{ $m['mine'] ? 'margin-left:auto' : '' }}">{{ $m['body'] }}</div>
                                        @endif
                                        {{-- attachments --}}
                                        @foreach ($m['attachments'] as $att)
                                            <div style="display:flex;justify-content:{{ $m['mine'] ? 'flex-end' : 'flex-start' }}">
                                                @if ($att['is_image'])
                                                    <a href="{{ $att['url'] }}" target="_blank" rel="noopener"><img src="{{ $att['url'] }}" alt="{{ $att['name'] }}" class="vy-msg-att-img"></a>
                                                @elseif (($att['kind'] ?? '') === 'voice')
                                                    {{-- a voice note from the team (2026-10-06) — played here, never downloaded --}}
                                                    <audio controls preload="none" src="{{ $att['url'] }}" class="vy-msg-att-voice" aria-label="Voice message"></audio>
                                                @else
                                                    <a href="{{ $att['url'] }}" target="_blank" rel="noopener" class="vy-msg-att-file">
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                                        <span style="min-width:0"><span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:170px">{{ $att['name'] }}</span><span style="color:var(--vy-faint);font-size:10.5px">{{ $att['size'] }}</span></span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endforeach
                                        {{-- reactions --}}
                                        @if (count($m['reactions']) > 0)
                                            <div class="vy-msg-reacts" style="justify-content:{{ $m['mine'] ? 'flex-end' : 'flex-start' }}">
                                                @foreach ($m['reactions'] as $r)
                                                    <form method="POST" action="{{ route('owner.messages.react', $active['id']) }}">
                                                        @csrf
                                                        <input type="hidden" name="message_id" value="{{ $m['id'] }}">
                                                        <input type="hidden" name="emoji" value="{{ $r['emoji'] }}">
                                                        <button type="submit" class="vy-msg-react {{ $r['mine'] ? 'is-mine' : '' }}">{{ $r['emoji'] }} {{ $r['count'] }}</button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        @endif
                                        {{-- meta + receipt --}}
                                        <div class="vy-msg-meta" style="justify-content:{{ $m['mine'] ? 'flex-end' : 'flex-start' }}">
                                            <span>{{ $m['meta'] }}</span>
                                            @if ($m['failed'])
                                                <span style="color:#B4400F;font-weight:600">· Not delivered</span>
                                            @elseif ($m['receipt'] !== null)
                                                <span class="vy-msg-receipt" style="color:{{ $m['receipt'] === 'Read' ? 'var(--vy-accent)' : 'var(--vy-faint)' }}">· {{ $m['receipt'] === 'Read' ? '✓✓' : '✓' }} {{ $m['receipt'] }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- per-message actions (hover) --}}
                                    <div class="vy-msg-acts" style="position:relative">
                                        <button type="button" class="vy-msg-act" title="Reply" aria-label="Reply" @click="replyTo = { id: '{{ $m['id'] }}', author: @js($m['author']), snippet: @js(\Illuminate\Support\Str::limit($m['body'] ?: 'Attachment', 60)) }; $refs.body.focus()">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17l-5-5 5-5"/><path d="M4 12h11a5 5 0 0 1 5 5v1"/></svg>
                                        </button>
                                        <button type="button" class="vy-msg-act" title="React" aria-label="React" @click="reactFor = (reactFor === '{{ $m['id'] }}' ? null : '{{ $m['id'] }}')">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                                        </button>
                                        {{-- ⚠️ PURELY CLIENT-SIDE: it copies the body ALREADY RENDERED on this
                                             page — no route, no request, nothing new exposed (#28 unchanged).
                                             A message with no body (an attachment-only row) has nothing to copy,
                                             so the control is not offered rather than copying an empty string.
                                             `aria-live` announces the outcome; the label reverts so the button
                                             does not lie about its next action. --}}
                                        @if (trim((string) $m['body']) !== '')
                                            <button type="button" class="vy-msg-act" title="Copy text" aria-label="Copy text"
                                                x-data="{ done: false }"
                                                @click="navigator.clipboard.writeText(@js($m['body'])).then(() => { done = true; setTimeout(() => done = false, 1600) })"
                                                :title="done ? 'Copied' : 'Copy text'" :aria-label="done ? 'Copied' : 'Copy text'">
                                                <template x-if="!done">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                                </template>
                                                <template x-if="done">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                                </template>
                                            </button>
                                        @endif
                                        {{-- ⚠️ DO-STAR-1 — a BOOKMARK toggle, not a rating (it never touches
                                             <x-star-rating>, which is for a 0–5 count). Its STATE is carried by
                                             the GLYPH — filled vs outline — so it survives greyscale, and by
                                             `aria-pressed` so it is announced.
                                             ⚠️ `aria-label` used to be the static string "Star" on BOTH states,
                                             which OVERRODE the state-aware `title` beside it: a screen-reader
                                             user heard "Star" on a button that would unstar. The correct words
                                             were already being computed and then discarded. --}}
                                        <form method="POST" action="{{ route('owner.messages.star', $active['id']) }}">
                                            @csrf
                                            <input type="hidden" name="message_id" value="{{ $m['id'] }}">
                                            <button type="submit" class="vy-msg-act {{ $m['starred'] ? 'is-on' : '' }}" title="{{ $m['starred'] ? 'Unstar' : 'Star' }}" aria-label="Star message" aria-pressed="{{ $m['starred'] ? 'true' : 'false' }}">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="{{ $m['starred'] ? '#E8A63A' : 'none' }}" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('owner.messages.pin', $active['id']) }}">
                                            @csrf
                                            <input type="hidden" name="message_id" value="{{ $m['id'] }}">
                                            <button type="submit" class="vy-msg-act {{ $m['pinned'] ? 'is-on' : '' }}" title="{{ $m['pinned'] ? 'Unpin' : 'Pin' }}" aria-label="Pin message" aria-pressed="{{ $m['pinned'] ? 'true' : 'false' }}">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="{{ $m['pinned'] ? 'var(--vy-accent)' : 'none' }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M9 10.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24V16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V7a1 1 0 0 1 1-1 2 2 0 0 0 0-4H8a2 2 0 0 0 0 4 1 1 0 0 1 1 1z"/></svg>
                                            </button>
                                        </form>
                                        {{-- reaction palette --}}
                                        <div x-show="reactFor === '{{ $m['id'] }}'" x-cloak @click.outside="reactFor = null" class="vy-react-pop" style="bottom:calc(100% + 6px);{{ $m['mine'] ? 'right:0' : 'left:0' }}">
                                            @foreach ($reactionPalette as $emoji)
                                                <form method="POST" action="{{ route('owner.messages.react', $active['id']) }}">
                                                    @csrf
                                                    <input type="hidden" name="message_id" value="{{ $m['id'] }}">
                                                    <input type="hidden" name="emoji" value="{{ $emoji }}">
                                                    <button type="submit">{{ $emoji }}</button>
                                                </form>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="vy-msg-empty">
                                <div style="width:52px;height:52px;border-radius:16px;background:rgba(217,102,46,.1);color:var(--vy-accent);display:flex;align-items:center;justify-content:center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2z"/></svg></div>
                                <div style="font-family:var(--vy-display);font-weight:600;font-size:15px;color:#6B615A">Start of your conversation with {{ $active['first_name'] }}</div>
                                <div style="font-size:12.5px;max-width:260px">Send a message below to start the conversation.</div>
                            </div>
                        @endforelse
                    </div>

                    {{-- reply-to context banner (Alpine) --}}
                    <div x-show="replyTo" x-cloak style="flex-shrink:0;margin-top:10px;display:flex;align-items:center;gap:9px;padding:8px 13px;border-radius:12px;background:rgba(120,70,40,.06);border:1px solid rgba(120,70,40,.12)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M9 17l-5-5 5-5"/><path d="M4 12h11a5 5 0 0 1 5 5v1"/></svg>
                        <span style="flex:1;min-width:0;font-size:12px;color:var(--vy-muted)">Replying to <b x-text="replyTo?.author"></b> · <span x-text="replyTo?.snippet"></span></span>
                        <button type="button" @click="replyTo = null" title="Cancel reply" aria-label="Cancel reply" style="border:none;background:transparent;color:var(--vy-muted);cursor:pointer;font-size:15px;line-height:1">&times;</button>
                    </div>

                    {{-- Composer --}}
                    <form method="POST" action="{{ route('owner.messages.reply', $active['id']) }}" enctype="multipart/form-data" class="vy-msg-composer">
                        @csrf
                        <input type="hidden" name="reply_to_message_id" :value="replyTo?.id ?? ''">
                        <button type="button" class="vy-msg-attach" title="Attach a file" aria-label="Attach a file" @click="$refs.file.click()"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.4 11.05 12.25 20.2a5 5 0 0 1-7.07-7.07l9.19-9.19a3 3 0 0 1 4.24 4.24l-9.2 9.19a1 1 0 0 1-1.41-1.41l8.48-8.49"/></svg></button>
                        <input type="file" name="attachments[]" multiple x-ref="file" style="display:none" @change="files = $refs.file.files.length">
                        <input type="text" name="body" x-ref="body" maxlength="5000" placeholder="Message {{ $active['first_name'] }}…" aria-label="Message">
                        <span x-show="files > 0" x-cloak style="font-size:11.5px;color:var(--vy-accent);white-space:nowrap"><span x-text="files"></span> file(s)</span>
                        <button type="submit" class="vy-msg-send" aria-label="Send"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg></button>
                    </form>
                    {{-- Quick replies — seed the draft (a real send on submit) --}}
                    <div style="flex-shrink:0;display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                        @foreach ($quickReplies as $chip)
                            <button type="button" class="vy-msg-chip" @click="$refs.body.value = @js($chip); $refs.body.focus()">{{ $chip }}</button>
                        @endforeach
                    </div>
                </div>
            @else
                {{-- No conversations at all --}}
                <div class="vy-msg-thread">
                    <div class="vy-msg-empty">
                        <div style="width:52px;height:52px;border-radius:16px;background:rgba(217,102,46,.1);color:var(--vy-accent);display:flex;align-items:center;justify-content:center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2z"/></svg></div>
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:15px;color:#6B615A">No conversations yet</div>
                        <div style="font-size:12.5px;max-width:260px">Use <strong>New message</strong> to reach your management team.</div>
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-owner.shell>
