{{-- Owner notification feed (Phase 3 · Sub-gate 1) — the AUTHENTICATED owner's OWN notifications (payout sent,
     statement ready, …), via the per-user Notifiable relation. Each row links to the real screen; mark-read /
     mark-all-read are POST → controller. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<x-owner.shell active="notifications" :title="__('livewire/owner/notifications.title')" :subtitle="__('livewire/owner/notifications.subtitle', ['number' => $feed['unread']])" :viewer="$viewer">

    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px">
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="{{ route('owner.notifications') }}" class="vy-btn {{ $filter === 'all' ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:6px 12px;font-size:12.5px">{{ __('common.all') }}</a>
            @foreach ($feed['categories'] as $cat)
                <a href="{{ route('owner.notifications', ['filter' => $cat]) }}" class="vy-btn {{ $filter === $cat ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:6px 12px;font-size:12.5px">{{ \App\Support\Notifications\NotificationFeed::categoryLabel($cat) }}</a>
            @endforeach
        </div>
        @if ($feed['unread'] > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0">
                @csrf
                <button type="submit" class="vy-btn vy-btn--ghost" style="padding:6px 12px;font-size:12.5px">{{ __('livewire/owner/notifications.mark_all_read') }}</button>
            </form>
        @endif
    </div>

    <section class="vy-card vy-fade">
        @forelse ($feed['items'] as $item)
            {{-- Parity delta (Phase-3 rebuild): lead with the SHARED rounded-SQUARE category tile
                 (<x-notifications.category-icon> — ONE definition for panel + page), an unread WASH on the row, and
                 the unread dot beside the category tag — so this page reads identically to the bell panel. --}}
            <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 8px;border-bottom:1px solid var(--vy-hairline);border-radius:8px;{{ $item['read'] ? '' : 'background:rgba(217,102,46,.05)' }}">
                <x-notifications.category-icon :category="$item['category']" />
                <a href="{{ $item['url'] ?? '#' }}" style="flex:1;text-decoration:none;color:inherit">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline">
                        <span style="font-size:13.5px;font-weight:{{ $item['read'] ? '500' : '700' }};color:var(--vy-ink)">{{ $item['title'] }}</span>
                        <span style="display:flex;align-items:center;gap:8px;flex:none">
                            <span style="font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;color:var(--vy-muted)">{{ \App\Support\Notifications\NotificationFeed::categoryLabel($item['category']) }}</span>
                            {{-- ⚠️ 1.4.1 (operator GATE-29's finding, closed here): the dot carried "unread"
                                 on COLOUR ALONE — a bare `title` on a non-interactive span is not reliably
                                 announced. The dot stays (it is the design); the state now also has real TEXT. --}}
                            @unless ($item['read'])
                                <span aria-hidden="true" style="flex:none;width:8px;height:8px;border-radius:50%;background:var(--vy-accent)"></span>
                                <span class="vy-sr-only">{{ __('livewire/owner/notifications.unread_state') }}</span>
                            @endunless
                        </span>
                    </div>
                    <div style="font-size:12.5px;color:var(--vy-muted);margin-top:2px">{{ $item['context'] }}</div>
                    <div style="font-size:11px;color:var(--vy-muted);margin-top:3px">{{ $item['when'] }}</div>
                </a>
                @unless ($item['read'])
                    <form method="POST" action="{{ route('notifications.read', $item['id']) }}" style="margin:0;flex:none">
                        @csrf
                        <button type="submit" class="vy-btn vy-btn--ghost" style="padding:4px 9px;font-size:11px" title="{{ __('livewire/owner/notifications.mark_read') }}" aria-label="{{ __('livewire/owner/notifications.mark_read_aria', ['title' => $item['title']]) }}">✓</button>
                    </form>
                @endunless
                {{-- D29-5 — DISMISS, outside the @unless for the same reason as the operator feed: an
                     already-read notification can still be taken off the list. Matches the mark-read button
                     beside it exactly (§N.8f). --}}
                <form method="POST" action="{{ route('notifications.dismiss', $item['id']) }}" style="margin:0;flex:none">
                    @csrf
                    <button type="submit" class="vy-btn vy-btn--ghost" style="padding:4px 9px;font-size:11px" title="{{ __('livewire/owner/notifications.dismiss') }}" aria-label="{{ __('livewire/owner/notifications.dismiss_aria', ['title' => $item['title']]) }}">&times;</button>
                </form>
            </div>
        @empty
            <div class="vy-empty">{{ __('livewire/owner/notifications.empty') }}</div>
        @endforelse

                {{-- WAVE 3 — the realm-neutral shared pager. BOTH notification pages get it in the same
                     change: NotificationFeed::forUser() is shared, so paginating one realm and not the
                     other would hide that realm's older notifications with no way to reach them. --}}
                <x-pager :paginator="$feed['paginator']" :label="__('livewire/owner/notifications.pager_label')" />
    </section>

    {{-- ⚠️ This sentence sits DIRECTLY ON THE PAGE GRADIENT, not on a glass card — the same distinction the
         topbar sub-line needed at Owner-4. Measured on the gradient: --vy-muted 4.43 and the accent LINK
         3.17; --vy-on-grad reads 5.46, and the accent needs its tint-safe value to clear AA here (4.52). --}}
    <p class="vy-line-sub" style="font-size:12px;margin-top:14px;color:var(--vy-on-grad)">
        {!! __('livewire/owner/notifications.preferences_note', ['url' => e(route('owner.notification-preferences'))]) !!}
    </p>
</x-owner.shell>
