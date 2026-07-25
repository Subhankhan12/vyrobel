{{-- Shared top-bar notification BELL + PANEL (Phase-3 notification rebuild) — reused verbatim by BOTH the
     operator and owner shells (same mechanism, same shared feed). The bell OPENS A PANEL (Alpine popover, the
     Livewire-4-injected Alpine already used across the app — no new dependency); it NO LONGER navigates. Only
     the footer "Open all notifications →" link goes to the full feed page (its route passed in as $openAllUrl).

     🪤 REAL notifications only — the panel renders the AUTHENTICATED user's OWN recent rows via the ONE shared
        {@see \App\Support\Notifications\NotificationFeed::recentForUser()} (capped, honest empty state). No
        fabricated rows, no fake category pills.
     🔒 SELF-ONLY (#28/#26) — the feed reads the user's own Notifiable rows (an owner sees only their own, no
        cross-owner/tenant; payloads are name-only). The panel inherits this — no new scope.
     🔒 Mark-all reuses the EXISTING self-only POST route (notifications.read-all); PRG-redirect-back closes the
        panel on reload and clears the badge (approved).

     There is NO separate panel wireframe — this is built in the feed page's EXACT design language (tinted
     square category tile via <x-notifications.category-icon>, bold-if-unread title, uppercase REAL category
     tag, one-line context, relative time, unread dot + faint row wash). RAW-BLADE RULE: every `@` follows a
     space, newline or tag. --}}
@props(['openAllUrl'])
@php
    $feed = \App\Support\Notifications\NotificationFeed::recentForUser(auth()->user());
    $unread = (int) $feed['unread'];
@endphp
<div x-data="{ open: false }" @keydown.escape.window="open = false" style="position:relative;display:inline-flex">
    <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" class="vy-bell" title="Notifications" aria-label="Notifications" style="position:relative;background:none;border:none;padding:0;cursor:pointer;color:inherit;display:inline-flex;align-items:center">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M10 20a2 2 0 0 0 4 0"/></svg>
        @if ($unread > 0)<span aria-label="{{ $unread }} unread" style="position:absolute;top:-3px;right:-3px;min-width:15px;height:15px;padding:0 3px;border-radius:999px;background:var(--vy-accent,#b5654a);color:#fff;font-size:9.5px;font-weight:700;display:grid;place-items:center">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
    </button>

    <div x-cloak x-show="open" x-transition.opacity.duration.150ms @click.outside="open = false" role="menu" aria-label="Notifications"
         style="position:absolute;top:calc(100% + 10px);right:0;width:360px;max-width:calc(100vw - 32px);background:rgba(255,252,248,0.98);backdrop-filter:blur(22px) saturate(1.15);-webkit-backdrop-filter:blur(22px) saturate(1.15);border:1px solid var(--vy-glass-border);border-radius:var(--vy-r-lg);box-shadow:var(--vy-shadow);z-index:80;overflow:hidden">

        <div style="display:flex;justify-content:space-between;align-items:baseline;padding:13px 15px;border-bottom:1px solid var(--vy-hairline)">
            <span style="font-size:13.5px;font-weight:700;color:var(--vy-ink)">Notifications</span>
            <span style="font-size:11.5px;color:var(--vy-muted)">{{ $unread }} unread</span>
        </div>

        <div style="max-height:min(60vh,420px);overflow-y:auto">
            @forelse ($feed['items'] as $item)
                <a href="{{ $item['url'] ?? '#' }}" style="display:flex;align-items:flex-start;gap:11px;padding:11px 15px;border-bottom:1px solid var(--vy-hairline);text-decoration:none;color:inherit;{{ $item['read'] ? '' : 'background:rgba(217,102,46,.05)' }}">
                    <x-notifications.category-icon :category="$item['category']" />
                    <span style="flex:1;min-width:0">
                        <span style="display:flex;justify-content:space-between;gap:8px;align-items:baseline">
                            <span style="flex:1;min-width:0;font-size:12.5px;font-weight:{{ $item['read'] ? '500' : '700' }};color:var(--vy-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item['title'] }}</span>
                            <span style="display:flex;align-items:center;gap:6px;flex:none">
                                <span style="font-size:9.5px;text-transform:uppercase;letter-spacing:.04em;color:var(--vy-muted)">{{ ucwords(str_replace('_', ' ', $item['category'])) }}</span>
                                @unless ($item['read'])<span style="flex:none;width:7px;height:7px;border-radius:50%;background:var(--vy-accent)" title="Unread"></span>@endunless
                            </span>
                        </span>
                        <span style="display:block;font-size:12px;color:var(--vy-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item['context'] }}</span>
                        <span style="display:block;font-size:10.5px;color:var(--vy-faint);margin-top:2px">{{ $item['when'] }}</span>
                    </span>
                </a>
            @empty
                <div style="padding:28px 15px;text-align:center;font-size:12.5px;color:var(--vy-muted)">All caught up — no notifications.</div>
            @endforelse
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 15px;border-top:1px solid var(--vy-hairline)">
            @if ($unread > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="vy-btn vy-btn--ghost" style="padding:5px 10px;font-size:11.5px">Mark all read</button>
                </form>
            @else
                <span></span>
            @endif
            <a href="{{ $openAllUrl }}" style="font-size:12px;color:var(--vy-accent);text-decoration:none;font-weight:600">Open all notifications →</a>
        </div>
    </div>
</div>
