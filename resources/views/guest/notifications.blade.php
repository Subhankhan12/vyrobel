{{-- Guest notification feed (guest notifications · GATE 3) — the AUTHENTICATED guest's OWN notifications,
     the page the header bell links to.

     ⚠️ NO WIREFRAME EXISTS for this screen (19 guest prototypes import a bell; the surface it opens was never
     drawn), so it MIRRORS the proven owner/operator feed page rather than inventing a second feed UI: the same
     shared category tile, the same row shape reading title/context/category/when, the same unread wash + dot,
     the same category filter row, the same mark-read / dismiss / mark-all-read acts. Only the chrome is this
     realm's — the light guest shell, its hero idiom, its warm glass.

     🔒 SELF-ONLY (#28) — `$feed` is built by the ONE shared NotificationFeed from the guard-resolved guest's
     own Notifiable relation; another guest's rows are not reachable here.

     ⚠️ THE ACTIONS POST TO THE GUEST-GUARD ROUTES (`guest.notifications.*`, Gate 1), never the operator/owner
     ones — same shared controller, resolved inside `auth:guest`.

     ⚠️ THIS REALM HAS NO ALPINE — every control is a plain link or a POST form (native GET / progressive
     enhancement), which is why the bell links here rather than opening a popover.

     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-guest.shell active="notifications" title="Notifications">

    {{-- Hero — the guest-realm idiom (the Messages/Payments pages open the same way). --}}
    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;background:linear-gradient(135deg,rgba(255,252,248,.72),rgba(255,246,238,.5));border:1px solid var(--vy-glass-border);border-radius:22px;box-shadow:var(--vy-shadow);padding:22px 26px;margin-bottom:18px;animation:vyFadeUp .5s cubic-bezier(.22,.61,.36,1) .06s both">
        <div style="width:52px;height:52px;flex-shrink:0;border-radius:16px;background:linear-gradient(140deg,#E8703A,#C2410C);display:grid;place-items:center;box-shadow:var(--vy-shadow-accent)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9z"/><path d="M10 21a2 2 0 0 0 4 0"/></svg>
        </div>
        <div style="min-width:0">
            {{-- ⚠️ PROMOTED FROM A <div>, NOT INVENTED. This text was already the page's visible title; only its ELEMENT was wrong, so a screen reader navigating by heading found nothing on this screen. ⚠️ `margin:0` keeps the rendering byte-identical — an <h1> carries default margins a <div> does not. --}}
            <h1 style="font-family:var(--vy-display);font-weight:700;font-size:24px;letter-spacing:-.02em;color:var(--vy-ink);margin:0">Notifications</h1>
            <div style="font-size:13px;color:var(--vy-muted);margin-top:3px">
                @if ($feed['unread'] > 0)
                    {{ $feed['unread'] }} unread · updates about your bookings, messages and account.
                @else
                    Updates about your bookings, messages and account.
                @endif
            </div>
        </div>
    </div>

    {{-- Filters + mark-all — mirrors the owner/operator feed's control row. --}}
    @if ($feed['items'] !== [] || $feed['categories'] !== [])
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px">
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="{{ route('guest.notifications') }}" class="{{ $filter === 'all' ? 'vy-btn' : 'vy-btn vy-btn--ghost' }}" style="padding:6px 12px;font-size:12.5px">All</a>
                @foreach ($feed['categories'] as $cat)
                    <a href="{{ route('guest.notifications', ['filter' => $cat]) }}" class="{{ $filter === $cat ? 'vy-btn' : 'vy-btn vy-btn--ghost' }}" style="padding:6px 12px;font-size:12.5px">{{ ucwords(str_replace('_', ' ', $cat)) }}</a>
                @endforeach
            </div>
            @if ($feed['unread'] > 0)
                <form method="POST" action="{{ route('guest.notifications.read-all') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px">Mark all read</button>
                </form>
            @endif
        </div>
    @endif

    <section class="vy-card">
        @forelse ($feed['items'] as $item)
            <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 8px;border-bottom:1px solid var(--vy-hairline);border-radius:8px;{{ $item['read'] ? '' : 'background:rgba(217,102,46,.05)' }}">
                <x-notifications.category-icon :category="$item['category']" />
                <a href="{{ $item['url'] ?? '#' }}" style="flex:1;min-width:0;text-decoration:none;color:inherit">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;flex-wrap:wrap">
                        <span style="font-size:13.5px;font-weight:{{ $item['read'] ? '500' : '700' }};color:var(--vy-ink)">{{ $item['title'] }}</span>
                        <span style="display:flex;align-items:center;gap:8px;flex:none">
                            <span style="font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;color:var(--vy-muted)">{{ ucwords(str_replace('_', ' ', $item['category'])) }}</span>
                            {{-- 1.4.1 — the dot is the design, but "unread" must not ride on colour alone. --}}
                            @unless ($item['read'])
                                <span aria-hidden="true" style="flex:none;width:8px;height:8px;border-radius:50%;background:var(--vy-accent)"></span>
                                <span class="vy-sr-only">Unread</span>
                            @endunless
                        </span>
                    </div>
                    <div style="font-size:12.5px;color:var(--vy-muted);margin-top:2px">{{ $item['context'] }}</div>
                    <div style="font-size:11px;color:var(--vy-muted);margin-top:3px">{{ $item['when'] }}</div>
                </a>
                @unless ($item['read'])
                    <form method="POST" action="{{ route('guest.notifications.read', $item['id']) }}" style="margin:0;flex:none">
                        @csrf
                        <button type="submit" class="vy-btn vy-btn--ghost" style="padding:4px 9px;font-size:11px" title="Mark read" aria-label="Mark &ldquo;{{ $item['title'] }}&rdquo; as read">&check;</button>
                    </form>
                @endunless
                {{-- Dismiss sits OUTSIDE the unless, exactly as in the other two feeds: an already-read
                     notification can still be taken off the list (§N.8f). --}}
                <form method="POST" action="{{ route('guest.notifications.dismiss', $item['id']) }}" style="margin:0;flex:none">
                    @csrf
                    <button type="submit" class="vy-btn vy-btn--ghost" style="padding:4px 9px;font-size:11px" title="Dismiss" aria-label="Dismiss &ldquo;{{ $item['title'] }}&rdquo;">&times;</button>
                </form>
            </div>
        @empty
            {{-- ⚠️ THE SEEDED REALITY. The demo persists zero guest notifications, so this is the state a fresh
                 guest actually sees — an honest empty state, not a placeholder list. --}}
            <div style="padding:34px 16px;text-align:center">
                <div style="font-size:14px;font-weight:600;color:var(--vy-ink)">All caught up</div>
                <div style="font-size:12.5px;color:var(--vy-muted);margin-top:5px">
                    You have no notifications yet. Updates about your bookings, messages and account will appear here.
                </div>
            </div>
        @endforelse
    </section>

    @if ($feed['paginator']->hasPages())
        <div style="margin-top:16px">{{ $feed['paginator']->links() }}</div>
    @endif
</x-guest.shell>
