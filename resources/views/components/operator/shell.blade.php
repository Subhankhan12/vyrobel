{{-- Operator back-office shell (design pass) — the shared sidebar + topbar chrome, extracted on the
     FIRST operator screen so every later operator screen inherits ONE nav/topbar (the same way
     <x-owner.shell> was extracted for the owner portal). Presentation only.

     Props:
       active    — the nav key to highlight ('calendar' | 'front-desk' | …)
       title     — the topbar <h1>
       subtitle  — a small line under the title (e.g. the active property name)
       viewer    — ['name' => string, 'initials' => string] (the authenticated staff user, for the chip)

     Nav honesty: ONLY items with a real, built route navigate. The active item is highlighted
     (non-link); every other item is a non-interactive "Soon" placeholder for an operator screen not
     built yet — never a fake link. Operator screens are TENANT-scoped, never owner-scoped. --}}
@props([
    'active' => '',
    'title' => '',
    'subtitle' => '',
    'viewer' => ['name' => 'Operator', 'initials' => 'O'],
])
@php
    // [key, label, icon-path, route|null]. A null route renders a disabled "Soon" placeholder.
    $navGroups = [
        ['OPERATIONS', [
            ['dashboard', 'Dashboard', 'M3 13h8V3H3zM13 21h8v-6h-8zM13 3v8h8V3zM3 21h8v-6H3z', route('app.dashboard')],
            ['inbox', 'Inbox', 'M4 13h5l2 3h2l2-3h5M4 13V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v7M4 13v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5', route('app.inbox')],
            ['calendar', 'Calendar', 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4', route('app.rack')],
            ['front-desk', 'Front desk', 'M4 20v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', route('app.front-desk')],
            ['housekeeping', 'Housekeeping', 'M3 21h18M6 21V10l6-4 6 4v11M10 21v-5h4v5', route('app.housekeeping')],
            ['maintenance', 'Maintenance', 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z', route('app.maintenance')],
            ['units', 'Units', 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z', route('app.units')],
            ['reservations', 'Reservations', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', null],
        ]],
        ['DISTRIBUTION', [
            ['channels', 'Channels', 'M12 3v4M12 17v4M3 12h4M17 12h4M6 6l3 3M15 15l3 3M18 6l-3 3M9 15l-3 3', null],
            ['rates', 'Rates', 'M4 4h9l7 7-9 9-7-7zM8 8h.01', null],
        ]],
        ['OWNERS', [
            ['owners', 'Owners', 'M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 20c0-3 3-5 6-5s6 2 6 5M16 11a3 3 0 1 0 0-6M22 20c0-3-3-5-6-5', route('app.owners')],
            ['payout-run', 'Payout run', 'M3 7h18v12H3zM3 11h18M15 15h3', route('app.payouts.run')],
        ]],
        ['ACCOUNT', [
            ['settings', 'Settings', 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2', null],
            ['security', 'Security', 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z', null],
        ]],
    ];
@endphp

<div class="vy-shell">
    <aside class="vy-side">
        <div class="vy-brand">
            <span class="vy-brand-mark">V</span>
            <span>
                <span class="vy-brand-name">Vyrobel</span><br>
                <span class="vy-brand-sub">Operator</span>
            </span>
        </div>

        @foreach ($navGroups as [$group, $items])
            <nav class="vy-nav-group" aria-label="{{ $group }}">
                <span class="vy-nav-label">{{ $group }}</span>
                @foreach ($items as [$key, $label, $icon, $href])
                    @php $isActive = $key === $active; @endphp
                    @if ($isActive)
                        <span class="vy-nav-item is-active" aria-current="page">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </span>
                    @elseif ($href)
                        <a class="vy-nav-item" href="{{ $href }}">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </a>
                    @else
                        <span class="vy-nav-item" aria-disabled="true" title="Coming soon">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </span>
                    @endif
                @endforeach
            </nav>
        @endforeach
    </aside>

    <div class="vy-main">
        <div class="vy-topbar">
            <div>
                <h1>{{ $title }}</h1>
                @if ($subtitle !== '')
                    <div class="vy-topbar-sub">{{ $subtitle }}</div>
                @endif
            </div>
            <div class="vy-topbar-actions">
                <span class="vy-bell" title="Notifications — coming soon" aria-label="Notifications">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M10 20a2 2 0 0 0 4 0"/></svg>
                </span>
                <span class="vy-chip">
                    <span class="vy-avatar">{{ $viewer['initials'] }}</span>
                    <span class="vy-chip-name">{{ $viewer['name'] }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="vy-icobtn" title="Sign out" aria-label="Sign out">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </button>
                </form>
            </div>
        </div>

        {{ $slot }}
    </div>
</div>
