{{-- VYROBEL FAVICON — the one place the browser tab icon is declared, so every surface (front website,
     operator, owner, guest and the platform admin panels) carries the SAME terracotta "V" logo.

     ⚠️ A PLAIN STATIC FILE, not @vite: the Vite helper throws when the build manifest is missing, which
        would turn a forgotten `npm run build` into a 500. `filemtime` cache-busts without a manifest and
        is guarded because the file's absence must never break a render.
     ⚠️ The inline data-URI below is the fallback the admin panels already shipped: it needs no file at
        all, so the tab icon survives even if public/favicon.svg is ever missing. --}}
@php($vyFav = public_path('favicon.svg'))
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}{{ is_file($vyFav) ? '?v='.filemtime($vyFav) : '' }}">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23C2410C'/%3E%3Ctext x='16' y='23' font-family='sans-serif' font-size='19' font-weight='700' fill='white' text-anchor='middle'%3EV%3C/text%3E%3C/svg%3E">
<link rel="apple-touch-icon" href="{{ asset('favicon.svg') }}{{ is_file($vyFav) ? '?v='.filemtime($vyFav) : '' }}">
