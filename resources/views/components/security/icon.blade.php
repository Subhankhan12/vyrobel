{{-- The Security pages' icons, in one place so both pages draw the same shape for the same thing. Decorative only:
     every use sits beside words that say the same, so each is aria-hidden. --}}
@props(['name', 'size' => 15])
@php
    $paths = [
        'shield' => '<path d="M12 3 4.5 6v5c0 4.6 3.2 8.4 7.5 10 4.3-1.6 7.5-5.4 7.5-10V6z"/><path d="M9 12l2 2 4-4.5"/>',
        'shield-alert' => '<path d="M12 3 4.5 6v5c0 4.6 3.2 8.4 7.5 10 4.3-1.6 7.5-5.4 7.5-10V6z"/><path d="M12 8.5v4M12 15.5h.01"/>',
        'plus' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'device' => '<rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M10 6.5h4M9.5 13.5l2 2 3-3.5"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'apps' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><path d="M16.5 13v7M13 16.5h7"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'key' => '<circle cx="8" cy="15" r="4"/><path d="M10.8 12.2 20 3M16 7l3 3M14 9l2 2"/>',
        'fingerprint' => '<path d="M12 11v3a8 8 0 0 1-1.5 4.7M8.5 7.5A5 5 0 0 1 17 11v1.5M7 11a5 5 0 0 1 .4-2M6.5 18.5A12 12 0 0 0 9 13v-2a3 3 0 0 1 6 0v1M15.5 20a14 14 0 0 0 1.4-4"/>',
        'lock' => '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
        'download' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
        'logout' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6L6 18M6 6l12 12"/>',
        'bell' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'user-check' => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0M15.5 11.5l2 2 3.5-4"/>',
        'pause' => '<circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/>',
    ];
@endphp
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] ?? $paths['shield'] !!}</svg>
