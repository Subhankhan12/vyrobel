{{-- Owner portal · MESSAGES (Wave 3) — the owner's conversations with their operator's staff, styled on the
     shared owner design system via <x-owner.shell>. See App\Livewire\Owner\OwnerMessages for the
     authoritative REAL / DERIVED / OMITTED map.

     ⚠️ OWNER-SCOPED, and not by anything in this file: MessageThread is BelongsToOwner, so OwnerScope
     constrains every read to `owner_id IN (their ids)`. A guest thread has owner_id NULL and is therefore
     excluded automatically — the two-inbox boundary falls out of the scope, not out of a predicate.

     Honesty map (short form) — this design is ~1/3 fabricated, so the omissions matter more than usual:
       • REAL     — the owner's threads; the staff correspondent (a real user + their real back-office role);
                    bodies + timestamps; the last-message preview.
       • DERIVED  — the unread badge/filter/count (owner_last_read_at vs later staff replies — NOT the
                    operator's unread_count, which is theirs); relative timestamps; initials.
       • OMITTED  — "✓✓ Read" (an INVENTED enum value: delivery_status has no `read`), presence dots (no
                    presence data exists), "usually replies within an hour" (a promise the app cannot
                    measure), the typing indicator, localStorage stars/reactions, the non-persisted pin,
                    blob attachments, the canned auto-reply bot, and the "Vyrobel Support"/"Vyrobel Billing"
                    vendor desks (a cross-tenant helpdesk cannot live in an org-scoped table) + the
                    "Maintenance team" queue (a team is not a user to address).

     RAW-BLADE RULE: never write `word@directive` — Blade guards with \B, so a directive whose `@` follows a
     word character ships the RAW SOURCE to the page as ordinary text, invisible to assertSee. Every `@`
     below follows a space, a newline or a tag. --}}
<div>
    <x-owner.shell active="messages" title="Messages" :subtitle="$subtitle" :viewer="$viewer">

        @if (session('owner_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">{{ session('owner_notice') }}</div>
        @endif
        @if (session('owner_error'))
            <div class="vy-flash is-err vy-fade" role="alert">{{ session('owner_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="vy-flash is-err vy-fade" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="vy-msg-cols vy-fade">

            {{-- ── The conversation rail ────────────────────────────────────────────────────────── --}}
            <div class="vy-card vy-card--flush" style="padding: 14px;">

                {{-- Search — a GET form. A scoped read must re-run the middleware that establishes the owner
                     context (invariant #20); a wire-action would bypass it. --}}
                <form method="GET" action="{{ route('owner.messages') }}" class="vy-input-group" style="margin-bottom: 12px;">
                    @if ($filter !== null)
                        <input type="hidden" name="filter" value="{{ $filter }}">
                    @endif
                    {{-- Searching bodies is legitimate here: `messages.body` is a PLAINTEXT column, so a LIKE
                         genuinely matches. (Unlike guest email/phone, which are encrypted and unsearchable —
                         that standing blind-index deferral does not apply to message bodies.) --}}
                    <input class="vy-input" type="search" name="q" value="{{ $query }}"
                           placeholder="Search messages" aria-label="Search your messages">
                    <button type="submit" class="vy-btn vy-btn--ghost">Search</button>
                </form>

                {{-- All / Unread — plain GET links, each count REAL. --}}
                <div style="display: flex; gap: 6px; margin-bottom: 6px;">
                    <a class="vy-pill @if ($filter === null) is-active @endif"
                       href="{{ route('owner.messages').($query !== '' ? '?q='.urlencode($query) : '') }}"
                       @if ($filter === null) aria-current="page" @endif>All</a>
                    <a class="vy-pill @if ($filter === 'unread') is-active @endif"
                       href="{{ route('owner.messages').'?filter=unread'.($query !== '' ? '&q='.urlencode($query) : '') }}"
                       @if ($filter === 'unread') aria-current="page" @endif>
                        Unread
                        @if ($unread > 0)
                            <span class="vy-unread-dot" style="display: inline-block; margin-left: 5px;"></span>
                        @endif
                    </a>
                </div>

                @forelse ($rows as $row)
                    <a class="vy-convo-row @if ($row['unread']) is-unread @endif" href="{{ route('owner.messages.show', $row['id']) }}">
                        <span class="vy-convo-av">{{ $row['initials'] }}</span>
                        <span class="vy-convo-main">
                            <span class="vy-convo-name">{{ $row['name'] }}</span>
                            {{-- The REAL back-office role. The design's "Property manager · Seaside Villas" was
                                 free text with no staff↔property model behind it. --}}
                            <span class="vy-convo-role">{{ $row['role'] }}</span>
                            @if ($row['preview'] !== null)
                                <span class="vy-convo-preview">{{ $row['preview'] }}</span>
                            @endif
                        </span>
                        <span class="vy-convo-meta">
                            <span class="vy-convo-time">{{ $row['time'] }}</span>
                            @if ($row['unread'])
                                <span class="vy-unread-dot" aria-label="Unread"></span>
                            @endif
                        </span>
                    </a>
                @empty
                    <div class="vy-empty" style="padding: 34px 16px;">
                        @if ($query !== '')
                            <strong style="display: block; color: var(--vy-ink); margin-bottom: 5px;">No messages match “{{ $query }}”</strong>
                            Try another name or keyword.
                        @elseif ($filter === 'unread')
                            <strong style="display: block; color: var(--vy-ink); margin-bottom: 5px;">You're all caught up</strong>
                            No unread messages.
                        @else
                            <strong style="display: block; color: var(--vy-ink); margin-bottom: 5px;">No conversations yet</strong>
                            Start one with your management team — they'll see it in their inbox.
                        @endif
                    </div>
                @endforelse
            </div>

            {{-- ── Compose — ONE real query for the org's staff, replacing the design's 5 fabricated
                 directory entries (two of which were the SaaS vendor, one a team with no user). ───── --}}
            <div class="vy-card">
                <h2 class="vy-card-title" style="margin-bottom: 4px;">New message</h2>
                <div class="vy-fx-note" style="margin-bottom: 14px;">
                    Goes to your management team's inbox — they reply here.
                </div>

                @if ($staff !== [])
                    <div style="margin-bottom: 14px;">
                        <span class="vy-eyebrow">Your team</span>
                        <div style="display: flex; flex-direction: column; gap: 2px; margin-top: 6px;">
                            @foreach ($staff as $member)
                                <div style="display: flex; align-items: center; gap: 10px; padding: 7px 0;">
                                    <span class="vy-convo-av" style="width: 30px; height: 30px; font-size: 11px;">{{ $member['initials'] }}</span>
                                    <span>
                                        <span class="vy-convo-name" style="font-size: 12.5px;">{{ $member['name'] }}</span>
                                        <span class="vy-convo-role">{{ $member['role'] }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('owner.messages.start') }}">
                    @csrf
                    <label class="vy-eyebrow" for="subject">Subject (optional)</label>
                    <input class="vy-input" type="text" id="subject" name="subject" maxlength="160"
                           value="{{ old('subject') }}" placeholder="What's it about?" style="width: 100%; margin: 6px 0 12px;">

                    <label class="vy-eyebrow" for="body">Message</label>
                    <textarea class="vy-input" id="body" name="body" rows="4" maxlength="5000" required
                              placeholder="Write your message…" style="width: 100%; margin-top: 6px; resize: vertical;">{{ old('body') }}</textarea>

                    <button type="submit" class="vy-btn vy-btn--accent" style="margin-top: 12px; width: 100%; justify-content: center;">Send</button>
                </form>
            </div>
        </div>

    </x-owner.shell>
</div>
