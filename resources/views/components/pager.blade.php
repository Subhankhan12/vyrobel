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
    $vyBtn = 'display:inline-flex;align-items:center;padding:7px 13px;border-radius:999px;background:rgba(255,252,248,.58);border:1px solid var(--vy-glass-border);font-size:12.5px;font-weight:500;color:var(--vy-ink);text-decoration:none;white-space:nowrap';
    $vyOff = 'display:inline-flex;align-items:center;padding:7px 13px;border-radius:999px;font-size:12.5px;font-weight:500;color:var(--vy-faint);white-space:nowrap';
    $vyMeta = 'font-size:12px;color:var(--vy-muted);font-variant-numeric:tabular-nums';
@endphp

@if ($paginator->hasPages())
    <nav style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:14px;padding-top:13px;border-top:1px solid var(--vy-hairline)"
         role="navigation" aria-label="Pagination" data-vy-pager>
        <span style="{{ $vyMeta }}">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} {{ $label }}
        </span>

        <span style="display:inline-flex;align-items:center;gap:8px">
            @if ($paginator->onFirstPage())
                <span style="{{ $vyOff }}" aria-disabled="true">‹ Previous</span>
            @else
                <a style="{{ $vyBtn }}" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Previous</a>
            @endif

            <span style="{{ $vyMeta }};padding:0 2px">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a style="{{ $vyBtn }}" href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
            @else
                <span style="{{ $vyOff }}" aria-disabled="true">Next ›</span>
            @endif
        </span>
    </nav>
@endif
