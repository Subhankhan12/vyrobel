{{-- The admin console's Previous/Next pager — ONE copy, used by every paginated console list. The active
     filters ride the links (the paginator is built ->withQueryString()). Expects $rows (a LengthAwarePaginator). --}}
@if ($rows->hasPages())
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border-top: 1px solid var(--ad-border);">
        <span style="color: var(--ad-faint); font-size: 12.5px;">
            {{ __('admin/partials/pager.showing', ['first' => $rows->firstItem(), 'last' => $rows->lastItem(), 'total' => number_format($rows->total())]) }}
        </span>
        <span style="display: flex; gap: 8px;">
            @if ($rows->onFirstPage())
                <span class="ad-btn ad-btn--ghost ad-btn--sm" style="opacity: .4; cursor: default;">{{ __('common.previous') }}</span>
            @else
                <a href="{{ $rows->previousPageUrl() }}" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('common.previous') }}</a>
            @endif
            @if ($rows->hasMorePages())
                <a href="{{ $rows->nextPageUrl() }}" class="ad-btn ad-btn--ghost ad-btn--sm">{{ __('common.next') }}</a>
            @else
                <span class="ad-btn ad-btn--ghost ad-btn--sm" style="opacity: .4; cursor: default;">{{ __('common.next') }}</span>
            @endif
        </span>
    </div>
@endif
