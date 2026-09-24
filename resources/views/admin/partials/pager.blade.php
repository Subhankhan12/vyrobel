{{-- The admin console's Previous/Next pager — ONE copy, used by every paginated console list. The active
     filters ride the links (the paginator is built ->withQueryString()). Expects $rows (a LengthAwarePaginator). --}}
@if ($rows->hasPages())
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-top: 1px solid var(--ad-border);">
        <span style="color: var(--ad-faint); font-size: 12.5px;">
            Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ number_format($rows->total()) }}
        </span>
        <span style="display: flex; gap: 8px;">
            @if ($rows->onFirstPage())
                <span class="ad-btn ad-btn--ghost ad-btn--sm" style="opacity: .4; cursor: default;">Previous</span>
            @else
                <a href="{{ $rows->previousPageUrl() }}" class="ad-btn ad-btn--ghost ad-btn--sm">Previous</a>
            @endif
            @if ($rows->hasMorePages())
                <a href="{{ $rows->nextPageUrl() }}" class="ad-btn ad-btn--ghost ad-btn--sm">Next</a>
            @else
                <span class="ad-btn ad-btn--ghost ad-btn--sm" style="opacity: .4; cursor: default;">Next</span>
            @endif
        </span>
    </div>
@endif
