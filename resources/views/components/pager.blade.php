{{-- The realm-neutral pagination control (WAVE 3).

     GET LINKS, NOT WIRE-ACTIONS — deliberately. Livewire's WithPagination changes pages over
     /livewire/update, and SetCurrentPropertyForWebRequest is mounted on the /app ROUTE GROUP, not globally,
     so it does not run there (invariant #20). The lists already document the same rule for their filters:
     "Scope-dependent reads ride GET links, never wire-actions". A page is a scope-dependent read too, so a
     page change is an ordinary request that re-runs EVERY middleware and page 2 is freshly tenant- AND
     property-scoped. `->withQueryString()` on the paginator carries the active filters onto the page links.

     SELF-CONTAINED STYLING (inline, in the --vy-* palette both layouts define). It began as layout CSS, but
     injecting a block into the owner layout twice produced two different 500s that every static check sailed
     past — the second because a Blade directive NAME inside a CSS comment is still compiled. Inline styles
     need no layout edit at all and work identically in both realms.

     Renders NOTHING on a single page — no decorative control.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@props(['paginator', 'label' => 'results'])

@php
    // The wireframes render prev/next as round icon buttons. The visible "Page N of M" between them is
    // KEPT even though the wireframe omits it — a bare chevron pair tells you neither where you are nor
    // whether another page exists, and screen-reader users lose the position entirely.
    $vyBtn = 'display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.55);border:1px solid var(--vy-glass-border);color:var(--vy-ink);text-decoration:none;flex:none';
    $vyOff = 'display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.28);border:1px solid var(--vy-glass-border);color:var(--vy-faint);flex:none';
    $vyMeta = 'font-size:12px;color:var(--vy-muted);font-variant-numeric:tabular-nums';
    $vyPrevIco = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>';
    $vyNextIco = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>';
@endphp

@if ($paginator->hasPages())
    <nav style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:14px;padding-top:13px;border-top:1px solid var(--vy-hairline)"
         role="navigation" aria-label="Pagination" data-vy-pager>
        <span style="{{ $vyMeta }}">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} {{ $label }}
        </span>

        <span style="display:inline-flex;align-items:center;gap:8px">
            @if ($paginator->onFirstPage())
                <span style="{{ $vyOff }}" aria-disabled="true" aria-label="Previous page">{!! $vyPrevIco !!}</span>
            @else
                <a style="{{ $vyBtn }}" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page" title="Previous page">{!! $vyPrevIco !!}</a>
            @endif

            <span style="{{ $vyMeta }};padding:0 2px">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a style="{{ $vyBtn }}" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page" title="Next page">{!! $vyNextIco !!}</a>
            @else
                <span style="{{ $vyOff }}" aria-disabled="true" aria-label="Next page">{!! $vyNextIco !!}</span>
            @endif
        </span>
    </nav>
@endif
