{{-- MESSAGES: GUESTS | TEAM (team chat, 2026-10-06 — his "toggle for client and employees"). The two kinds of conversation
     side by side, each with its unread count (kept live by partials/live-pulse through `data-vy-badge`). Shown only to
     someone who may read both: without `guests.view` there is one kind, and a toggle with one side is not a choice.
     Params: $current ('guests' | 'team'), $guestsLabel, $guestUnread (int), $teamUnread (int).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@can('guests.view')
    <nav class="vy-msg-toggle" aria-label="Conversations">
        <a href="{{ route('app.inbox') }}" class="vy-msg-tab {{ $current === 'guests' ? 'is-on' : '' }}" @if ($current === 'guests') aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            {{ $guestsLabel }}
            <span class="vy-msg-count" data-vy-badge="guests" @if ($guestUnread <= 0) hidden @endif>{{ $guestUnread }}</span>
        </a>
        <a href="{{ route('app.inbox.team') }}" class="vy-msg-tab {{ $current === 'team' ? 'is-on' : '' }}" @if ($current === 'team') aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Team
            <span class="vy-msg-count" data-vy-badge="team" @if ($teamUnread <= 0) hidden @endif>{{ $teamUnread }}</span>
        </a>
    </nav>
@endcan
