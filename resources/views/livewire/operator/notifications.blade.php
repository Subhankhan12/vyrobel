{{-- Operator notification feed (Phase 3 · Sub-gate 1) — the AUTHENTICATED user's OWN notifications (per-user
     Notifiable relation). Each row links to the real screen; mark-read / mark-all-read are POST → controller.
     `?filter=` (category) rides GET. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<div>
    <x-operator.shell active="notifications" :title="__('livewire/operator/notifications.page_title')" :subtitle="trans_choice('livewire/operator/notifications.unread_count', $feed['unread'], ['count' => $feed['unread']])" :viewer="$viewer">

        {{-- The design's toolbar: an unread PILL beside the heading, one SEGMENTED tab shell, mark-all-read.
             ⚠️ The unread figure is $feed['unread'] — the SAME NotificationFeed value the bell badge reads.
             One source; never a second count query on this page (the shared-count anti-drift). --}}
        <div class="vyn-toolbar vy-fade">
            <div class="vyn-left">
                @if ($feed['unread'] > 0)
                    <span class="vyn-unread-pill">{{ trans_choice('livewire/operator/notifications.unread_count', $feed['unread'], ['count' => $feed['unread']]) }}</span>
                @endif
                {{-- Tabs ride GET (?filter=) — a real re-request through the tenant middleware (#20). --}}
                <nav class="vyn-seg" aria-label="{{ __('livewire/operator/notifications.filter_label') }}">
                    <a href="{{ route('app.notifications') }}" class="vyn-seg-i @if ($filter === 'all') is-on @endif" @if ($filter === 'all') aria-current="page" @endif>{{ __('common.all') }}</a>
                    <a href="{{ route('app.notifications', ['filter' => 'unread']) }}" class="vyn-seg-i @if ($filter === 'unread') is-on @endif" @if ($filter === 'unread') aria-current="page" @endif>{{ __('livewire/operator/notifications.tab_unread') }}</a>
                    @foreach ($feed['categories'] as $cat)
                        <a href="{{ route('app.notifications', ['filter' => $cat]) }}" class="vyn-seg-i @if ($filter === $cat) is-on @endif" @if ($filter === $cat) aria-current="page" @endif>{{ \App\Support\Notifications\NotificationFeed::categoryLabel($cat) }}</a>
                    @endforeach
                </nav>
            </div>
            @if ($feed['unread'] > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0">
                    @csrf
                    <button type="submit" class="vy-btn vy-btn--ghost vyn-readall">{{ __('livewire/operator/notifications.mark_all_read') }}</button>
                </form>
            @endif
        </div>

        <div class="vy-card vy-fade">
            @forelse ($feed['items'] as $item)
                {{-- Parity delta (Phase-3 rebuild): the icon is the SHARED rounded-SQUARE tile
                     (<x-notifications.category-icon> — ONE definition for panel + page, no drift), and unread rows
                     carry a faint orange WASH so this page reads identically to the bell panel. --}}
                <div class="vyn-row @unless ($item['read']) is-unread @endunless">
                    <x-notifications.category-icon :category="$item['category']" />
                    <a href="{{ $item['url'] ?? '#' }}" class="vyn-body">
                        <span class="vyn-titleline">
                            {{-- The design puts the CATEGORY as a chip beside the title (not right-aligned text). --}}
                            <span class="vyn-title @unless ($item['read']) is-unread @endunless">{{ $item['title'] }}</span>
                            <span class="vyn-cat">{{ \App\Support\Notifications\NotificationFeed::categoryLabel($item['category']) }}</span>
                        </span>
                        <span class="vyn-context">{{ $item['context'] }}</span>
                        <span class="vyn-when">{{ $item['when'] }}</span>
                    </a>
                    @unless ($item['read'])
                        {{-- ⚠️ 1.4.1 — the dot was carrying "unread" on colour alone: a bare `title` on a
                             non-interactive span is not reliably announced. The dot stays (it is the design),
                             but the state now also has real TEXT for assistive tech. --}}
                        <span class="vyn-dot" aria-hidden="true"></span>
                        <span class="vy-sr-only">{{ __('livewire/operator/notifications.unread_state') }}</span>
                        <form method="POST" action="{{ route('notifications.read', $item['id']) }}" style="margin:0;flex:none">
                            @csrf
                            <button type="submit" class="vy-btn vy-btn--ghost vyn-tick" title="{{ __('livewire/operator/notifications.mark_read') }}" aria-label="{{ __('livewire/operator/notifications.mark_read_aria', ['title' => $item['title']]) }}">✓</button>
                        </form>
                    @endunless
                    {{-- D29-5 — DISMISS, deliberately OUTSIDE the @unless: mark-read only makes sense while a
                         row is unread, but you can take an already-read notification off your list. Same form
                         + button idiom as the tick beside it (§N.8f), never a new control style. --}}
                    <form method="POST" action="{{ route('notifications.dismiss', $item['id']) }}" style="margin:0;flex:none">
                        @csrf
                        <button type="submit" class="vy-btn vy-btn--ghost vyn-tick" title="{{ __('livewire/operator/notifications.dismiss') }}" aria-label="{{ __('livewire/operator/notifications.dismiss_aria', ['title' => $item['title']]) }}">&times;</button>
                    </form>
                </div>
            @empty
                <div class="vy-empty">{{ __('livewire/operator/notifications.empty_state') }}</div>
            @endforelse

                {{-- WAVE 3 — the realm-neutral shared pager. BOTH notification pages get it in the same
                     change: NotificationFeed::forUser() is shared, so paginating one realm and not the
                     other would hide that realm's older notifications with no way to reach them. --}}
                <x-pager :paginator="$feed['paginator']" :label="__('livewire/operator/notifications.pager_label')" />
        </div>

        <p class="vy-line-sub" style="font-size:12px;margin-top:14px;color:var(--vy-muted)">
            {!! __('livewire/operator/notifications.preferences_hint', ['url' => e(route('app.notification-preferences'))]) !!}
        </p>

    </x-operator.shell>

    <style>
        .vyn-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .vyn-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        /* The design's inline unread pill. The figure is the shared NotificationFeed count — one source. */
        .vyn-unread-pill { font-size: 11.5px; font-weight: 600; color: var(--vy-accent-text); background: rgba(232,112,58,.12); border-radius: 999px; padding: 4px 11px; white-space: nowrap; }
        /* One segmented shell with the active tab as a raised white pill (the design), not loose buttons. */
        .vyn-seg { display: inline-flex; flex-wrap: wrap; gap: 2px; background: rgba(255,255,255,.55); border: 1px solid var(--vy-glass-border); border-radius: 999px; padding: 3px; }
        .vyn-seg-i { padding: 6px 14px; border-radius: 999px; font-size: 12.5px; color: var(--vy-muted); text-decoration: none; white-space: nowrap; }
        .vyn-seg-i:hover { color: var(--vy-accent-text); }
        .vyn-seg-i.is-on { background: #fff; color: var(--vy-ink); font-weight: 600; box-shadow: var(--vy-shadow-sm); }
        .vyn-readall { padding: 7px 14px; font-size: 12.5px; }

        .vyn-row { display: flex; align-items: flex-start; gap: 12px; padding: 13px 10px; border-bottom: 1px solid var(--vy-hairline); border-radius: 10px; }
        .vyn-row.is-unread { background: rgba(217,102,46,.05); }
        .vyn-body { flex: 1; min-width: 0; text-decoration: none; color: inherit; display: flex; flex-direction: column; gap: 2px; }
        .vyn-titleline { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .vyn-title { font-size: 13.5px; font-weight: 500; color: var(--vy-ink); }
        .vyn-title.is-unread { font-weight: 700; }
        /* Category as a CHIP beside the title (the design), not right-aligned uppercase text. */
        .vyn-cat { font-size: 10.5px; font-weight: 600; color: var(--vy-muted); background: rgba(120,70,40,.07); border-radius: 999px; padding: 2px 9px; white-space: nowrap; }
        .vyn-context { font-size: 12.5px; color: var(--vy-muted); }
        .vyn-when { font-size: 11px; color: var(--vy-faint); }
        .vyn-dot { flex: none; width: 8px; height: 8px; border-radius: 50%; background: var(--vy-accent); margin-top: 6px; }
        .vyn-tick { padding: 4px 9px; font-size: 11px; flex: none; }
    </style>
</div>
