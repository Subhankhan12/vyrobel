{{-- Operator Unified Inbox — conversation (design pass · Inbox UI). Styled on the shared operator design
     system via <x-operator.shell>. The transcript + delivery states are REAL and tenant-scoped; the reply
     + lifecycle actions POST to the real InboxService (guarded) and persist. A deferred channel shows an
     honest "not connected" composer — never a fake send. PII: guest NAME + the CHANNEL only; the encrypted
     participant identity/contact is never rendered. --}}
<div>
    <x-operator.shell active="inbox" title="Conversation"
        :subtitle="$thread['guest'] . ' · ' . $thread['channel_label']" :viewer="$viewer">

        @if (session('inbox_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                {{ session('inbox_notice') }}
            </div>
        @endif
        @if (session('inbox_error'))
            <div class="vy-flash is-err vy-fade" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v5M12 16h.01M10.3 3.9L2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                {{ session('inbox_error') }}
            </div>
        @endif

        <div class="vy-detail-cols vy-fade">
            {{-- MAIN: header + transcript + composer --}}
            <div class="vy-stack">
                <div class="vy-card">
                    <div class="vy-convo-head">
                        <a href="{{ route('app.inbox') }}" class="vy-convo-back">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                            Inbox
                        </a>
                        <div class="vy-convo-who">
                            <span class="vy-convo-name">{{ $thread['guest'] }}</span>
                            <span class="vy-chan tone-{{ $thread['channel_tone'] }}"><span class="vy-chan-glyph">{{ $thread['channel_short'] }}</span>{{ $thread['channel_label'] }}</span>
                            <span class="vy-tag tone-{{ $thread['status'] === 'closed' ? 'departed' : ($thread['status'] === 'snoozed' ? 'pending' : 'confirmed') }}"><span class="vy-dot"></span>{{ ucfirst($thread['status']) }}</span>
                        </div>
                        <div class="vy-convo-actions">
                            {{-- Assign (real) --}}
                            <details class="vy-assign">
                                <summary>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 20c0-3.3 2.7-5 6-5s6 1.7 6 5M18 14v6M21 17h-6"/></svg>
                                    {{ $assignee['name'] ? 'Reassign' : 'Assign' }}
                                </summary>
                                <form class="vy-assign-pop" method="POST" action="{{ route('app.inbox.assign', $thread['id']) }}">
                                    @csrf
                                    <select name="user_id" aria-label="Assign to">
                                        <option value="">— Unassigned —</option>
                                        @foreach ($staff as $person)
                                            <option value="{{ $person['id'] }}" @selected($assignee['name'] === $person['name'])>{{ $person['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="vy-act vy-act--accent" style="justify-content:center;">Save</button>
                                </form>
                            </details>

                            @if ($thread['can_snooze'])
                                <form method="POST" action="{{ route('app.inbox.snooze', $thread['id']) }}">@csrf
                                    <button type="submit" class="vy-act vy-act--ghost">Snooze</button>
                                </form>
                            @endif
                            @if ($thread['can_close'])
                                <form method="POST" action="{{ route('app.inbox.close', $thread['id']) }}">@csrf
                                    <button type="submit" class="vy-act vy-act--ghost">Close</button>
                                </form>
                            @endif
                            @if ($thread['can_reopen'])
                                <form method="POST" action="{{ route('app.inbox.reopen', $thread['id']) }}">@csrf
                                    <button type="submit" class="vy-act vy-act--accent">Reopen</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="vy-card">
                    @if (count($messages) === 0)
                        <div class="vy-empty" style="padding: 24px;">No messages in this conversation yet.</div>
                    @else
                        <div class="vy-msgs">
                            @php $prevDay = null; @endphp
                            @foreach ($messages as $m)
                                @if ($m['day_key'] !== $prevDay)
                                    <div class="vy-day">{{ $m['day_label'] }}</div>
                                    @php $prevDay = $m['day_key']; @endphp
                                @endif
                                <div class="vy-msg {{ $m['out'] ? 'is-out' : 'is-in' }}">
                                    <div class="vy-bubble">{{ $m['body'] }}</div>
                                    <div class="vy-msg-meta">
                                        <span>{{ $m['author'] }} · {{ $m['time'] }}</span>
                                        @if ($m['delivery'])
                                            <span class="vy-deliv {{ $m['delivery']['failed'] ? 'is-fail' : '' }}">
                                                @unless ($m['delivery']['failed'])
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                                @endunless
                                                {{ $m['delivery']['label'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Composer — real reply via the thread's channel, or an honest "not connected" state --}}
                    @if ($thread['can_reply'])
                        <form class="vy-composer" method="POST" action="{{ route('app.inbox.reply', $thread['id']) }}">
                            @csrf
                            <textarea name="body" rows="3" placeholder="Write a reply…" required></textarea>
                            <div class="vy-composer-foot">
                                <span class="vy-composer-via">
                                    <span class="vy-chan tone-{{ $thread['channel_tone'] }}"><span class="vy-chan-glyph">{{ $thread['channel_short'] }}</span>{{ $thread['channel_label'] }}</span>
                                    Replying via {{ $thread['channel_label'] }}
                                </span>
                                <button type="submit" class="vy-btn vy-btn--accent">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
                                    Send
                                </button>
                            </div>
                        </form>
                    @else
                        {{-- INVARIANT #24 — a deferred channel can't send; honest disabled state, never a fake send. --}}
                        <div class="vy-composer is-blocked">
                            <div class="vy-composer-blocked">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9L2 18a2 2 0 0 0 1.7 3h16.6A2 2 0 0 0 22 18L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                                The {{ $thread['channel_label'] }} channel isn’t connected yet — replies can’t be sent from here.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT RAIL: guest · reservation · assignee --}}
            <div class="vy-stack">
                <div class="vy-card">
                    <span class="vy-ctx-eyebrow">Guest</span>
                    <div class="vy-ctx-guest">
                        <span class="vy-thread-av">{{ $thread['initials'] }}</span>
                        <div>
                            <div class="vy-ctx-name">{{ $guestCard['name'] }}</div>
                            <div class="vy-ctx-sub">{{ $guestCard['channel'] }}@if ($guestCard['stat']) · {{ $guestCard['stat'] }}@endif</div>
                        </div>
                    </div>
                </div>

                <div class="vy-card">
                    <span class="vy-ctx-eyebrow">Reservation</span>
                    @if ($reservationCard)
                        <div style="margin-top: 10px;">
                            <div class="vy-ctx-line"><span class="k">Reference</span><span class="v">{{ $reservationCard['reference'] }}</span></div>
                            @if ($reservationCard['unit'])<div class="vy-ctx-line"><span class="k">Unit</span><span class="v">{{ $reservationCard['unit'] }}</span></div>@endif
                            <div class="vy-ctx-line"><span class="k">Dates</span><span class="v">{{ $reservationCard['dates'] }}</span></div>
                            <div class="vy-ctx-line"><span class="k">Status</span><span class="v">{{ ucwords(str_replace('_', ' ', $reservationCard['status'])) }}</span></div>
                        </div>
                        <a href="{{ route('app.reservations.show', $reservationCard['id']) }}" class="vy-ctx-link">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                            View reservation
                        </a>
                    @elseif (count($linkable) > 0)
                        {{-- Not linked — link the thread to one of this guest's reservations (real, tenant-scoped). --}}
                        <form class="vy-assign-pop" method="POST" action="{{ route('app.inbox.link-reservation', $thread['id']) }}" style="margin-top: 10px;">
                            @csrf
                            <select name="reservation_id" aria-label="Link a reservation">
                                <option value="">Choose a reservation…</option>
                                @foreach ($linkable as $r)
                                    <option value="{{ $r['id'] }}">{{ $r['label'] }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="vy-act vy-act--accent" style="justify-content:center;">Link</button>
                        </form>
                    @else
                        <div class="vy-ctx-sub" style="margin-top: 10px;">Not linked to a reservation.</div>
                    @endif
                </div>

                <div class="vy-card">
                    <span class="vy-ctx-eyebrow">Assigned to</span>
                    <div style="margin-top: 10px;">
                        @if ($assignee['name'])
                            <span class="vy-thread-assignee" style="font-size: 13px;"><span class="vy-mini-avatar" style="width:22px;height:22px;font-size:9px;">{{ $assignee['initials'] }}</span> {{ $assignee['name'] }}</span>
                        @else
                            <span class="vy-assignee-none">Unassigned</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </x-operator.shell>
</div>
