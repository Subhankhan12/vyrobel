{{-- Operator back-office shell (design pass) — the shared sidebar + topbar chrome, extracted on the
     FIRST operator screen so every later operator screen inherits ONE nav/topbar (the same way
     <x-owner.shell> was extracted for the owner portal). Presentation only.

     Props:
       active    — the nav key to highlight ('calendar' | 'front-desk' | …)
       title     — the topbar <h1>
       subtitle  — a small line under the title (e.g. the active property name)
       viewer    — ['name' => string, 'initials' => string] (the authenticated staff user, for the chip)

     Nav honesty: ONLY items with a real, built route navigate. A role-gated item the acting role can't
     reach (its route resolved to null) is FILTERED OUT below — a permission-less role simply doesn't see
     it (mirroring <x-owner.shell>), never a misleading "Coming soon" for a page that works for an admin.
     The one placeholder that remains is the genuinely account-gated "channels" (Channex / a Pro feature).
     Operator screens are TENANT-scoped, never owner-scoped. --}}
@props([
    'active' => '',
    'title' => '',
    'subtitle' => '',
    'viewer' => ['name' => 'Operator', 'initials' => 'O'],
])
@php
    // Phase 7b feature-gating: whether the org's plan grants Channels (Pro-only). Shown as a "Pro" tag on the
    // Channels nav item for an Essential org (vs the account-gated "Soon" everyone else sees).
    $__billingOrg = \App\Models\Organization::query()->find(app(\App\Support\Tenancy\TenantContext::class)->id());
    $channelsAllowed = $__billingOrg?->planAllows('channels') ?? true;

    // [key, label, icon-path, route|null]. A null route renders a disabled "Soon" placeholder.
    // Regrouped to the prototype's sidebar sections (Operator Dashboard design): OVERVIEW · FRONT DESK ·
    // OPERATIONS · FINANCE · SYSTEM. Every real route + its permission gate is preserved verbatim; only the
    // section grouping/order and two labels (Inbox→Messages, Rates→Rates & availability) match the design.
    // [key, label, icon-path, route|null]. A null route renders a disabled "Soon" placeholder.
    $navGroups = [
        ['OVERVIEW', [
            ['portfolio', 'Portfolio', 'M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01', auth()->user()?->can('properties.view') ? route('app.portfolio') : null],
            ['dashboard', 'Dashboard', 'M3 13h8V3H3zM13 21h8v-6h-8zM13 3v8h8V3zM3 21h8v-6H3z', route('app.dashboard')],
            ['insights', 'Insights', 'M3 3v18h18M7 14l3-4 3 3 4-6', auth()->user()?->can('reports.view') ? route('app.insights') : null],
            ['search', 'Search', 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.35-4.35', auth()->user()?->can('reservations.view') ? route('app.search') : null],
        ]],
        ['FRONT DESK', [
            ['calendar', 'Calendar', 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4', route('app.rack')],
            ['front-desk', 'Front desk', 'M4 20v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', route('app.front-desk')],
            ['reservations', 'Reservations', 'M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4', route('app.reservations')],
            ['guest-requests', 'Guest requests', 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2zM9 10h6M9 13h4', auth()->user()?->can('reservations.view') ? route('app.guest-requests') : null],
            ['guests', 'Guests', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75', route('app.guests')],
            ['reviews', 'Reviews', 'M12 2l2.4 7.4H22l-6 4.6 2.3 7.4-6.3-4.6-6.3 4.6L7.9 14 2 9.4h7.6z', auth()->user()?->can('reviews.view') ? route('app.reviews') : null],
            ['inbox', 'Messages', 'M4 13h5l2 3h2l2-3h5M4 13V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v7M4 13v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5', route('app.inbox')],
        ]],
        ['OPERATIONS', [
            ['housekeeping', 'Housekeeping', 'M3 21h18M6 21V10l6-4 6 4v11M10 21v-5h4v5', route('app.housekeeping')],
            ['schedule', 'Team schedule', 'M4 6h16v14H4zM4 10h16M8 3v4M16 3v4M9 14h2M13 14h2M9 17h2', auth()->user()?->can('schedule.view') ? route('app.schedule') : null],
            ['maintenance', 'Maintenance', 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z', route('app.maintenance')],
            ['units', 'Units', 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z', route('app.units')],
            ['rates', 'Rates & availability', 'M4 4h9l7 7-9 9-7-7zM8 8h.01', auth()->user()?->can('rates.view') ? route('app.rates') : null],
            ['channels', 'Channels', 'M12 3v4M12 17v4M3 12h4M17 12h4M6 6l3 3M15 15l3 3M18 6l-3 3M9 15l-3 3', null],
        ]],
        ['FINANCE', [
            ['owners', 'Owners', 'M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM2 20c0-3 3-5 6-5s6 2 6 5M16 11a3 3 0 1 0 0-6M22 20c0-3-3-5-6-5', route('app.owners')],
            ['payout-run', 'Payout run', 'M3 7h18v12H3zM3 11h18M15 15h3', route('app.payouts.run')],
            ['reports', 'Reports', 'M9 17v-6M12 17V7M15 17v-3M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z', auth()->user()?->can('reports.view') ? route('app.reports') : null],
            ['unit-requests', 'Unit requests', 'M12 5v14M5 12h14M4 4h16', auth()->user()?->can('units.view') ? route('app.unit-requests') : null],
        ]],
        ['SYSTEM', [
            ['notifications', 'Notifications', 'M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6M10 20a2 2 0 0 0 4 0', route('app.notifications')],
            ['profile', 'Your profile', 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z', route('app.profile')],
            ['roles', 'Roles & access', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M22 11h-6', auth()->user()?->can('users.manage') ? route('app.roles') : null],
            ['settings', 'Settings', 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2', auth()->user()?->can('properties.manage') ? route('app.settings') : null],
            ['security', 'Security', 'M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6z', route('app.security')],
            ['billing', 'Billing', 'M3 7h18v12H3zM3 11h18M7 15h4', auth()->user()?->can('billing.manage') ? route('app.billing') : null],
            ['email-templates', 'Email templates', 'M3 5h18v14H3zM3 7l9 6 9-6', route('app.email-templates')],
        ]],
    ];

    // Drop role-gated items the acting role can't reach — their route resolved to null (that is how each
    // item encodes "this role lacks the permission"). A permission-less role simply doesn't see them,
    // exactly like <x-owner.shell>'s filter — never a misleading "Coming soon" for a page that already
    // works for an admin (Roles / Settings / Billing all 403 for a Hotel Manager but exist for the org
    // admin). The ONE null kept is 'channels': it is genuinely account-gated (Channex not connected / a
    // Pro feature), so its honest "Pro"/"Soon" placeholder is correct, not a missing permission. Drop a
    // whole group if nothing survives.
    $navGroups = array_values(array_filter(array_map(function ($grp) {
        [$group, $items] = $grp;
        $items = array_values(array_filter($items, fn ($it) => $it[3] !== null || $it[0] === 'channels'));

        return [$group, $items];
    }, $navGroups), fn ($grp) => $grp[1] !== []));
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
                    @elseif ($key === 'channels' && ! $channelsAllowed)
                        {{-- Feature-gated by plan: Channels is a Pro feature; an Essential org sees a "Pro" lock. --}}
                        <span class="vy-nav-item" aria-disabled="true" title="Channels is a Pro feature — upgrade to unlock">
                            <svg class="vy-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                            {{ $label }}<span class="vy-plan-tag">Pro</span>
                        </span>
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
                <x-operator.property-switcher />
                <x-notifications.bell :open-all-url="route('app.notifications')" />
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
