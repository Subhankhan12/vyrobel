{{-- Owner portal · MESSAGES (Wave 3 → exact-match 3-pane rebuild) — the owner↔OPERATOR relay as a full inbox,
     matched to the decoded "Owner Messages" wireframe on the REAL relay. See App\Livewire\Owner\OwnerMessages
     for the REAL/DERIVED/OMITTED map. Shell + --vy-* tokens (warm glass, Sora/Inter, vyFadeUp).
     🔒 #28 owner↔operator ONLY (OwnerScope on owner_id → a guest/cross-owner thread 404s) + staff correspondents
     named, NEVER owner→guest; #20 every write (send/compose) is a controller POST, audited; #39 honest — presence
     + typing OMITTED (no real-time infra), never a fake "online". Reply/attach/react/read/star/pin = GATE 2.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="messages" :title="'Messages'" :subtitle="$subtitle" :viewer="$viewer">
    <style>
        .vy-msg-wrap { display:flex; gap:18px; align-items:stretch; height:calc(100vh - 150px); min-height:460px; }
        .vy-msg-rail { width:320px; flex-shrink:0; display:flex; flex-direction:column; gap:12px; min-height:0; }
        .vy-msg-pane { flex:1; min-width:0; display:flex; flex-direction:column; min-height:0; }
        .vy-msg-search { display:flex; align-items:center; gap:9px; padding:8px 12px; border-radius:12px; background:rgba(255,252,248,.7); border:1px solid var(--vy-glass-border); box-shadow:inset 0 1px 0 rgba(255,255,255,.6); }
        .vy-msg-search input { flex:1; min-width:0; border:none; outline:none; background:transparent; font:inherit; font-size:13px; color:var(--vy-ink); }
        .vy-msg-tabs { display:flex; gap:3px; padding:3px; border-radius:12px; background:rgba(120,70,40,.06); border:1px solid rgba(255,255,255,.6); }
        .vy-msg-tab { flex:1; display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:7px 10px; border:none; border-radius:9px; cursor:pointer; font:inherit; font-size:12.5px; font-weight:500; color:var(--vy-muted); background:transparent; text-decoration:none; }
        .vy-msg-tab.is-on { background:rgba(255,255,255,.85); color:var(--vy-accent); font-weight:600; box-shadow:0 2px 8px rgba(150,90,50,.12); }
        .vy-msg-badge { min-width:17px; height:17px; padding:0 5px; box-sizing:border-box; border-radius:20px; background:var(--vy-grad); color:#fff; font-size:10px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; }
        .vy-msg-list { flex:1; min-height:0; overflow-y:auto; display:flex; flex-direction:column; gap:3px; padding:2px; }
        .vy-msg-conv { display:flex; align-items:center; gap:11px; width:100%; text-align:left; padding:11px; border-radius:15px; background:transparent; border:1px solid transparent; cursor:pointer; text-decoration:none; color:inherit; transition:background .14s; }
        .vy-msg-conv:hover { background:rgba(255,255,255,.5); }
        .vy-msg-conv.is-active { background:rgba(255,255,255,.72); border-color:rgba(255,255,255,.85); box-shadow:0 4px 14px rgba(150,90,50,.12); }
        .vy-msg-av { border-radius:50%; background:var(--vy-grad); display:flex; align-items:center; justify-content:center; color:#fff; font-family:var(--vy-display); font-weight:500; flex-shrink:0; }
        .vy-msg-thread { flex:1; min-height:0; overflow-y:auto; background:rgba(255,252,248,.5); backdrop-filter:blur(22px) saturate(1.15); border:1px solid rgba(255,255,255,.7); border-radius:20px; box-shadow:inset 0 1px 0 rgba(255,255,255,.6); padding:22px; display:flex; flex-direction:column; gap:14px; }
        .vy-msg-bubble { max-width:100%; padding:11px 15px; border-radius:16px; font-size:13.5px; line-height:1.5; box-shadow:0 4px 14px rgba(150,90,50,.08); text-wrap:pretty; }
        .vy-msg-bubble--me { background:var(--vy-grad); color:#fff; border-radius:16px 16px 5px 16px; }
        .vy-msg-bubble--op { background:rgba(255,255,255,.85); color:var(--vy-ink); border:1px solid rgba(255,255,255,.8); border-radius:16px 16px 16px 5px; }
        .vy-msg-meta { font-size:10.5px; color:var(--vy-faint); margin-top:4px; display:flex; align-items:center; gap:6px; }
        .vy-msg-daysep { display:flex; justify-content:center; margin:4px 0 2px; }
        .vy-msg-daysep span { font-size:10.5px; font-weight:600; letter-spacing:.04em; color:var(--vy-faint); background:rgba(255,255,255,.72); border:1px solid rgba(255,255,255,.8); padding:4px 12px; border-radius:20px; }
        .vy-msg-composer { flex-shrink:0; margin-top:14px; display:flex; align-items:center; gap:8px; border:1px solid rgba(120,70,40,.14); background:rgba(255,255,255,.72); border-radius:26px; padding:6px 6px 6px 14px; }
        .vy-msg-composer input { flex:1; min-width:0; border:none; outline:none; background:transparent; font:inherit; font-size:13.5px; color:var(--vy-ink); padding:9px 0; }
        .vy-msg-send { width:40px; height:40px; border-radius:50%; background:var(--vy-grad); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 6px 16px rgba(194,65,12,.35); }
        .vy-msg-chip { padding:7px 14px; border-radius:30px; background:rgba(255,255,255,.55); border:1px solid rgba(120,70,40,.12); color:var(--vy-muted); font:inherit; font-size:12px; font-weight:500; cursor:pointer; }
        .vy-msg-chip:hover { background:rgba(255,255,255,.85); }
        .vy-msg-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; color:var(--vy-faint); gap:10px; padding:32px 18px; }
    </style>

    @php
        $avGrad = ['linear-gradient(140deg,#E8703A,#C2410C)', 'linear-gradient(140deg,#6FA764,#4F7A46)', 'linear-gradient(140deg,#E8A63A,#C08A1E)', 'linear-gradient(140deg,#8A6A55,#5E4636)'];
        $activeId = $active['id'] ?? null;
    @endphp

    @if (session('owner_notice'))<div style="margin-bottom:12px;padding:10px 14px;border-radius:12px;background:rgba(79,122,70,.12);color:#3F6A38;font-size:13px">{{ session('owner_notice') }}</div>@endif
    @if (session('owner_error'))<div style="margin-bottom:12px;padding:10px 14px;border-radius:12px;background:rgba(180,64,15,.1);color:#B4400F;font-size:13px">{{ session('owner_error') }}</div>@endif

    <div class="vy-msg-wrap vy-fade">

        {{-- ══ CONVERSATION RAIL ══ --}}
        <aside class="vy-msg-rail" x-data="{ compose: false }">
            {{-- Compose --}}
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;position:relative">
                <span style="font-size:12.5px;color:var(--vy-faint)">{{ $total }} {{ \Illuminate\Support\Str::plural('conversation', $total) }}</span>
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
                                <span style="display:block;font-size:11.5px;color:var(--vy-faint);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $member['role'] }}</span>
                            </span>
                        </a>
                    @empty
                        <div style="padding:12px 10px;font-size:12.5px;color:var(--vy-faint)">No management contacts yet.</div>
                    @endforelse
                </div>
            </div>

            {{-- Search (real, server-side ?q) --}}
            <form method="GET" action="{{ route('owner.messages') }}" class="vy-msg-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#B0A49C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" value="{{ $query }}" placeholder="Search messages" aria-label="Search messages">
                @if ($query !== '')<a href="{{ route('owner.messages') }}" title="Clear" style="width:22px;height:22px;border-radius:50%;background:rgba(120,70,40,.1);color:var(--vy-muted);display:flex;align-items:center;justify-content:center;flex-shrink:0;text-decoration:none">&times;</a>@endif
            </form>

            {{-- All / Unread --}}
            <div class="vy-msg-tabs">
                <a href="{{ route('owner.messages', array_filter(['q' => $query ?: null])) }}" class="vy-msg-tab {{ $filter !== 'unread' ? 'is-on' : '' }}">All</a>
                <a href="{{ route('owner.messages', array_filter(['filter' => 'unread', 'q' => $query ?: null])) }}" class="vy-msg-tab {{ $filter === 'unread' ? 'is-on' : '' }}">Unread @if ($unread > 0)<span class="vy-msg-badge">{{ $unread }}</span>@endif</a>
            </div>

            {{-- Conversation list --}}
            <div class="vy-msg-list">
                @forelse ($rows as $i => $row)
                    <a href="{{ route('owner.messages', array_filter(['c' => $row['id'], 'q' => $query ?: null, 'filter' => $filter])) }}" class="vy-msg-conv {{ $activeId === $row['id'] ? 'is-active' : '' }}">
                        <span class="vy-msg-av" style="width:44px;height:44px;font-size:14.5px;background:{{ $avGrad[$i % count($avGrad)] }}">{{ $row['initials'] }}</span>
                        <span style="min-width:0;flex:1">
                            <span style="display:flex;align-items:baseline;justify-content:space-between;gap:8px">
                                <span style="font-family:var(--vy-display);font-weight:600;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row['name'] }}</span>
                                <span style="font-size:10.5px;color:var(--vy-faint);flex-shrink:0">{{ $row['time'] }}</span>
                            </span>
                            <span style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:3px">
                                <span style="font-size:12px;color:{{ $row['unread'] ? 'var(--vy-ink)' : 'var(--vy-muted)' }};font-weight:{{ $row['unread'] ? 600 : 500 }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row['preview'] ?? 'No messages yet' }}</span>
                                @if ($row['unread'])<span class="vy-msg-badge" style="min-width:18px;height:18px">1</span>@endif
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
                    <div><h2 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:20px">{{ $composeStaff['name'] }}</h2><div style="font-size:12.5px;color:var(--vy-faint);margin-top:2px">{{ $composeStaff['role'] }}</div></div>
                </div>
                <div class="vy-msg-thread">
                    <div class="vy-msg-empty">
                        <div style="width:52px;height:52px;border-radius:16px;background:rgba(217,102,46,.1);color:var(--vy-accent);display:flex;align-items:center;justify-content:center"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2z"/></svg></div>
                        <div style="font-family:var(--vy-display);font-weight:600;font-size:15px;color:#6B615A">Start your conversation with {{ explode(' ', $composeStaff['name'])[0] }}</div>
                        <div style="font-size:12.5px;max-width:260px">Send a message below — they'll reply in your inbox.</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('owner.messages.start') }}" class="vy-msg-composer" style="margin-top:14px">
                    @csrf
                    <input type="hidden" name="staff_id" value="{{ $composeStaff['id'] }}">
                    <input type="text" name="body" required maxlength="5000" placeholder="Message {{ explode(' ', $composeStaff['name'])[0] }}…" aria-label="Message">
                    <button type="submit" class="vy-msg-send" aria-label="Send"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg></button>
                </form>
            @elseif ($active !== null)
                {{-- Active thread header (staff correspondent — presence honestly omitted) --}}
                <div style="display:flex;align-items:center;gap:13px;margin-bottom:14px;flex-shrink:0">
                    <span class="vy-msg-av" style="width:46px;height:46px;font-size:15px">{{ $active['initials'] }}</span>
                    <div><h2 style="margin:0;font-family:var(--vy-display);font-weight:600;font-size:20px">{{ $active['name'] }}</h2><div style="font-size:12.5px;color:var(--vy-faint);margin-top:2px">{{ $active['role'] }}</div></div>
                </div>

                {{-- Thread --}}
                <div class="vy-msg-thread" id="own-thread">
                    @forelse ($messages as $m)
                        @if ($m['day_label'] !== null)<div class="vy-msg-daysep"><span>{{ $m['day_label'] }}</span></div>@endif
                        <div style="display:flex;justify-content:{{ $m['mine'] ? 'flex-end' : 'flex-start' }}">
                            <div style="max-width:74%">
                                <div class="vy-msg-bubble {{ $m['mine'] ? 'vy-msg-bubble--me' : 'vy-msg-bubble--op' }}">{{ $m['body'] }}</div>
                                <div class="vy-msg-meta" style="justify-content:{{ $m['mine'] ? 'flex-end' : 'flex-start' }}">
                                    <span>{{ $m['meta'] }}</span>
                                    @if ($m['failed'])<span style="color:#B4400F;font-weight:600">· Not delivered</span>@endif
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

                {{-- Composer --}}
                <form method="POST" action="{{ route('owner.messages.reply', $active['id']) }}" class="vy-msg-composer" x-data="{ setDraft(t) { this.$refs.body.value = t; this.$refs.body.focus(); } }" x-ref="composer">
                    @csrf
                    <input type="text" name="body" x-ref="body" required maxlength="5000" placeholder="Message {{ $active['first_name'] }}…" aria-label="Message">
                    <button type="submit" class="vy-msg-send" aria-label="Send"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></svg></button>
                </form>
                {{-- Quick replies — seed the draft (a real send on submit) --}}
                <div style="flex-shrink:0;display:flex;gap:8px;margin-top:10px;flex-wrap:wrap">
                    @foreach ($quickReplies as $chip)
                        <button type="button" class="vy-msg-chip" @click="document.querySelector('form.vy-msg-composer input[name=body]').value = @js($chip); document.querySelector('form.vy-msg-composer input[name=body]').focus()">{{ $chip }}</button>
                    @endforeach
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
