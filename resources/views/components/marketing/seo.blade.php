{{-- MARKETING SEO HEAD (front-website Gate 1) — the per-page head block every marketing page emits.
     World (ii): Vyrobel had NO SEO infrastructure; the front site brings it, and this component is where a
     page's title/description/canonical/OG/Twitter live — ONE emitter (§N.8f), so Gate 7's sitemap and the
     pages can never disagree about a canonical.

     ⚠️ CANONICALS DEPEND ON APP_URL — url() resolves against it, so a wrong APP_URL in production emits
     wrong canonicals on every page. Recorded as a go-live requirement in DEPLOYMENT §7.4.

     ⚠️ hreflang is DEFERRED WITH D7 (English first) but the SLOT is designed now: pass
     :alternates="['en' => ..., 'nl' => ...]" and the link tags emit; omit it (the default) and nothing
     renders. Adding four locales later is a data change, not a rewrite.

     ⚠️ og:image is emitted ONLY when given — the design's og-cover.png is not in public/ yet (Gate 2 places
     it); a default pointing at a 404 would be a broken card that LOOKS configured. --}}
@props([
    'title',
    'description',
    'canonical',                 // path ('/product') or absolute URL
    'ogType' => 'website',
    'ogImage' => null,           // absolute URL or public path; null = no og:image/twitter:image tags
    'index' => true,
    'alternates' => [],          // the D7-deferred hreflang slot: ['en' => url, 'nl' => url, 'x-default' => url]
])
@php
    $canonicalUrl = str_starts_with($canonical, 'http') ? $canonical : url($canonical);
    $ogImageUrl = $ogImage === null ? null : (str_starts_with($ogImage, 'http') ? $ogImage : url($ogImage));
@endphp
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonicalUrl }}">
@foreach ($alternates as $locale => $href)
<link rel="alternate" hreflang="{{ $locale }}" href="{{ $href }}">
@endforeach
<meta name="robots" content="{{ $index ? 'index, follow' : 'noindex, nofollow' }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="Vyrobel">
<meta property="og:locale" content="en_GB">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
@if ($ogImageUrl)
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="{{ $ogImageUrl }}">
@else
<meta name="twitter:card" content="summary">
@endif
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
{{-- A8 (2026-11-11): the structured-data types whose values are REAL — Organization + WebSite on the home page, a BreadcrumbList elsewhere — and
     only when the site's address is a real public one (never `localhost`). Product / Offer / rating stay refused (App\Support\Marketing\StructuredData). --}}
@php($__ld = \App\Support\Marketing\StructuredData::graph($canonical, $index))
@if ($__ld !== null)
<script type="application/ld+json">{!! json_encode($__ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endif
