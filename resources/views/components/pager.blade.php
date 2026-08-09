{{-- The operator realm's ONE pagination control (WAVE 3 pagination gate).

     ⚠️ GET LINKS, NOT WIRE-ACTIONS — deliberately, and this is the whole design decision. Livewire's
     WithPagination changes pages over /livewire/update, and SetCurrentPropertyForWebRequest is mounted on the
     /app ROUTE GROUP (routes/web.php), not globally — so it does NOT run on that endpoint (invariant #20).
     These lists already document the same rule for their filters: "Scope-dependent reads ride GET links
     (?filter=, ?tag=, ?q=), never wire-actions". A page IS a scope-dependent read, so it rides a GET link
     too: a full request re-runs EVERY middleware, so page 2 is freshly tenant- AND property-scoped rather
     than merely inheriting a prop. (SetTenantForWebRequest is global and would have survived either way;
     the property read-set would not.)

     `->withQueryString()` on the paginator carries ?filter=/?tag=/?q= across page links, so paging never
     silently widens the result set the user is looking at.

     Styled in the --vy-* warm-glass tokens; Livewire/Laravel's stock Tailwind + Bootstrap views are not used
     anywhere in this realm and would not match. Renders NOTHING on a single page — no decorative control.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@props(['paginator', 'label' => 'results'])

@if ($paginator->hasPages())
    <nav class="vy-pager" role="navigation" aria-label="Pagination">
        <span class="vy-pager-count">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }} {{ $label }}
        </span>

        <span class="vy-pager-links">
            @if ($paginator->onFirstPage())
                <span class="vy-pager-btn is-off" aria-disabled="true">‹ Previous</span>
            @else
                <a class="vy-pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Previous</a>
            @endif

            <span class="vy-pager-page">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a class="vy-pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
            @else
                <span class="vy-pager-btn is-off" aria-disabled="true">Next ›</span>
            @endif
        </span>
    </nav>
@endif
