{{-- Operator Unified Inbox — thread list (design pass · Inbox UI). Styled on the shared operator design
     system via <x-operator.shell>. Threads + channels are REAL and tenant-scoped; the badge is keyed to
     the real channel enum. The list only READS; opening a row navigates to the real conversation. Alpine
     filters the REAL rendered rows client-side (no invented data). PII: guest NAME only — the encrypted
     participant identity/contact is never rendered. --}}
<div>
    <x-operator.shell active="inbox" title="Inbox" subtitle="Guest messages" :viewer="$viewer">
        <div x-data="{ filter: 'all', channel: 'all', q: '' }">

            {{-- DERIVED summary chips + honest NO-BACKEND Compose --}}
            <div class="vy-rack-toolbar vy-fade">
                <div class="vy-left vy-summary" style="margin: 0;">
                    <div class="vy-summary-chip @if ($summary['unread'] > 0) is-due @endif" @click="filter = 'unread'" style="cursor: pointer;"><span class="vy-sc-num">{{ $summary['unread'] }}</span><span class="vy-sc-label">Unread</span></div>
                    <div class="vy-summary-chip" @click="filter = 'all'" style="cursor: pointer;"><span class="vy-sc-num">{{ $summary['open'] }}</span><span class="vy-sc-label">Open</span></div>
                    <div class="vy-summary-chip" @click="filter = 'mine'" style="cursor: pointer;"><span class="vy-sc-num">{{ $summary['mine'] }}</span><span class="vy-sc-label">Assigned to me</span></div>
                    <div class="vy-summary-chip" @click="filter = 'closed'" style="cursor: pointer;"><span class="vy-sc-num">{{ $summary['closed'] }}</span><span class="vy-sc-label">Closed</span></div>
                </div>
                <div class="vy-right">
                    <span class="vy-pill is-soon" aria-disabled="true">Compose <span class="vy-soon-tag">Soon</span></span>
                </div>
            </div>

            {{-- Filter row: segmented status toggle + channel filter + search --}}
            <div class="vy-rack-toolbar vy-fade" style="margin-top: 12px;">
                <div class="vy-left">
                    <div class="vy-seg" style="max-width: 420px;">
                        <label class="vy-seg-opt" :class="{ 'is-checked': filter === 'all' }" @click="filter = 'all'">All</label>
                        <label class="vy-seg-opt" :class="{ 'is-checked': filter === 'unread' }" @click="filter = 'unread'">Unread</label>
                        <label class="vy-seg-opt" :class="{ 'is-checked': filter === 'mine' }" @click="filter = 'mine'">Assigned to me</label>
                        <label class="vy-seg-opt" :class="{ 'is-checked': filter === 'closed' }" @click="filter = 'closed'">Closed</label>
                    </div>
                </div>
                <div class="vy-right" style="gap: 10px;">
                    <select class="vy-input" style="width: auto; padding: 8px 12px;" x-model="channel" aria-label="Filter by channel">
                        <option value="all">All channels</option>
                        @foreach ($channels as $c)
                            <option value="{{ $c['value'] }}">{{ $c['label'] }}</option>
                        @endforeach
                    </select>
                    <div class="vy-inwrap" style="width: 220px;">
                        <span class="vy-in-ico"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg></span>
                        <input type="search" class="vy-input" placeholder="Search conversations…" x-model="q" aria-label="Search conversations">
                    </div>
                </div>
            </div>

            @if (count($threads) === 0)
                <div class="vy-card vy-fade"><div class="vy-empty" style="padding: 40px 20px;">No conversations yet — inbound guest messages will appear here.</div></div>
            @else
                <div class="vy-card vy-fade" style="margin-top: 16px;">
                    <div class="vy-inbox">
                        @foreach ($threads as $t)
                            <a class="vy-thread-row @if ($t['is_unread']) is-unread @endif @if ($t['status'] === 'closed') vy-thread-closed @endif"
                               href="{{ route('app.inbox.show', $t['id']) }}"
                               x-show="
                                   ({'all': @js($t['status']) !== 'closed', 'unread': @js($t['is_unread']) && @js($t['status']) !== 'closed', 'mine': @js($t['mine']) && @js($t['status']) !== 'closed', 'closed': @js($t['status']) === 'closed'})[filter]
                                   && (channel === 'all' || channel === @js($t['channel_key']))
                                   && (q === '' || @js($t['search']).includes(q.toLowerCase()))
                               " x-transition.opacity>
                                <span class="vy-thread-av">
                                    {{ $t['initials'] }}
                                    @if ($t['is_unread'])<span class="vy-unread-dot"></span>@endif
                                </span>
                                <div class="vy-thread-main">
                                    <div class="vy-thread-top">
                                        <span class="vy-thread-name">{{ $t['guest'] }}</span>
                                        <span class="vy-chan tone-{{ $t['channel_tone'] }}"><span class="vy-chan-glyph">{{ $t['channel_short'] }}</span>{{ $t['channel_label'] }}</span>
                                        @if ($t['status'] === 'closed')<span class="vy-tag tone-departed"><span class="vy-dot"></span>Closed</span>@endif
                                    </div>
                                    <div class="vy-thread-preview">{{ $t['preview'] }}</div>
                                    @if ($t['reservation'])
                                        <span class="vy-thread-res">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                                            {{ $t['reservation'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="vy-thread-right">
                                    <span class="vy-thread-time">{{ $t['time'] }}</span>
                                    @if ($t['unread'] > 0)<span class="vy-unread-count">{{ $t['unread'] }}</span>@endif
                                    @if ($t['assignee'])
                                        <span class="vy-thread-assignee"><span class="vy-mini-avatar">{{ $t['assignee_initials'] }}</span></span>
                                    @else
                                        <span class="vy-assignee-none">Unassigned</span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </x-operator.shell>
</div>
