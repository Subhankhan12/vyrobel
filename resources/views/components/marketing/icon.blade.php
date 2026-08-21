{{-- The design's own 15 mega-row glyphs, extracted from the Site Nav partial. Strokes bound to
     currentColor (the tile sets the colour from a token) — the source's #B4400F literal is NOT copied
     (D6). --}}
@props(['name'])
@switch($name)
    @case('overview')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3 8 4-16 3 8h4"></path></svg>
    @break
    @case('features')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.6 6.4.8-4.7 4.3 1.2 6.3L12 17l-5.5 3 1.2-6.3L3 9.4l6.4-.8z"></path></svg>
    @break
    @case('modules')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"></path></svg>
    @break
    @case('try')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v15H4zM4 10h16M9 3v4M15 3v4M9 15l2 2 4-4"></path></svg>
    @break
    @case('integrations')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a3 3 0 106 0 3 3 0 00-6 0M12 3v6M12 15v6M3 12h6M15 12h6"></path></svg>
    @break
    @case('docs')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h10l4 4v12H5zM15 4v4h4M8 13h8M8 17h5"></path></svg>
    @break
    @case('changelog')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 6h.01M5 12h.01M5 18h.01M10 6h9M10 12h9M10 18h6"></path></svg>
    @break
    @case('security')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3.5v5c0 5-3.4 8.4-8 9.5-4.6-1.1-8-4.5-8-9.5v-5z"></path></svg>
    @break
    @case('hotels')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16M6 20V8l6-4 6 4v12M10 20v-5h4v5M9 11h.01M15 11h.01"></path></svg>
    @break
    @case('villas')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 20h18M5 20V11l7-6 7 6v9M9 20v-6h6v6M4 15h3M17 15h3"></path></svg>
    @break
    @case('apartments')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V4h9v17M13 21V10h7v11M7 8h.01M10 8h.01M7 12h.01M10 12h.01M7 16h.01M16 14h.01M16 18h.01"></path></svg>
    @break
    @case('stories')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V6a2 2 0 012-2h9l5 5v10a2 2 0 01-2 2H6a2 2 0 01-2-2zM15 4v5h5M8 14h6M8 17h4"></path></svg>
    @break
    @case('compare')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16M6 8H3l3 6 3-6H6zM18 8h-3l3 6 3-6h-3zM7 6h10"></path></svg>
    @break
    @case('alternatives')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h7v12H4zM13 6h7v12h-7zM8 10v4M15 12h3"></path></svg>
    @break
    @case('switching')
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h13l-4-4M20 16H7l4 4"></path></svg>
    @break
    @default
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle></svg>
@endswitch
