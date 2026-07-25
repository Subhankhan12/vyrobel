{{-- Shared notification category ICON tile (Phase-3 notification rebuild) — the ONE definition of the
     per-category glyph + rounded-SQUARE tile, reused by BOTH the bell panel (<x-notifications.bell>) and the
     full feed pages (operator + owner) so the two surfaces read identically and can never drift. Keyed to the
     REAL category the notification stores (finance / front_desk / maintenance / reputation / account / system);
     an unknown category falls back to the generic bell. Presentation-only; renders nothing but an icon. --}}
@props(['category' => 'general'])
@php
    $paths = [
        'finance' => '<path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'front_desk' => '<path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/><path d="M9 7h.01M15 7h.01M9 11h.01M15 11h.01M9 15h6"/>',
        'maintenance' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z"/>',
        'reputation' => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
        'account' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'system' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
    ];
    $path = $paths[$category] ?? '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>';
@endphp
<span aria-hidden="true" style="flex:none;width:34px;height:34px;border-radius:10px;background:rgba(232,112,58,.12);color:var(--vy-accent);display:flex;align-items:center;justify-content:center;margin-top:1px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>
</span>
