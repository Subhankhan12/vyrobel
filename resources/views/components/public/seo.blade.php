{{-- STOREFRONT SEO HEAD (D10) — the ONE emitter for the public booking storefront.

     ⚠️ WHY THIS IS NOT `x-marketing.seo`. The pattern is shared (§N.8f applies to the PATTERN); the
     COMPONENT deliberately is not. Three reasons, each concrete:
       1. `x-marketing.seo` hardcodes `og:site_name = "Vyrobel"`. On a storefront the site is the TENANT —
          a guest booking "Demo Seaside Property" is not visiting a Vyrobel-branded site, and stamping the
          platform's name onto a tenant's booking page is a false attribution, not a cosmetic default.
       2. The marketing component has no JSON-LD slot and no notion of a schema graph.
       3. It carries marketing concerns (the D7 hreflang slot, the marketing og-cover) into a SEALED,
          MONEY-CARRYING surface. Coupling them means a future marketing change can alter the head of the
          checkout funnel. The blast radius is the argument.
     Sharing the emitter would have saved perhaps fifteen lines and made a marketing edit able to reach the
     money path. Two small components beat one shared one here.

     ⚠️ THIS COMPONENT EMITS THE PAGE'S ONLY `<title>`. The shell suppresses its own bare title when a
     `:seo` payload is supplied, so there is exactly one title element per page — never two.

     ⚠️ CANONICALS AND OG URLS DEPEND ON APP_URL (route() resolves against it). A wrong APP_URL in
     production mis-canonicalises every storefront page SILENTLY — no error, no warning, just the whole
     storefront pointing at the wrong host. Recorded as a go-live requirement in DEPLOYMENT §7.4.5b.

     ⚠️ JSON-LD IS ENCODED WITH JSON_HEX_TAG (and friends). The graph carries tenant-controlled strings
     (property and room names); without hex-encoding a name containing a closing script tag would break out
     of the block. The `{!! !!}` below is safe ONLY because of that flag set — do not remove it. --}}
@props(['seo'])
@php
    /** @var array<string, mixed> $seo */
    $seoTitle = (string) ($seo['title'] ?? '');
    $seoDescription = (string) ($seo['description'] ?? '');
    $seoCanonical = $seo['canonical'] ?? null;
    $seoIndex = ($seo['index'] ?? true) === true;
    $seoImage = $seo['ogImage'] ?? $seo['og_image'] ?? null;
    $seoSiteName = (string) ($seo['site_name'] ?? '');
    $seoOgType = (string) ($seo['og_type'] ?? 'website');
    $seoGraph = $seo['json_ld'] ?? null;

    $seoJson = is_array($seoGraph) && $seoGraph !== []
        ? json_encode(
            $seoGraph,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        )
        : null;
@endphp
<title>{{ $seoTitle }}</title>
@if ($seoDescription !== '')
<meta name="description" content="{{ $seoDescription }}">
@endif
@if ($seoCanonical !== null)
<link rel="canonical" href="{{ $seoCanonical }}">
@endif
<meta name="robots" content="{{ $seoIndex ? 'index, follow' : 'noindex, nofollow' }}">
<meta property="og:type" content="{{ $seoOgType }}">
@if ($seoSiteName !== '')
<meta property="og:site_name" content="{{ $seoSiteName }}">
@endif
@if ($seoCanonical !== null)
<meta property="og:url" content="{{ $seoCanonical }}">
@endif
<meta property="og:title" content="{{ $seoTitle }}">
@if ($seoDescription !== '')
<meta property="og:description" content="{{ $seoDescription }}">
@endif
{{-- og:image is emitted ONLY when the tenant has a real photo. No placeholder, no stock, no platform
     fallback: a social card pointing at an image the property does not have is a fabricated impression of
     the property, and a card pointing at a 404 merely looks configured. --}}
@if ($seoImage !== null)
<meta property="og:image" content="{{ $seoImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="{{ $seoImage }}">
@else
<meta name="twitter:card" content="summary">
@endif
<meta name="twitter:title" content="{{ $seoTitle }}">
@if ($seoDescription !== '')
<meta name="twitter:description" content="{{ $seoDescription }}">
@endif
@if ($seoJson !== null)
<script type="application/ld+json">{!! $seoJson !!}</script>
@endif
